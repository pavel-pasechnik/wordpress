<?php

/**
 * Title: Cover With Contrast Background
 * Slug: krishna-academy/cover-contrast
 * Categories: banner
 * Description:
 * Viewport Width: 1376
 * Inserter: true
 * Keywords: banner
 * Block Types: core/cover
 * Post Types:
 * Template Types:
 */

?>
<!-- wp:cover {"overlayColor":"contrast","isUserOverlayColor":true,"align":"full","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull has-base-color has-text-color has-link-color">
	<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span>
	<div class="wp-block-cover__inner-container">
		<!-- wp:heading {"textAlign":"center"} -->
		<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e('A simple heading', 'themeslug'); ?></h2>
		<!-- /wp:heading -->
	</div>
</div>
<!-- /wp:cover -->