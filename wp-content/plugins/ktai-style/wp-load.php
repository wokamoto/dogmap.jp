<?php
/* ==================================================
 *   Search WP root and load WordPress
   ================================================== */

define('WP_LOAD_CONF', 'wp-load-conf.php');
define('WP_LOAD_PATH_STRING', 'WP-LOAD-PATH:');

$wp_load_conf = dirname(__FILE__) . '/' . WP_LOAD_CONF;
if (file_exists($wp_load_conf)) {
	$conf = file_get_contents($wp_load_conf);
	if (preg_match('!^' . preg_quote(WP_LOAD_PATH_STRING) . '(.*/)$!m', $conf, $path) && strpos($path[1], '..') === false && file_exists($path[1]))  {
		$wp_root = $path[1];
	}
}
if (! isset($wp_root)) {
	// Place the path to the WordPress root directory
	$wp_root = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
	// if WP root is /home/foo/public_html/wp-core/ and wp-content is moved to /home/foo/public_html/wp-content/, $wp_root is below:
	//$wp_root = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-core/';
}

if (file_exists($wp_root . 'wp-load.php')) {
	require $wp_root . 'wp-load.php';
} elseif (file_exists($wp_root . 'wp-config.php')) {
	require $wp_root . 'wp-config.php';
} else {
	$wpload_error = isset($wpload_error) ? $wpload_error : 'Could not find wp-load.php/wp-config.php because custom WP_PLUGIN_DIR is set.';
	$wpload_error .= "\n" . 'Please cofigure ' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . ' file to figure wordpress root directory.';
	if (isset($wpload_status) && is_int($wpload_status)) {
		echo $wpload_error;
		exit($wpload_status);
	} else {
		exit($wpload_error);
	}
}
?>