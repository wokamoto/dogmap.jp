<?php
/* ==================================================
 *   Ktai Login Page
 *   Based on wp-login.php of WP 2.9.2
   ================================================== */

define ('KTAI_ADMIN_MODE', true);
define ('WP_ADMIN', true);

if ( !defined('ABSPATH') ) {
	global $wpload_error;
	$wpload_error = 'Could not open the login page because custom WP_PLUGIN_DIR is set.';
	require dirname(__FILE__) . '/wp-load.php';
}
global $Ktai_Style;
if ( ! isset($Ktai_Style) || ! $Ktai_Style->is_ktai() || @$_COOKIE[KTAI_COOKIE_PCVIEW] ) {
	wp_redirect(get_bloginfo('wpurl') . '/wp-login.php');
	exit();
} elseif ( ! class_exists('KtaiStyle_Admin') ) {
	wp_die(__('Mobile admin feature is not available.', 'ktai_style'));
}
nocache_headers();

/* ==================================================
 *   KtaiStyle_Login class
   ================================================== */

class KtaiStyle_Login {
	private $base;
	private $mime_type;
	private $charset;
	private $iana_charset;
	private $warp_end;
	private $style_input;
	private $errors;

// ==================================================
public function __construct($base) {
	$this->base = $base;
	$this->base->admin = new KtaiStyle_Admin;
	$this->errors = array(
		'oldsession' => __('<strong>ERROR</strong>: Your login session has been expired. Please login again.', 'ktai_style')
	);
	switch ($_REQUEST['action']) {
	case 'logout':
		$this->logout();
		break;
	default:
		$this->login();
		break;
	}
}

// ==================================================
private function logout() {
	if (function_exists('wp_logout_url')) {
		check_admin_referer('log-out');
	}
	$this->base->admin->logout();
	$redirect_to = KtaiStyle::LOGIN_PAGE . '?loggedout=true';
	if (isset($_POST['redirect_to']) || isset($_GET['redirect_to'])) {
		$redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : $_GET['redirect_to'];
		$redirect_to = $this->base->admin->shrink_redirect_to($redirect_to);
	}
	$this->base->admin->safe_redirect($redirect_to);
}

// ==================================================
private function login_header($title = 'Log in', $message = '', $wp_error = '') {
	setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
	if ( SITECOOKIEPATH != COOKIEPATH ) {
		setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);
	}
	$body_color = 'bgcolor="#fbfbfb" text="#333333" link="#777777" vlink="#777777"';
	$error_color = 'color="red"';
	$message_color = 'color="#ff5566"';
	$logo_file = $this->base->strip_host($this->base->get('plugin_url')) . KtaiStyle::INCLUDES_DIR . '/wplogo';
	$this->mime_type    = 'text/html';
	$this->charset      = $this->base->get('charset');
	$this->iana_charset = $this->base->get('iana_charset');
	$this->base->ktai->set('mime_type', $mime_type); // don't use 'application/xhtml+xml'
	switch ($this->base->is_ktai()) {
	case 'DoCoMo':
		$logo_ext = '.gif';
		$wrap_start = '';
		$this->wrap_end = '';
		$this->style_input = '';
		break;
	case 'Unknown':
		$logo_ext = '.png';
		$style_body = ' style="text-align:center;"';
		$wrap_start = '<div style="width:19em;margin:0 auto;text-align:left;">';
		$this->wrap_end = '</div>';
		$this->style_input = 'style="width:100%" ';
		break;
	default:
		$logo_ext = '.png';
		$wrap_start = '';
		$this->wrap_end = '';
		$this->style_input = '';
		break;
	}
	ob_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">
<html><head>
<meta http-equiv="Content-Type" content="<?php echo esc_attr($this->mime_type); ?>; charset=<?php echo esc_attr($this->iana_charset); ?>" />
<?php if ($this->base->is_ktai('type') == 'TouchPhone') { ?>
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<?php } ?>
<meta name="robots" content="noindex,nofollow" />
<title><?php bloginfo('name'); ?> &rsaquo; <?php echo $title; ?></title>
</head><body <?php echo $body_color . $style_body; ?>>
<?php
	echo $wrap_start;
	$logo_html = sprintf('<center><h1><img src="%s%s" alt="WordPress"/></h1></center>', $logo_file, $logo_ext);
	$logo_html = apply_filters('ktai_login_logo', $logo_html, $logo_file, $logo_ext);
	$logo_html = apply_filters('ks_login_logo/ktai_style.php', $logo_html, $logo_file, $logo_ext);
	echo $logo_html;
?><br />
	<?php
	$message = apply_filters('login_message', $message);
	if ( !empty( $message ) ) {
		echo $message . "€n";
	}
	if ($wp_error && $wp_error->get_error_code()) {
		$errors = '';
		$messages = '';
		foreach ($wp_error->get_error_codes() as $code) {
			$severity = $wp_error->get_error_data($code);
			foreach ($wp_error->get_error_messages($code) as $error) {
				if ( 'message' == $severity ) {
					$messages .= '	' . $error . "<br />\n";
				} else {
					$errors .= '	' . $error . "<br />\n";
				}
			}
		}
		if (! empty($errors)) {
			echo "<p><font $error_color>" . apply_filters('login_errors', $errors) . '</font></p>';
		}
		if (! empty($messages)) {
			echo "<p><font $message_color>" . apply_filters('login_messages', $messages) . '</font></p>';
		}
	}
}

// ==================================================
private function login_footer() {
?>
<p><?php echo $this->wrap_end; ?></body></html>
<?php
	$buffer = ob_get_contents();
	ob_end_clean();
	if (function_exists('mb_convert_encoding')) {
		$buffer = mb_convert_encoding($buffer, $this->charset, get_bloginfo('charset'));
	}
	$buffer = $this->base->ktai->convert_pict($buffer);
	$buffer = $this->base->ktai->shrink_pre_split($buffer);
	$buffer = $this->base->ktai->shrink_post_split($buffer);
	header ("Content-Type: {$this->mime_type}; charset={$this->iana_charset}");
	echo $buffer;
}

// ==================================================
private function login() {
	if ( isset($_REQUEST['redirect_to']) ) {
		$redirect_to = $_REQUEST['redirect_to'];
		$redirect_to = $this->base->admin->shrink_redirect_to($redirect_to);
	} else {
		$redirect_to = '';
	}
	$user = $this->base->admin->signon();
	$recirect_to = apply_filters('login_redirect', $redirect_to, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user);

	if ( !is_wp_error($user) ) { // success
		if (! $user->has_cap('edit_posts') && (empty($redirect_to) || $redirect_to == KtaiStyle::ADMIN_DIR . '/')) {
			$redirect_to = KtaiStyle::ADMIN_DIR . '/profile.php';
		} elseif ( empty($redirect_to) ) {
			$redirect_to = KtaiStyle::ADMIN_DIR . '/';
		}
		if ( !$this->base->get('cookie_available') ) {
			$redirect_to = add_query_arg(KtaiStyle_Admin::SESSION_NAME, $this->base->admin->get('sid'), $redirect_to);
		}
		$this->base->admin->safe_redirect($redirect_to);
		exit();
	}
	
	$errors = $user;
	if ( !empty($_GET['loggedout']) ) { // Clear error
		$errors = new WP_Error('loggedout', __('You are now logged out.', 'ktai_style'), 'message');
	} elseif ( isset($_POST['testcookie']) && empty($_COOKIE[TEST_COOKIE]) ) {
		$errors->add('test_cookie', __("<strong>ERROR</strong>: Cookies are blocked or not supported by your browser. You must <a href='http://www.google.com/cookies.html'>enable cookies</a> to use WordPress."));
	}
	if (preg_match('!^' . KtaiStyle::ADMIN_DIR . '/$!', $redirect_to)) {
		$redirect_to = '';
	}
	if ($_GET['error'] && isset($this->errors[$_GET['error']])) {
		$errors->add('session', $this->errors[$_GET['error']]);
	}
	$this->login_header(__('Log in'), '', $errors);
?>
<form method="post" action="./<?php echo esc_attr(basename(__FILE__)); ?>"><div>
<?php _e('Username') ?><br /><input type="text" name="log" id="user_login" class="input" size="20" tabindex="10" istyle="3" mode="alphabet" value="<?php echo esc_attr(stripslashes($user_login)); ?>" <?php echo $this->style_input; ?>/><br />
<?php _e('Password') ?><br /><input type="password" name="pwd" id="user_pass" class="input" size="20" tabindex="20" istyle="3" mode="alphabet" value="" <?php echo $this->style_input; ?>/></div>
<?php // do_action('login_form'); 
if ($this->base->get('cookie_available')) { ?>
<div><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> <?php _e('Remember Me'); ?></label></div>
<?php } ?>
<p><input type="submit" name="wp-submit" value="<?php _e('Log in'); ?>" />
<?php if ( !empty($redirect_to) ) { ?>
<input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirect_to, ENT_QUOTES); ?>" />
<?php }
if ($this->base->get('cookie_available')) { ?>
<input type="hidden" name="testcookie" value="1" />
<?php } ?>
</p></form>
<p><img localsrc="64" alt="<?php _e('&larr;', 'ktai_style'); ?>" /><a href="<?php bloginfo('url'); ?>/"><?php printf(__('Back to %s', 'ktai_style'), get_bloginfo('title', 'display')); ?></a>
<?php 
	if ($this->base->get('pcview_enabled')) { 
		echo '<br /><img localsrc="64" alt="' . __('&larr;', 'ktai_style') . '" /><a href="' . get_bloginfo('wpurl') . '/wp-login.php?pcview=true">' . __('Go to PC login form.', 'ktai_style') . '</a>';
	}
	$this->login_footer();
}

// ===== End of class ====================
}

global $Ktai_Login;
$Ktai_Login = new KtaiStyle_Login($Ktai_Style);
exit();
?>