<?php
/* ==================================================
 *   functions to override pluggable.php
   ================================================== */

// ==================================================
function auth_redirect() {
	global $Ktai_Style;
	nocache_headers();
	$uri = preg_replace('!^.*/wp-admin/!' , KtaiStyle::ADMIN_DIR . '/', $_SERVER['REQUEST_URI']);
	wp_redirect($Ktai_Style->get('plugin_url') . KtaiStyle::LOGIN_PAGE . '?redirect_to=' . urlencode($uri));
	exit();
}

// ==================================================
function check_admin_referer($action = -1, $query_arg = '_wpnonce') {
	global $Ktai_Style;
	if ( !isset($Ktai_Style->admin) ) {
		$Ktai_Style->ks_die('No admin functions.');
	}
	$adminurl = strtolower($Ktai_Style->get('plugin_url') . KtaiStyle::ADMIN_DIR . '/');
	$referer = strtolower($Ktai_Style->admin->get_referer());
	$result = isset($_REQUEST[$query_arg]) ? wp_verify_nonce($_REQUEST[$query_arg], $action) : false;
	if ( !$result && (-1 != $action || strpos($referer, $adminurl) === false)) {
		$Ktai_Style->admin->nonce_ays($action);
		exit();
	}
	do_action('check_admin_referer', $action);
}

// ==================================================
function get_currentuserinfo() {
	global $current_user, $Ktai_Style;
	if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
		return false;
	}
	if (! empty($current_user)) {
		return;
	}
	$user_id = KtaiStyle_Admin::check_session();
	if (! $user_id) {
		wp_set_current_user(0);
		return false;
	}
	wp_set_current_user($user_id);
}

// ==================================================
function wp_set_auth_cookie($user_id, $remember = false, $secure = false) {
	global $Ktai_Style;
	$sid = false;
	if ( isset($Ktai_Style->admin) && $user_id ) {
		$sid = $Ktai_Style->admin->set_auth_cookie($user_id, $remember, $secure);
	}
	return $sid;
}
?>