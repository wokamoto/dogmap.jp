<?php
/**
 * JSON Feed Template for displaying JSON Posts feed.
 *
 */
if ( !function_exists('json_encode') ) {
	function json_encode( $string ) {
		global $wp_json;

		if ( !is_a($wp_json, 'Services_JSON') ) {
			require_once(dirname(__FILE__) . '/includes/class-json.php');
			$wp_json = new Services_JSON();
		}

		return $wp_json->encodeUnsafe( $string );
	}
}

$limit = get_query_var('limit');
$limit = (int)(is_numeric($limit) ? $limit : 0);
$callback = trim(esc_html(get_query_var('callback')));
$charset = get_bloginfo('charset');

if ($limit > 0) {
	query_posts("showposts=$limit");
}
if ( have_posts() ) {
	$json = array();
	while ( have_posts() ) {
		the_post();
		$id = $post->ID;

		$single = array(
			'id'        => (int) get_the_ID() ,
			'title'     => get_the_title() ,
			'link'      => get_permalink() ,
			'permalink' => get_permalink(),
//			'content'   => get_the_content(),
//			'excerpt'   => get_the_excerpt(),
			'date'      => the_date('Y-m-d H:i:s','','',false) ,
			'author'    => get_the_author() ,
			);

		// thumbnail
		if (has_post_thumbnail($id)) {
			$single["thumbnail"] = preg_replace("/^.*['\"](https?:\/\/[^'\"]*)['\"].*/i","$1",get_the_post_thumbnail($id));
		}

		// category
		$categories = array();
		foreach((array)(get_the_category()) as $category) { 
			$categories[] = $category->cat_name; 
		}
		$single["categories"] = $categories;

		// tag
		$tags = array();
		foreach((array)(get_the_tags()) as $tag) { 
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
