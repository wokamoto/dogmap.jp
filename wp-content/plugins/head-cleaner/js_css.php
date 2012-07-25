<?php
if (!defined('HC_CACHE_DIR'))
	define('HC_CACHE_DIR', 'cache/head-cleaner');
if (!defined('HC_EXPIRED_JS_CSS'))
	define('HC_EXPIRED_JS_CSS', 2592000);	// 60 * 60 * 24 * 30 [sec.]
if (!defined('SHORTINIT'))
	define('SHORTINIT', true );

$error  = false;
if (isset($_GET['f']) && isset($_GET['t'])) {
	$cache_dir = realpath(dirname(__FILE__) . '/../../' . HC_CACHE_DIR) . '/';
	$filename_hash = trim(stripslashes($_GET['f']));
	$type = trim(stripslashes($_GET['t']));

	if (strlen($filename_hash) == 32 && ($type == 'js' || $type == 'css')) {
		$is_gzip = (strpos(strtolower($_SERVER['HTTP_ACCEPT_ENCODING']), 'gzip') !== FALSE);
		$ob_gzip = false;

		$filename = "{$cache_dir}{$type}/{$filename_hash}.{$type}";
		if ($is_gzip && file_exists($filename.'.gz'))
			$filename .= '.gz';
		else
			$ob_gzip = $is_gzip;

		if (file_exists($filename)) {
			$offset = (!defined('HC_EXPIRED_JS_CSS') ? HC_EXPIRED_JS_CSS : 60 * 60 * 24 * 30);
			$content_type = 'text/' . ($type == 'js' ? 'javascript' : $type);

			header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($filename)).' GMT');
			header('Expires: '.gmdate('D, d M Y H:i:s', time() + $offset).' GMT');
			header("Content-type: $content_type");

			if ($is_gzip)
				header('Content-Encoding: gzip');

			if ($ob_gzip)
				ob_start("ob_gzhandler");

			readfile($filename);

			if ($ob_gzip)
				ob_end_flush();

			$error  = false;
		} else {
			$error = 404;
		}
	} else {
		$error = 403;
	}
} else {
	$error = 403;
}

if ( $error !== FALSE ) {
	if(!function_exists('get_option')) {
		$path = (defined('ABSPATH') ? ABSPATH : dirname(dirname(dirname(dirname(__FILE__)))) . '/');
		require_once(file_exists($path.'wp-load.php') ? $path.'wp-load.php' : $path.'wp-config.php');
	}

	$err_msg = "Unknown Error";
	switch ($error) {
	case 403:
		header("HTTP/1.0 403 Forbidden");
		$err_msg = "403 : Forbidden";
		break;
	case 404:
		header('HTTP/1.1 404 Not Found');
		$err_msg = "404 : Not Found";
		break;
	default:
		break;
	}

	if (function_exists('wp_die')) {
		wp_die($err_msg);
	} else {
		echo $err_msg;
		die();
	}
}
