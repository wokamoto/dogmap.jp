<?php
/* ==================================================
 *   Patches for other plugins
   ================================================== */

/* ==================================================
 * Keep access to admin screen if not exists ktai style admin directory
 */
if (file_exists(WP_PLUGIN_DIR . '/wphone') || file_exists(WP_PLUGIN_DIR . '/mobileadmin')) {
	define ('KTAI_KEEP_ADMIN_ACESS', true);
}

/* ==================================================
 * Erase Location URL for Ktai Location
 */
function ks_erase_location_url($content) {
	return preg_replace('!\s*<div class="([-. \w]+ +)?locationurl( +[-. \w]+)?">.*?</div>!se', '"$1$2" ? "<div class=\"$1$2\">$3</div>" : ""', $content);
}
add_filter('the_content', 'ks_erase_location_url', 88);

/* ==================================================
 * Disable WP-SpamFree
 */
if (! class_exists('wpSpamFree')):
class wpSpamFree {
	public function __construct() {
		return;
	}
}
endif;

/* ==================================================
 * Shrink FireStats Images
 */
if (defined('FS_WORDPRESS_PLUGIN_VER')):
global $Ktai_Flags, $Ktai_Browsers;
$Ktai_Flags = array(
	'jp' => 237, 'us' => 90,  'es' => 366, 'ru' => 367, 'fr' => 499,
	'de' => 700, 'it' => 701, 'gb' => 702, 'cn' => 703, 'kr' => 704, 
);
/*
$Ktai_Browsers = array(
	'macos' => 434, 'linux' => 252, 'debian' => 190, 'java' => 93,
	'docomo' => 'd109',
); */
function ks_shrink_firestat_images($return) {
	global $Ktai_Style, $Ktai_Flags, $Ktai_Browsers;
	if ($Ktai_Style->is_ktai() == 'Unknown') {
		return $return;
	}
	if (preg_match("|<img src='[^']*plugins/firestats/img/flags/(\w+)\.png' alt='([^']*)' [^>]*class='fs_flagicon' ?/>|", $return, $match) && isset($Ktai_Flags[$match[1]])) {
		$return = str_replace($match[0], '<img localsrc="' . $Ktai_Flags[$match[1]] . '" alt="' . $match[2] . '" />', $return);
	}
/*
	if (preg_match("|<img src='[^']*plugins/firestats/img/browsers/(\w+)\.png' alt='([^']*)' [^>]*class='fs_browsericon' ?/>|", $return, $match) && isset($Ktai_Browsers[$match[1]])) {
		$return = str_replace($match[0], '<img localsrc="' . $Ktai_Browsers[$match[1]] . '" alt="' . $match[2] . '" />', $return);
	}
*/
	return $return;
}
add_filter('get_comment_author_link', 'ks_shrink_firestat_images', 101);
endif;

/* ==================================================
 * Disable title-replace by All in One SEO Pack 
 */
global $aiosp;
if (isset($aiosp) && is_object($aiosp)) {
	remove_action('wp_head', array($aiosp, 'wp_head'));
	remove_action('template_redirect', array($aiosp, 'template_redirect'));
}

/* ==================================================
 * Disable Disqus comment system
 */
if (defined('DISQUS_URL')) {
	remove_filter('comments_template', 'dsq_comments_template');
	remove_filter('comments_number', 'dsq_comments_number');
	remove_filter('get_comments_number', 'dsq_get_comments_number');
	remove_filter('bloginfo_url', 'dsq_bloginfo_url');
	remove_action('loop_start', 'dsq_loop_start');
	remove_action('loop_end', 'dsq_comment_count');
	remove_action('wp_footer', 'dsq_comment_count');
}

/* ==================================================
 * Insert ks_fix_encoding_form() for Contact Form 7
 */
if (defined('WPCF7_VERSION')) {
	function ks_remove_fragment($url) {
		return preg_replace('/#[^#]*$/', '', $url);
	}
	add_filter('wpcf7_form_action_url', 'ks_remove_fragment');
	function ks_add_fix_encoding_form($form) {
		return $form . ks_fix_encoding_form(false);
	}
	add_filter('wpcf7_form_elements', 'ks_add_fix_encoding_form');
}

/* ==================================================
 * Kill fortysix-mobile
 */
if (function_exists('fsmb_response_mobile')) {
	remove_action('template_redirect', 'fsmb_response_mobile', 1);
}
?>