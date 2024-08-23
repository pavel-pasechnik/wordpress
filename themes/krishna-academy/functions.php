<?php

#include get_parent_theme_file_path('inc/helpers.php');


# insert styles
add_action('wp_enqueue_scripts', 'krishna_academy_enqueue_styles');

function krishna_academy_enqueue_styles()
{
	wp_enqueue_style(
		'krishna-academy-normalize',
		get_stylesheet_uri('https://cdnjs.cloudflare.com/ajax/libs/modern-normalize/3.0.0/modern-normalize.min.css'),
	);
	wp_enqueue_style(
		'krishna-academy-reset',
		get_parent_theme_file_uri('assets/css/reset.css'),
		array(),
		wp_get_theme()->get('Version'),
		'all'
	);
	wp_enqueue_style(
		'krishna-academy-style',
		get_stylesheet_uri()
	);
}

# Add style in to Designer
add_action('after_setup_theme', 'krishna_academy_setup');

function krishna_academy_setup()
{
	add_editor_style(get_stylesheet_uri());
}

# Add javascript
add_action('wp_enqueue_scripts', 'krishna_academy_enqueue_scripts');

function krishna_academy_enqueue_scripts()
{
	wp_enqueue_script(
		'krishna_academy-menu',
		get_parent_theme_file_uri('assets/js/index.js'),
		array(),
		wp_get_theme()->get('Version'),
		true
	);
}

# Area named Loop for assigning parts of the template


add_filter('default_wp_template_part_areas', 'krishna_academy_template_part_areas');

function krishna_academy_template_part_areas(array $areas)
{
	$areas[] = array(
		'area'        => 'loop',
		'area_tag'    => 'section',
		'label'       => __('Loop', 'krishna_academy'),
		'description' => __('Custom description', 'krishna_academy'),
		'icon'        => 'layout'
	);

	return $areas;
}

# This code removes jquery-migrate's dependency on jquery in WordPress. After executing this code, jquery-migrate will no longer be loaded along with jquery on your site.

add_action('wp_default_scripts', function ($scripts) {
	if (!empty($scripts->registered['jquery'])) {
		$scripts->registered['jquery']->deps = array_diff($scripts->registered['jquery']->deps, ['jquery-migrate']);
	}
});

# disable remote templates

add_filter('should_load_remote_block_patterns', '__return_false');

# new custom template category for your theme

add_action('init', 'krishna_academy_register_pattern_categories');

function krishna_academy_register_pattern_categories()
{
	register_block_pattern_category('krishna_academy/custom', array(
		'label'       => __('Theme Name: Custom', 'krishna_academy'),
		'description' => __('Custom patterns for Theme Name.', 'krishna_academy')
	));
}
