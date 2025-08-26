<?php

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
            <?php settings_fields('advmo_media_overview'); ?>
            <?php do_settings_sections('advmo_media_overview'); ?>
        </form>
    </div>
</div>