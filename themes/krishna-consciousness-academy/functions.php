<?php

/**
 * Krishna Consciousness Academy functions and definitions.
 *
 */

// Adds theme support for post formats.
if (! function_exists('krishna_consciousness_academy_post_format_setup')) :
	/**
	 * Adds theme support for post formats.
	 */
	function krishna_consciousness_academy_post_format_setup()
	{
		add_theme_support('post-formats', array('aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video'));
		add_theme_support('block-templates');
		add_theme_support('block-template-parts');
		add_theme_support('site-logo');
		add_theme_support('wp-block-styles');
		add_theme_support('align-wide');
		add_theme_support('editor-styles');
		add_theme_support('responsive-embeds');
		add_theme_support('custom-logo', [
			'height'      => 100,
			'width'       => 100,
			'flex-height' => true,
			'flex-width'  => true,
		]);
	}
endif;
add_action('after_setup_theme', 'krishna_consciousness_academy_post_format_setup');

// Enqueues editor-style.css in the editors.
if (! function_exists('krishna_consciousness_academy_editor_style')) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 */
	function krishna_consciousness_academy_editor_style()
	{
		// Включаем поддержку стилей редактора (iframe)
		add_theme_support('editor-styles');

		// Загружаем те же CSS, что и на фронте (порядок важен)
		add_editor_style(array(
			' style.css',
			'assets/css/reset.css',
			'assets/css/header.css',
			'assets/css/footer.css',
			'assets/css/hero.css',
			'assets/css/front-page.css',
			'assets/css/page.css',
			'assets/css/courses.css',
			'assets/css/course.css',
			'assets/css/editor-style.css' // твои точечные правки под редактор
		));
	}
endif;
add_action('after_setup_theme', 'krishna_consciousness_academy_editor_style');

// Enqueues style.css on the front.
if (! function_exists('krishna_consciousness_academy_enqueue_styles')) :
	/**
	 * Enqueues style.css and additional CSS files with cache busting.
	 *
	 */
	function krishna_consciousness_academy_enqueue_styles()
	{
		$theme      = wp_get_theme();
		$theme_ver  = $theme ? $theme->get('Version') : null;

		// 1) Basic style.css theme (allows the customizer/theme headers to work correctly)
		wp_enqueue_style(
			'krishna-consciousness-academy-style',
			get_parent_theme_file_uri('style.css'),
			array(),
			$theme_ver
		);

		// 2) Our additional CSS from assets/css
		$css_files = array(
			'assets/css/reset.css',
			'assets/css/header.css',
			'assets/css/footer.css',
			'assets/css/hero.css',
			'assets/css/front-page.css',
			'assets/css/page.css',
			'assets/css/courses.css',
			'assets/css/course.css',
		);

		foreach ($css_files as $rel_path) {
			$handle   = 'kca-' . sanitize_title(basename($rel_path, '.css'));
			$uri      = get_theme_file_uri($rel_path);
			$abs_path = get_theme_file_path($rel_path);
			$ver      = file_exists($abs_path) ? filemtime($abs_path) : $theme_ver;

			wp_enqueue_style($handle, $uri, array('krishna-consciousness-academy-style'), $ver);
		}
	}
endif;
add_action('wp_enqueue_scripts', 'krishna_consciousness_academy_enqueue_styles');

// Enqueues theme JavaScript on the front.
if (! function_exists('krishna_consciousness_academy_enqueue_scripts')) :
	/**
	 * Enqueue custom JS files with cache busting and defer.
	 *
	 */
	function krishna_consciousness_academy_enqueue_scripts()
	{
		$theme     = wp_get_theme();
		$theme_ver = $theme ? $theme->get('Version') : null;

		$js_files = array(
			'assets/js/nav-scroll.js',
			'assets/js/anchor-offset.js',
		);

		foreach ($js_files as $rel_path) {
			$handle   = 'kca-' . sanitize_title(basename($rel_path, '.js'));
			$src      = get_theme_file_uri($rel_path);
			$abs_path = get_theme_file_path($rel_path);
			$ver      = file_exists($abs_path) ? filemtime($abs_path) : $theme_ver;

			// Подключаем в футере, без зависимостей, с версией по filemtime
			wp_enqueue_script($handle, $src, array(), $ver, true);

			// Добавляем атрибут defer для ускорения загрузки
			if (function_exists('wp_script_add_data')) {
				wp_script_add_data($handle, 'defer', true);
			}
		}
	}
endif;
add_action('wp_enqueue_scripts', 'krishna_consciousness_academy_enqueue_scripts', 20);

// Registers custom block styles.
if (! function_exists('krishna_consciousness_academy_block_styles')) :
	/**
	 * Registers custom block styles.
	 *
	 */
	function krishna_consciousness_academy_block_styles()
	{
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __('Checkmark', 'krishna-consciousness-academy'),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action('init', 'krishna_consciousness_academy_block_styles');

// Registers pattern categories.
if (! function_exists('krishna_consciousness_academy_pattern_categories')) :
	/**
	 * Registers pattern categories.
	 *
	 */
	function krishna_consciousness_academy_pattern_categories()
	{

		register_block_pattern_category(
			'krishna_consciousness_academy_page',
			array(
				'label'       => __('Pages', 'krishna-consciousness-academy'),
				'description' => __('A collection of full page layouts.', 'krishna-consciousness-academy'),
			)
		);

		register_block_pattern_category(
			'krishna_consciousness_academy_post-format',
			array(
				'label'       => __('Post formats', 'krishna-consciousness-academy'),
				'description' => __('A collection of post format patterns.', 'krishna-consciousness-academy'),
			)
		);
	}
endif;
add_action('init', 'krishna_consciousness_academy_pattern_categories');

// Registers block binding sources.
if (! function_exists('krishna_consciousness_academy_register_block_bindings')) :
	/**
	 * Registers the post format block binding source.
	 *
	 */
	function krishna_consciousness_academy_register_block_bindings()
	{
		register_block_bindings_source(
			'krishna-consciousness-academy/format',
			array(
				'label'              => _x('Post format name', 'Label for the block binding placeholder in the editor', 'krishna-consciousness-academy'),
				'get_value_callback' => 'krishna_consciousness_academy_format_binding',
			)
		);
	}
endif;
add_action('init', 'krishna_consciousness_academy_register_block_bindings');

// Registers block binding callback function for the post format name.
if (! function_exists('krishna_consciousness_academy_format_binding')) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 */
	function krishna_consciousness_academy_format_binding()
	{
		$post_format_slug = get_post_format();

		if ($post_format_slug && 'standard' !== $post_format_slug) {
			return get_post_format_string($post_format_slug);
		}
	}
endif;

// Registers custom Template Part areas (e.g., "hero", "courses").
// WP core does NOT provide a register_block_template_part_area() function.
// Correct way is to extend the default areas via the
// `default_wp_template_part_areas` filter (PHP = area; theme.json = parts).
add_filter('default_wp_template_part_areas', function (array $areas) {
	$areas[] = array(
		'area'        => 'hero',
		'area_tag'    => 'section', // allowed: div, header, main, section, article, aside, footer
		'label'       => __('Hero', 'krishna-consciousness-academy'),
		'description' => __('Template parts used in the Hero section.', 'krishna-consciousness-academy'),
		'icon'        => 'header'
	);

	$areas[] = array(
		'area'        => 'courses',
		'area_tag'    => 'section',
		'label'       => __('Courses', 'krishna-consciousness-academy'),
		'description' => __('Template parts used in Courses layouts.', 'krishna-consciousness-academy'),
		'icon'        => 'sidebar'
	);

	return $areas;
});

// -----------------------------------------------------------------------------
// Автоподтяжка логотипа из настроек темы (custom_logo) в блок Site Logo
// Если блок core/site-logo присутствует в шаблоне, но пустой (в нём не выбран
// attachment), мы подставляем разметку классического логотипа.
// Это делает лого видимым на фронте сразу после установки в настройках WP.
add_filter('render_block', function ($block_content, $block) {
	if (empty($block_content)) {
		return $block_content;
	}

	if (! is_array($block) || empty($block['blockName'])) {
		return $block_content;
	}

	// Только для блока core/site-logo.
	if ($block['blockName'] !== 'core/site-logo') {
		return $block_content;
	}

	// Если блок уже отрендерил <img>, ничего не делаем.
	if (strpos($block_content, '<img') !== false) {
		return $block_content;
	}

	// Если в блоке пусто/нет изображения — используем custom_logo как fallback.
	if (function_exists('has_custom_logo') && has_custom_logo()) {
		$logo_html = get_custom_logo(); // ссылка + <img>
		if ($logo_html) {
			// Оборачиваем в див с классами блока, чтобы стили совпадали.
			return '<div class="wp-block-site-logo">' . $logo_html . '</div>';
		}
	}

	return $block_content;
}, 10, 2);
