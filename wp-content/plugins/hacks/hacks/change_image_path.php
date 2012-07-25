<?php
function change_image_path($content) {
	$content = str_replace(
		'http://wokamoto.wordpress.com/',
		'http://memo.dogmap.jp/',
		$content);
	if (function_exists('is_ktai') && is_ktai()) {
		return $content;
	}
	$content = str_replace(
		array(
			'http://dogmap.jp/wp-content/uploads/',
			'http://dogmap.jp/wp-content/themes/dogmap/images/',
			'http://dogmap.jp/wp-content/videopop/',
			'http://dogmap.jp/wp-admin/images/',
			'http://dogmap.jp/wp-includes/images/',
			'http://dogmap.jp/wp-content/cache/',
		),
		array(
			'http://static.dogmap.jp/',
			'http://static.dogmap.jp/theme_icons/',
			'http://static.dogmap.jp/movies/',
			'http://static.dogmap.jp/images/wp-admin/',
			'http://static.dogmap.jp/images/wp-includes/',
			'http://static.dogmap.jp/dogmap/cache/',
		),
		$content);
	return $content;
}
add_filter('the_content', 'change_image_path', 11);
