<?php

/**
 * Title: Hero
 * Slug: krishna-academy/hero
 * Categories: custom
 * Description:
 * Viewport Width:
 * Inserter: true
 * Keywords: testimonials
 * Block Types:
 * Post Types:
 * Template Types:
 */


$buttons = array(
	__('Button A', 'krishna-academy'),
	__('Button B', 'krishna-academy')
);

?>
<!-- wp:cover {"url":"<?php echo esc_url(get_theme_file_uri('assets/images/hero-background.jpg')); ?>","id":3838,"dimRatio":50,"overlayColor":"contrast","align":"full"} -->
<div class="wp-block-cover alignfull">
	<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim"></span>
	<img class="wp-block-cover__image-background wp-image-3838" alt="" src="<?php echo esc_url(get_theme_file_uri('assets/images/hero-background.jpg')); ?>" data-object-fit="cover" />
	<div class="wp-block-cover__inner-container">

		<!-- wp:heading {"textAlign":"center"} -->
		<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e('Welcome to My Site', 'krishna-academy'); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center"><?php esc_html_e('This is my little home away from home.', 'krishna-academy'); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-buttons">
			<?php foreach ($buttons as $button) : ?>
				<!-- wp:button {"className":"wp-block-button is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button"><?php echo esc_html($button); ?></a></div>
				<!-- /wp:button -->
			<?php endforeach; ?>
		</div>
		<!-- /wp:buttons -->

	</div>
</div>
<!-- /wp:cover -->