<?php
/*
** A settings page to configure the plugin.
**  It should ask to select a cloud storage provider and provide the necessary credentials.
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

// check user capabilities
if (!current_user_can('manage_options')) {
	return;
}
?>
<div id="advmo">
	<div class="wrap">
		<h2 class="advmo-print-notices-after"></h2>
		<form method="post" action="options.php">
			<?php settings_fields('advmo'); ?>
			<?php do_settings_sections('advmo'); ?>
			<?php submit_button(); ?>
		</form>
	</div>
</div>