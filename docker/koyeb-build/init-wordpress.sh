#!/usr/bin/env bash
set -e

DOCROOT="/var/www/html"
WP="wp --allow-root --path=${DOCROOT}"

CA_DEST="/etc/ssl/certs/mysql-ca.pem"
if [ -f "${CA_DEST}" ]; then
  echo "Using bundled DB CA certificate at ${CA_DEST}"
elif [ -n "${WORDPRESS_DB_SSL_CA_URL:-}" ]; then
  echo "Fetching DB CA certificate from URL..."
  curl -fsSL "${WORDPRESS_DB_SSL_CA_URL}" -o "${CA_DEST}" || echo "WARNING: failed to download CA from WORDPRESS_DB_SSL_CA_URL"
fi

# --- DB host/port normalization ---
RAW_HOST="${WORDPRESS_DB_HOST:-}"
RAW_PORT="${WORDPRESS_DB_PORT:-}"
# If RAW_HOST contains a colon, split; otherwise use RAW_PORT or 3306
if [[ "$RAW_HOST" == *:* ]]; then
  DB_HOST_ONLY="${RAW_HOST%%:*}"
  DB_PORT_ONLY="${RAW_HOST##*:}"
else
  DB_HOST_ONLY="$RAW_HOST"
  DB_PORT_ONLY="${RAW_PORT:-3306}"
fi
DB_HOSTPORT="${DB_HOST_ONLY}:${DB_PORT_ONLY}"

# --- 1) Wait for DB (if provided) ---
if [ -n "${WORDPRESS_DB_HOST:-}" ]; then
  echo "Waiting for MySQL at ${WORDPRESS_DB_HOST}..."
  for i in {1..60}; do
    (echo > "/dev/tcp/${DB_HOST_ONLY}/${DB_PORT_ONLY}" 2>/dev/null) && break || sleep 1
  done
fi

# --- Drop ALL tables on boot if requested (only before first init) ---
if [ "${RESET_DB_ON_BOOT:-0}" = "1" ] && [ ! -f "${DOCROOT}/wp-config.php" ]; then
  if [ -n "${WORDPRESS_DB_NAME}" ] && [ -n "${WORDPRESS_DB_USER}" ] && [ -n "${WORDPRESS_DB_PASSWORD}" ] && [ -n "${DB_HOST_ONLY}" ] && [ -n "${DB_PORT_ONLY}" ]; then
    echo "RESET_DB_ON_BOOT=1 and no wp-config.php → Dropping ALL TABLES in ${WORDPRESS_DB_NAME}@${DB_HOST_ONLY}:${DB_PORT_ONLY} ..."
    MYSQL_OPTS=( -h "${DB_HOST_ONLY}" -P "${DB_PORT_ONLY}" -u "${WORDPRESS_DB_USER}" --protocol=TCP --batch --skip-column-names )
    # SSL options if enabled
    case "$(echo "${WORDPRESS_DB_SSL}" | tr '[:upper:]' '[:lower:]')" in
      1|true|required)
        CA_PATH="${WORDPRESS_DB_SSL_CA_PATH:-/etc/ssl/certs/mysql-ca.pem}"
        MYSQL_OPTS+=( --ssl-mode=REQUIRED --ssl-ca="${CA_PATH}" )
        ;;
    esac
    export MYSQL_PWD="${WORDPRESS_DB_PASSWORD}"
    mysql "${MYSQL_OPTS[@]}" -e "SET SESSION sql_notes=0; SET FOREIGN_KEY_CHECKS=0; SELECT CONCAT('DROP TABLE IF EXISTS \`', table_name, '\`;') FROM information_schema.tables WHERE table_schema='${WORDPRESS_DB_NAME}'; SET FOREIGN_KEY_CHECKS=1;" \
      | mysql "${MYSQL_OPTS[@]}" "${WORDPRESS_DB_NAME}" \
      && echo "All tables dropped in ${WORDPRESS_DB_NAME}." \
      || echo "WARNING: Failed to drop tables (database may be empty or unreachable)."
    unset MYSQL_PWD
  else
    echo "RESET_DB_ON_BOOT=1 set, but DB env vars are incomplete; skipping drop."
  fi
else
  if [ "${RESET_DB_ON_BOOT:-0}" = "1" ] && [ -f "${DOCROOT}/wp-config.php" ]; then
    echo "RESET_DB_ON_BOOT=1 but wp-config.php exists → redeploy detected, skipping drop."
  fi
fi

# --- 2) Create wp-config.php from ENV if missing ---
if [ ! -f "${DOCROOT}/wp-config.php" ]; then
  echo "Creating wp-config.php from environment variables..."
  ${WP} config create \
    --dbname="${WORDPRESS_DB_NAME}" \
    --dbuser="${WORDPRESS_DB_USER}" \
    --dbpass="${WORDPRESS_DB_PASSWORD}" \
    --dbhost="${DB_HOSTPORT}" \
    --force \
    --skip-check

  echo "DB resolved as host=${DB_HOST_ONLY} port=${DB_PORT_ONLY}"

  # Write custom constants to a separate include to avoid quoting issues
  cat > "${DOCROOT}/wp-config.custom.php" <<'PHP'
<?php
$__home = getenv('WP_HOME') ?: ('https://' . getenv('KOYEB_APP_ID') . '.koyeb.app');
$__siteurl = getenv('WP_SITEURL') ?: $__home;
// Normalize to no trailing slash
$__home = rtrim($__home, '/');
$__siteurl = rtrim($__siteurl, '/');
if (!defined('WP_HOME')) define('WP_HOME', $__home);
if (!defined('WP_SITEURL')) define('WP_SITEURL', $__siteurl);
$__debug = getenv('WORDPRESS_DEBUG');
if (!defined('WP_DEBUG')) define('WP_DEBUG', ($__debug === '1' || strtolower((string)$__debug) === 'true'));
// Force SSL for admin
if (!defined('FORCE_SSL_ADMIN')) define('FORCE_SSL_ADMIN', true);
// Behind proxy: trust X-Forwarded-Proto to detect HTTPS (Koyeb)
$__xfp = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
if ($__xfp === 'https') { $_SERVER['HTTPS'] = 'on'; }
// Skip redirect for Koyeb health check
if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD' &&
    str_contains($_SERVER['HTTP_USER_AGENT'] ?? '', 'Koyeb Health Check')
) {
    header("HTTP/1.1 200 OK");
    header("Content-Length: 0");
    exit;
}

// --- DB SSL support (Aiven etc.) ---
$__ssl = strtolower((string) getenv('WORDPRESS_DB_SSL'));
if ($__ssl === '1' || $__ssl === 'true' || $__ssl === 'required') {
    $ca_path = getenv('WORDPRESS_DB_SSL_CA_PATH');
    if (!$ca_path) {
        $ca_path = '/etc/ssl/certs/mysql-ca.pem'; // same as CA_DEST in init script
    }
    if (!defined('MYSQL_CLIENT_FLAGS')) {
        // Require mysqli SSL connection
        if (defined('MYSQLI_CLIENT_SSL')) {
            define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
        }
    }
    if (!defined('MYSQL_SSL_CA')) {
        define('MYSQL_SSL_CA', $ca_path);
    }
}
// Security hardening: disable theme/plugin file editor in admin
if (!defined('DISALLOW_FILE_EDIT')) define('DISALLOW_FILE_EDIT', true);
PHP

  # Ensure the include is present just before the ABSPATH block
  if ! grep -q "wp-config.custom.php" "${DOCROOT}/wp-config.php"; then
    sed -i "/^\/\* That's all, stop editing!.*\*\//i require_once __DIR__ . '\/wp-config.custom.php';" "${DOCROOT}/wp-config.php"
  fi

  # Keys/salts
  ${WP} config shuffle-salts || true

  # Validate wp-config.php; if broken — recreate cleanly
  if ! php -l "${DOCROOT}/wp-config.php" >/dev/null 2>&1; then
    echo "wp-config.php has syntax errors. Recreating..."
    mv "${DOCROOT}/wp-config.php" "${DOCROOT}/wp-config.php.bad" 2>/dev/null || true
    ${WP} config create \
      --dbname="${WORDPRESS_DB_NAME}" \
      --dbuser="${WORDPRESS_DB_USER}" \
      --dbpass="${WORDPRESS_DB_PASSWORD}" \
      --dbhost="${DB_HOSTPORT}" \
      --force \
      --skip-check

    echo "DB resolved as host=${DB_HOST_ONLY} port=${DB_PORT_ONLY}"

    cat > "${DOCROOT}/wp-config.custom.php" <<'PHP'
<?php
$__home = getenv('WP_HOME') ?: ('https://' . getenv('KOYEB_APP_ID') . '.koyeb.app');
$__siteurl = getenv('WP_SITEURL') ?: $__home;
// Normalize to no trailing slash
$__home = rtrim($__home, '/');
$__siteurl = rtrim($__siteurl, '/');
if (!defined('WP_HOME')) define('WP_HOME', $__home);
if (!defined('WP_SITEURL')) define('WP_SITEURL', $__siteurl);
$__debug = getenv('WORDPRESS_DEBUG');
if (!defined('WP_DEBUG')) define('WP_DEBUG', ($__debug === '1' || strtolower((string)$__debug) === 'true'));
// Force SSL for admin
if (!defined('FORCE_SSL_ADMIN')) define('FORCE_SSL_ADMIN', true);
// Behind proxy: trust X-Forwarded-Proto to detect HTTPS (Koyeb)
$__xfp = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
if ($__xfp === 'https') { $_SERVER['HTTPS'] = 'on'; }
// Skip redirect for Koyeb health check
if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD' &&
    str_contains($_SERVER['HTTP_USER_AGENT'] ?? '', 'Koyeb Health Check')
) {
    header("HTTP/1.1 200 OK");
    header("Content-Length: 0");
    exit;
}

// --- DB SSL support (Aiven etc.) ---
$__ssl = strtolower((string) getenv('WORDPRESS_DB_SSL'));
if ($__ssl === '1' || $__ssl === 'true' || $__ssl === 'required') {
    $ca_path = getenv('WORDPRESS_DB_SSL_CA_PATH');
    if (!$ca_path) {
        $ca_path = '/etc/ssl/certs/mysql-ca.pem'; // same as CA_DEST in init script
    }
    if (!defined('MYSQL_CLIENT_FLAGS')) {
        // Require mysqli SSL connection
        if (defined('MYSQLI_CLIENT_SSL')) {
            define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
        }
    }
    if (!defined('MYSQL_SSL_CA')) {
        define('MYSQL_SSL_CA', $ca_path);
    }
}
// Security hardening: disable theme/plugin file editor in admin
if (!defined('DISALLOW_FILE_EDIT')) define('DISALLOW_FILE_EDIT', true);
PHP
    if ! grep -q "wp-config.custom.php" "${DOCROOT}/wp-config.php"; then
      sed -i "/^\/\* That's all, stop editing!.*\*\//i require_once __DIR__ . '\/wp-config.custom.php';" "${DOCROOT}/wp-config.php"
    fi
    ${WP} config shuffle-salts || true
  fi
fi

# --- 3) Optional auto-install (default admin/admin) ---
if ! ${WP} core is-installed >/dev/null 2>&1; then
  echo "Running WordPress installation..."
  SITE_URL="${WP_HOME:-https://$KOYEB_APP_ID.koyeb.app}"
  ${WP} core install \
    --url="${SITE_URL}" \
    --title="${WP_SITE_TITLE:-My Site}" \
    --admin_user="${WP_ADMIN_USER:-admin}" \
    --admin_password="${WP_ADMIN_PASS:-admin}" \
    --admin_email="${WP_ADMIN_EMAIL:-admin@example.com}" \
    --skip-email
  # Ensure pretty permalinks work on first run
  ${WP} rewrite structure '/%postname%/' --hard || true
  ${WP} rewrite flush --hard || true

  # --- Install Offload Media – Cloud Storage plugin ---
  if ! ${WP} plugin is-installed offload-media-cloud-storage; then
    echo "Installing Offload Media – Cloud Storage plugin..."
    ${WP} plugin install offload-media-cloud-storage --activate
  fi
fi

# --- 4) Final validation ---
if ! php -l "${DOCROOT}/wp-config.php" >/dev/null 2>&1; then
  echo "FATAL: wp-config.php still has syntax errors after repair. Dumping tail for diagnostics:" >&2
  nl -ba "${DOCROOT}/wp-config.php" | tail -n 60 >&2 || true
  exit 1
fi

# --- 5) Run Apache ---
exec apache2-foreground