<?php

/**
 * Title: Hero
 * Slug: krishna-consciousness-academy/hero
 * Categories: hero
 * Block Types: core/template-part/hero
 * Description: A hero section.
 *
 * @package Krishna_Consciousness_Academy
 */

if (! function_exists('ka_cat_id')) {
	function ka_cat_id($slug)
	{
		$slug = sanitize_title((string) $slug);
		$term = get_term_by('slug', $slug, 'category');
		if ($term && ! is_wp_error($term)) {
			return (int) $term->term_id;
		}
		return 0;
	}
}

$cat_id = ka_cat_id('branding');

?>

<!-- wp:group {"className":"hero-section container"} -->
<div class="wp-block-group hero-section container">
	<!-- wp:image {"sizeSlug":"full","className":"hero-picture"} -->
	<figure class="wp-block-image size-full hero-picture"><img src="" alt="Vaishnav studies" /></figure>
	<!-- /wp:image -->

	<!-- wp:group {"className":"hero-promo"} -->
	<div class="wp-block-group hero-promo">
		<!-- wp:query {"queryId":1,"query":{"perPage":1,"pages":1,"offset":0,"postType":"post","postStatus":"publish","inherit":false,"orderBy":"date","order":"asc","categoryIds":[<?php echo (int) $cat_id; ?>]},"displayLayout":{"type":"grid","columns":1}} -->
		<!-- wp:post-template -->
		<!-- wp:post-content /-->
		<!-- /wp:post-template -->
		<!-- /wp:query -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->