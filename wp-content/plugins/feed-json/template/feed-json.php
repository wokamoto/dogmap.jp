<?php
/**
 * JSON Feed Template for displaying JSON Posts feed.
 *
 */
$callback = trim(esc_html(get_query_var('callback')));
$charset  = get_option('charset');

if ( have_posts() ) {
	global $wp_query;
	$query_array = $wp_query->query;

	// Make sure query args are always in the same order
	ksort( $query_array );

	$json = array();

	while ( have_posts() ) {
		the_post();
		$id = (int) $post->ID;

		$single = array(
			'id'        => $id ,
			'title'     => get_the_title() ,
            'permalink' => get_permalink(),
            'content'   => get_the_content(),
            'excerpt'   => get_the_excerpt(),
			'date'      => get_the_date('Y-m-d H:i:s','','',false) ,
			'author'    => get_the_author() ,
			);

		// thumbnail
		if (function_exists('has_post_thumbnail') && has_post_thumbnail($id)) {
			$single["thumbnail"] = preg_replace("/^.*['\"](https?:\/\/[^'\"]*)['\"].*/i","$1",get_the_post_thumbnail($id));
		}

		// category
		$single["categories"] = array();
		$categories = get_the_category();
		if ( ! empty( $categories ) ) {
			$single["categories"] = wp_list_pluck( $categories, 'cat_name' );
		}

		// tags
		$single["tags"] = array();
		$tags = get_the_tags();
		if ( ! empty( $tags) ) {
			$single["tags"] = wp_list_pluck( $tags, 'name' );
		}

		$json[] = $single;
	}

	$json = json_encode($json);

	nocache_headers();
	if (!empty($callback)) {
		header("Content-Type: application/x-javascript; charset={$charset}");
		echo "{$callback}({$json});";
	} else {
		header("Content-Type: application/json; charset={$charset}");
		echo $json;
	}

} else {
	status_header('404');
	wp_die("404 Not Found");
}
