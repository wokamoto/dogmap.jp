<?php
require_once('zip.lib.php');

if (!isset($_POST['plugin_name']) || !isset($_POST['shortcode_name']) || !isset($_POST['plugin_code']) || !isset($_POST['plugin_retval'])) {
	die();
}

if (trim($_POST['plugin_name']) == '' || trim($_POST['shortcode_name']) == '' || trim($_POST['plugin_retval']) == '') {
	die();
}

function strip_1($val) {
	return trim(stripslashes(htmlspecialchars($val,ENT_QUOTES)));
}
function strip_2($val) {
	return preg_replace('/[^a-z0-9_\-]+/i', '_', strip_tags(htmlspecialchars_decode($val)));
}

$plugin_name = strip_1($_POST['plugin_name']);
$plugin_url = preg_replace(array('/%3A/i', '/%2F/i'), array(':', '/'), rawurlencode(strip_1($_POST['plugin_url'])));
$plugin_ver = strip_1($_POST['plugin_ver']);
$plugin_author = strip_1($_POST['plugin_author']);
$plugin_authorurl = preg_replace(array('/%3A/i', '/%2F/i'), array(':', '/'), rawurlencode(strip_1($_POST['plugin_authorurl'])));
$plugin_copyright = strip_1($_POST['plugin_copyright']);
$plugin_desc = strip_1($_POST['plugin_desc']);
$plugin_text_domain = strip_1($_POST['plugin_text_domain']);
$plugin_domain_path = strip_1($_POST['plugin_domain_path']);
if ( !empty($plugin_domain_path) ) {
	if ( !preg_match('/^\//', $plugin_domain_path) )
		$plugin_domain_path = '/' . $plugin_domain_path;
	if ( !preg_match('/\/$/', $plugin_domain_path) )
		$plugin_domain_path = $plugin_domain_path . '/';
}

$shortcode_name = strip_2(strip_1($_POST['shortcode_name']));
$atts_keynum = (int) strip_1($_POST['atts_keynum']);
$atts = array();
for ($i = 0; $i <= $atts_keynum; $i++) {
	if ( isset($_POST["atts_key_$i"]) && trim($_POST["atts_key_$i"]) !== '' ) {
		$key = strip_2(strip_1($_POST["atts_key_$i"]));
		$val = strip_2(strip_1($_POST["atts_default_$i"]));
		$atts[$key] = $val;
	}
}

$atts_default = '';
foreach ($atts as $key => $val) {
	$atts_default .= ($atts_default === '' ? '' : ',')
			. "\n\t\t\t'$key' => '$val'";
}
$atts_default .= ($atts_default === '' ? '' : "\n\t\t\t");

$plugin_code = str_replace(array("\\"."'", "\\".'"'), array("'",'"'), str_replace("\\\\", "\\", trim($_POST['plugin_code'])));
$plugin_retval = '$' . strip_2(str_replace('$', '', strip_1($_POST['plugin_retval'])));

$template_filename = "shortcode_plugin_frame.php.txt";
$handle = fopen($template_filename, "r");
$template = fread($handle, filesize($template_filename));
fclose($handle);

$contents = str_replace(
	array(
		'%plugin_name%',
		'%plugin_url%',
		'%plugin_ver%',
		'%plugin_author%',
		'%plugin_authorurl%',
		'%plugin_text_domain%',
		'%plugin_domain_path%',
		'%plugin_copyright%',
		'%plugin_desc%',
		'%shortcode_name%',
		'%class_name%',
		'%atts_default%',
		'%plugin_code%',
		'%plugin_retval%',
	),
	array(
		$plugin_name,
		$plugin_url,
		$plugin_ver,
		$plugin_author,
		$plugin_authorurl,
		$plugin_text_domain,
		$plugin_domain_path,
		$plugin_copyright,
		$plugin_desc,
		$shortcode_name,
		ucfirst(strtolower($shortcode_name)) . '_Controller',
		$atts_default,
		$plugin_code,
		$plugin_retval,
	),
	$template
);

$plugin_file = strtolower(strip_2($plugin_name));
$zipfile = new zipfile();
$zipfile -> addFile($contents, "$plugin_file/$plugin_file.php" );
$zip_buffer = $zipfile->file();
unset($zipfile);

header( "Content-Type: application/octet-stream" );
header( "Content-disposition: attachment; filename=$plugin_file.zip" );
print $zip_buffer;

//header('Content-type: text/plain; charset=utf-8');
//echo $contents;
?>
