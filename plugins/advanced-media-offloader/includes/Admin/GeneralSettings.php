<?php

namespace Advanced_Media_Offloader\Admin;

use Advanced_Media_Offloader\Factories\CloudProviderFactory;

class GeneralSettings
{
	private static $instance = null;
	protected array $cloud_providers;
	protected $cloudProviderFactory;

	private function __construct(?CloudProviderFactory $cloudProviderFactory = null)
	{
		// Allow injection or create factory if none provided
		$this->cloudProviderFactory = $cloudProviderFactory ?: new CloudProviderFactory();

		// Get available providers from the factory
		$this->cloud_providers = $this->cloudProviderFactory::getAvailableProviders();

		// Register hooks
		$this->register_hooks();
	}

	public static function getInstance(?CloudProviderFactory $cloudProviderFactory = null): self
	{
		if (self::$instance === null) {
			self::$instance = new self($cloudProviderFactory);
		}
		return self::$instance;
	}

	public static function create(CloudProviderFactory $cloudProviderFactory): self
	{
		return new self($cloudProviderFactory);
	}

	/**
	 * Create an instance of the selected cloud provider.
	 *
	 * @param string $provider_key The cloud provider key.
	 * @return \Advanced_Media_Offloader\Abstracts\S3_Provider|null
	 */
	protected function createCloudProvider(string $provider_key)
	{
		try {
			return $this->cloudProviderFactory::create($provider_key);
		} catch (\Exception $e) {
			error_log('ADVMO Error: ' . $e->getMessage());
			return null;
		}
	}

	private function register_hooks()
	{
		add_action('admin_menu', [$this, 'add_settings_page']);
		add_action('admin_init', [$this, 'initialize']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('wp_ajax_advmo_test_connection', [$this, 'check_connection_ajax']);
	}

	public function initialize()
	{
		register_setting('advmo', 'advmo_settings', [
			'sanitize_callback' => [$this, 'sanitize']
		]);

		$this->add_settings_section();
		$this->add_provider_field();
		$this->add_credentials_field();
		$this->add_retention_policy_field();
		$this->add_path_prefix_field();
		$this->add_object_versioning_field();
		$this->add_mirror_delete_field();
	}

	private function add_settings_section()
	{
		add_settings_section(
			'cloud_provider',
			__('Cloud Provider', 'advanced-media-offloader'),
			function () {
				echo '<p>' . esc_attr__('Select a cloud storage provider and provide the necessary credentials.', 'advanced-media-offloader') . '</p></div>';
			},
			'advmo',
			[
				'before_section' => '<div class="advmo-section advmo-cloud-provider-settings"><div class="advmo-section-header">',
				'after_section' => '</div>',
			]
		);

		add_settings_section(
			'general_settings',
			__('General Settings', 'advanced-media-offloader'),
			function () {
				echo '<p>' . esc_attr__('Configure the core options for managing and offloading your media files to cloud storage.', 'advanced-media-offloader') . '</p></div>';
			},
			'advmo',
			[
				'before_section' => '<div class="advmo-section advmo-general-settigns"><div class="advmo-section-header">',
				'after_section' => '</div>',
			]
		);
	}

	private function add_provider_field()
	{
		add_settings_field(
			'cloud_provider',
			__('Cloud Provider', 'advanced-media-offloader'),
			[$this, 'cloud_provider_field'],
			'advmo',
			'cloud_provider',
			[
				'class' => 'advmo-field advmo-cloud-provider',
			]
		);
	}

	private function add_credentials_field()
	{
		add_settings_field(
			'advmo_cloud_provider_credentials',
			__('Credentials', 'advanced-media-offloader'),
			[$this, 'cloud_provider_credentials_field'],
			'advmo',
			'cloud_provider',
			[
				'class' => 'advmo-field advmo-cloud-provider-credentials',
			]
		);
	}
	private function add_retention_policy_field()
	{
		add_settings_field(
			'retention_policy',
			__('Retention Policy', 'advanced-media-offloader'),
			[$this, 'retention_policy_field'],
			'advmo',
			'general_settings',
			[
				'class' => 'advmo-field advmo-retention_policy',
			]
		);
	}
	private function add_mirror_delete_field()
	{
		add_settings_field(
			'mirror_delete',
			__('Mirror Delete', 'advanced-media-offloader'),
			[$this, 'mirror_delete_field'],
			'advmo',
			'general_settings',
			[
				'class' => 'advmo-field advmo-mirror-delete',
			]
		);
	}

	private function add_object_versioning_field()
	{
		add_settings_field(
			'object_versioning',
			__('File Versioning', 'advanced-media-offloader'),
			[$this, 'object_versioning_field'],
			'advmo',
			'general_settings',
			[
				'class' => 'advmo-field advmo-object-versioning',
			]
		);
	}

	private function add_path_prefix_field()
	{
		add_settings_field(
			'path_prefix',
			__('Custom Path Prefix', 'advanced-media-offloader'),
			[$this, 'path_prefix_field'],
			'advmo',
			'general_settings',
			[
				'class' => 'advmo-field advmo-path-prefix',
			]
		);
	}

	public function path_prefix_field()
	{
		$options = get_option('advmo_settings');
		$path_prefix = isset($options['path_prefix']) ? $options['path_prefix'] : "wp-content/uploads/";
		$path_prefix_Active = isset($options['path_prefix_active']) ? $options['path_prefix_active'] : 0;
		echo '<div class="advmo-checkbox-option">';
		echo '<input type="checkbox" id="path_prefix_active" name="advmo_settings[path_prefix_active]" value="1" ' . checked(1, $path_prefix_Active, false) . '/>';
		echo '<label for="path_prefix_active">' . esc_html__('Use Custom Path Prefix', 'advanced-media-offloader') . '</label>';
		echo '<p class="description">' . '<input type="text" id="path_prefix" name="advmo_settings[path_prefix]" value="' . esc_html($path_prefix) . '"' . ($path_prefix_Active ? '' : ' disabled') . '/>'  . '</p>';
		echo '<p class="description">' . esc_html__('Add a common prefix to organize offloaded media files from this site in your cloud storage bucket.', 'advanced-media-offloader') . '</p>';
		echo '</div>';
	}

	public function object_versioning_field()
	{
		$options = get_option('advmo_settings');
		$object_versioning = isset($options['object_versioning']) ? $options['object_versioning'] : 0;

		echo '<div class="advmo-checkbox-option">';
		echo '<input type="checkbox" id="object_versioning" name="advmo_settings[object_versioning]" value="1" ' . checked(1, $object_versioning, false) . '/>';
		echo '<label for="object_versioning">' . esc_html__('Add Version to Bucket Path', 'advanced-media-offloader') . '</label>';
		echo '<p class="description">' . esc_html__('Automatically add unique timestamps to your media file paths to ensure the latest versions are always delivered. This prevents outdated content from being served due to CDN caching, even when you replace files with the same name. Eliminate manual cache invalidation and guarantee your visitors always see the most up-to-date media.', 'advanced-media-offloader') . '</p>';
		echo '</div>';
	}

	public function mirror_delete_field()
	{
		$options = get_option('advmo_settings');
		$mirror_delete = isset($options['mirror_delete']) ? intval($options['mirror_delete']) : 0;
		echo '<div class="advmo-checkbox-option">';
		echo '<input type="checkbox" id="mirror_delete" name="advmo_settings[mirror_delete]" value="1" ' . checked(1, $mirror_delete, false) . '/>';
		echo '<label for="mirror_delete">' . esc_html__('Sync Deletion with Cloud Storage', 'advanced-media-offloader') . '</label>';
		echo '<p class="description">' . esc_html__('When enabled, deleting a media file in WordPress will also remove it from your cloud storage.', 'advanced-media-offloader') . '</p>';
		echo '</div>';
	}

	public function retention_policy_field()
	{
		$options = get_option('advmo_settings');
		$retention_policy = isset($options['retention_policy']) ? intval($options['retention_policy']) : 0;

		echo '<div class="advmo-radio-group">';

		echo '<div class="advmo-radio-option">';
		echo '<input type="radio" id="retention_policy" name="advmo_settings[retention_policy]" value="0" ' . checked(0, $retention_policy, false) . '/>';
		echo '<label for="retention_policy_none">' . esc_html__('Retain Local Files', 'advanced-media-offloader') . '</label>';
		echo '<p class="description">' . esc_html__('Keep all files on your local server after offloading to the cloud. This option provides redundancy but uses more local storage.', 'advanced-media-offloader') . '</p>';
		echo '</div>';

		echo '<div class="advmo-radio-option">';
		echo '<input type="radio" id="retention_policy_cloud" name="advmo_settings[retention_policy]" value="1" ' . checked(1, $retention_policy, false) . '/>';
		echo '<label for="retention_policy_cloud">' . esc_html__('Smart Local Cleanup', 'advanced-media-offloader') . '</label>';
		echo '<p class="description">' . esc_html__('Remove local copies after cloud offloading, but keep the original file as a backup. Balances storage efficiency with data safety.', 'advanced-media-offloader') . '</p>';
		echo '</div>';

		echo '<div class="advmo-radio-option">';
		echo '<input type="radio" id="retention_policy_all" name="advmo_settings[retention_policy]" value="2" ' . checked(2, $retention_policy, false) . '/>';
		echo '<label for="retention_policy_all">' . esc_html__('Full Cloud Migration', 'advanced-media-offloader') . '</label>';
		echo '<p class="description">' . esc_html__('Remove all local files, including originals, after successful cloud offloading. Maximizes local storage savings but relies entirely on cloud storage.', 'advanced-media-offloader') . '</p>';
		echo '</div>';

		echo '</div>';
	}

	public function sanitize($options)
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		// Initialize sanitized options with defaults
		$sanitized = [
			'cloud_provider' => '',
			'retention_policy' => 0,
			'object_versioning' => 0,
			'path_prefix' => '',
			'mirror_delete' => 0,
			'path_prefix_active' => 0
		];

		try {
			// Validate and sanitize cloud provider
			$sanitized['cloud_provider'] = $this->sanitizeCloudProvider($options);

			// Sanitize retention policy
			$sanitized['retention_policy'] = $this->sanitizeRetentionPolicy($options);

			// Sanitize object versioning
			$sanitized['object_versioning'] = $this->sanitizeObjectVersioning($options);

			// Sanitize path prefix
			$sanitized['path_prefix'] = $this->sanitizePathPrefix($options);

			// Sanitize mirror delete
			$sanitized['mirror_delete'] = isset($options['mirror_delete']) && (int) $options['mirror_delete'] === 1 ? 1 : 0;

			// Sanitize path prefix active
			$sanitized['path_prefix_active'] = isset($options['path_prefix_active']) && (int) $options['path_prefix_active'] === 1 ? 1 : 0;

			add_settings_error(
				'advmo_messages',
				'advmo_message',
				__('Settings Saved', 'advanced-media-offloader'),
				'updated'
			);

			return $sanitized;
		} catch (\Exception $e) {
			add_settings_error(
				'advmo_messages',
				'advmo_message',
				$e->getMessage(),
				'error'
			);

			return $options;
		}
	}

	/**
	 * Sanitizes the cloud provider setting
	 *
	 * @param array $options
	 * @return string
	 * @throws Exception
	 */
	private function sanitizeCloudProvider(array $options): string
	{

		$provider = sanitize_text_field(isset($options['cloud_provider']) ? $options['cloud_provider'] : '');

		if (!array_key_exists($provider, $this->cloud_providers)) {
			throw new \Exception(__('Invalid Cloud Provider!', 'advanced-media-offloader'));
		}

		return $provider;
	}

	/**
	 * Sanitizes the retention policy setting
	 *
	 * @param array $options
	 * @return int
	 */
	private function sanitizeRetentionPolicy(array $options): int
	{
		$policy = isset($options['retention_policy']) ? (int) $options['retention_policy'] : 0;
		return in_array($policy, [0, 1, 2], true) ? $policy : 0;
	}

	/**
	 * Sanitizes the object versioning setting
	 *
	 * @param array $options
	 * @return int
	 */
	private function sanitizeObjectVersioning(array $options): int
	{
		return isset($options['object_versioning']) && (int) $options['object_versioning'] === 1 ? 1 : 0;
	}

	/**
	 * Sanitizes the path prefix setting
	 *
	 * @param array $options
	 * @return string
	 */
	private function sanitizePathPrefix(array $options): string
	{
		return isset($options['path_prefix']) ? advmo_sanitize_path($options['path_prefix']) : '';
	}

	public function add_settings_page()
	{

		// Advanced Media Offload Logo as Icon
		$svg_icon = '<svg width="358" height="258" viewBox="0 0 358 258" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M0.0100098 176.677C0.0100098 140.074 24.3664 109.179 57.758 99.2859C62.823 43.9549 109.353 0.616943 166.006 0.616943C207.398 0.616943 243.362 23.7509 261.718 57.7489C316.583 65.5989 357.99 115.763 357.99 171.712C357.99 203.324 345.663 225.134 329.249 238.845C313.363 252.115 294.346 257.237 280.921 257.383H280.848H280.776C240.825 257.383 208.247 225.816 206.624 186.263L201.404 192.33C196.595 197.919 188.166 198.551 182.577 193.741C176.988 188.932 176.356 180.503 181.165 174.914L202.899 149.657C212.952 137.974 231.116 138.198 240.879 150.125L261.374 175.166C266.044 180.872 265.204 189.283 259.498 193.953C253.792 198.623 245.381 197.783 240.711 192.077L233.261 182.975C233.261 183.039 233.261 183.104 233.261 183.168C233.261 209.385 254.494 230.643 280.701 230.683C288.571 230.579 301.441 227.284 312.132 218.353C322.328 209.836 331.29 195.601 331.29 171.712C331.29 126.154 295.784 86.5289 252.109 83.5669C250.177 83.4359 248.225 83.3689 246.256 83.3689C199.345 83.3689 161.307 121.356 161.223 168.247L161.423 176.516V176.677C161.423 221.25 125.289 257.383 80.716 257.383C36.143 257.383 0.0100098 221.25 0.0100098 176.677ZM100.765 99.042C96.041 97.338 89.913 96.4629 85.057 96.1309C91.366 57.1129 125.206 27.3169 166.006 27.3169C191.795 27.3169 214.82 39.222 229.861 57.863C175.925 65.794 134.522 112.263 134.522 168.402V168.563L134.722 176.829C134.64 206.586 110.492 230.683 80.716 230.683C50.89 230.683 26.7104 206.504 26.7104 176.677C26.7104 149.604 46.644 127.163 72.632 123.27C75.262 122.876 77.961 122.671 80.716 122.671C84.182 122.671 89.51 123.366 91.705 124.158C98.641 126.66 106.291 123.065 108.793 116.13C111.295 109.194 107.701 101.543 100.765 99.042Z" fill="#A7AAAD"/>
					</svg>';
		$icon_base64 = 'data:image/svg+xml;base64,' . base64_encode($svg_icon);

		add_menu_page(
			__('Advanced Media Offloader', 'advanced-media-offloader'),
			__('Media Offloader', 'advanced-media-offloader'),
			'manage_options',
			'advmo',
			[$this, 'general_settings_page_view'],
			$icon_base64,
			100
		);

		add_submenu_page(
			'advmo',
			__('General Settings', 'advanced-media-offloader'),
			__('General Settings', 'advanced-media-offloader'),
			'manage_options',
			'advmo',
			[$this, 'general_settings_page_view']
		);
	}

	public function cloud_provider_field($args)
	{
		$options = get_option('advmo_settings', []);
		$current_provider = isset($options['cloud_provider']) && !empty($options['cloud_provider'])
			? $options['cloud_provider']
			: '';

		echo '<select name="advmo_settings[cloud_provider]">';

		// Add placeholder option if no provider is selected
		if (empty($current_provider)) {
			echo '<option value="" selected disabled>' . esc_html__('Select a cloud provider', 'advanced-media-offloader') . '</option>';
		}

		foreach ($this->cloud_providers as $key => $provider) {
			$disabled = isset($provider['class']) && $provider['class'] === null ? 'disabled' : '';
			$selected = $current_provider === $key ? 'selected' : '';
			echo '<option value="' . esc_attr($key) . '" ' . esc_attr($selected) . ' ' . esc_attr($disabled) . '>' . esc_html($provider['name']) . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Get the cloud provider key from the plugin settings.
	 *
	 * @return string The cloud provider key or an empty string if not set.
	 */
	private function get_cloud_provider_key(): string
	{
		return advmo_get_cloud_provider_key();
	}

	public function cloud_provider_credentials_field()
	{
		$cloud_provider_key = $this->get_cloud_provider_key();

		if (!empty($cloud_provider_key)) {
			try {
				// Use the CloudProviderFactory to create an instance of the selected cloud provider
				/** @var CloudProviderInterface $cloud_provider_instance */
				$cloud_provider_instance = CloudProviderFactory::create($cloud_provider_key);

				// Render the credentials fields specific to the selected cloud provider
				$cloud_provider_instance->credentialsField();
			} catch (\Exception $e) {
				// Display an error message if the cloud provider is unsupported or instantiation fails
				echo '<p class="description">' . esc_html__('Selected cloud provider is not supported or failed to initialize.', 'advanced-media-offloader') . '</p>';
			}
		} else {
			echo '<p class="description">' . esc_html__('Please select a valid cloud provider to configure credentials.', 'advanced-media-offloader') . '</p>';
		}
	}

	public function general_settings_page_view()
	{
		advmo_get_view('admin/general_settings');
	}

	public function enqueue_scripts()
	{
		if (!advmo_is_settings_page()) {
			return;
		}

		if (advmo_is_settings_page('general')) {
			wp_enqueue_script('advmo_settings', ADVMO_URL . 'assets/js/advmo_settings.js', [], ADVMO_VERSION, true);
			wp_localize_script('advmo_settings', 'advmo_ajax_object', [
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('advmo_test_connection'),
				'i18n' => [
					'test_connection' => __('Test Connection', 'advanced-media-offloader'),
					'recheck' => __('Re-Check', 'advanced-media-offloader'),
					'connected' => __('Connected.', 'advanced-media-offloader'),
					'last_check' => __('Last check:', 'advanced-media-offloader')
				]
			]);
		}

		if (advmo_is_settings_page('media-overview')) {
			wp_enqueue_script('advmo_bulkoffload', ADVMO_URL . 'assets/js/advmo_bulkoffload.js', [], ADVMO_VERSION, true);
			wp_localize_script('advmo_bulkoffload', 'advmo_ajax_object', [
				'ajax_url' => admin_url('admin-ajax.php'),
				'bulk_offload_nonce' => wp_create_nonce('advmo_bulk_offload')
			]);
		}


		wp_enqueue_style('advmo_admin', ADVMO_URL . 'assets/css/admin.css', [], ADVMO_VERSION);

		// Enqueue RTL styles if needed
		if (is_rtl()) {
			wp_enqueue_style('advmo_admin_rtl', ADVMO_URL . 'assets/css/admin-rtl.css', ['advmo_admin'], ADVMO_VERSION);
		}
	}

	public function check_connection_ajax()
	{
		$current_time = current_time('d/m/Y - h:i A');
		$response_data = [
			'last_check' => $current_time
		];

		// Verify nonce
		if (!$this->verify_security_nonce('security_nonce', 'advmo_test_connection')) {
			$response_data['message'] = __('Invalid nonce!', 'advanced-media-offloader');
			wp_send_json_error($response_data);
		}

		// Retrieve plugin settings
		$cloud_provider_key = $this->get_cloud_provider_key();

		if (empty($cloud_provider_key)) {
			$response_data['message'] = __('Invalid Cloud Provider!', 'advanced-media-offloader');
			wp_send_json_error($response_data);
		}

		try {
			$cloud_provider = $this->createCloudProvider($cloud_provider_key);
			if (!$cloud_provider) {
				throw new \Exception(__('Could not initialize cloud provider', 'advanced-media-offloader'));
			}

			$connection_result = $cloud_provider->checkConnection();

			// Always update last check time regardless of result
			update_option('advmo_last_connection_check', $current_time);

			if ($connection_result) {
				$response_data['message'] = __('Connection successful!', 'advanced-media-offloader');
				wp_send_json_success($response_data);
			} else {
				$response_data['message'] = __('Connection failed!', 'advanced-media-offloader');
				wp_send_json_error($response_data, 401);
			}
		} catch (\Exception $e) {
			// Log error and update check time
			error_log('Advanced Media Offloader Connection Error: ' . $e->getMessage());
			update_option('advmo_last_connection_check', $current_time);

			$response_data['message'] = sprintf(
				/* translators: %s: error message */
				__('Failed to establish a connection: %s', 'advanced-media-offloader'),
				esc_html($e->getMessage())
			);
			wp_send_json_error($response_data, 500);
		}
	}

	/**
	 * Verify the security nonce for AJAX requests.
	 *
	 * @return bool Whether the nonce is valid.
	 */
	private function verify_security_nonce($name, $action)
	{
		$security_nonce = isset($_POST[$name]) ? sanitize_text_field($_POST[$name]) : '';
		return wp_verify_nonce($security_nonce, $action);
	}

	// Prevent cloning of the instance
	private function __clone() {}

	// Prevent unserializing of the instance
	public function __wakeup() {}
}
