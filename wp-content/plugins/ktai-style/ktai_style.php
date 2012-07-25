<?php
/*
Plugin Name: Ktai Style
Plugin URI: http://wordpress.org/extend/plugins/ktai-style/
Version: 2.0.5
Description: Provides lightweight pages and simple admin interfaces for mobile phones.
Author: IKEDA Yuriko
Author URI: http://en.yuriko.net/
Text Domain: ktai_style
Domain Path: /lang
*/
define ('KTAI_STYLE_VERSION', '2.0.5');

/*  Copyright (c) 2007-2011 IKEDA Yuriko
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( (defined('WP_INSTALLING') && WP_INSTALLING) || (defined('DOING_CRON') && DOING_CRON) ) {
	return;
}

define('KTAI_DO_ECHO', true);
define('KTAI_NOT_ECHO', false);
define('KTAI_NOT_ESCAPE', false);
if (! defined('KTAI_COOKIE_PCVIEW')) :
	define ('KTAI_COOKIE_PCVIEW', 'ktai_pc_view');
endif;

if (! defined('WP_LOAD_CONF')) {
	define('WP_LOAD_CONF', 'wp-load-conf.php');
	define('WP_LOAD_PATH_STRING', 'WP-LOAD-PATH:');
}

/* ==================================================
 *   KtaiStyle class
   ================================================== */

class KtaiStyle {
	private static $wp_vers = NULL;
	private $plugin_dir;
	private $plugin_url;
	private $plugin_basename;
	private $admin_dir;
	public	$textdomain_loaded = false;
	private $encoding = false;
	private $encoding_converted = false;
	public  $ktai;
	public	$theme;
	public	$admin;
	public  $config;
	public	$shrinkage;
	public  $redir;
	const TEXT_DOMAIN = 'ktai_style';
	const DOMAIN_PATH = '/lang';
	const ADMIN_AVAILABLE_WP_OLDEST = '2.8';
	const ADMIN_AVAILABLE_WP_NEWEST = '3.0.99';
	const ADMIN_DIR = 'admin';
	const LOGIN_PAGE = 'login.php';
	const CONFIG_DIR = 'config';
	const INCLUDES_DIR = 'inc';
	const PATCHES_DIR = 'patches';
	const QUOTED_STRING_REGEX = '[^\\\\>]*?(?:\\\\.[^\\\\>]*?)*';
	const DOUBLE_QUOTED_STRING_REGEX = '[^"\\\\>]*?(?:\\\\.[^"\\\\>]*?)*';

/* ==================================================
 * @param	none
 * @return	object  $this
 * @since	0.70
 */
public function __construct() {
	$this->plugin_dir = basename(dirname(__FILE__));
	$this->plugin_url = plugins_url($this->plugin_dir . '/');
	$this->plugin_basename = plugin_basename(__FILE__);
	$this->admin_dir = dirname(__FILE__) . '/' . self::ADMIN_DIR;
	if ( function_exists('mb_internal_encoding') ) {
		$this->encoding = mb_internal_encoding();
	} else {
		$this->encoding = get_bloginfo('charset');
	}
	add_action('plugins_loaded', array($this, 'load_textdomain'));
	add_action('plugins_loaded', array($this, 'determine_pcview'));
	$this->set_allowedtags();
}

/* ==================================================
 * @param	string  $key
 * @return	mixed   $value
 */
public function get($key) {
	switch ($key) {
	case 'wp_vers':
		return NULL;
	case 'plugin_dir':
	case 'plugin_url':
	case 'plugin_basename':
	case 'textdomain_loaded':
	case 'encoding_converted':
	case 'theme':
	case 'theme_root':
	case 'theme_root_uri':
	case 'template_dir':
	case 'template_uri':
		return $this->$key;
	default:
		if (! $this->ktai) {
			return KtaiServices::get($key);
		}
		return $this->ktai->get($key);
	}
}

/* ==================================================
 * @param	string  $name
 * @return	mix     $value
 */
public function get_option($name, $return_default = false) {
	if (! $return_default) {
		$value = get_option($name);
		if (preg_match('/^ks_theme/', $name)) {
			$value = preg_replace('|^wp-content/|', '', $value);
		}
		if (false !== $value) {
			return $value;
		}
	}
	// default values 
	switch ($name) {
	case 'ks_theme':
		return 'default';
	case 'ks_date_color':
		return '#00aa33';
	case 'ks_author_color':
		return '#808080';
	case 'ks_comment_type_color':
		return '#808080';
	case 'ks_external_link_color':
		return '#660099';
	case 'ks_edit_color':
		return 'maroon';
	case 'ks_year_format':
		return 'Y-m-d';
	case 'ks_month_date_format':
		return 'n/j H:i';
	case 'ks_time_format':
		return 'H:i';
	case 'ks_theme_touch':
	case 'ks_theme_mova':
	case 'ks_theme_foma':
	case 'ks_theme_ezweb':
	case 'ks_theme_sb_pdc':
	case 'ks_theme_sb_3g':
	case 'ks_theme_willcom':
	case 'ks_theme_emobile':
	default:
		return NULL;
	}
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function load_textdomain() {
	if (! $this->textdomain_loaded) {
		load_plugin_textdomain(self::TEXT_DOMAIN, false, $this->get('plugin_dir') . self::DOMAIN_PATH);
		$this->textdomain_loaded = true;
	}
}

/* ==================================================
 * @param	none
 * @return	boolean $is_ktai
 */
public function is_ktai() {
	if ($this->ktai && ! isset($_COOKIE[KTAI_COOKIE_PCVIEW])) {
		return $this->ktai->get('operator');
	} 
	return false;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
private function set_allowedtags() {
	global $allowedposttags, $allowedtags;
	if ($allowedposttags) {
		$allowedposttags['img']['localsrc'] = array();
		$allowedposttags['img']['alt'] = array();
	}
	if ($allowedtags) {
		$allowedtags['img']['localsrc'] = array();
		$allowedtags['img']['alt'] = array();
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function determine_pcview() {
	if ($this->get('pcview_enabled') && isset($_GET['pcview'])) {
		setcookie(KTAI_COOKIE_PCVIEW, ($_GET['pcview'] == 'true'), 0, COOKIEPATH, COOKIE_DOMAIN);
		if ( COOKIEPATH != SITECOOKIEPATH ) {
			setcookie(KTAI_COOKIE_PCVIEW, ($_GET['pcview'] == 'true'), 0, SITECOOKIEPATH, COOKIE_DOMAIN);
		}
		$location = remove_query_arg('pcview', $_SERVER['REQUEST_URI']);
		wp_redirect($location);
		exit;
	}
}

/* ==================================================
 * @param	string   $version
 * @param	string   $operator
 * @return	boolean  $result
 */
public function check_wp_version($version, $operator = '>=') {
	if (! isset(self::$wp_vers)) {
		self::$wp_vers = get_bloginfo('version');
	}
	return version_compare(self::$wp_vers, $version, $operator);
}

/* ==================================================
 * @return	boolean  $result
 * @since	2.0.4
 */
public function admin_available_wp() {
	return $this->check_wp_version(self::ADMIN_AVAILABLE_WP_OLDEST);
}

/* ==================================================
 * @return	boolean  $result
 * @since	2.0.5
 */
public function admin_available_wp_upper() {
	return ( self::ADMIN_AVAILABLE_WP_NEWEST < 1 
	|| $this->check_wp_version(self::ADMIN_AVAILABLE_WP_NEWEST, '<=') );
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function init_mobile() {
	require dirname(__FILE__) . '/' . self::INCLUDES_DIR . '/theme.php';
	$this->theme = new KtaiThemes();
	add_action('sanitize_comment_cookies', array($this, 'convert_encodings'));
	add_filter('query_vars', array($this, 'query_vars'));
	add_action('setup_theme', array($this->theme, 'load_theme_function'));
	add_action('comments_template', array($this->theme, 'comments_template'));
	add_action('template_redirect', array($this, 'output'), 11);
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'locale_stylesheet');
	remove_action('wp_head', 'wp_print_scripts');
	remove_action('wp_head', 'wp_generator');
	if (file_exists($this->admin_dir)) {
		if ( $this->admin_available_wp() && $this->ktai->get('flat_rate') ) {
			require $this->admin_dir . '/pluggable-override.php'; // must be loaded before pluggable.php
			require $this->admin_dir . '/class.php';
			if ( !defined('KTAI_ADMIN_MODE') ) {
				add_action('plugins_loaded', array($this, 'check_ktai_login'));
			}
		} else {
			/* don't load admin feature */
		}
	} elseif ( !defined('KTAI_KEEP_ADMIN_ACESS') || !KTAI_KEEP_ADMIN_ACESS ) {
		// kill access to WP's admin feature
		function auth_redirect() {
			exit();
		}
		add_action('plugins_loaded', array($this, 'shutout_login'));
	}
}

/* ==================================================
 * @param	boolean $exit
 * @return	none
 */
public function check_ktai_login($exit = false) {
	if ( class_exists('KtaiStyle_Admin') && $this->ktai->get('flat_rate') ) {
		$this->admin = new KtaiStyle_Admin;
		$user_login = $this->admin->check_session();
		$this->admin->renew_session();
	}
	$login_url = parse_url(site_url('/wp-login', 'login'));
	if (preg_match('!^' . preg_quote($login_url['path'], '!') . '($|\?|\.php)!', $_SERVER['REQUEST_URI'])) {
		if ( $exit ) {
			wp_die(__('Mobile admin feature is not available.', 'ktai_style'));
			exit;
		}
		wp_redirect($this->get('plugin_url') . self::LOGIN_PAGE);
		exit();
	}
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function shutout_login() {
	$this->check_ktai_login(true);
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	1.80
 */
public function convert_encodings() {
	if (isset($_GET['ks']) && ! empty($_GET['ks'])) {
		$encoding = $this->input_encoding();
		if ( !$encoding ) {
			$encoding = isset($_GET['Submit']) ? $this->detect_encoding($_GET['Submit']) : $this->ktai->get('charset');
		}
		$_GET['s'] = $this->decode_from_ktai($_GET['ks'], $encoding, false);
	} else {
		$_GET['s'] = NULL;
	}
	if (isset($_POST['urlquery']) && isset($_POST['post_password'])) {
		parse_str(stripslashes_deep($_POST['urlquery']), $query);
		foreach($query as $k => $v) {
			$_GET[$k] = $v;
		}
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['charset_detect']) && function_exists('mb_convert_encoding')) {
		$_POST = $this->convert_post_encodings($_POST, $this->detect_encoding(stripslashes($_POST['charset_detect'])));
		$this->encoding_converted = true;
	}
}

/* ==================================================
 * @param	array    $post
 * @param	string   $encoding
 * @return	array    $post
 * @since	1.80
 */
private function convert_post_encodings($post, $encoding) {
	if ( empty($post) ) {
		return $post;
	}
	foreach ($post as $k => $v) {
		if ( empty($v) ) {
			$post[$k] = $v;
		} elseif ( is_array($v) ) {
			$post[$k] = $this->convert_post_encodings($v, $encoding);
		} else {
			$post[$k] = $this->decode_from_ktai($v, $encoding);
		}
	}
	return $post;
}

/* ==================================================
 * @param	string  $input
 * @return	string  $encoding
 */
public function input_encoding() {
	if ( !ini_get('mbstring.encoding_translation') && function_exists('mb_http_input') ) {
		$encoding = mb_http_input('G');
	} else {
		$encoding = NULL;
	}
	return $encoding;
}

/* ==================================================
 * @param	string  $input
 * @return	string  $encoding
 */
public function detect_encoding($input) {
	if ( empty($input) || !function_exists('mb_detect_encoding') ) {
		$encoding = 'auto';
	} else {
		$encoding = mb_detect_encoding($input, array('ASCII', 'JIS', 'UTF-8', 'SJIS', 'EUC-JP'));
		if ( !$encoding || $encoding == 'ASCII' ) {
			$encoding = 'auto';
		}
	}
	return $encoding;
}

/* ==================================================
 * @param	string  $encoding1
 * @param	string  $encoding2
 * @return	boolean $is_same
 */
public function similar_encoding($encoding1, $encoding2) {
	$normalize = array(
		'shift_jis'     => 'sjis',
		'sjis-win'      => 'sjis',
		'cp932'         => 'sjis',
		'eucjp-win'     => 'euc-jp',
		'iso-2022-jp'   => 'jis',
		'iso-2022-jp-1' => 'jis',
		'iso-2022-jp-2' => 'jis',
	);
	$encoding1 = strtr(strtolower($encoding1), $normalize);
	$encoding2 = strtr(strtolower($encoding2), $normalize);
	return (strcmp($encoding1, $encoding2) === 0);
}

/* ==================================================
 * @param	array    $ctype
 * @return	string   $encoding
 */
public function check_encoding($buffer, $encoding) {
	if ( !function_exists('mb_check_encoding') ) {
		return true;
	}
	if ($encoding == 'auto') {
		$encoding = mb_detect_encoding($buffer, 'JIS, SJIS, UTF-8, EUC-JP');
	}
	if ($this->similar_encoding($encoding, 'sjis')) {
		$result = mb_check_encoding($buffer, 'Shift_JIS') || mb_check_encoding($buffer, 'SJIS-win');
	} else {
		$result = mb_check_encoding($buffer, $encoding);
	}
	return $result;
}

/* ==================================================
 * @param	string  $buffer
 * @param	string  $encoding
 * @return	string  $buffer
 */
public function decode_from_ktai($buffer, $encoding = 'auto', $allow_pics = NULL) {
	if ( !$this->check_encoding($buffer, $encoding) ) {
		$this->ks_die(sprintf(__('Invalid character found for %s encoding', 'ktai_style'), $encoding));
		// exit;
	}
	if ( is_null($allow_pics) ) {
		$allow_pics = $this->get_option('ks_allow_pictograms');
	}
	$buffer = stripslashes($buffer);
	if ($this->similar_encoding($encoding, $this->ktai->get('charset'))) {
		$buffer = $this->ktai->pickup_pics($buffer);
		if ( !$allow_pics ) {
			$buffer = preg_replace('!<img localsrc="[^"]*" />!', '', $buffer);
		}
	}
	if ( function_exists('mb_convert_encoding') ) {
		$buffer = mb_convert_encoding($buffer, get_bloginfo('charset'), $encoding);
	}
	if ( $buffer ) {
		$buffer = add_magic_quotes($buffer); // avoid returning empty array
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @param	string  $encoding
 * @return	string  $buffer
 */
public function encode_for_ktai($buffer, $encoding = '') {
	if (function_exists('mb_convert_encoding')) {
		if (! $encoding) {
			if (isset($this->ktai)) {
				$encoding = $this->ktai->get('charset');
			} else {
				global $Ktai_Style;
				$encoding = $Ktai_Style->ktai->get('charset');
			}
		}
		if ( function_exists('mb_convert_encoding') ) {
			$buffer = mb_convert_encoding($buffer, $encoding, get_bloginfo('charset'));
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	array   $vars
 * @return	array   $vars
 * @since	2.0.0
 */
public function query_vars($vars) {
	$vars[] = 'menu';
	$vars[] = 'view';
	$vars[] = 'img';
	$vars[] = 'kp';
	return $vars;
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function output() {
	if (is_robots() || is_feed() || is_trackback()) {
		return;
	}

	require dirname(__FILE__) . '/' . self::INCLUDES_DIR . '/template-tags.php';
	if (is_404()) {
		$this->theme->bypass_admin_404();
	}
	if (! $template = $this->theme->load_template()) {
		$this->ks_die(__("Can't display pages. Bacause mobile phone templates are collapsed.", 'ktai_style'));
		// exit;
	}

	add_filter('wp_list_categories', array($this, 'filter_tags'), 90);
	add_filter('wp_list_pages', array($this, 'filter_tags'), 90);
	add_filter('ktai_raw_content', array($this->ktai, 'shrink_pre_encode'), 9);
	add_filter('ktai_encoding_converted', array($this->ktai, 'shrink_pre_split'), 5);
	add_filter('ktai_encoding_converted', array($this->ktai, 'replace_smiley'), 7);
	add_filter('ktai_encoding_converted', array($this->ktai, 'convert_pict'), 9);
	add_filter('ktai_split_page', array($this->ktai, 'shrink_post_split'), 15);
	add_action('ktai_wp_head', array($this, 'disallow_index'));
	$buffer = $this->ktai->get('preamble');
	$buffer .= ($buffer ? "\n" : '');
	ob_start();
	include $template;
	$buffer .= ob_get_contents();
	ob_end_clean();
	if ( isset($this->admin) ) {
		$this->admin->store_referer()->save_data();
		$this->admin->unset_prev_session($Ktai_Style->admin->get_sid());
	}
	$buffer = apply_filters('ktai_raw_content', $buffer);
	$buffer = apply_filters('raw_content/ktai_style.php', $buffer); // backward compatiblity
	$buffer = $this->encode_for_ktai($buffer);
	$buffer = apply_filters('ktai_encoding_converted', $buffer);
	$buffer = apply_filters('encoding_converted/ktai_style.php', $buffer); // backward compatiblity
	$buffer = apply_filters('ktai_split_page', $buffer, $this->shrinkage->get_page_num());
	$buffer = apply_filters('split_page/ktai_style.php', $buffer, $this->shrinkage->get_page_num()); // backward compatiblity
	$mime_type    = apply_filters('ktai_mime_type', $this->ktai->get('mime_type'));
	$iana_charset = apply_filters('ktai_iana_charset', $this->ktai->get('iana_charset'));
	if (ks_is_front() || ks_is_menu('comments')) {
		nocache_headers();
	}
	if (function_exists('mb_http_output')) {
		mb_http_output('pass');
	}
	header ("Content-Type: $mime_type; charset=$iana_charset");
	echo $buffer;
	exit;
}

/* ==================================================
 * @param	string  $html
 * @return	string  $html
 */
public function filter_tags($html) {
	if (! class_exists('Ktai_HTML_Filter')) {
		require_once dirname(__FILE__) . '/' . self::INCLUDES_DIR . '/kses.php';
	}
	$html = Ktai_HTML_Filter::kses($html, $this->get('allowedtags'));
	return $html;
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function init_pc() {
	if ( defined('WP_USE_THEMES') && WP_USE_THEMES ) {
		add_action('wp_head', array($this, 'show_mobile_url'));
//		add_action('atom_head', array($this, 'show_mobile_url_atom_head'));
//		add_action('atom_entry', array($this, 'show_mobile_url_atom_entry'));
		add_action('rss2_ns', array($this, 'show_mobile_url_rss2_ns'));
//		add_action('rss2_head', array($this, 'show_mobile_url_rss2_head'));
//		add_action('rss2_item', array($this, 'show_mobile_url_rss2_item'));
		if (isset($_COOKIE[KTAI_COOKIE_PCVIEW])) {
			add_action('wp_head', array($this, 'switch_ktai_view_css'));
			add_action('wp_footer', array($this, 'switch_ktai_view'));
		}
	} elseif (is_admin())  {
		add_filter('tiny_mce_before_init', array($this, 'make_valid_pictogram_tag'));
		require dirname(__FILE__) . '/' . self::INCLUDES_DIR . '/theme.php';
		require dirname(__FILE__) . '/' . self::CONFIG_DIR . '/panel.php';
		$this->config = new KtaiStyle_Config();
		add_action('in_plugin_update_message-' . $this->plugin_basename, array($this, 'add_update_notice'));
		if ( file_exists($this->admin_dir) && $this->admin_available_wp() ) {
			require $this->admin_dir . '/install.php';
			register_activation_hook(__FILE__, array($this, 'check_wp_load'));
			register_activation_hook(__FILE__, array('KtaiStyle_Install', 'install'));
			register_deactivation_hook(__FILE__, array('KtaiStyle_Install', 'uninstall'));
			if (function_exists('get_blog_list')) {
				add_action('activate_sitewide_plugin', array('KtaiStyle_Install', 'install_sitewidely'));
				add_action('deactivate_sitewide_plugin', array('KtaiStyle_Install', 'uninstall_sitewidely'));
			}
		}
	}
	add_filter('the_content', array('KtaiServices', 'convert_pict'));
	add_filter('get_comment_text', array('KtaiServices', 'convert_pict'));
}

/* ==================================================
 * @param	array   $init
 * @return	array   $init
 * @since	1.80
 */
public function make_valid_pictogram_tag($init) {
	if (isset($init['extended_valid_elements']) && preg_match('/\bimg\[/', $init['extended_valid_elements'])) {
		$init['extended_valid_elements'] = preg_replace('/\bimg\[/', 'img[localsrc|', $init['extended_valid_elements']);
	} else {
		$init['extended_valid_elements'] = 'img[localsrc|longdesc|usemap|src|border|alt|title|hspace|vspace|width|height|align|class|style]';
	}
	return $init;
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function check_wp_load() {
	$wp_root = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
	if ( !file_exists($wp_root . 'wp-load.php') && !file_exists($wp_root . 'wp-config.php') && function_exists('plugins_url')) {
		$conf = dirname(__FILE__) . '/' . WP_LOAD_CONF;
		if (file_put_contents($conf, "<?php /*\n" . WP_LOAD_PATH_STRING . ABSPATH . "\n*/ ?>", LOCK_EX)) { // <?php /* syntax highiting fix */
			$stat = stat(dirname(__FILE__));
			chmod($conf, 0000666 & $stat['mode']);
		}
	}
}

/* ==================================================
 * @param   int      $post_id
 * @return	string   $url
 * @since	1.10
 */
static function get_self_url() {
	if (! preg_match('|^(https?://[^/]*)|', get_bloginfo('url'), $host)) {
		$scheme = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://';
		$host[1] = $scheme . $_SERVER['SERVER_NAME'];
	}
	return $host[1] . $_SERVER['REQUEST_URI'];
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.10
 */
public function show_mobile_url() {
	$url = self::get_self_url();
?>
<link rel="alternate" media="handheld" type="text/html" href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_atom_head() {
	$url = preg_replace('!(\?feed=atom|feed/atom/?)$!', '', self::get_self_url());
?>
<link rel="alternate" x:media="handheld" type="text/html" href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_atom_entry() {
	$url = get_permalink();
?>
<link rel="alternate" x:media="handheld" type="text/html" href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_rss2_ns() {
?>
	xmlns:xhtml="http://www.w3.org/1999/xhtml"
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_rss2_head() {
	$url = preg_replace('!(\?feed=rss2|feed/rss2/?)$!', '', self::get_self_url());
?>
<xhtml:link rel="alternate" media="handheld" type="text/html" href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_rss2_item() {
	$url = get_permalink();
?>
<xhtml:link rel="alternate" media="handheld" type="text/html" href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	0.95
 */
public function switch_ktai_view_css() {
	$style = <<< E__O__T
#switch-mobile {color:white; background:gray; text-align:center; clear:both;}
#switch-mobile a, #switch-mobile a:link, #switch-mobile a:visited {color:white;}
E__O__T;
	$style = apply_filters('ktai_switch_mobile_view_css', $style);
	$style = apply_filters('switch_ktai_view_css/ktai_style.php', $style);
	if ($style) {
		echo '<style type="text/css">' . $style . '</style>';
	}
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	0.95
 */
public function switch_ktai_view() {
	$here = $_SERVER['REQUEST_URI'];
	$menu = '<div id="switch-mobile"><a href="' . 
	esc_attr($here . (strpos($here, '?') === false ? '?' : '&') . 'pcview=false') . 
	'">' . __('To Mobile view', 'ktai_style') . '</a></div>';
	$menu = apply_filters('ktai_switch_mobile_view', $menu, $here);
	$menu = apply_filters('switch_ktai_view/ktai_style.php', $menu, $here);
	echo $menu;
}

/* ==================================================
 * @param	string   $action
 * @return	string   $nonce
 */
function create_anon_nonce($action = -1) {
	$i = wp_nonce_tick();
	return substr(wp_hash($i . $action), -12, 10);
}

/* ==================================================
 * @param	string   $nonce
 * @param	string   $action
 * @return	boolean  $verified
 */
function verify_anon_nonce($nonce, $action = -1) {
	$i = wp_nonce_tick();
	// Nonce generated 0-12 hours ago
	if ( substr(wp_hash($i . $action), -12, 10) == $nonce )
		return 1;
	// Nonce generated 12-24 hours ago
	if ( substr(wp_hash(($i - 1) . $action), -12, 10) == $nonce )
		return 2;
	// Invalid nonce
	return false;
}

/* ==================================================
 * @param	string  $url
 * @return	string  $url
 */
public function strip_host($url = '/') {
	$url_parts = parse_url($url);
	$http_host = explode(':', $_SERVER['HTTP_HOST']);
	if (  isset($url_parts['host']) && $url_parts['host'] == $http_host[0]
	&& ( !isset($url_parts['port']) || $url_parts['port'] == $http_host[1] ) ) {
		$url = preg_replace('!^https?://[^/]*/?!', '/', $url);
	}
	return $url;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function disallow_index() {
	if ( ks_is_comment_post() || ks_is_redir() ) {
		echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.5
 */
public function add_update_notice() {
	echo '<br />';
	_e('Mobile themes in <code>ktai-style/themes/*</code> are initialized to the distribution state. If you customize these themes directory, create a <code>wp-content/ktai-themes/</code> directory and move your themes to there.', 'ktai_style');
}

/* ==================================================
 * @param	string  $message
 * @param	string  $title
 * @param	boolean $show_back_link
 * @param	boolean $encoded
 * @return	none
 * based on wp_die() at wp-includes/functions() of WP 2.2.3
 */
public function ks_die($message, $title = '', $show_back_link = true, $encoded = false) {

	if ( is_wp_error( $message ) ) {
		if ( empty($title) ) {
			$error_data = $message->get_error_data();
			if ( is_array($error_data) && isset($error_data['title']) )
				$title = $error_data['title'];
		}
		$errors = $message->get_error_messages();
		switch ( count($errors) ) :
		case 0 :
			$message = '';
			break;
		case 1 :
			$message = '<p>' . $errors[0] . '</p>';
			break;
		default :
			$message = '<ul><li>' . join( '</li><li>', $errors ) . '</li></ul>';
			break;
		endswitch;
	} elseif (is_string($message) && strpos($message, '<p>') === false) {
		$message = '<p>' . $message . '</p>';
	}
	if ($show_back_link && isset($this->admin) && $referer = $this->admin->get_referer()) {
		$message .= sprintf(__('Back to <a href="%s">the previous page</a>.', 'ktai_style'), esc_attr($referer));
	}

	$logo_ext = 'png';
	$header = '';
	switch ($this->is_ktai()) {
	case 'DoCoMo':
		$logo_ext = 'gif';
		break;
	case 'KDDI':
	case 'SoftBank':
		$header = '<style><![CDATA[ p {margin-bottom:1em;} ]]></style>';
		break;
	case 'Touch':
		$header = '<meta name="viewport" content="width=device-width,initial-scale=1.0" />';
	default:
		break;
	}
	if ( !defined('KTAI_ADMIN_HEAD') ) :
		$encoding     = $this->ktai->get('charset');
		$iana_charset = $this->ktai->get('iana_charset');
		$mime_type = 'text/html';
		$this->ktai->set('mime_type', $mime_type);
		if (function_exists('mb_http_output')) {
			mb_http_output('pass');
		}
		header ("Content-Type: $mime_type; charset=$iana_charset");

		if ( !$encoded ) {
			$title   = $this->encode_for_ktai($title, $encoding);
			$message = $this->encode_for_ktai($message, $encoding);
		}
		if (empty($title)) {
			$title = $this->encode_for_ktai(__('WordPress | Error', 'ktai_style'), $encoding);
		}
		echo '<?xml version="1.0" encoding="' . $iana_charset .'" ?>' . "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="<?php echo $mime_type; ?>; charset=<?php echo $iana_charset; ?>" />
<meta name="robots" content="noindex,nofollow" />
<title><?php echo esc_html($title); ?></title>
<?php echo $header; ?>
</head>
<body>
<?php endif; // KTAI_ADMIN_HEAD
$logo_url = $this->strip_host($this->get('plugin_url')) . self::INCLUDES_DIR . '/wplogo.' . $logo_ext ;
$title = '<div><h1 id="logo"><img alt="WordPress" src="' . $logo_url . '" /></h1></div>';
$title = apply_filters('ktai_die_logo', $title, $logo_url, $logo_ext);
$title = apply_filters('ks_die_logo/ktai_style.php', $title, $logo_url, $logo_ext);
echo $title, $message; ?>
</body>
</html>
<?php
	if (defined('KTAI_ADMIN_HEAD')) {
		ob_flush();
	}
	exit();
}

// ===== End of class ====================
}

/* ==================================================
 *   KS_Error class
   ================================================== */

function is_ks_error($thing) {
	return (is_object($thing) && is_a($thing, 'KS_Error'));
}

class KS_Error extends Exception {

public function setCode($code) {
	$this->code = $code;
}

// ===== End of class ====================
}

/* ==================================================
 * @param	string  $attribute
 * @return	string  $is_ktai
 */
function is_ktai($attribute = NULL) {
	global $Ktai_Style;
	switch ($attribute) {
	case 'type':
		return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('type') : false;
	case 'flat_rate':
		return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('flat_rate') : false;
	case 'search_engine':
		return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->is_search_engine() : KtaiStyle::is_search_engine();
	default:
		return isset($Ktai_Style->ktai) ? $Ktai_Style->is_ktai() : false;
	}
}

/* ==================================================
 * @param	string  $name
 * @return	mix     $value
 */
function ks_option($name) {
	return KtaiStyle::get_option($name);
}

// ==================================================
global $Ktai_Style;
$Ktai_Style = new KtaiStyle;
require dirname(__FILE__) . '/operators/base.php';
$Ktai_Style->ktai = KtaiServices::factory();
if (is_ktai()) {
	require dirname(__FILE__) . '/' . KtaiStyle::PATCHES_DIR . '/mobile.php';
	$Ktai_Style->init_mobile();
	do_action('ktai_init_mobile');
	do_action('init_mobile/ktai_style.php');
} else {
	require dirname(__FILE__) . '/' . KtaiStyle::PATCHES_DIR . '/pc.php';
	$Ktai_Style->init_pc();
	do_action('ktai_init_pc');
	do_action('init_pc/ktai_style.php');
}
?>