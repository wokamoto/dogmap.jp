<?php
/* ==================================================
 *   KtaiStyle_Admin class
   ================================================== */

define('KTAI_COOKIE', 'wordpress_mobile_logged_in_' . COOKIEHASH);

class KtaiStyle_Admin {
	public    $base;
	protected $sid;
	protected $next_id;
	protected $user_id;
	protected $user_agent;
	protected $term_ID;
	protected $sub_ID;
	protected $data;
	const SESSION_LIFETIME = 3600;
	const SESSION_NAME = 'ksid';

function __construct() {
	global $Ktai_Style;
	$this->base = $Ktai_Style;
	$this->admin_url = $this->base->strip_host($this->base->get('plugin_url') . KtaiStyle::ADMIN_DIR . '/');
	add_filter('get_edit_post_link', array($this, 'fix_edit_page_link'), 10, 3);
	add_filter('get_edit_post_link', array($this, 'fix_edit_post_link'), 10, 3);
	add_filter('get_edit_comment_link', array($this, 'fix_edit_comment_link'));
}

/* ==================================================
 * @param	none
 * @return	string  $user_login
 */
public function get_current_user() {
	return $this->user_login;
}

/* ==================================================
 * @param	string  $key
 * @return	mix     $param
 */
public function get($key) {
	return isset($this->$key) ? $this->$key : NULL;
}

/* ==================================================
 * @param	string  $key;
 * @return	mix     $data
 */
public function get_data($key) {
	return isset($this->data[$key]) ? stripslashes($this->data[$key]) : NULL;
}

/* ==================================================
 * @param	string  $key
 * @param   mix     $value
 * @return	mix     $value
 */
public function set_data($key, $value) {
	return $this->data[$key] = $value;
}

/* ==================================================
 * @param	none
 * @return	mix     $result
 */
public function save_data() {
	global $wpdb;
	if (! $this->sid) {
		return false;
	}
	$result = $wpdb->update($wpdb->prefix . 'ktaisession', array('data' => serialize($this->data)), array('sid' => $this->sid));
	return $result;
}

/* ==================================================
 * @param	none
 * @return	string  $sid
 */
public function get_sid() {
	global $Ktai_Style;
	$sid = NULL;
	if ($Ktai_Style->get('cookie_available')) {
		$sid = isset($_COOKIE[KTAI_COOKIE]) ? $_COOKIE[KTAI_COOKIE] : NULL;
	} elseif (isset($_POST[self::SESSION_NAME])) {
		$sid = $_POST[self::SESSION_NAME];
	} elseif (isset($_GET[self::SESSION_NAME])) {
		$sid = $_GET[self::SESSION_NAME];
	}
	if (! is_string($sid) && ! is_numeric($sid)) {
		$sid = NULL;
	}
	return $sid;
}

/* ==================================================
 * @param	none
 * @return	string  $sid
 */
private function make_sid() {
	$salt = wp_salt();
	$rand = uniqid(mt_rand(), true);
	$sid = base64_encode(hash_hmac('sha1', $rand, $salt, true));
	return str_replace(array('+', '/', '='), array('_', '.', ''), $sid);
}

/* ==================================================
 * @param	string  $sid
 * @param	int     $expires
 * @param	boolean $remember
 * @return	none
 * @since	2.0.0
 */
public function set_cookie($sid, $expires, $remember = false) {
	if ( !$remember ) {
		$expires = 0;
	}
	// Set httponly if the php version is >= 5.2.0
	if ( version_compare(phpversion(), '5.2.0', 'ge') ) {
		setcookie(KTAI_COOKIE, $sid, $expires, COOKIEPATH, COOKIE_DOMAIN, false, true);
		if ( COOKIEPATH != SITECOOKIEPATH ) {
			setcookie(KTAI_COOKIE, $sid, $expires, SITECOOKIEPATH, COOKIE_DOMAIN, false, true);
		}
	} else {
		$cookie_domain = COOKIE_DOMAIN;
		if ( !empty($cookie_domain) ) {
			$cookie_domain .= '; HttpOnly';
		}
		setcookie(KTAI_COOKIE, $sid, $expires, COOKIEPATH, $cookie_domain);
		if ( COOKIEPATH != SITECOOKIEPATH ) {
			setcookie(KTAI_COOKIE, $sid, $expires, SITECOOKIEPATH, $cookie_domain);
		}
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function erase_cookie() {
	setcookie(KTAI_COOKIE, '', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
	setcookie(KTAI_COOKIE, '', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
}

/* ==================================================
 * @param	int     $user_id
 * @param	string  $sid
 * @param	mix     $data
 * @return	string  $sid
 */
public function set_session($user_id, $sid = NULL, $data = NULL, $remember = false) {
	global $wpdb;
	if ($this->base->get('cookie_available')) {
		$lifetime = $remember ? 1209600 : 172800;
		$lifetime = apply_filters('auth_cookie_expiration', $lifetime, $user_id, $remember);
	} else {
		$lifetime = self::SESSION_LIFETIME;
	}
	$sid      = $sid ? $sid : $this->make_sid();
	$expires  = time() + $lifetime;
	$ua_hash  = wp_hash($_SERVER['HTTP_USER_AGENT']);
	$tid_hash = $this->base->get('term_ID') ? wp_hash($this->base->get('term_ID')) : '';
	$sub_hash = $this->base->get('sub_ID') ? wp_hash($this->base->get('sub_ID')) : '';
	$result = $wpdb->insert($wpdb->prefix . 'ktaisession', array(
		'sid'        => $sid,
		'expires'    => date('Y-m-d H:i:s', $expires),
		'user_id'    => $user_id,
		'user_agent' => $ua_hash,
		'term_id'    => $tid_hash,
		'sub_id'     => $sub_hash,
		'data'       => ($data ? serialize($data) : ''),
		));
	if (! $result) {
		return NULL;
	}
	$this->sid        = $sid;
	$this->next_id    = NULL;
	$this->expires    = $expires;
	$this->user_id    = $user_id;
	$this->user_agent = $ua_hash;
	$this->term_ID    = $tid_hash;
	$this->sub_ID     = $sub_hash;
	$this->data       = $data;
	if ( $this->base->get('cookie_available') ) {
		$this->set_cookie($sid, $expires, $remember);
	}
	return $sid;
}

/* ==================================================
 * @param	none
 * @return	string  $new_sid
 */
public function renew_session() {
	if ($this->base->get('cookie_available')) {
		return $this->sid;
	}
	
	global $wpdb;
	$renewtime = self::SESSION_LIFETIME / 2;
	if ($this->next_id) {
		$sid = $this->next_id;
		$sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}ktaisession` WHERE next_id = %s", $sid);
		$result = $wpdb->get_row($sql);
		if ($result) {
			$this->sid        = $sid;
			$this->next_id    = $result->next_id;
			$this->expires    = strtotime($result->expires);
			$this->user_id    = $result->user_id;
			$this->user_agent = $result->user_agent;
			$this->term_ID    = $result->term_id;
			$this->sub_ID     = $result->sub_id;
			$this->data       = unserialize($result->data);
			return $sid;
		}
		$this->next_id = NULL;
	}
	if ( !$this->sid || time() + $renewtime <= $this->expires) {
		return false; // use current session
	}
	$new_sid = $this->make_sid();
	$sql = $wpdb->prepare("UPDATE `{$wpdb->prefix}ktaisession` SET next_id = %s WHERE sid = %s AND next_id IS NULL LIMIT 1", $new_sid, $this->sid);
	$result = $wpdb->query($sql);
	if ($result) {
		return $this->set_session($this->user_id, $new_sid, $this->data);
	}
	return false;
}

/* ==================================================
 * @param	string  $sid
 * @return	boolean $is_succeeded
 */
public function unset_session($sid) {
	global $wpdb;
	if ($sid) {
		if ( $this->base->get('cookie_available') ) {
			$this->erase_cookie();
		}
		$sql = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ktaisession` WHERE sid = %s LIMIT 1", $sid);
		$result = $wpdb->query($sql);
		if ($result) {
			return true;
		}
	}
	return false;
}

/* ==================================================
 * @param	string  $sid
 * @return	boolean $is_succeeded
 */
public function unset_prev_session($sid) {
	global $wpdb;
	if ($sid) {
		$sql = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ktaisession` WHERE next_id = %s  LIMIT 1", $sid);
		$result = $wpdb->query($sql);
		if ($result) {
			return true;
		}
	}
	return false;
}

/* ==================================================
 * @param	none
 * @return	boolean $is_succeeded
 */
public function garbage_sessions() {
	global $wpdb;
	$sql = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ktaisession` WHERE expires < %s", date('Y-m-d H:i:s', time()));
	$result = $wpdb->query($sql);
	if ($result) {
		return true;
	}
	return false;
}

/* ==================================================
 * @param	none
 * @return	string   $user_login
 */
public function check_session() {
	global $wpdb;
	$sid = self::get_sid();
	if (empty($sid)) {
		return false;
	}
	self::garbage_sessions();
	$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}ktaisession` WHERE sid = %s", $sid));

	if ( !$result || strcmp(wp_hash($_SERVER['HTTP_USER_AGENT']), $result->user_agent) != 0 ) {
		return false;
	}

	// restore the session
	if (isset($this)) {
		$this->sid        = $sid;
		$this->next_id    = $result->next_id;
		$this->expires    = strtotime($result->expires);
		$this->user_id    = $result->user_id;
		$this->user_agent = $result->user_agent;
		$this->term_ID    = $result->term_id;
		$this->sub_ID     = $result->sub_id;
		$data = unserialize($result->data);
		$this->data       = ($data ? $data : NULL);
	}
	return $result->user_id;
}

/* ==================================================
 * @param	array   $credential
 * @param	string  $secure_cookie
 * @return	object  $user
 * @since	2.0.0
 * based on wp_signon() of wp-includes/user.php at WP 2.9.1
 */
public function signon( $credentials = '', $secure_cookie = '' ) {
	if ( empty($credentials) ) {
		if ( ! empty($_POST['log']) ) {
			$credentials['user_login'] = $_POST['log'];
		}
		if ( ! empty($_POST['pwd']) ) {
			$credentials['user_password'] = $_POST['pwd'];
		}
		if ( ! empty($_POST['rememberme']) ) {
			$credentials['remember'] = $_POST['rememberme'];
		}
	}
	
	if ( !empty($credentials['remember']) ) {
		$credentials['remember'] = true;
	} else {
		$credentials['remember'] = false;
	}
	
	// TODO do we deprecate the wp_authentication action?
	do_action_ref_array('wp_authenticate', array(&$credentials['user_login'], &$credentials['user_password']));
	
	if ( '' === $secure_cookie ) {
		$secure_cookie = is_ssl() ? true : false;
	}
	global $auth_secure_cookie; // XXX ugly hack to pass this to wp_authenticate_cookie
	$auth_secure_cookie = $secure_cookie;
	
//	add_filter('authenticate', 'wp_authenticate_cookie', 30, 3);
	$user = wp_authenticate($credentials['user_login'], $credentials['user_password']);
	
	if ( is_wp_error($user) ) {
		if ( $user->get_error_codes() == array('empty_username', 'empty_password') ) {
			$user = new WP_Error('', '');
		}
		return $user;
	}
	
	if ( !$this->set_auth_cookie($user->ID, $credentials['remember'], $secure_cookie) ) {
		return new WP_Error('session', __('<strong>ERROR</strong>: Cannot create a login session.', 'ktai_style'));
	}
	do_action('wp_login', $credentials['user_login']);
	return $user;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
private function set_auth_cookie($user_id, $remember = false, $secure = false) {
	$sid = false;
	if ( $user_id ) {
		$sid = $this->set_session($user_id, NULL, NULL, $remember);
	}
	return $sid;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function logout() {
	$sid = $this->get_sid();
	$this->unset_session($sid);
	$this->unset_prev_session($sid);
	do_action('wp_logout');
}

/* ==================================================
 * @param	string   $uri
 * @return	none
 */
public function redirect($uri) {
	if ( !$this->base->ktai->get('cookie_available') && $this->is_internal($uri)) {
		$uri = $this->add_sid($uri);
	}
	if (preg_match('#^' . preg_quote(KtaiStyle::ADMIN_DIR, '#') . '/#', $uri)) {
		$uri = $this->base->get('plugin_url') . $uri;
	}
	wp_redirect($uri);
}

/* ==================================================
 * @param	string   $uri
 * @return	none
 */
public function safe_redirect($uri) {
	if ( !$this->base->ktai->get('cookie_available') && $this->is_internal($uri)) {
		$uri = $this->add_sid($uri);
	}
	if (preg_match('#^' . preg_quote(KtaiStyle::ADMIN_DIR, '#') . '/#', $uri)) {
		$uri = $this->base->get('plugin_url') . $uri;
	}
	wp_safe_redirect($uri);
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function shrink_redirect_to($uri) {
	$plugin_dir_regex = preg_quote(preg_replace('!^https?://[^/]*/!', '', $this->base->get('plugin_url')), '!');
	$uri = preg_replace("!^.*?$plugin_dir_regex!", '', $uri);
	return $uri;
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function auth_redirect() {
	if ( !$this->check_session() ) {
		$uri = remove_query_arg('ksid');
		$uri = preg_replace('!^.*' . preg_quote($this->base->strip_host($this->base->get('plugin_url')), '!') . '!', '', $uri);
		wp_redirect($this->base->get('plugin_url') . KtaiStyle::LOGIN_PAGE . '?error=oldsession&redirect_to=' . urlencode($uri));
		exit();
	}
}

/* ==================================================
 * @param	none
 * @return	object  $this
 */
public function store_referer() {
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$current = $this->clean_sid($_SERVER['REQUEST_URI']);
		$orig_referer = $this->get_data('referer');
		if ($orig_referer) {
			if (strcmp($orig_referer, $current) === 0) {
				return $this; // dont store
			}
			$this->set_data('orig_referer', $orig_referer);
		}
		$this->set_data('referer', $current);
	}
	return $this;
}

/* ==================================================
 * @param	none
 * @return	string  $referer
 */
public function get_referer() {
	$referer = '';
	$current = $_SERVER['REQUEST_URI'];
	if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
		$referer = $_REQUEST['_wp_http_referer'];
	} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$referer = $_SERVER['HTTP_REFERER'];
	} else {
		if (isset($this->data['referer'])) {
			$referer = $this->get_data('referer');
			if (strcmp($referer, $current) == 0 && isset($this->data['orig_referer'])) {
				$referer = $this->get_data('orig_referer');
			}
		}
	}
	if (strcmp($referer, $current) == 0) {
		$referer = false;
	}
	return $referer;
}

/* ==================================================
 * @param	string  $action
 * @return	none
 */
public function nonce_ays($action) {
	global $pagenow;

	if ( $this->get_referer() ) {
		$adminurl = clean_url($this->get_referer());
	} else {
		$adminurl = $this->base->strip_host($this->base->get('plugin_url')) . KtaiStyle::ADMIN_DIR . '/';
	}
	
	$title = $this->base->encode_for_ktai(__('WordPress Confirmation', 'ktai_style'));
	list ($desc, $allow_proceed) = $this->explain_nonce($action);
	if ($allow_proceed) {
		$no  = __('No');
		$yes = __('Yes');
	} else {
		$no  = __('Back', 'ktai_style');
	}
	// Remove extra layer of slashes.
	$_POST = stripslashes_deep($_POST);
	if ($_POST) {
		$q = http_build_query($_POST);
		$q = explode( ini_get('arg_separator.output'), $q);
		$html .= '<form method="post" action="' . clean_url($pagenow) . '"><input type="hidden" name="' . self::SESSION_NAME . '" value="' . esc_attr($this->sid) . '" />';
		foreach ( (array) $q as $a ) {
			$v = substr(strstr($a, '='), 1);
			$k = substr($a, 0, -(strlen($v)+1));
			$html .= '<input type="hidden" name="' . esc_attr(urldecode($k)) . '" value="' . esc_attr(urldecode($v)) . '" />';
		}
		$add_html = '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce($action) . '" /><p>' . esc_html($desc) . '<br /><a href="' . $adminurl . '">' . $no . '</a>' . ($allow_proceed ? ' <input type="submit" value="' . $yes . '" />' : '') . '</div></form>';
	} else {
		$add_html = '<p>' . esc_html($desc) . '<br /><a href="' . $adminurl . '">' . $no. '</a>' . ($allow_proceed ? ' <a href="' . clean_url(add_query_arg('_wpnonce', wp_create_nonce($action), $_SERVER['REQUEST_URI'])) . '">' . $yes . '</a>' : '') . ' </p>';
	}
	$html .= $this->base->encode_for_ktai($add_html);
	$this->base->ks_die($html, $title, false, true);
}

/* ==================================================
 * @param	string  $action
 * @return	string  $desc
 * @return	boolean $allow_proceed
 */
private function explain_nonce($action) {
	global $Ktai_Style;
	remove_filter('the_title', array($Ktai_Style->shrinkage, 'shrink_title'), 90);
	if ( $action === -1 || ! preg_match('/([a-z]+)-([a-z]+)(_(.+))?/', $action, $matches) ) {
		return;
	}
	$verb = $matches[1];
	$noun = $matches[2];
	$trans = array();
	$trans['change']['cats'] = array(__('Are you sure you want to change categories of this post: &quot;%s&quot;?', 'ktai_style'), 'get_the_title');
	$trans['unapprove']['comment'] = array(__('Are you sure you want to unapprove the comment: &quot;%s&quot;?', 'ktai_style'), 'use_id');
	$trans['approve']['comment'] = array(__('Are you sure you want to approve the comment: &quot;%s&quot;?', 'ktai_style'), 'use_id');
	$trans['delete']['comment'] = array(__('Are you sure you want to delete the comment: &quot;%s&quot;?', 'ktai_style'), 'use_id');
	$trans['delete']['post'] = array(__('Are you sure you want to delete this post: &quot;%s&quot;?', 'ktai_style'), 'get_the_title');
	$trans['delete']['page'] = array(__('Are you sure you want to delete this page: &quot;%s&quot;?', 'ktai_style'), 'get_the_title');
	$trans['bulk']['spamdelete'] = array(__('Are you sure you want to delete all spam?', 'ktai_style'));

	if ( isset($trans[$verb][$noun]) ) {
		if (! empty($trans[$verb][$noun][1]) ) {
			$lookup = $trans[$verb][$noun][1];
			$object = $matches[4];
			if ('use_id' != $lookup) {
				$object = call_user_func($lookup, $object);
			}
			$desc = sprintf($trans[$verb][$noun][0], $object);
		} else {
			$desc = $trans[$verb][$noun][0];
		}
		$allow_proceed = true;
	} else {
		$desc = wp_explain_nonce($action);
		$allow_proceed = false;
	}
	return array($desc, $allow_proceed);
}

/* ==================================================
 * @param	string  $uri
 * @return	boolean $is_internal
 */
public function is_internal($uri) {
	return preg_match('#^([-\w]+\.php|\.?/$|/.*' . preg_quote(KtaiStyle::ADMIN_DIR, '#') . '/)#', $this->base->strip_host($uri));
}

/* ==================================================
 * @param	string  $uri
 * @param	int     $id
 * @param	string  $context
 * @return	string  $uri
 */
public function fix_edit_page_link($uri, $id, $context = NULL) {
	$post = get_post($id);
	if ($post && $post->post_type == 'page') {
		$uri = str_replace('post.php', 'page.php', $uri);
	}
	return $uri;
}

/* ==================================================
 * @param	string  $uri
 * @param	int     $id
 * @param	string  $context
 * @return	string  $uri
 */
public function fix_edit_post_link($uri, $id, $context = NULL) {
	if (is_null($context)) {
		$uri = str_replace('&amp;', '&', $uri);
	}
	return preg_replace('!^' . preg_quote(admin_url()) . '!', $this->admin_url, $uri);
}

/* ==================================================
 * @param	string  $uri
 * @return	string  $uri
 */
public function fix_edit_comment_link($uri) {
	return preg_replace('!^' . preg_quote(admin_url()) . '!', $this->admin_url, $uri);
}

/* ==================================================
 * @param	string  $uri
 * @return	string  $uri
 */
public function clean_sid($uri) {
	return remove_query_arg(self::SESSION_NAME, $uri);
}

/* ==================================================
 * @param	string  $uri
 * @param	boolean $display
 * @return	string  $uri
 */
public function add_sid($uri, $display = false) {
	if ($this->sid) {
		switch (true) {
		case (strpos($uri, '?') === false):
			$uri .= '?';
			break;
		case $display:
			$uri .= '&amp;';
			break;
		default:
			$uri .= '&';
			break;
		}
		$uri .= sprintf('%s=%s', 
			self::SESSION_NAME,
			$this->sid);
	}
	return $uri;
}

// ===== End of class ====================
}
?>