<?php
/*
 * Plugin Name:       Advanced Media Offloader
 * Plugin URI:        https://wpfitter.com/plugins/advanced-media-offloader/
 * Description:       Offload WordPress media to Amazon S3, Cloudflare R2, DigitalOcean Spaces, Min.io or Wasabi.
 * Version:           4.0.0
 * Requires at least: 5.6
 * Requires PHP:      8.1
 * Author:            WP Fitter
 * Author URI:        https://wpfitter.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       advanced-media-offloader
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('ADVMO')) {
	/**
	 * The main ADVMO class
	 */
	class ADVMO
	{

		/** @var Container */
		public $container;

		/**
		 * The plugin version number.
		 *
		 * @var string
		 */
		public $version;

		/**
		 * The offloader instance.
		 *
		 * @var Advanced_Media_Offloader\Offloader
		 */
		public $offloader;

		/**
		 * The plugin data array.
		 *
		 * @var array
		 */
		public $data = array();

		/**
		 * A dummy constructor to ensure WP Fitter Media Offloader is only setup once.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function __construct()
		{
			$plugin_data = get_file_data(__FILE__, array('Version' => 'Version'));
			$this->version = $plugin_data['Version'];
		}

		/**
		 * Sets up the Advanced Media Offloader
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function initialize()
		{

			// Define constants.
			$this->define('ADVMO', true);
			$this->define('ADVMO_PATH', plugin_dir_path(__FILE__));
			$this->define('ADVMO_URL', plugin_dir_url(__FILE__));
			$this->define('ADVMO_BASENAME', plugin_basename(__FILE__));
			$this->define('ADVMO_VERSION', $this->version);
			$this->define('ADVMO_API_VERSION', 1);

			// Register activation hook.
			register_activation_hook(__FILE__, array($this, 'plugin_activated'));

			// Register deactivation hook.
			register_deactivation_hook(__FILE__, array($this, 'plugin_deactivated'));

			// Set up container
			$this->setup_container();

			// Include files and setup WordPress hooks
			$this->include_files();
			$this->setup_hooks();
		}

		private function setup_container()
		{
			// Include autoloader
			if (file_exists(ADVMO_PATH . 'vendor/scoper-autoload.php')) {
				require_once ADVMO_PATH . 'vendor/scoper-autoload.php';
			} elseif (file_exists(ADVMO_PATH . 'vendor/autoload.php')) {
				require_once ADVMO_PATH . 'vendor/autoload.php';
			}

			$this->container = new \Advanced_Media_Offloader\Core\Container();

			$this->container->register('cloud_provider_factory', function ($c) {
				return new \Advanced_Media_Offloader\Factories\CloudProviderFactory();
			});

			// Register core services
			$this->container->register('cloud_provider', function ($c) {
				$cloud_provider_key = advmo_get_cloud_provider_key();
				if (empty($cloud_provider_key)) {
					return null;
				}

				if ($c->has('cloud_provider_factory')) {
					$cloud_provider_factory = $c->get('cloud_provider_factory');
					return $cloud_provider_factory::create($cloud_provider_key);
				}

				return null;
			});

			$this->container->register('offloader', function ($c) {
				if ($c->has('cloud_provider') && $c->get('cloud_provider') !== null) {
					return \Advanced_Media_Offloader\Offloader::get_instance($c->get('cloud_provider'));
				}
				return null;
			});

			$this->container->register('settings_page', function ($c) {
				return \Advanced_Media_Offloader\Admin\GeneralSettings::create($c->get('cloud_provider_factory'));
			});

			$this->container->register('media_overview_page', function ($c) {
				return \Advanced_Media_Offloader\Admin\MediaOverview::getInstance();
			});

			$this->container->register('bulk_offload_handler', function ($c) {
				return \Advanced_Media_Offloader\BulkOffloadHandler::get_instance();
			});
		}

		private function setup_hooks()
		{
			# check if AWS SDK is loaded
			if (!class_exists(WPFitter\Aws\S3\S3Client::class)) {
				// Show admin notice if AWS SDK is missing.
				add_action('admin_notices', function () {
					$this->notice(__('AWS SDK for PHP is required to use Advanced Media Offloader. Please install it via Composer.', 'advanced-media-offloader'), 'error');
				});
				return;
			}

			# Include admin if needed
			if (is_admin()) {
				$this->container->get('settings_page'); // Initialize settings
				$this->container->get('media_overview_page'); // Initialize media overview
				new \Advanced_Media_Offloader\Admin\Observers\CurrentScreen();

				# Add link to the settings page in the plugins list
				add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);
			}

			# Initialize offloader if cloud provider is configured
			if ($this->container->has('offloader') && $this->container->get('offloader') !== null) {
				$this->container->get('offloader')->initializeHooks();
			}

			# Initialize bulk offload handler
			$this->container->get('bulk_offload_handler');

			# Register WP CLI commands
			$this->register_cli_commands();
		}

		private function include_files()
		{
			# include Utility Functions
			include_once ADVMO_PATH . 'utility-functions.php';
		}

		/**
		 * Register WP CLI commands if WP CLI is available.
		 *
		 * @return void
		 */
		private function register_cli_commands()
		{
			if (defined('WP_CLI') && WP_CLI) {
				WP_CLI::add_command('advmo offload', 'Advanced_Media_Offloader\\CLI\\OffloadCommand');
			}
		}

		public function plugin_action_links($links)
		{
			$settings_page_link = '<a href="' . esc_url(admin_url('admin.php?page=advmo')) . '">' . __('Settings', 'advanced-media-offloader') . '</a>';
			array_unshift($links, $settings_page_link);
			return $links;
		}

		/**
		 * Completes the setup process on "init" of earlier.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function init()
		{
			// Load textdomain file.
			load_plugin_textdomain('advanced-media-offloader', false, dirname(plugin_basename(__FILE__)) . '/languages/');

			// Get selected cloud provider in plugin settings page.
			$cloud_provider_key = advmo_get_cloud_provider_key();

			if ($cloud_provider_key) {
				try {
					// Use the Factory to create the cloud provider instance.
					$cloud_provider = Advanced_Media_Offloader\Factories\CloudProviderFactory::create($cloud_provider_key);

					// Instantiate the Offloader with the cloud provider.
					$this->offloader = Advanced_Media_Offloader\Offloader::get_instance($cloud_provider);
					$this->offloader->initializeHooks();
				} catch (Exception $e) {
					// Handle exception or display admin notice.
					add_action('admin_notices', function () use ($e) {
						$this->notice($e->getMessage(), 'error');
					});
				}
			}
		}

		/**
		 * Plugin Activation Hook
		 *
		 * @since 1.0.0
		 */
		public function plugin_activated()
		{
			// Set the first activated version of Advanced Media Offloader.
			if (null === get_option('advmo_first_activated_version', null)) {
				update_option('advmo_first_activated_version', ADVMO_VERSION, true);
			}
		}

		/**
		 * Plugin Deactivation Hook
		 *
		 * @since 3.3.4
		 */
		public function plugin_deactivated()
		{
			// Clear any scheduled tasks
			wp_clear_scheduled_hook('advmo_bulk_offload_cron');
			wp_clear_scheduled_hook('advmo_check_stalled_processes');
			// Clear bulk offload related options
			delete_option('advmo_bulk_offload_cancelled');
			delete_option('advmo_bulk_offload_last_update');
			delete_option('advmo_bulk_offload_data');
			delete_option('advmo_last_connection_check');
			// Clear any custom cron schedules
			remove_filter('cron_schedules', array($this->container->get('bulk_offload_handler'), 'add_cron_interval'));

			// Note: We don't delete the cloud provider settings to preserve configuration
		}

		public function define($name, $value = true)
		{
			if (!defined($name)) {
				define($name, $value);
			}
		}

		public function notice($message, $type = 'info')
		{
			$class = 'notice notice-' . $type;
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_attr($message));
		}
	}

	function advmo()
	{
		global $advmo;

		// Instantiate only once.
		if (!isset($advmo)) {
			$advmo = new ADVMO();
			$advmo->initialize();
		}
		return $advmo;
	}

	// Instantiate.
	advmo();
} // class_exists check
