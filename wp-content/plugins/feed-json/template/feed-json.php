<?php
/**
 * JSON Feed Template for displaying JSON Posts feed.
 *
 */
$callback = trim(esc_html(get_query_var('callback')));
$charset  = get_bloginfo('charset');

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

	global $wp_query, $fj_feed_json;
	$query_array = $wp_query->query;

	// Make sure query args are always in the same order
	ksort( $query_array );

	$cache_key = 'fj-query-' . md5( http_build_query( $query_array ) . $fj_feed_json->get_incrementor_value() );

	if ( ( $json = get_transient( $cache_key ) ) === false ) {
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

		// Cache the JSON for 12 hours
		set_transient( $cache_key, $json, ( HOUR_IN_SECONDS * 12 ) );
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
	header("HTTP/1.0 404 Not Found");
	wp_die("404 Not Found");
}
