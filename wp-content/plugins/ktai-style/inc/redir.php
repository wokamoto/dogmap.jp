<?php
/* ==================================================
 *   Redirect to external sites
   ================================================== */

if ( !defined('ABSPATH')) {
	global $wpload_error;
	$wpload_error = 'Could not open the redirect page because custom WP_PLUGIN_DIR is set.';
	require dirname(dirname(__FILE__)) . '/wp-load.php';
}
nocache_headers();

if (! defined('KTAI_ALWAYS_RELAY_PAGE')) {
	define ('KTAI_ALWAYS_RELAY_PAGE', false);
}
// define('KTAI_USE_400', true);

/* ==================================================
 *   KtaiStyle_Redir class
   ================================================== */

class KtaiStyle_Redir {
	private	$base;
	public	$url        = NULL;
	public	$full_url   = NULL;
	public	$mobile_url = NULL;
	public	$same_host  = false;
	const URL_PARAM = 'url';
	const NONCE_PARAM = '_wpnonce';
	const PCONLY_SITE_PARAM = 'pconly';
	const IGNORE_CONTENT_REGEX = '#\b(image|audio|video|model)\b/#i';
	const REMOTE_GET_TIMEOUT = 4.0;

// ==================================================
public function __construct($base) {
	$this->base = $base;
	if (! isset($_GET[self::NONCE_PARAM]) || empty($_GET[self::NONCE_PARAM]) 
	||  ! isset($_GET[self::URL_PARAM]) || empty($_GET[self::URL_PARAM])) {
		self::show_error();
		// exit;
	}
	$this->url = stripslashes($_GET[self::URL_PARAM]);
	$nonce = stripslashes($_GET[self::NONCE_PARAM]);
	if (! $this->base->verify_anon_nonce($nonce, 'redir_' . md5($this->url) . md5($_SERVER['HTTP_USER_AGENT']))) {
		return;
	}
	$this->full_url = $this->url = clean_url($this->url);
	if (preg_match('|^/|', $this->url)) {
		$this->full_url = preg_replace('|^(https?://[^/]*)/.*$|', '$1', get_bloginfo('url')) . $this->url;
	}
	if (isset($_GET[self::PCONLY_SITE_PARAM]) && $_GET[self::PCONLY_SITE_PARAM] == 'true') {
		return;
	}
	$this->mobile_url = $this->discover_mobile($this->url);
	if ($this->mobile_url && $this->compare_host() && !KTAI_ALWAYS_RELAY_PAGE) {
		wp_redirect($this->mobile_url);
		exit;
	}
}

/* ==================================================
 * @param	string   $key
 * @return	boolean  $isset
 * @since	1.81
 */
public function has($key) {
	return isset($this->$key) && !empty($this->$key);
}

/* ==================================================
 * @param	string   $key
 * @return	mix      $value
 * @since	1.81
 */
public function get($key) {
	return isset($this->$key) ? $this->$key : NULL;
}

/* ==================================================
 * @param	none
 * @return	none
 */
static function show_error() {
	header("HTTP/1.0 400 Bad Request");
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<HTML><HEAD>
<TITLE>400 Bad Request</TITLE>
</HEAD><BODY>
<H1>Bad Request</H1>
Your request could not be understood by the server due to malformed syntax.
</BODY></HTML>
<?php
	exit;
}

/* ==================================================
 * @param	string   $url
 * @return	string   $mobile_url
 */
private function discover_mobile($url) {
	$parsed = parse_url($url);
	if ( !isset($parsed['host']) ) {
		return false;
	}
	$response = wp_remote_head($url, array( 'timeout' => self::REMOTE_GET_TIMEOUT, 'httpversion' => '1.0' ) );
	if ( is_wp_error($response) ) {
		return false;
	}
	if ( isset($response['headers']['content-type']) 
	&& preg_match(self::IGNORE_CONTENT_REGEX, implode(' ', (array) $response['headers']['content-type'])) ) {
		return false;
	}
	$response = wp_remote_get($url, array( 'timeout' => self::REMOTE_GET_TIMEOUT, 'httpversion' => '1.0' ) );
	if ( is_wp_error($response) ) {
		return false;
	}
	$contents = $response['body'];
	if ( !preg_match_all('#<link([^>]*?)media=([\'"])handheld\\2([^>]*)/?>#is', $contents, $links, PREG_SET_ORDER) ) {
		return false;
	}
	$mobile_url = false;
	foreach ($links as $l) {
		$attr = $l[1] . $l[3];
		if ( !preg_match('/rel=([\'"])alternate\\1/i', $attr) || !preg_match('/href=([\'"])(.*?)\\1/is', $attr, $href) ) {
			continue;
		}
		if ( !preg_match('!^(https?:/)?/!', $href[2]) ) { // relarive URL
			$href[2] = $url . $href[2];
		}
		$mobile_url = sanitize_url($href[2]); // available after WP 2.3
		if ($mobile_url) {
			break;
		}
	}
	return $mobile_url;
}

// ==================================================
private function compare_host() {
	$pc     = parse_url($this->full_url);
	$mobile = parse_url($this->mobile_url);
	$this->same_host = 
	    ($pc['scheme'] === $mobile['scheme'] 
		&& $pc['host'] === $mobile['host'] 
		&& $pc['port'] === $mobile['port']);
	return $this->same_host;
}

// ==================================================
public function use_template($template) {
	require dirname(__FILE__) . '/template-tags.php';
	add_filter('ktai_raw_content', array($this->base->ktai, 'shrink_pre_encode'), 9);
	add_filter('ktai_encoding_converted', array($this->base->ktai, 'shrink_pre_split'), 5);
	add_filter('ktai_encoding_converted', array($this->base->ktai, 'replace_smiley'), 7);
	add_filter('ktai_encoding_converted', array($this->base->ktai, 'convert_pict'), 9);
	add_filter('ktai_encoding_converted', array($this->base->ktai, 'shrink_post_split'), 15);
	$buffer = $this->base->ktai->get('preamble');
	$buffer .= ($buffer ? "\n" : '');
	ob_start();
	include $template;
	$buffer .= ob_get_contents();
	ob_end_clean();
	$buffer = apply_filters('ktai_raw_content', $buffer);
	$buffer = apply_filters('raw_content/ktai_style.php', $buffer);
	$buffer = $this->base->encode_for_ktai($buffer);
	$buffer = apply_filters('ktai_encoding_converted', $buffer);
	$buffer = apply_filters('encoding_converted/ktai_style.php', $buffer);
	$mime_type    = apply_filters('ktai_mime_type', $this->base->ktai->get('mime_type'));
	$iana_charset = apply_filters('ktai_iana_charset', $this->base->ktai->get('iana_charset'));
	if (defined('KTAI_USE_400') && KTAI_USE_400 && is_null($this->full_url)) {
			header("HTTP/1.0 400 Bad Request");
	}
	if (function_exists('mb_http_output')) {
		mb_http_output('pass');
	}
	header ("Content-Type: $mime_type; charset=$iana_charset");
	echo $buffer;
}

// ==================================================
public function output() {
	$charset = $this->base->ktai->get('charset');
	$title = __('Confirm connecting to external sites', 'ktai_style');
	$html = '<p>';

	if (! $this->mobile_url) {
		$html .=  __('You are about to visit a website for PC:', 'ktai_style') 
		. '<br /><a href="' . esc_url($this->url) . '">' . esc_url($this->full_url) . '</a>';
	} else {
		if ($this->mobile_url === $this->full_url) {
			$html .= __('A mobile view is provided with the same URL at the visiting site:', 'ktai_style');
		} else {
			$html .= '<p>' . __('A mobile site found for the visiting site:', 'ktai_style');
		}
		$html .= '<br /><a href="' . esc_url($this->mobile_url) . '">' . esc_url($this->mobile_url) . '</a>';
		if (! $this->same_host) {
			$html .= '<br /><font color="red">' . __('The host is diffrent from the origial. Make sure the valid mobile site.', 'ktai_style') . '</font>';
		}
		if ($this->mobile_url != $this->full_url) {
			$html .= '</p><p>' . __('The original URL of the site:', 'ktai_style') 
			. '<br /><a href="' . esc_url($this->url) . '">' . esc_url($this->full_url) . '</a>';
		}
	}

	if (is_ktai() == 'KDDI' && is_ktai('type') == 'WAP2.0') {
		$html .= '<br />'. sprintf(__('(<a %s>View the site by PC Site Viewer.</a>)', 'ktai_style'), ' href="device:pcsiteviewer?url=' . esc_url($this->full_url) . '"');
	} elseif (is_ktai() == 'DoCoMo' && is_ktai('type') == 'FOMA') {
		$html .= '<br />'. sprintf(__('(<a %s>View the site by Full Browser.</a>)', 'ktai_style'), 'href="' . esc_url($this->url) . '" ifb="' . esc_url($this->full_url) . '"');
	}
	$html .= "</p>\n<p>" . __("If you are sure, follow above link. If not, go to the previous page with browser's back button.", 'ktai_style') . '</p>';
	$html .='<form action=""><div>' . __('To copy the URL, use below text field:', 'ktai_style') . '<br /><input type="text" name="url" size="80" maxlength="255" value="' . esc_url($this->full_url) . '" /></div></form>';
	$this->base->ks_die($html, $title, false);
}

// ==================================================
public function nonce_error() {
	$charset = $this->base->ktai->get('charset');
	$title = __('Error linking to external sites', 'ktai_style');
	$html = '<p>' . __("A certain time has elapsed since you viewed the page, the link to exteral sites has became invalid.<br />\nGo back the previous page and reload it. After that, retry clicking the link.", 'ktai_style') . '</p>';
	if (defined('KTAI_USE_400') && KTAI_USE_400) {
		header("HTTP/1.0 400 Bad Request");
	}
	$this->base->ks_die(apply_filters('ktai_redir_error', $html), $title, false);
}

// ===== End of class ====================
}

/* ==================================================
 * @param	string  $key
 * @return	boolean $isset
 * @since	1.81
 */
function ks_redir_has($key) {
	global $Ktai_Style;
	return is_object($Ktai_Style->redir) && $Ktai_Style->redir->has($key);
}

/* ==================================================
 * @param	string  $key
 * @return	mix     $value
 * @since	1.81
 */
function ks_redir_get($key) {
	global $Ktai_Style;
	return is_object($Ktai_Style->redir) ? $Ktai_Style->redir->get($key) : NULL;
}

/* ==================================================
 * @param	none
 * @return	boolean $is_same_host
 * @since	1.81
 */
function ks_redir_same_host() {
	global $Ktai_Style;
	return is_object($Ktai_Style->redir) && $Ktai_Style->redir->get('same_host');
}

// ==================================================
global $Ktai_Style;
if ( !isset($Ktai_Style) || !$Ktai_Style->is_ktai() ) {
	KtaiStyle_Redir::show_error();
	// exit;
}
$Ktai_Style->redir = new KtaiStyle_Redir($Ktai_Style);
$template = $Ktai_Style->theme->get('template_dir') . 'redir.php';
if ( false !== strpos($template, '/') && file_exists($template)) {
	$Ktai_Style->redir->use_template($template);
} elseif ($Ktai_Style->redir->has('full_url')) {
	$Ktai_Style->redir->output();
} else {
	$Ktai_Style->redir->nonce_error();
}
exit();
?>