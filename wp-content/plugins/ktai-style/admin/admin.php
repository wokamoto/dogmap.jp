<?php
/* ==================================================
 *   based on wp-admin/admin.php, menu-header.php of WP 2.3
   ================================================== */

define ('KTAI_ADMIN_MODE', true);
define ('WP_ADMIN', true);

if ( !defined('ABSPATH')) {
	global $wpload_error;
	$wpload_error = 'Admin feature does not work if custom WP_PLUGIN_DIR is set.';
	require dirname(dirname(__FILE__)) . '/wp-load.php';
}
global $Ktai_Style;
if ( ! isset($Ktai_Style) || ! $Ktai_Style->is_ktai() || @$_COOKIE[KTAI_COOKIE_PCVIEW] ) {
	wp_redirect(get_bloginfo('wpurl') . '/wp-login.php');
	exit();
} elseif ( ! class_exists('KtaiStyle_Admin') ) {
	wp_die(__('Mobile admin feature is not available.', 'ktai_style'));
}
nocache_headers();

require dirname(__FILE__) . '/templates.php';
$Ktai_Style->admin = new KtaiStyle_AdminTemplates();
$Ktai_Style->admin->auth_redirect();
$Ktai_Style->admin->renew_session();
require ABSPATH . 'wp-admin/includes/admin.php';
require dirname(dirname(__FILE__)) . '/' . KtaiStyle::INCLUDES_DIR . '/template-tags.php';
update_category_cache();
wp_get_current_user();
require dirname(__FILE__) . '/menu.php';
$page_charset = $Ktai_Style->get('charset');
$iana_charset = $Ktai_Style->get('iana_charset');
$mime_type    = 'text/html';
$Ktai_Style->ktai->set('mime_type', $mime_type); // don't use 'application/xhtml+xml'
?>