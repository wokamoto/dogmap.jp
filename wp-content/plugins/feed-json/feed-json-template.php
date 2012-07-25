<?php
/**
 * JSON Feed Template for displaying JSON Posts feed.
 *
 */
$callback = trim(esc_html(get_query_var('callback')));
$charset = get_bloginfo('charset');

if (!function_exists('json_encode')) {
	// For PHP < 5.2.0
	function json_encode( $string ) {
		if ( !class_exists('Services_JSON') ) {
			require_once( 'class-json.php' );
		}
		$json = new Services_JSON();
		return $json->encode( $string );
	}
}

if ( have_posts() ) {
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
		$categories = array();
		foreach((array)get_the_category() as $category) { 
			$categories[] = $category->cat_name; 
		}
		$single["categories"] = $categories;

		// tag
		$tags = array();
		foreach((array)get_the_tags() as $tag) { 
			$tags[] = $tag->name; 
		}
		$single["tags"] = $tags;

		$json[] = $single;
	}
	$json = json_encode($json);

	nocache_headers();
	if (!empty($callback)) {
		header("Content-Type: application/x-javascript; charset=$charset");
		echo "$callback($json);";
	} else {
		header("Content-Type: application/json; charset=$charset");
		echo "$json";
	}

} else {
	header("HTTP/1.0 404 Not Found");
	wp_die("404 Not Found");
}
