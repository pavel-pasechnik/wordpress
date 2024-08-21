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
