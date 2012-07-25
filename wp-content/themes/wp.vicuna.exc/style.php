<?php
$cwd = dirname(__FILE__);
$dir = mb_substr( $cwd, 0, mb_strrpos($cwd, 'wp-content'));

require_once($dir.'wp-config.php');

$config = get_option('vicuna_config');

if (!isset($config['skin'])) {
	$config['skin'] = 'style-vega';
	update_option('vicuna_config', $config);
}
$skin = $config['skin'];
header("Content-Type: text/css");
?>
@charset "utf-8";

@import url(<?php echo $skin; ?>/import.css);
