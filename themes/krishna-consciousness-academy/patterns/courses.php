<?php

/**
 * Title: Courses
 * Slug: krishna-consciousness-academy/courses
 * Categories: courses
 * Block Types: core/template-part/courses
 * Description: A list of courses.
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Krishna Consciousness Academy 1.0
 */

if (! function_exists('ka_cat_courses_id')) {
	function ka_cat_courses_id($slug)
	{
		$slug = sanitize_title((string) $slug);
		$term = get_term_by('slug', $slug, 'category');
		if ($term && ! is_wp_error($term)) {
			return (int) $term->term_id;
		}
	}
}

$course_cat_id = ka_cat_courses_id('courses');

?>

<!-- wp:query {"query":{"perPage":1,"postType":"post","inherit":false,"order":"desc","taxQuery":{"category":[<?php echo $course_cat_id; ?>]}}} -->
<!-- wp:post-template {"className":"courses-title"} -->
<!-- wp:post-terms {"term":"category","className":"course-card-category"} /-->
<!-- /wp:post-template -->
<!-- /wp:query -->

<!-- wp:query {"queryId":1,"query":{"postType":"post","postStatus":"publish","inherit":false,"order":"asc","taxQuery":{"category":[<?php echo $course_cat_id; ?>]}}} -->
<!-- wp:post-template {"className":"course-card"} -->
<!-- wp:group -->
<div class="wp-block-group course-card-content">
	<!-- wp:post-excerpt {"className":"course-card-excerpt"} /-->
	<!-- wp:post-featured-image {"isLink":true} /-->
</div>
<!-- /wp:group -->
<!-- wp:post-title {"className":"course-card-title", "isLink":true} /-->
<!-- /wp:post-template -->
<!-- /wp:query -->