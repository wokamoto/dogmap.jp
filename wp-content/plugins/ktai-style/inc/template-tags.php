<?php
/* ==================================================
 *   Ktai Template Tags
   ================================================== */

define('KTAI_EXCERPT_LENGTH', 300);

if ( !function_exists('esc_html') ) :
function esc_html( $text ) {
	$safe_text = wp_specialchars( $safe_text, ENT_QUOTES );
	return apply_filters( 'esc_html', $safe_text, $text );
}
function esc_attr( $text ) {
	return attribute_escape($text);
}
function esc_url( $url, $protocols = null ) {
	return clean_url( $url, $protocols, 'display' );
}
endif;

require dirname(__FILE__) . '/shrinkage.php';

/* ==================================================
 * @param	array   $func_get_args
 * @param	array   $defaults
 * @return	array   $args
 * Based on wp_parse_args() at wp-includes/functions.php of WP 2.7
 */
function _ks_parse_arg($func_get_args, $defaults = '') {
	$r = array();
	$arg = $func_get_args[0];
	if ( is_object($arg) ) {
		$r = get_object_vars($arg);
		if ( is_array($defaults) ) {
			$r = array_merge($defaults, $r);
		}
	} elseif ( is_array($arg) ) {
		if ( is_array($defaults) ) {
			$r = array_merge($defaults, $arg);
		}
	} elseif (is_string($arg) && count($arg) == 1 && preg_match('/^\w+=/', $arg) && strpos($arg, ' ') === false) { // query striing
		wp_parse_str($arg, $r);
		if ( is_array($defaults) ) {
			$r = array_merge($defaults, $r);
		}
	} elseif (is_array($defaults)) {
		$func_get_args = (array) $func_get_args; // force array for null arguments
		$r = array();
		foreach ($defaults as $key => $value) {
			$a = array_shift($func_get_args);
			$r[$key] = is_null($a) ? $value : $a;
		}
	}
	return $r;
}

/* ==================================================
 * @param	string  $key
 * @param	string  $query
 * @return	string  $query
 */
function _ks_quoted_remove_query_arg($key, $query) {
	$query = preg_replace(array('/&amp;/', '/&#038;/'), array('&', '&'), $query);
	$query = remove_query_arg($key, $query);
	$query = preg_replace('/&(?:$|([^#])(?![a-z1-4]{1,8};))/', '&amp;$1', $query);
	return $query;
}

/* ==================================================
 * @param	mix     $post
 * @return	boolean $password_required
 */
function ks_post_password_required($post = NULL) {
	$post = get_post($post);
	if (empty($post->post_password)) {
		return false;
	}
	return (stripslashes($_POST['post_password']) !== $post->post_password);
}

/* ==================================================
 * @param	string  $accesskey
 * @return	string  $output
 */
function ks_accesskey_html($accesskey) {
	if (strlen("$accesskey") == 1 && strpos('0123456789*#', "$accesskey") !== false) {
		$output = ' accesskey="' . $accesskey . '"';
	} else {
		$output = '';
	}
	return $output;
}

/* ==================================================
 * @param	string  $link
 * @param	string  $label
 * @param	string  $post_password
 * @param	string  $before
 * @param	string  $after
 * @return	string  $output
 */
function _ks_internal_link($link, $accesskey, $label, $post_password = NULL, $before = '', $after = '') {
	if ($post_password && ! ks_post_password_required()) {
		$param = '';
		$url =  @parse_url($link);
		$query = $url['query'];
		if ($query) {
			$param = '<input type="hidden" name="urlquery" value="' . htmlspecialchars($query, ENT_QUOTES) . '" />';
		}
		$output  = '<form method="post" action="' . esc_attr($link) . '">' . $param 
		. '<input type="hidden" name="post_password" value="' . htmlspecialchars($post_password, ENT_QUOTES) . '" />' 
		. $before 
		. '<label' . ks_accesskey_html($accesskey) . '><input type="submit" name="submit" value="' . esc_attr($label) . '" /></label>' 
		. $after . '</form>';
	} else {
		$output = $before . '<a href="' . esc_attr($link) . '"' . ks_accesskey_html($accesskey) . '>' . $label . '</a>' . $after;
	}
	return $output;
}

/* ==================================================
 * @param	string  $type
 * @return	boolean $is_home
 */
function ks_is_menu($type = NULL) {
	$is_menu = false;
	$value = get_query_var('menu');
	if (isset($type) && preg_match('/^[_a-z0-9]+$/', $type) && $value) {
		$is_menu = ($type === $value);
	} else {
		$is_menu = !empty($value);
	}
	return $is_menu;
}

/* ==================================================
 * @param	none
 * @return	boolean $is_front
 */
function ks_is_front() {
	global $paged;
	return (is_home() && ! ks_is_menu() && intval($paged) < 2);
}

/* ==================================================
 * @param	none
 * @return	boolean $is_comments_list
 */
function ks_is_comments_list() {
	$view = get_query_var('view');
	if ($view == 'co_list') {
		return true;
	} elseif (get_query_var('cpage') && ! ks_is_comment_post()) {
		return true;
	}
	return false;	
}

/* ==================================================
 * @param	none
 * @return	boolean $is_comment_post
 */
function ks_is_comment_post() {
	$view = get_query_var('view');
	if ($view == 'co_post') {
		return true;
	}
	return false;	
}

/* ==================================================
 * @param	none
 * @return	boolean $is_comments
 */
function ks_is_comments() {
	return (ks_is_comments_list() || ks_is_comment_post());
}


/* ==================================================
 * @param	none
 * @return	boolean $is_comments
 * @since	1.81
 */
function ks_is_redir() {
	global $Ktai_Style;
	return isset($Ktai_Style->redir);
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $url (with tailing slash)
 */
function ks_plugin_url($echo = true) {
	global $Ktai_Style;
	$url = $Ktai_Style->strip_host($Ktai_Style->get('plugin_url'));
	if ($echo) {
		echo $url;
	}
	return $url;
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $url (with tailing slash)
 */
function ks_admin_url($echo = true) {
	global $Ktai_Style;
	$url = isset($Ktai_Style->admin) ? $Ktai_Style->admin->get('admin_url') : '';
	if ($echo) {
		echo $url;
	}
	return $url;
}

/* ==================================================
 * @param	string  $before
 * @param	string  $after
 * @param	boolean $echo
 * @param	string  $accesskey
 * @param	string  $label
 * @return	string  $output
 * @since	2.0.0
 */
function ks_admin_link() {
	$defaults = array(
		'before'    => '',
		'after'     => '',
		'echo'      => true,
		'accesskey' => '',
		'anchor'     => __('Site Admin'),
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	if ( isset($r['label']) ) {
		$r['anchor'] = $r['label'];
	}

	$output = '';
	if ( is_user_logged_in() && $url = ks_admin_url(KTAI_NOT_ECHO) ) {
		$output = $r['before'] . sprintf('<a href="%s"%s>%s</a>', $url, ks_accesskey_html($r['accesskey']), $r['anchor']) . $r['after'];
		if ($r['echo']) {
			echo $output;
		}
	}
	return $output;
}

/* ==================================================
 * @param	string  $before
 * @param	string  $after
 * @param	boolean $echo
 * @param	string  $accesskey
 * @param	string  $label
 * @return	string  $output
 */
function ks_login_link() {
	$defaults = array(
		'before'    => '',
		'after'     => '',
		'echo'      => true,
		'accesskey' => '',
		'anchor'     => __('Log in'),
		'anchor_logout' => __('Log out'),
		'before_logout' => NULL,
		'after_logout' => NULL,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	global $Ktai_Style;
	$output = '';
	if ( isset($Ktai_Style->admin) ) {
		if (is_user_logged_in()) {
			$url = ks_get_logout_url();
			if ( isset($r['label_logout']) ) { // backward compati
				$r['anchor_logout'] = $r['label_logout'];
			}
			$anchor = $r['anchor_logout'];
			$before = isset($r['before_logout']) ? $r['before_logout'] : $r['before'];
			$after = isset($r['after_logout']) ? $r['after_logout'] : $r['after'];
		} else {
			$url = ks_get_login_url();
			if ( isset($r['label']) ) { // backward compati
				$r['anchor'] = $r['label'];
			}
			$anchor = $r['anchor'];
			$before = $r['before'];
			$after = $r['after'];
		}
		$output = $before . sprintf('<a href="%s"%s>%s</a>', $url, ks_accesskey_html($r['accesskey']), $anchor) . $after;
		if ($r['echo']) {
			echo $output;
		}
	}
	return $output;
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $url
 */
function ks_get_login_url($echo = false, $redirect = '') {
	global $Ktai_Style;
	if (strlen($redirect)) {
		$redirect = '?redirect_to=' . urlencode($redirect);
	} else {
		$redirect = '';
	}
	if ( isset($Ktai_Style->admin) ) {
		$url = ks_plugin_url(KTAI_NOT_ECHO) . KtaiStyle::LOGIN_PAGE . $redirect;
	} elseif ( defined('KTAI_KEEP_ADMIN_ACESS') && KTAI_KEEP_ADMIN_ACESS ) {
		$url = wp_login_url($redirect);
	} else {
		$url = '';
	}
	if ($echo && $url) {
		echo esc_attr($url);
	}
	return $url;
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $url
 */
function ks_get_logout_url($echo = false, $redirect = '') {
	global $Ktai_Style;
	if (strlen($redirect)) {
		$redirect = '&redirect_to=' . urlencode($redirect);
	} else {
		$redirect = '';
	}
	if ( isset($Ktai_Style->admin) ) {
		if ( !$Ktai_Style->get('cookie_available') ) {
			$redirect = '&' . KtaiStyle_Admin::SESSION_NAME . '=' . KtaiStyle_Admin::get_sid() . $redirect;
		}
		$url = ks_plugin_url(KTAI_NOT_ECHO) . KtaiStyle::LOGIN_PAGE . '?action=logout' . $redirect;
		$url = wp_nonce_url($url, 'log-out');
	} else {
		$url = wp_logout_url($redirect);
	}
	if ($echo) {
		echo esc_attr($url);
	}
	return $url;
}

/* ==================================================
 * @param	none
 * @return	boolean $user_id
 */
function ks_is_loggedin() {
	$user = wp_get_current_user();
	return $user->ID;
}

/* ==================================================
 * @param	none
 * @return	none
 */
function ks_session_id_form() {
	if (class_exists('KtaiStyle_Admin')) {
		$sid = KtaiStyle_Admin::get_sid();
		if ($sid) {
			echo '<input type="hidden" name="' . KtaiStyle_Admin::SESSION_NAME . '" value="' . htmlspecialchars($sid, ENT_QUOTES) . '" />';
		}
	}
}

/* ==================================================
 * @param	string   $version
 * @param	string   $operator
 * @return	boolean  $result
 */
function ks_check_wp_version($version, $operator = '>=') {
	global $Ktai_Style;
	return $Ktai_Style->check_wp_version($version, $operator);
}

/* ==================================================
 * @param	boolean  $echo
 * @return	none
 */
function ks_fix_encoding_form($echo = true) {
	$output = '<input type="hidden" name="charset_detect" value="' 
	. __('Encoding discriminant strings to avoid charset mis-understanding', 'ktai_style') 
	. '" />';
	if ($echo) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	none
 * @return	string  $charset
 */
function ks_detect_encoding() {
	return KtaiStyle::detect_encoding(isset($_POST['charset_detect']) ? stripslashes($_POST['charset_detect']) : 'auto');
}

/* ==================================================
 * @param	string  $key
 * @param	string  $charset
 * @return	string  $value
 */
function ks_mb_get_form($key, $charset = NULL) {
	if (! isset($_POST[$key])) {
		return NULL;
	}

	global $Ktai_Style;
	$value = $_POST[$key];
	if (function_exists('mb_convert_encoding') && ! $Ktai_Style->get('encoding_converted')) {
		$charset = $charset ? $charset : ks_detect_encoding();
		$value = $Ktai_Style->decode_from_ktai($value, $charset);
	}
	return stripslashes($value);
}

/* ==================================================
 * @param	string  $buffer
 * @param	string  $buffer
 * @return	none
 */
function ks_convert_kana($buffer) {
	$charset = get_bloginfo('charset');
	if (preg_match('/^(utf-8|shift_jis|sjis|sjis-win|euc-jp|eucjp-win)$/i', $charset) && function_exists('mb_convert_kana')) {
		$buffer = mb_convert_kana($buffer, 'knrs', $charset);
	}
	return $buffer;
}

/* ==================================================
 * @param	none
 * @return	boolean is_required_term_id
 */
function ks_is_required_term_id() {
	global $Ktai_Style;
	return (! ks_is_loggedin() && ks_option('ks_require_term_id') && $Ktai_Style->ktai->get('sub_ID_available'));
}

/* ==================================================
 * @param	string  $action
 * @param	string  $method
 * @return	none
 */
function ks_require_term_id_form($action, $method = 'post') {
	global $Ktai_Style;
	$utn = '';
	if (! ks_is_loggedin() && ks_option('ks_require_term_id') && $Ktai_Style->is_ktai() == 'DoCoMo') {
		if ($Ktai_Style->ktai->get('sub_ID')) {
			$action .= ((strpos($action, '?') === false) ? '?' : '&') . 'guid=ON';
		} else {
			$utn = ' utn="utn"';
		}
	}
	if (strcasecmp($method, 'post') !== 0) {
		$method = 'get';
	}
	echo '<form method="' . $method . '" action="' . esc_attr($action) . "\"$utn>";
}

/* ==================================================
 * @param	string  $value
 * @return	none
 */
function ks_inline_error_submit($value = NULL) {
	global $post;
	if ($post->post_password) {
		echo '<input type="hidden" name="post_password" value="' . htmlspecialchars($post->post_password, ENT_QUOTES) . '" />';
	}
	if (empty($value)) {
		$value = __('Say It!');
	}
	echo '<input type="submit" name="inline" value="' . esc_attr($value) . '" />';
}

/* ==================================================
 * @param	none
 * @return	none
 */
function ks_do_comment_form_action() {
	global $id, $post, $Ktai_Style;
	$id = isset($id) ? $id : $post->ID;
	ob_start();
	do_action('comment_form', $id);
	$form = ob_get_contents();
	ob_end_clean();
	echo $Ktai_Style->shrinkage->shrink_content($form);
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $title
 */
function ks_title($echo = true) {
	$title = trim(wp_title('', false)); 
	if (empty($title)) {
		$title = get_bloginfo('name');
	}
	if ($echo) {
		echo $title;
	}
	return $title;
}

/* ==================================================
 * @param	mix     $args
 * @return	string  $title
 */
function ks_page_title() {
	$defaults = array(
		'logo_file' => '',
		'echo' => true,
		'before' => '<h1>',
		'after' => '</h1>',
		'before_logo' => '<h1 align="center">',
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	$before = $r['before'];
	$after = $r['after'];
	if (is_search()) {
	 	$title = sprintf(__('Search results of %s', 'ktai_style'), get_search_query() );
	} elseif (is_category()) {
	 	$title = sprintf(__('Archive for the %s category', 'ktai_style'), single_cat_title('', false));
	} elseif (is_tag()) {
	 	$title = sprintf(__('Posts Tagged as %s', 'ktai_style'), single_tag_title('', false));
	} elseif (is_day()) {
	 	$title = sprintf(__('Archive for %s', 'ktai_style'), get_the_time(__('F jS, Y', 'ktai_style')));
	} elseif (is_month()) {
	 	$title = sprintf(__('Archive for %s', 'ktai_style'), get_the_time(__('F, Y', 'ktai_style')));
	} elseif (is_year()) {
	 	$title = sprintf(__('Archive for year %s', 'ktai_style'), get_the_time(__('Y', 'ktai_style')));
	} elseif (is_author()) {
		global $authordata;
		$authordata = get_userdata(get_query_var('author'));
	 	$title = sprintf(__('Archive written by %s', 'ktai_style'), get_the_author());
	} elseif (is_single()) {
		$title = '';
	} else {
		$before = isset($r['before_logo']) ? $r['before_logo'] : $r['before'];
		$after = isset($r['after_logo']) ? $r['after_logo'] : $r['after'];
		if ($r['logo_file']) {
			$title = sprintf('<img src="%s%s" alt="%s" />', ks_theme_url(KTAI_NOT_ECHO), $r['logo_file'], get_bloginfo('name') );
		} elseif ($r['logo_html']) {
			$title = $r['logo_html'];
		} else {
		 	$title = get_bloginfo('name');
		 	$before = $r['before'];
		 	$after =  $r['after'];
		}
	}
	if ($title) {
		$title = $before . $title . $after;
	}
	$title = apply_filters('ktai_page_title', $title, $before, $after);
	if ($r['echo']) {
		echo $title;
	}
	return $title;
}

/* ==================================================
 * @param	string  $content
 * @param	int     $length
 * @param	int     $start
 * @param	string  $charset
 * @return	string  $content
 */
function ks_cut_html($content, $length, $start = 0, $charset = NULL) {
	if (empty($charset)) {
		$charset = get_bloginfo('charset');
	}
	if (function_exists('mb_strcut')) {
		$fragment = mb_strcut($content, $start, $length, $charset);
	} else {
		$fragment = substr($content, $start, $length);
	}
	if (strlen($content) - $start < $length) {
		return $fragment;
	}
	$fragment = preg_replace('/<[^>]*$/', '', $fragment);
	$fragment = preg_replace('/&#?[a-zA-Z0-9]*?$/', '', $fragment);
	$w_start_tags = $fragment;
	while (preg_match('!(<[^/]>|<[^/][^>]*[^/]>)([^<]*?)$!s', $fragment, $only_start_tag) && (preg_match('/^\s*$/', $only_start_tag[2]) || strlen($only_start_tag[2]) < 32)) {
		$fragment = preg_replace('/' . preg_quote($only_start_tag[0], '/') . '$/', '', $fragment);
	}
	if (preg_match('/^\s*$/', $fragment)) { // keep back if the fragment is empty
		$fragment = $w_start_tags;
	}
	$form_start = strrpos($fragment, '<form ');
	$form_end   = strrpos($fragment, '</form>');
	if ($form_start > 0 && ($form_end === false || $form_end < $form_start)) {
		$fragment = substr($fragment, 0, $form_start); // prevent spliting inside forms
	}
	return apply_filters('ktai_cut_html', $fragment, $content, $length, $start, $charset);
}

/* ==================================================
 * @param	int     $more_link_text
 * @param	int     $stripteaser
 * @param	int     $more_file
 * @param	int     $strip_length
 * @param	boolean $echo
 * @return	none
 * based on the_content() at wp-includes/post-template.php of WP 2.2.3
 */
function ks_content() {
	global $id;
	$defaults = array(
		'more_link_text' => '(more...)',
		'stripteaser' => 0,
		'more_file' => '',
		'strip_length' => 0,
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	$content = ks_get_content($r['more_link_text'], $r['stripteaser'], $r['more_file']);
	$content = apply_filters('the_content', $content);
	if ($r['strip_length'] && strlen($content) > $r['strip_length']) {
		$content = ks_cut_html($content, $r['strip_length'], 0, get_bloginfo('charset'));
		$content .= (empty($r['more_link_text']) ? 
			apply_filters('excerpt_more', '[...]') : 
			sprintf('<span><a href="%1$s#more-%2$d">%3$s</a></span>', get_permalink(), $id, $r['more_link_text'])
		);
		$content = force_balance_tags($content);
	}
	$content = str_replace(']]>', ']]&gt;', $content);
	if ($r['echo']) {
		echo $content;
	}
	return $content;
}
/* ==================================================
 * @param	int     $more_link_text
 * @param	int     $stripteaser
 * @param	int     $more_file
 * @param	int     $strip_length
 * @return	string  $output
 * based on get_the_content() at wp-includes/post-template.php of WP 2.2.3
 */
function ks_get_content($more_link_text = '(more...)', $stripteaser = 0, $more_file = '', $strip_length = 0) {
	global $id, $post, $more, $single, $page, $pages, $numpages;
	global $pagenow;
	$output = '';

	$need_password = ks_check_password();
	if ($need_password) {
		return $need_password;
	}

	if ( $more_file != '' )
		$file = $more_file;
	else
		$file = $pagenow; //$_SERVER['PHP_SELF'];

	if ( $page > count($pages) ) // if the requested page doesn't exist
		$page = count($pages); // give them the highest numbered page that DOES exist

	$content = $pages[$page-1];
	if ( preg_match('/<!--more(.*?)?-->/', $content, $matches) ) {
		$content = explode($matches[0], $content, 2);
		if ( !empty($matches[1]) && !empty($more_link_text) )
			$more_link_text = strip_tags(wp_kses_no_null(trim($matches[1])));
	} else {
		$content = array($content);
	}
	if ( (false !== strpos($post->post_content, '<!--noteaser-->') && ((!$multipage) || ($page==1))) )
		$stripteaser = 1;
	$teaser = $content[0];
	if ( ($more) && ($stripteaser) )
		$teaser = '';
	$output .= $teaser;
	if ( count($content) > 1 ) {
		if ( $more ) {
			if (ks_applied_appl_xhtml()) {
				$output .= '<span name="more-'.$id.'"></span>'.$content[1];
			} else {
				$output .= '<a name="more-'.$id.'"></a>'.$content[1];
			}
		} else {
			$output = balanceTags($output);
			if ( ! empty($more_link_text) )
				$output .= apply_filters( 'the_content_more_link', sprintf(' <a href="%s#more-%d">%s</a>', get_permalink(), $id, $more_link_text), $more_link_text );
		}
	} elseif ($strip_length && strlen($output) > $strip_length) {
		$output = ks_cut_html($output, $strip_length, 0, get_bloginfo('charset'));
		$output .= (empty($more_link_text) ? 
			apply_filters('excerpt_more', '[...]') : 
			sprintf('<span><a href="%1$s#more-%2$d">%3$s</a></span>', get_permalink(), $id, $more_link_text)
		);
		$output = force_balance_tags($output);
	}

	return $output;
}

/* ==================================================
 * @param	string  $message
 * @param	string  $message
 * @return	string  $form
 */
function ks_excerpt($strip_length = KTAI_EXCERPT_LENGTH, $echo = true) {
	$func = create_function('$len', 'return ' . intval($strip_length) . ';');
	add_filter('excerpt_length', $func, 999);
	$excerpt = trim(preg_replace('/[\r\n]/', '', get_the_excerpt()));
	remove_filter('excerpt_length', $func, 999);
	if ($echo) {
		echo apply_filters('the_excerpt', $excerpt);
	}
	return $excerpt;
}

/* ==================================================
 * @param	string  $message
 * @return	string  $form
 * based on get_the_content and get_the_password_form() at wp-includes/post-template.php of WP 2.2.3
 */
function ks_check_password($message = '') {
	if (empty($message)) {
		$message = __("This post is password protected. To view it please enter your password below:");
	}
	if (! ks_post_password_required()) {
		return NULL;
	} else {
		$form = '<form method="post" action="' . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES) . '"><p>' . $message . '</p><p><input name="post_password" type="password" size="20" />';
		if (ks_is_comments_list()) {
			$form .= '<input type="hidden" name="view" value="co_list" />';
		} elseif (ks_is_comment_post()) {
			$form .= '<input type="hidden" name="view" value="co_post" />';
		}
		$url = parse_url($_SERVER['REQUEST_URI']);
		$query = $url['query'];
		if (empty($query) && isset($_POST['urlquery'])) {
			$query = $_POST['urlquery'];
		}
		if ($query) {
			$form .= '<input type="hidden" name="urlquery" value="' . htmlspecialchars($query, ENT_QUOTES) . '" />';
		}
		$form .= '<input type="submit" name="Submit" value="' . __("Submit") . '" /></p></form>';
		return $form;
	}
}

/* ==================================================
 * @param	int     $timestamp
 * @param	string  $year
 * @param	string  $mon_date
 * @param	string  $today
 * @return	none
 */
function _ks_timestamp($timestamp, $year = NULL, $mon_date = NULL, $today = NULL, $echo = true) {
	$year     = ! is_null($year)     ? $year     : ks_option('ks_year_format');
	$mon_date = ! is_null($mon_date) ? $mon_date : ks_option('ks_month_date_format');
	$today    = ! is_null($today)    ? $today    : ks_option('ks_time_format');
	$now = current_time('timestamp');
	$timestamp = intval($timestamp);
	if (date('Y', $timestamp) != gmdate('Y', $now)) {
		$output = date($year, $timestamp);
	} elseif (date('m-d', $timestamp) != gmdate('m-d', $now)) {
		$output = date($mon_date, $timestamp);
	} else {
		$output = date($today, $timestamp);
	}
	if ($echo) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	string  $year
 * @param	string  $mon_date
 * @param	string  $today
 * @return	none
 */
function ks_time($year = NULL, $mon_date = NULL, $today = NULL) {
	_ks_timestamp(get_post_time(), $year, $mon_date, $today);
	return;
}

function ks_get_time($year = NULL, $mon_date = NULL, $today = NULL) {
	return _ks_timestamp(get_post_time(), $year, $mon_date, $today, KTAI_NOT_ECHO);
}

/* ==================================================
 * @param	string  $year
 * @param	string  $mon_date
 * @param	string  $today
 * @return	none
 */
function ks_mod_time($year = NULL, $mon_date = NULL, $today = NULL) {
	_ks_timestamp(get_the_modified_time('U'), $year, $mon_date, $today);
	return;
}

function ks_get_mod_time($year = NULL, $mon_date = NULL, $today = NULL) {
	return _ks_timestamp(get_the_modified_time('U'), $year, $mon_date, $today, KTAI_NOT_ECHO);
}

/* ==================================================
 * @param	string  $year
 * @param	string  $mon_date
 * @param	string  $today
 * @return	none
 */
function ks_comment_datetime($year = NULL, $mon_date = NULL, $today = NULL) {
	_ks_timestamp(get_comment_time('U'), $year, $mon_date, $today);
	return;
}

function ks_get_comment_datetime($year = NULL, $mon_date = NULL, $today = NULL) {
	return _ks_timestamp(get_comment_time('U'), $year, $mon_date, $today, KTAI_NOT_ECHO);
}

/* ==================================================
 * @param	boolean $echo
 * @param	string  $return
 * based on get_comment_author_link() at comment-template.php of WP 2.5
 */
function ks_comment_author_link($echo = true) {
	global $Ktai_Style;
	$url    = get_comment_author_url();
	$author = get_comment_author();
	if ( empty( $url ) || 'http://' == $url ) {
		$return = $author;
	} else {
		$return = '<a href="' . $url . '" >' . $author . '</a>';
	}
	$return = apply_filters('get_comment_author_link', $return);
	$return = $Ktai_Style->shrinkage->shrink_content($return);
	if ($echo) {
		echo $return;
	}
	return $return;
}

/* ==================================================
 * @param	int     $num
 * @param	boolean $echo
 * @return	string  $output
 */
function ks_pict_number($num, $echo = false) {
	global $Ktai_Style;
	if (! is_numeric($num)) {
		return;
	}
	$output = __('[]', 'ktai_style');
	if ($num >= 0 && $num <= 10) {
		$num = $num % 10;
		if ($num) {
			$output = sprintf('<img localsrc="%1$d" alt="(%2$d)" />', 179 + $num, $num);
		} else {
			$output = '<img localsrc="325" alt="(0)" />';
		}
	}
	if ($echo) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	int     $count
 * @param	int     $max
 * @param	string  $link
 * @param	string  $label
 * @param	string  $format
 * @param	boolean $hide_over_max
 * @param	boolean $echo
 * @return	none
 */
function ks_ordered_link() {
//function ks_ordered_link($count, $max = 10, $link, $label = NULL, $format, $echo) {
	$defaults = array(
		'count'  => 1,
		'max'    => 10,
		'link'   => NULL,
		'anchor'  => NULL,
		'format' => '%link',
		'hide_over_max' => false,
		'echo'   => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	if ( isset($r['label']) ) {
		$r['anchor'] = $r['label'];
	}

	if ($r['max'] <= 0 || $r['max'] > 10) {
		$r['max'] = 10;
	}
	if ($r['count'] > $r['max']) {
		$r['count'] = $r['hide_over_max'] ? '' : 99;
	}
	$output = ks_pict_number($r['count']);
	if ($r['link']) {
		$anchor = '<a href="' . htmlspecialchars($r['link'], ENT_QUOTES) . '"' . ks_accesskey_html($r['count']) . '>';
		if (! is_null($r['anchor'])) {
			$anchor .= $r['anchor'] . '</a>';
		}
	} else {
		$anchor = $r['anchor'];
	}
	$output .= str_replace('%link', $anchor, $r['format']);
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	int     $i
 * @param	string  $accesskey
 * @param	string  $label
 * @param	string  $post_status
 * @param	string  $post_password
 * @return	string  $output
 * based on wp_link_pages() at wp-includes/post-template.php at WP 2.2.3
 */
function _ks_page_link($i, $accesskey, $label, $post_status, $post_password) {
	if ($i == 1) {
		$output = _ks_internal_link(get_permalink(), $accesskey, esc_html($label), $post_password);
	} elseif ('' == get_option('permalink_structure') || 'draft' == $post_status) {
		$output = _ks_internal_link(get_permalink() . '&amp;page=' . $i, $accesskey, esc_html($label), $post_password);
	} else {
		$page = user_trailingslashit($i, 'single_paged');
		$output = _ks_internal_link(trailingslashit(get_permalink()) . $page, $accesskey, esc_html($label), $post_password);
	}
	return $output;
}

/* ==================================================
 * @param	mix     $arg
 * @return	string  $output
 * based on wp_link_pages() at wp-includes/post-template.php at WP 2.2.3
 */
function ks_link_pages() {
	global $post, $id, $page, $numpages, $multipage, $more, $pagenow;

	$defaults = array(
		'before' => '<p>' . __('Pages:'),
		'after' => '</p>',
		'next_or_number' => 'number',
		'nextpagelink' => __('Next page'),
		'previouspagelink' => __('Previous page'),
		'pagelink' => '%',
		'more_file' => '',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	extract($r, EXTR_SKIP);

	if (! $multipage || ks_post_password_required($post)) {
		return;
	}
	if ($more_file != '') {
		$file = $more_file;
	} else {
		$file = $pagenow;
	}

	$output = '';
	if ( 'number' == $next_or_number ) {
		for ( $i = 1; $i < ($numpages+1); $i = $i + 1 ) {
			$j = str_replace('%',"$i",$pagelink);
			$output .= ' ';
			if ( ($i != $page) || ((!$more) && ($page==1)) ) {
				$output .= _ks_page_link($i, $j, $j, $post->post_status, $post->post_password);
			}
		}
	} elseif ($more) {
		$i = $page - 1;
		if ($i > 0) {
			$output .= _ks_page_link($i, '*', $previouspagelink, $post->post_status, $post->post_password);
		}
		$i = $page + 1;
		if ($i <= $numpages) {
			$output .= _ks_page_link($i, '#', $nextpagelink, $post->post_status, $post->post_password);
		}
	}

	if (strlen($output)) {
		$output = $before . $output . $after;
	}

	if ($echo) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	string  $after
 * @return	int     $max_num_pages
 */
function _ks_get_max_num_pages() {
	if (isset($GLOBALS['wp_query']->max_num_pages)) {
		$pages = $GLOBALS['wp_query']->max_num_pages;
	} else {
		global $max_num_pages, $posts_per_page;
		if ($max_num_pages) {
			$pages = $max_num_pages;
		} else { // WordPress 2.0
			global $wpdb, $request;
			if (preg_match('#FROM\s(.*)\sGROUP BY#siU', $request, $matches)) {
				$fromwhere = $matches[1];
			} else {
				$fromwhere = $wpdb->posts;
			}
			$numposts = $wpdb->get_var("SELECT COUNT(DISTINCT ID) FROM $fromwhere");
			$pages = $max_num_pages = ceil($numposts / $posts_per_page);
		}
	}
	return $pages;
}

/* ==================================================
 * @param	string  $before
 * @param	string  $after
 * @return	none
 */
function ks_pagenum() {
	$defaults = array(
		'before' => ' (',
		'after' => ')',
		'echo' => true,
		'paged' => 0,
		'max_pages' => 0,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	if ($r['paged'] < 1) {
		global $paged;
		$r['paged'] = $paged ? $paged : 1;
	}
	if ($r['max_pages'] < 1) {
		$r['max_pages'] = _ks_get_max_num_pages();
	}
	if ($r['max_pages'] > 1) {
		$output = $r['before'] . intval($r['paged']) . '/' . intval($r['max_pages']) . $r['after'];
	} else {
		$output = '';
	}
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	int    $per_page
 * @return	int    $next_num
 */
function ks_get_next_num() {
	global $posts_per_page;
	$per_page = $posts_per_page ? $posts_per_page : get_option('posts_per_page');
	$page = get_query_var('paged') ? get_query_var('paged') : 1;

	if (isset($GLOBALS['wp_query']->max_num_pages)) {
		$pages = $GLOBALS['wp_query']->max_num_pages;
	} else {
		global $max_num_pages;
		if ($max_num_pages) {
			$pages = $max_num_pages;
		} else {
			global $wpdb, $request;
			if (preg_match('#FROM\s(.*)\sGROUP BY#siU', $request, $matches)) {
				$fromwhere = $matches[1];
			} else {
				$fromwhere = $wpdb->posts;
			}
			$numposts = $wpdb->get_var("SELECT COUNT(DISTINCT ID) FROM $fromwhere");
			$pages = $max_num_pages = ceil($numposts / $per_page);
		}
	}

	if ($page == $GLOBALS['wp_query']->max_num_pages -1) {
		$next_num = $GLOBALS['wp_query']->found_posts % $per_page;
	} else {
		$next_num = $per_page;
	}
	return $next_num;
}

/* ==================================================
 * @param	string  $format
 * @param	string  $link
 * @param	boolean $in_same_cat
 * @param	string  $excluded_categories
 * @param	string  $accesskey
 * @return	none
 * based on previous_post_link() at wp-includes/link-template.php of WP 2.2.3
 */
function ks_previous_post_link() {
	$defaults = array(
		'format' => '<img localsrc="7" alt="&laquo; ">*.%link',
		'link' => '%title',
		'in_same_cat' => false,
		'excluded_categories' => '',
		'accesskey' => '*',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	if ( is_attachment() )
		$post = get_post($GLOBALS['post']->post_parent);
	else
		$post = get_previous_post($r['in_same_cat'], $r['excluded_categories']);

	if ( !$post ) {
		return;
	}
	$title = apply_filters('the_title', $post->post_title, $post);
	$date = mysql2date(get_option('date_format'), $post->post_date);
	$string = '<a href="'.get_permalink($post).'"' . ks_accesskey_html($r['accesskey']) . '>';
	$r['link'] = str_replace('%title', $title, $r['link']);
	$r['link'] = str_replace('%date', $date, $r['link']);
	$r['link'] = $string . $r['link'] . '</a>';
	$r['format'] = str_replace('%link', $r['link'], $r['format']);
	$output = apply_filters('previous_post_link', $r['format'], $r['link']);

	if ($r['echo']) {
		echo $output;
	} 
	return $output;
}

/* ==================================================
 * @param	string  $format
 * @param	string  $link
 * @param	boolean $in_same_cat
 * @param	string  $excluded_categories
 * @param	string  $accesskey
 * @param	boolean $echo
 * @return	string  $output
 * based on next_post_link() at wp-includes/link-template.php of WP 2.2.3
 */
function ks_next_post_link() {
	$defaults = array(
		'format' => '#.%link<img localsrc="8" alt=" &raquo;">',
		'link' => '%title',
		'in_same_cat' => false,
		'excluded_categories' => '',
		'accesskey' => '#',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	
	$post = get_next_post($r['in_same_cat'], $r['excluded_categories']);
	if ( !$post ) {
		return;
	}
	$title = apply_filters('the_title', $post->post_title, $post);
	$date = mysql2date(get_option('date_format'), $post->post_date);
	$string = '<a href="'.get_permalink($post).'"' . ks_accesskey_html($r['accesskey']) . '>';
	$r['link'] = str_replace('%title', $title, $r['link']);
	$r['link'] = str_replace('%date', $date, $r['link']);
	$r['link'] = $string . $r['link'] . '</a>';
	$r['format'] = str_replace('%link', $r['link'], $r['format']);
	$output = apply_filters('next_post_link', $r['format'], $r['link']);

	if ($r['echo']) {
		echo $output;
	} 
	return $output;
}

/* ==================================================
 * @param	string  $label
 * @param	string  $accesskey
 * @param	boolean $echo
 * @return	string  $output
 * based on previous_posts_link() at wp-includes/link-template.php of WP 2.2.3
 */
function ks_previous_posts_link() {
	if (is_single()) {
		return;
	}
	$defaults = array(
		'anchor' => '<img localsrc="7" alt="&laquo; ">' . __('*.Prev', 'ktai_style'),
		'accesskey' => '*',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	if ( isset($r['label']) ) {
		$r['anchor'] = $r['label'];
	}

	$paged = intval(get_query_var('paged'));
	$output = '';
	if ($paged > 1) {
		$output = '<a href="' . 
		KtaiStyle::strip_host(clean_url(_ks_quoted_remove_query_arg('kp', get_previous_posts_page_link()))) . 
		'"' . ks_accesskey_html($r['accesskey']) . '>' .
		 preg_replace('/&([^#])(?![a-z]{1,8};)/', '&amp;$1', $r['anchor']) .'</a>';
	}
	if ($r['echo']) {
		echo $output;
	} 
	return $output;
}

/* ==================================================
 * @param	string  $label
 * @param	string  $accesskey
 * @param	int     $max_pages
 * @return	none
 * based on next_posts_link() at wp-includes/link-template.php of WP 2.2.3
 */
function ks_next_posts_link() {
	if (is_single()) {
		return;
	}
	$defaults = array(
		'anchor' => __('#.Next', 'ktai_style') . '<img localsrc="8" alt=" &raquo;">',
		'accesskey' => '#',
		'max_pages' => 0,
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	if ( isset($r['label']) ) {
		$r['anchor'] = $r['label'];
	}

	if (! $r['max_pages']) {
		$r['max_pages'] = _ks_get_max_num_pages();
	}
	$paged = intval(get_query_var('paged'));
	$nextpage = intval($paged) + 1;
	$output = '';
	if (empty($paged) || $nextpage <= $r['max_pages']) {
		$output = '<a href="' . 
		KtaiStyle::strip_host(clean_url(_ks_quoted_remove_query_arg('kp', get_next_posts_page_link($r['max_pages'])))) . 
		'"' . ks_accesskey_html($r['accesskey']) . '>' . 
		preg_replace('/&([^#])(?![a-z]{1,8};)/', '&amp;$1', $r['anchor']) .'</a>';
	}
	if ($r['echo']) {
		echo $output;
	} 
	return $output;
}

/* ==================================================
 * @param	string  $sep
 * @param	string  $before
 * @param	string  $after
 * @param	string  $prev_label
 * @param	string  $next_label
 * @param	string  $prev_key
 * @param	string  $next_key
 * @param	boolean $echo
 * @return	string  $output
 * based on posts_nav_link() at wp-includes/link-template of WP 2.2.3
 */
function ks_posts_nav_link() {
	if (is_single() || is_page()) {
		return;
	}
	$defaults = array(
		'sep' => ' | ',
		'before' => '',
		'after' => '',
		'prev_anchor' => NULL,
		'next_anchor' => NULL,
		'prev_key' => '*',
		'next_key' => '#',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	if ( isset($r['prev_label']) ) {
		$r['prev_anchor'] = $r['prev_label'];
	}
	if ( isset($r['next_label']) ) {
		$r['next_anchor'] = $r['next_label'];
	}

	$max_pages = _ks_get_max_num_pages();
	$paged = intval(get_query_var('paged'));
	if ($paged < 1) {
		$paged = 1;
	}

	//only have sep if there's both prev and next results
	if ($paged < 2 || $paged >= $max_pages) {
		$r['sep'] = '';
	}

	$output = '';
	if ( $max_pages > 1 ) {
		$prev_args = array('echo' => false, 'accesskey' => $r['prev_key']);
		if (isset($r['prev_anchor'])) {
			$prev_args['anchor'] = $r['prev_anchor'];
		}
		$next_args = array('echo' => false, 'accesskey' => $r['next_key']);
		if (isset($r['next_anchor'])) {
			$next_args['anchor'] = $r['next_anchor'];
		}
		$output .= $r['before'];
		$output .= ks_previous_posts_link($prev_args);
		$output .= preg_replace('/&([^#])(?![a-z]{1,8};)/', '&amp;$1', $r['sep']);
		$output .= ks_next_posts_link($next_args);
		$output .= $r['after'];
	}
	if ($r['echo']) {
		echo $output;
	} 
	return $output;
}

/* ==================================================
 * @param	int     $num
 * @param	string  $first
 * @param	string  $last
 * @param	string  $prev_key
 * @param	string  $next_key
 * @param	boolean $echo
 * @return	string  $output
 */
function ks_posts_nav_multi() {
	$defaults = array(
		'num' => 3,
		'first' => __('First', 'ktai_style'),
		'last' => __('Last', 'ktai_style'),
		'prev_key' => '*',
		'next_key' => '#',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	if ($r['num'] < 0 || $r['num'] > 99) { 
		$r['num'] = 3;
	}
	if (is_single() || is_page()) {
		return;
	}
	$max_pages = _ks_get_max_num_pages();
	if ( $max_pages <= 1 ) {
		return;
	}
	$paged = intval(get_query_var('paged'));
	if ($paged < 1) {
		$paged = 1;
	}
	$output = '';
	if ($paged - $r['num'] > 1) {
		$output .= '<a href="' . KtaiStyle::strip_host(clean_url(get_pagenum_link(1))) . '">';
		$output .= preg_replace('/&([^#])(?![a-z]{1,8};)/', '&amp;$1', $r['first']) .'</a>...';
	}
	for ($count = $paged - $r['num'] ; $count <= $paged + $r['num'] ; $count++) {
		if ($count < 1) {
			continue;
		} elseif ($count > $max_pages) {
			break;
		} elseif ($count == $paged -1) {
			$output .= ' <a href="' . KtaiStyle::strip_host(clean_url(get_pagenum_link($count))) . '"' . ks_accesskey_html($r['prev_key']) . '>'. $count .'</a>';
		} elseif ($count == $paged) {
			$output .= " [$count]";
		} elseif ($count == $paged +1) {
			$output .= ' <a href="' . KtaiStyle::strip_host(clean_url(get_pagenum_link($count))) . '"' . ks_accesskey_html($r['next_key']) . '>'. $count .'</a>';
		} else {
			$output .= ' <a href="' . KtaiStyle::strip_host(clean_url(get_pagenum_link($count))) . '">'. $count .'</a>';
		}
	}
	if ($paged + $r['num'] < $max_pages) {
		$output .= '...<a href="' . KtaiStyle::strip_host(clean_url(get_pagenum_link($max_pages))) . '">';
		$output .= preg_replace('/&([^#])(?![a-z]{1,8};)/', '&amp;$1', $r['last']) .'</a>';
	}
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	string  $before
 * @param	string  $after
 * @param	int     $show_all_limit
 * @param	boolean $echo
 * @return	string  $output
 */
function ks_posts_nav_dropdown() {
	if (is_single() || is_page()) {
		return;
	}
	$defaults = array(
		'before' => '',
		'after' => '',
		'min_pages' => 2,
		'show_all_limit' => 13,
		'paged' => 0,
		'max_pages' => 0,
		'id' => 'paged',
		'baseurl' => NULL,
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	if ($r['min_pages'] < 1) {
		$r['min_pages'] = 2;
	}
	if ($r['show_all_limit'] < 1) {
		$r['show_all_limit'] = 13;
	}
	global $Ktai_Style;
	if ($r['paged'] < 1) {
		$r['paged'] = intval(get_query_var('paged'));
		if ($r['paged'] < 1) {
			$r['paged'] = 1;
		}
	}
	if ($r['max_pages'] < 1) {
		$r['max_pages'] = _ks_get_max_num_pages();
	}
	if ($r['max_pages'] < $r['min_pages']) {
		return;
	}
	$url = parse_url($r['baseurl'] ? $r['baseurl'] : get_pagenum_link($r['paged']));
	$query = $url['query'];
	$form_html = '';
	if ($query) {
		parse_str($query, $params);
		unset($params[$r['id']]);
		unset($params['kp']);
		if (isset($params['ks'])) {
			$params['ks'] = $_GET['s'];
		}
		foreach($params as $k => $v) {
			$form_html .= '<input type="hidden" name="' . htmlspecialchars($k, ENT_QUOTES) . '" value="' . htmlspecialchars($v, ENT_QUOTES) . '" />';
		}
	}
	$link = preg_replace('!/page/\d+!', '', $url['path']);
	$output = $r['before'] . '<form method="get" action="' . htmlspecialchars($link, ENT_QUOTES) . '">' . $form_html . '<select name="' . $r['id'] . '">';

	if ($r['max_pages'] <= $r['show_all_limit']) {
		$show = array();
		for ($count = 1; $count <= $r['max_pages'] ; $count++) {
			$show[] = $count;
		}
	} else {
		$show = array_unique(array(1, $r['paged'], $r['max_pages']));
		for ($digit = 1, $has_lower = $has_upper = true ; 
		     $has_lower || $has_upper ; 
		     $digit *= 10) {
			while ($has_lower) {
				if ($r['paged'] - $digit <= 1) {
					$has_lower = false;
					break;
				}
				$show[] = $r['paged'] - $digit;
				if ($r['paged'] - $digit *2 <= 1) {
					$has_lower = false;
					break;
				}
				$show[] = $r['paged'] - $digit *2;
				if ($digit < 10 && !defined('KTAI_ADMIN_MODE')) {
					if ($r['paged'] <= 4) {
						$has_lower = false;
						break;
					}
					$show[] = $r['paged'] - 3;
					if ($r['paged'] <= 5) {
						$has_lower = false;
						break;
					}
					$show[] = $r['paged'] - 4;
				}
				if ($r['paged'] - $digit *5 <= 1) {
					$has_lower = false;
					break;
				}
				$show[] = $r['paged'] - $digit *5;
				break;
			}
			while ($has_upper) {
				if ($r['paged'] + $digit >= $r['max_pages']) {
					$has_upper = false;
					break;
				}
				$show[] = $r['paged'] + $digit;
				if ($r['paged'] + $digit *2 >= $r['max_pages']) {
					$has_upper = false;
					break;
				}
				$show[] = $r['paged'] + $digit *2;
				if ($digit < 10 && !defined('KTAI_ADMIN_MODE')) {
					if ($r['paged'] + 3 >= $r['max_pages']) {
						$has_lower = false;
						break;
					}
					$show[] = $r['paged'] + 3;
					if ($r['paged'] + 4 >= $r['max_pages']) {
						$has_lower = false;
						break;
					}
					$show[] = $r['paged'] + 4;
				}
				if ($r['paged'] + $digit *5 >= $r['max_pages']) {
					$has_upper = false;
					break;
				}
				$show[] = $r['paged'] + $digit *5;
				break;
			}
		}
		sort($show);
	}

	foreach ($show as $s) {
		$output .= '<option value="' . $s . ($s == $r['paged'] ? '" selected="selected' : '') . '">' . $s . '</option>';
	}
	$output .= '</select><input type="submit" value="' . __('Move to page', 'ktai_style') . '" /></form>' . $r['after'];
	if ($r['echo'] && (! $Ktai_Style->ktai || ! ($size = $Ktai_Style->get('page_size')) || $size - 300 >= strlen($output))) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	int     $post_id
 * @return	string  $link
 */
function ks_get_comments_list_link($post_id = 0) {
	$link = get_permalink($post_id);
	$link .= (strpos($link, '?') === false ? '?' : '&' ) . 'view=co_list';
	return $link;
}

/* ==================================================
 * @param	object  $comment
 * @param	array   $args
 * @return	string  $link
*/
function ks_get_comment_link($comment = null, $args = array()) {
	global $comment;
	$link = get_comment_link($comment, $args);
	if (! preg_match('/(comment-page-|cpage=)\d+/', $link)) {
		$param = (strpos($link, '?') === false ? '?' : '&' ) . 'view=co_list';
		$link = preg_replace('/(#comment-\d+)/', $param . '$1', $link);
	}
	return $link;
}

/* ==================================================
 * @param	string  $icon
 * @param	string  $zero
 * @param	string  $one
 * @param	string  $more
 * @param	string  $none
 * @param	string  $sec
 * @param	string  $accesskey
 * @param   boolean $echo
 * @return	string  $output
 * based on comments_popup_link() at wp-includes/comment-template.php of WP 2.2.3
 */
function ks_comments_link() {
//function ks_comments_link($icon = NULL, $zero = NULL, $one = NULL, $more = NULL, $none = NULL, $sec = NULL, $accesskey = NULL) {
	global $id, $post;
	$defaults = array(
		'icon' => '<img localsrc="86" alt="" />',
		'zero' => __('No comments', 'ktai_style'),
		'one'  => __('One comment', 'ktai_style'),
		'more' => __('% comments', 'ktai_style'),
		'none' => '<img localsrc="61" alt="' . __('X ', 'ktai_style') . '" />' . __('Comments off', 'ktai_style'),
		'sec'  => __('View comments (Need password)', 'ktai_style'),
		'accesskey' => NULL,
		'before' => '',
		'after' => '',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	$number = get_comments_number($id);
	$output = '';
	if ( 0 == $number && 'closed' == $post->comment_status && 'closed' == $post->ping_status ) {
		$icon = isset($r['icon_none']) ? $r['icon_none'] : '';
		$output = strlen($r['none']) < 1 ? NULL : ($icon . "{$r['none']}");
	} else {
		$co_addr = ks_get_comments_list_link();
		if (! ks_post_password_required($post)) {
			if ($number == 0) {
				$icon = isset($r['icon_zero']) ? $r['icon_zero'] : $r['icon'];
				$output = strlen($r['zero']) < 1 ? NULL : ($icon . $r['zero']);
			} else {
				if ($number > 1) {
					$co_num = str_replace('%', number_format_i18n($number), $r['more']);
					$icon = isset($r['icon_more']) ? $r['icon_more'] : $r['icon'];
				} else {
					$co_num = $r['one'];
					$icon = isset($r['icon_one']) ? $r['icon_one'] : $r['icon'];
				}
				$output = strlen($co_num) < 1 ? NULL : _ks_internal_link($co_addr, $r['accesskey'], $co_num, $post->post_password,$icon);
			}
			$output = apply_filters('comments_number', $output, $number);
		} else {
			$icon = isset($r['icon_sec']) ? $r['icon_sec'] : $r['icon'];
			$output = strlen($r['sec']) < 1 ? NULL : ($icon . '<a href="' . htmlspecialchars($co_addr, ENT_QUOTES) . '"' . ks_accesskey_html($r['accesskey']) . '>' . $r['sec'] . '</a>');
		}
	}
	if (strlen($output)) {
		$output = $r['before'] . $output . $r['after'];
		if ($r['echo']) {
			echo $output;
		}
	}
	return $output;
}

/* ==================================================
 * @param	int     $id
 * @return	string  $address
 */
function ks_comments_post_url($id = 0) {
	$address = get_permalink($id);
	$address .= (strpos($address, '?') === false ? '?' : '&' ) . 'view=co_post';
	if (isset($_GET['replytocom'])) {
		$address = add_query_arg('replytocom', absint($_GET['replytocom'], $address));
	}
	if (! ks_is_loggedin() && ks_option('ks_require_term_id') && is_ktai() == 'DoCoMo') {
		$address .= '&guid=ON';
	}
	return $address;
}

/* ==================================================
 * @param	string  $label
 * @param	string  $before
 * @param	string  $after
 * @param	string  $icon
 * @param	string  $accesskey 
 * @param   boolean $echo
 * @return	string  $output
 */
function ks_comments_post_link() {
	if (! comments_open()) {
		return;
	}
	$defaults = array(
		'anchor' => __('Post comments', 'ktai_style'),
		'before' => '',
		'after' => '',
		'icon' => '<img localsrc="149" alt="" />',
		'accesskey' => NULL,
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	if ( isset($r['label']) ) {
		$r['anchor'] = $r['label'];
	}
	
	$post_url = ks_comments_post_url();
	global $post;
	$post_pass = ks_post_password_required($post) ? NULL : $post->post_password;
	$output = $r['before'] . _ks_internal_link($post_url, $r['accesskey'], $r['anchor'], $post_pass, $r['icon']) . $r['after'];
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	string  $anchor
 * @param	string  $before
 * @param	string  $after
 * @param	string  $icon
 * @param	string  $accesskey
 * @param	string  $echo
 * @return	string  $html

function ks_edit_comment_link() {
	$defaults = array(
		'anchor' => __('Edit'),
		'before' => '',
		'after' => '',
		'icon' => '<img localsrc="104" alt="" />',
		'accesskey' => NULL,
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	$edit_url = get_edit_comment_link();
	if ( !$edit_url ) {
		return false;
	}
	if ( isset($r['color']) ) {
		$edit_color = $r['color'];
	} else {
		$edit_color = ks_option('ks_edit_color');
	}
	$anchor = empty($edit_color) ? $r['anchor'] : sprintf('<font color="%s">%s</font>', $edit_color, $r['anchor']);
	$post_pass = ks_post_password_required($post) ? NULL : $post->post_password;
	$output = $r['before'] . _ks_internal_link($edit_url, $r['accesskey'], $anchor, $post_pass, $r['icon']) . $r['after'];
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}
 */
/* ==================================================
 * @param	string  $icon
 * @param	string  $label
 * @param	string  $accesskey
 * @return	none
 */
function ks_back_to_post() {
	$defaults = array(
		'icon' => '<img localsrc="64" alt="' . __('&lt;-', 'ktai_style') . '" />',
		'anchor' => __('Back to the post', 'ktai_style'),
		'accesskey' => '*',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	if ( isset($r['label']) ) {
		$r['anchor'] = $r['label'];
	}

	global $post;
	$output = _ks_internal_link(get_permalink(), $r['accesskey'], $r['anchor'], $post->post_password, $r['icon']);
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	array   $comments
 * @param	string  $order
 * @return	array   $sep_comments
 */
function ks_separete_comments($comments, $order = 'asc') {
	$sep_comments = array('comment' => array(), 'pings' => array());
	$retrieve_func = ($order == 'desc') ? 'array_unshift' : 'array_push';
	if ($comments) : 
		foreach ($comments as $c) : 
			if ($c->comment_type && $c->comment_type != 'comment') {
				$retrieve_func($sep_comments['pings'], $c);
			} else {
				$retrieve_func($sep_comments['comment'], $c);
			}
		endforeach;
	endif;
	return $sep_comments;
}

/* ==================================================
 * @param	int     $num
 * @param	string  $type
 * @param	boolean $group_by_post
 * @return	array   $sorted
 */
function ks_get_recent_comments() {
	$defaults = array(
		'num' => 20,
		'type' => '',
		'group_by_post' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	
	global $wpdb, $comment;
	if (! is_numeric($r['num']) || $r['num'] <= 0) {
		$r['num'] = 20;
	} else {
		$r['num'] = intval($r['num']);
	}
	if ($r['type'] == 'comment') {
		$refine = "AND (c.comment_type = '' OR c.comment_type = 'comment')";
	} elseif ($r['type'] == 'trackback+pingback' || $r['type'] == 'pings') {
		$refine = "AND (c.comment_type = 'trackback' OR c.comment_type = 'pingback')";
	} elseif ($r['type'] == 'trackback') {
		$refine = "AND c.comment_type = 'trackback'";
	} elseif ($r['type'] == 'pingback') {
		$refine = "AND c.comment_type = 'pingback'";
	} else {
		$refine = '';
	}
	$comments = $wpdb->get_results( "SELECT * FROM $wpdb->comments AS c, $wpdb->posts AS p WHERE c.comment_approved = '1' $refine AND c.comment_post_ID = p.ID AND (p.post_status = 'publish' OR p.post_status = 'static') ORDER BY comment_date DESC LIMIT {$r['num']}" );
	if (count($comments) <= 0) {
		return NULL;
	}
	if (! $r['group_by_post']) {
		return $comments;
	}
	$grouped = array();
	foreach ($comments as $c) {
		$post_id = $c->comment_post_ID;
		if (! isset($grouped[$post_id])) {
			$grouped[$post_id][] = get_post($post_id);
		}
		$grouped[$post_id][] = $c;
	}
	return $grouped;
}

/* ==================================================
 * @param	string  $separator
 * @return	none
 */
function ks_category() {
	$defaults = array(
		'separator' => ', ',
		'parents' => '',
		'color' => '',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	$a_open = $r['color'] ? ('<font color="' . $r['color'] . '">') : '';
	$a_close = $r['color'] ? '</font>' : '';
	$categories = get_the_category();
	if (empty($categories)) {
		$output = apply_filters('the_category', __('Uncategorized'), $r['separator'], $r['parents']);
	} else {
		$cat_links = array();
		foreach ($categories as $c) {
			$cat_links[] = '<a href="' . get_category_link($c->cat_ID) . '">' . $a_open . esc_attr($c->cat_name) . $a_close . '</a>';
		}
		$output = apply_filters('the_category', implode($r['separator'], $cat_links), $r['separator'], $r['parents']);
	}
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	mix      $args
 * @return	string   $output
 */
function ks_dropdown_categories() {
	global $Ktai_Style;
	$defaults = array(
		'show_count' => 1,
		'show_option_all' => __('All', 'ktai_style'),
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	$output = wp_dropdown_categories(array('echo' => false) + $r); 
	$output = $Ktai_Style->filter_tags($output);
	if ($r['echo'] && (! $Ktai_Style->ktai || ! ($size = $Ktai_Style->get('page_size')) || $size - 300 >= strlen($output))) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	string  $before
 * @param	string  $after
 * @param	string  $separator
 * @param	boolean $echo
 * @return	string  $output
 */
function ks_tags() {
	$defaults = array(
		'before' => '',
		'after' => '',
		'separator' => ', ',
		'color' => '',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	$tags = get_the_tags();
	if (! $tags) {
		return;
	}
	$a_open = $r['color'] ? ('<font color="' . $r['color'] . '">') : '';
	$a_close = $r['color'] ? '</font>' : '';
	$tag_links = array();
	foreach ($tags as $t) {
		$tag_links[] = '<a href="' . get_tag_link($t->term_id) . '">' . $a_open . esc_attr($t->name) . $a_close . '</a>';
	}
	$output = $r['before'] . apply_filters('the_tags', implode($r['separator'], $tag_links)) . $r['after'];
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	mix     $args
 * @return	none
 * baased on wp_tag_cloud() at category-template.php of WP 2.3.1
 */
function ks_tag_cloud($args = '') {
	$defaults = array(
		'number' => 45,
		'format' => 'flat',
		'orderby' => 'name',
		'order' => 'ASC',
		'exclude' => '',
		'include' => '',
		'color' => '',
		'echo' => true,
	);
	$r = wp_parse_args($args, $defaults);

	$tags = get_tags( array_merge($r, array('orderby' => 'count', 'order' => 'DESC')) ); // Always query top tags

	if (empty($tags)) {
		return;
	}
	$return = _ks_generate_tag_cloud($tags, $r);
	if (is_wp_error($return)) {
		return false;
	}
	$output = apply_filters('wp_tag_cloud', $return, $r);
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	array   $tags
 * @param	mix     $args
 * @return	none
 * baased on wp__generate_tag_cloud at category-template.php of WP 2.3.1
 */
function _ks_generate_tag_cloud($tags, $args = '') {
	global $wp_rewrite, $Ktai_Style;
	$defaults = array(
		'smallest' => 1,
		'largest' => 6,
		'unit' => '',
		'number' => 45,
		'format' => 'flat',
		'orderby' => 'name',
		'order' => 'ASC',
		'color' => '',
	);
	$r = wp_parse_args($args, $defaults);
	extract($r);

	if (! $tags) {
		return;
	}
	$counts = $tag_links = array();
	foreach ( (array) $tags as $tag ) {
		$counts[$tag->name] = $tag->count;
		$tag_links[$tag->name] = get_tag_link( $tag->term_id );
		if ( is_wp_error( $tag_links[$tag->name] ) )
			return $tag_links[$tag->name];
		$tag_ids[$tag->name] = $tag->term_id;
	}

	$min_count = min($counts);
	$spread = max($counts) - $min_count;
	if ($spread <= 0) {
		$spread = 1;
	}
	$font_spread = $largest - $smallest;
	if ($font_spread <= 0) {
		$font_spread = 1;
	}
	$font_step = $font_spread / $spread;

	if ('name' == $orderby) {
		uksort($counts, 'strnatcasecmp');
	} else {
		asort($counts);
	}
	if ('DESC' == $order) {
		$counts = array_reverse($counts, true);
	}
	$a = array();

	$color = $r['color'] ? (' color="' . $r['color'] . '"') : '';
	foreach ($counts as $tag => $count) {
		$tag_id = $tag_ids[$tag];
		$tag_link = clean_url($tag_links[$tag]);
		$tag = str_replace(' ', '&nbsp;', esc_attr( $tag ));
		$a[] = '<a href="' . $tag_link . '"><font size="' . ($smallest + (($count - $min_count) * $font_step)) . '"' . $color . '>' . $tag . '</font></a>';
	}

	switch ( $format ) :
	case 'array' :
		$return = &$a;
		break;
	case 'list' :
		$return = '<ul><li>' . implode('</li><li>', $a) . '</li></ul>';
		break;
	default :
		$return = implode(' ', $a);
		break;
	endswitch;

	return $return;
}

/* ==================================================
 * @param	mix     $args
 * @return	string  $output
 */
function ks_get_archives() {
	global $Ktai_Style;
	$defaults = array('echo' => true, 'type' => 'monthly');
	$r = _ks_parse_arg(func_get_args(), $defaults);

	if (isset($r['year']) && $r['year'] > 0) {
		if (! function_exists('ks_months_a_year_menu')) :
		function ks_months_a_year_menu($where, $r) {
			$where .= sprintf(' AND YEAR(post_date) = %d', $r['year']);
			return $where;
		}
		endif;
		add_filter('getarchives_where', 'ks_months_a_year_menu', 10, 2); 
	}
	ob_start();
	wp_get_archives(array('echo' => true) + $r);
	$output = ob_get_contents();
	ob_end_clean();
	$output = $Ktai_Style->filter_tags($output);
	$output = preg_replace('/ ?(\d+) ?/', '\\1' , $output);
	$output = str_replace('&nbsp;', ' ' , $output);
	$output = preg_replace('!href=([\'"])' . preg_quote(get_bloginfo('url'), '!') . '/?!', 'href=$1' . $Ktai_Style->shrinkage->get('url'), $output);
	if ($r['echo']) {
		echo $output;
	}
	if (function_exists('ks_months_a_year_menu')) {
		remove_filter('getarchives_where', 'ks_months_a_year_menu', 10, 2);
	}
	return $output;
}

/* ==================================================
 * @param	mix     $args
 * @return	string  $output
 */
function ks_dropdown_archives() {
	global $Ktai_Style;
	$defaults = array(
		'echo' => true,
		'type' => 'monthly'
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	if (isset($r['year']) && $r['year'] > 0) {
		if (function_exists('ks_months_a_year_menu')) :
		function ks_months_a_year_menu($where, $r) {
			$where .= sprintf(' AND YEAR(post_date) = %d', $r['year']);
			return $where;
		}
		endif;
		add_filter('getarchives_where', 'ks_months_a_year_menu', 10, 2); 
	}
	if ($r['type'] == 'monthly') {
		if (! function_exists('ks_dropdown_month_link')) :
		function ks_dropdown_month_link($link, $year, $month) {
			return sprintf('/?m=%04d%02d', $year, $month);
		}
		endif;
		add_filter('month_link', 'ks_dropdown_month_link', 10, 3); 
	} elseif ($r['type'] == 'yearly') {
		if (! function_exists('ks_dropdown_year_link')) :
		function ks_dropdown_year_link($link, $year) {
			return sprintf('/?m=%04d', $year);
		}
		endif;
		add_filter('year_link', 'ks_dropdown_year_link', 10, 2); 
	} elseif ($r['type'] == 'daily') {
		if (! function_exists('ks_dropdown_day_link')) :
		function ks_dropdown_day_link($link, $year, $month, $day) {
			return sprintf('/?m=%04d%02d%02d', $year, $month, $day);
		}
		endif;
		add_filter('day_link', 'ks_dropdown_day_link', 10, 4); 
	}
	ob_start();
	$args = $r;
	unset($args['year']);
	wp_get_archives(array('echo' => true, 'format' => 'option') + $args);
	$output = ob_get_contents();
	ob_end_clean();
	$output = $Ktai_Style->filter_tags($output);
	$output = preg_replace('!value=([\'"])/\?m=(\d+)\\1!', 'value="$2"', $output);
	$output = preg_replace('/ ?(\d+) ?/', '\\1' , $output);
	$output = preg_replace('! +</!', '</' , $output);
	$output = str_replace('&nbsp;', ' ' , $output);
	$output = preg_replace('!href=([\'"])' . preg_quote(get_bloginfo('url'), '!') . '/?!', 'href=$1' . $Ktai_Style->shrinkage->get('url'), $output);
	$output = '<select name="m">' . $output . '</select>';
	if ($r['echo'] && (! $Ktai_Style->ktai || ! ($size = $Ktai_Style->get('page_size')) || $size - 300 >= strlen($output))) {
		echo $output;
	}
	if (function_exists('ks_months_a_year_menu')) {
		remove_filter('getarchives_where', 'ks_months_a_year_menu', 10, 2); 
	}
	if (function_exists('ks_dropown_month_link')) {
		remove_filter('month_link', 'ks_dropown_month_link', 10, 3); 
	}
	if (function_exists('ks_dropdown_year_link')) {
		remove_filter('year_link', 'ks_dropdown_year_link', 10, 2); 
	}
	if (function_exists('ks_dropdown_day_link')) {
		remove_filter('day_link', 'ks_dropdown_day_link', 10, 4); 
	}
	return $output;
}

/* ==================================================
 * @param	mix     $args
 * @return	none
 */
function ks_list_bookmarks() {
	global $Ktai_Style;
	$defaults = array('echo' => true);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	$output = wp_list_bookmarks(array('echo' => false) + $r); // force wp_list_bookmark to return value
	$output = $Ktai_Style->filter_tags($output);
	$output = $Ktai_Style->shrinkage->convert_links($output);
	$output = preg_replace('/ ?(\d+) ?/', '\\1' , $output);
	$output = str_replace('&nbsp;', ' ' , $output);
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	string  $sep
 * @param	string  $before
 * @param	string  $after
 * @param	mix     $args
 * @return	none
 */
function ks_pages_menu() {
	$defaults = array(
		'sep' => ' | ',
		'before' => '',
		'after' => '',
		'args' => array(),
		'authors' => '',
		'exclude' => '',
		'parent_only' => true,
		'child_of' => false,
		'sort_column' => 'menu_order,post_title', 
		'all_page_anchor' => __('All Pages', 'ktai_style'),
		'color' => '',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);
	if ($r['args']) {
		$r = array_merge($r, _ks_parse_arg(array($r['args'])));
	}
	if ( isset($r['all_page_label']) ) {
		$r['all_page_anchor'] = $r['all_page_label'];
	}
	unset($r['args']);
	$args = $r;
	unset($args['sep'], $args['before'], $args['after'], $args['all_page_label'], $args['echo']);

	$pages = get_pages($args);
	$menu = array();
	if (count($pages) < 1) {
		return;
	}
	$has_children = 0;
	$a_open = $r['color'] ? ('<font color="' . $r['color'] . '">') : '';
	$a_close = $r['color'] ? '</font>' : '';
	foreach ($pages as $p) {
		if ($r['parent_only'] && $p->post_parent) {
			$has_children++;
			continue;
		}
		$menu[] = '<a href="' . KtaiStyle::strip_host(get_page_link($p->ID)) . '"' . $style . '>' . $a_open . esc_attr($p->post_title) . $a_close . '</a>';
	}
	if ($has_children) {
		$menu[] = '<a href="' . ks_blogurl(KTAI_NOT_ECHO) . '?menu=pages">' . $a_open . $r['all_page_anchor'] . $a_close . '</a>';
	}
	$output = $r['before'] . implode($r['sep'], $menu) . $r['after'];
	if ($r['echo']) {
		echo $output;
	}
	return $output;
}

/* ==================================================
 * @param	int     $user_id
 * @return	object  $user
 */
function ks_get_admin_user($user_id = 0) {
	$user_id = abs(intval($user_id));
	if (! $user_id) {
		global $admin_id;
		if (! $admin_id) { // check cache
			global $wpdb;
			$admin_id = $wpdb->get_var("SELECT user_id FROM `$wpdb->usermeta` WHERE meta_key = '{$wpdb->prefix}user_level' AND meta_value = 10 ORDER BY user_id ASC LIMIT 1");
		}
		$user_id = $admin_id;
	}
	return new WP_User($user_id);
}

/* ==================================================
 * @param	string  $before
 * @param	string  $after
 * @return	string  $menu
 */
function ks_switch_pc_view() {
	global $Ktai_Style;
	$defaults = array(
		'before' => ' (',
		'after' => ')',
		'color' => '',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	$here = $_SERVER['REQUEST_URI'];
	if (! $Ktai_Style->ktai->get('pcview_enabled') || is_user_logged_in()) {
		return;
	} elseif (preg_match('/\?menu=/', $here)) {
		$here = preg_replace('/\?menu=.*$/', '', $here);
	}
	$link = $here . (strpos($here, '?') === false ? '?' : '&') . 'pcview=true';
	$style = $r['color'] ? (' style="color:' . $r['color'] . '"') : '';
	$menu = $r['before'] . '<a href="' . esc_attr($link) . '"' . $style . '>' . __('To PC view', 'ktai_style') . '</a>' . $r['after'];
	$menu = apply_filters('ktai_switch_pc_view', $menu, $here, $r['before'], $r['after'], $r['color']);
	$menu = apply_filters('switch_pc_view/ktai_style.php', $menu, $here, $r['before'], $r['after'], $r['color']);
	if ($r['echo']) {
		echo $menu;
	}
	return $menu;
}

/* ==================================================
 * @param	string  $before
 * @param	string  $after
 * @return	string  $menu
 */
function ks_switch_inline_images() {
	global $Ktai_Style;
	$defaults = array(
		'before' => '<hr /><div align="center">',
		'after' => '</div>',
		'color' => '',
		'echo' => true,
	);
	$r = _ks_parse_arg(func_get_args(), $defaults);

	$inline_default = $Ktai_Style->shrinkage->get('image_inline_default');
	$is_inline      = $Ktai_Style->shrinkage->get('image_inline');
	$inline_images  = $Ktai_Style->shrinkage->get('num_image');
	if ( ( !$inline_default && !ks_is_flat_rate() ) || !$inline_images ) {
		return;
	}
	$link = remove_query_arg('img', $_SERVER['REQUEST_URI']);
	if ( $is_inline == $inline_default || isset($_COOKIE[KtaiShrinkage::COOKIE_IMAGE_INLINE]) ) {
		$link = add_query_arg('img', ($is_inline ? 'link' : 'inline'), $link);
	}
	$a_open = '<a id="inline" href="' . esc_attr($link) . '">' . ($r['color'] ? '<font color="' . $r['color'] . '">' : '');
	$a_close = ($r['color'] ? '</font>' : '') . '</a>';
	if ($is_inline) {
		$inline  = __('Show', 'ktai_style');
		$convert = $a_open . __('As Link', 'ktai_style') . $a_close;
	} else {
		$inline  = $a_open . __('Show', 'ktai_style') . $a_close;
		$convert = __('As Link', 'ktai_style');
	}
	$menu = $r['before'] . __('Images:', 'ktai_style') . ' ' . $inline . ' | ' . $convert . $r['after'];
	$menu = apply_filters('ktai_switch_inline_images', $menu, $r['before'], $r['after'], $r['color']);
	$menu = apply_filters('switch_inline_images/ktai_style.php', $menu, $r['before'], $r['after'], $r['color']);
	if ($r['echo']) {
		echo $menu;
	}
	return $menu;
}

/* ==================================================
 * @param	string  $html
 * @param	string  $align
 * @param	int     $margin
 * @return	string  $html
 */
function ks_image_alignment($html, $align = 'alignnone', $margin = 2) {
	if ($margin < 0) {
		$margin = 2;
	}
	switch (strtolower($align)) {
	case 'alignleft':
		if (ks_applied_appl_xhtml()) {
			$style = sprintf('style="float:left;margin-right:%dpx;" align="left"', $margin);
		} else {
			$style = 'align="left"';
		}
		break;
	case 'alignright':
		if (ks_applied_appl_xhtml()) {
			$style = sprintf('style="float:right;margin-left:%dpx;" align="right"', $margin);
		} else {
			$style = 'align="right"';
		}
		break;
	default:
		$style = '';
		$html .= '<br />';
	}
	if ($style) {
		$html = preg_replace('#(<img [^>]*)/>#', '$1 ' . $style . ' />', $html);
	}
	return apply_filters('ktai_image_alignment', $html, $align, $margin, $style);
}

if (class_exists('Walker')) :
/* ==================================================
 *   KS_Walker_Comment class
 *   based on class Walker_Comment at wp-includes/comment-template.php of WP 2.7
   ================================================== */
class KS_Walker_Comment extends Walker {
	public $tree_type = 'comment';
	public $db_fields = array ('parent' => 'comment_parent', 'id' => 'comment_ID');

	public function start_lvl(&$output, $depth, $args) {
		$GLOBALS['comment_depth'] = $depth + 1;

		switch ( $args['style'] ) {
			case 'div':
				break;
			case 'ol':
				echo '<ol>';
				break;
			case 'ul':
				echo '<ul>';
				break;
			case 'dl':
			default:
				echo '<dl>';
				break;
		}
	}

	public function end_lvl(&$output, $depth, $args) {
		$GLOBALS['comment_depth'] = $depth + 1;

		switch ( $args['style'] ) {
			case 'div':
				break;
			case 'ol':
				echo '</ol>';
				break;
			case 'ul':
				echo '</ul>';
				break;
			case 'dl':
			default:
				echo '</dl>';
				break;
		}
	}

	public function start_el(&$output, $comment, $depth, $args) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;

		if ( !empty($args['callback']) ) {
			call_user_func($args['callback'], $comment, $args, $depth);
			return;
		}

		$GLOBALS['comment'] = $comment;
		extract($args, EXTR_SKIP);

		switch ( $args['style'] ) {
		case 'div':
			$tag = 'div';
			break;
		case 'ol':
		case 'ul':
			$tag = 'li';
			break;
		case 'dl':
		default:
			$tag = 'dt';
			break;
		}
?>
<<?php echo $tag ?>><a name="comment-<?php comment_ID(); ?>"><?php 
		if (! ks_option('ks_separate_comments')) {
			?><font size="-1" color="<?php echo ks_option('ks_comment_type_color'); ?>">[<?php 
			comment_type(__('Comment', 'ktai_style'), __('Trackback'), __('Pingback')); ?>]</font><?php 
		}
		?></a> <img localsrc="<?php comment_type(68, 112, 112); ?>" alt="" /><?php ks_comment_author_link();
		?><img localsrc="46" alt=" @ " /><font color="<?php echo ks_option('ks_date_color'); ?>"><?php ks_comment_datetime(); ?></font>
		<?php /* ks_edit_comment_link('color=' . ks_option('ks_edit_color')); */
		edit_comment_link('<font color="' . ks_option('ks_edit_color') . '">' . __('Edit') . '</font>', '<img localsrc="104" alt="" />'); ?><br />
		<?php if ($comment->comment_approved == '0') {
			?><em><font color="red"><?php _e('Your comment is awaiting moderation.') ?></font></em><br />
		<?php }
		comment_text();
	
		comment_reply_link(array_merge(
			array(
				'before' => '<div>', 
				'after' => '</div>', 
				'reply_before' => '', 
				'reply_text' => '<img localsrc="149" alt="" />' . __('Reply'), 
				'login_text' => '<img localsrc="120" alt="" />' . __('Log in to Reply'), 
			), 
			$args, 
			array('depth' => $depth, 'max_depth' => $args['max_depth'])
		));
	}

	public function end_el(&$output, $comment, $depth, $args) {
		if ( !empty($args['end-callback']) ) {
			call_user_func($args['end-callback'], $comment, $args, $depth);
			return;
		}
		switch ( $args['style'] ) {
		case 'div':
			echo '</div>';
			break;
		case 'ol':
		case 'ul':
			echo '</li>';
			break;
		case 'dl':
		default:
			echo '</dt>';
			break;
		}
		if (isset($args['hr_color']) && $depth < 1) {?>
<hr color="<?php echo $args['hr_color']; ?>" />
<?php	}
	}
}
// ===== End of class ====================
endif;
?>