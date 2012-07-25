<?php
/* ==================================================
 *   Ktai Themes class
   ================================================== */

if ( !defined('ABSPATH')) {
	exit;
}

class KtaiThemes {
	private $theme;
	private $theme_base;
	private $theme_root;
	private $theme_root_uri;
	private $template_dir;
	private $template_uri;
	private $theme_data;
	public static $target = array('touch', 'mova', 'foma', 'ezweb', 'sb_pdc', 'sb_3g', 'willcom', 'emobile');
	public static $default_menu = array('comments', 'months', 'years', 'cats', 'tags', 'pages', 'links');
	public static $built_in_theme_root; // with trailing slash
	public static $built_in_theme_root_uri; // with trailing slash
	const DEFAULT_THEME = 'default';
	const OPTION_PREFIX = 'ks_theme';
	const BUILT_IN_THEMES_DIR = 'themes';
	const USER_THEMES_DIR ='ktai-themes';
	const SAME_THEME_AS_COMMON = '*';
	const SCREENSHOT_BASENAME = 'screenshot';

public function __construct($dir = false) {
	global $Ktai_Style;
	self::set_variables();
	if ( !$dir ) {
		if ( isset($Ktai_Style->ktai) ) {
			$dir = $Ktai_Style->ktai->get('theme');
			if ( !self::valid_dir_name($dir) ) {
				$dir = $Ktai_Style->get_option('ks_theme');
			}
		} else {
			$dir = $Ktai_Style->get_option('ks_theme');
		}
	}
	$this->get_theme_info($dir);
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public static function set_variables() {
	if ( !isset(self::$built_in_theme_root) ) {
		global $Ktai_Style;
		self::$built_in_theme_root = WP_PLUGIN_DIR . '/' . $Ktai_Style->get('plugin_dir') . '/' . self::BUILT_IN_THEMES_DIR . '/';
		self::$built_in_theme_root_uri = $Ktai_Style->get('plugin_url') . self::BUILT_IN_THEMES_DIR . '/';
	}
}

/* ==================================================
 * @param	string  $key
 * @return	mixed   $value
 * @since	2.0.0
 */
public function get($key) {
	return $this->$key;
}

/* ==================================================
 * @param	string  $dir
 * @return	boolean $valid
 * @since	2.0.0
 */
public static function valid_dir_name($dir) {
	$valid = false;
	if ( !empty($dir) 
	&& $dir !== self::SAME_THEME_AS_COMMON 
	&& ! preg_match('![^-_/a-zA-Z0-9]!', $dir)
	&& ! validate_file($dir) ) {
		$valid = true;
	}
	return $valid;
}

/* ==================================================
 * @param	string  $path
 * @return	boolean $valid
 * @since	2.0.0
 */
public static function valid_theme($path) {
	$valid = false;
	if ( is_dir($path) ) {
		$path = trailingslashit($path);
		if (file_exists($path . 'index.php') 
		&&  file_exists($path . 'style.css')) {
			$valid = true;
		}
	}
	return $valid;
}

/* ==================================================
 * @param	string  $dir
 * @return	boolean $valid
 * @since	2.0.0
 */
public static function complete_theme_dir($dir) {
	if (strpos($dir, '/') !== false) {
		$path = WP_CONTENT_DIR . '/' . $dir;
	} else {
		self::set_variables();
		$path = self::$built_in_theme_root . $dir;
	}
	return $path;
}

/* ==================================================
 * @param	string  $dir
 * @param	string  $target
 * @return	none
 * @since	2.0.0
 */
public static function set_theme($dir, $target = false) {
	if ( empty($target) ) {
		$option = self::OPTION_PREFIX;
	} elseif ( in_array($target, self::$target) ) {
		$option = self::OPTION_PREFIX . '_' . $target;
	} else {
		return;
	}
	if ( empty($dir) ) {
		delete_option($option);
	} elseif ($target == 'touch' && $dir == self::SAME_THEME_AS_COMMON) {
		update_option(self::OPTION_PREFIX . '_touch',  self::SAME_THEME_AS_COMMON);
	} elseif (self::valid_dir_name($dir)) {
		$path = self::complete_theme_dir($dir);
		if (self::valid_theme($path)) {
			update_option($option, $dir);
		}
	}
}

/* ==================================================
 * @param	string  $theme_dir
 * @return	none
 * @since	2.0.0
 */
public function get_theme_info($dir) {
	$this->theme = $dir;
	if (strpos($dir, '/') !== false) {
		$path_item = explode('/', $dir);
		$this->theme_base = array_pop($path_item);
		if (! $this->theme_base) {
			$this->theme_base = array_pop($path_item);
		}
		$this->theme_root = WP_CONTENT_DIR . '/' . implode('/', $path_item) . '/'; // with trailing slash
		$this->theme_root_uri = content_url() . '/' . implode('/', $path_item). '/'; // with trailing slash
	} else {
		$this->theme_base = $dir;
		$this->theme_root = self::$built_in_theme_root;
		$this->theme_root_uri = self::$built_in_theme_root_uri;
	}
	$this->template_dir = $this->theme_root . $this->theme_base . '/'; // with trailing slash
	if ( !self::valid_dir_name($this->theme) 
		|| !self::valid_theme($this->template_dir)) { // fall back to the default theme
		$this->theme = $this->theme_base = self::DEFAULT_THEME;
		$this->theme_root = self::$built_in_theme_root;
		$this->theme_root_uri = self::$built_in_theme_root_uri;
		$this->template_dir = self::$built_in_theme_root . $this->theme_base . '/';
	}
	$this->template_uri = $this->theme_root_uri . $this->theme_base . '/'; // trailing slash
	
	$this->theme_data = get_theme_data($this->template_dir . 'style.css');
	foreach ( array('png', 'jpg', 'gif') as $ext) {
		if (file_exists($this->template_dir . self::SCREENSHOT_BASENAME . '.' . $ext)) {
			$this->theme_data['Screenshot'] = $this->template_uri . self::SCREENSHOT_BASENAME . '.' . $ext;
			break;
		}
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function bypass_admin_404() {
	global $Ktai_Style;
	// redirect to dashboard or login screen if accessed to non-existing URLs
	if (isset($Ktai_Style->admin)) {
		if (preg_match('!^' . ks_plugin_url(KTAI_NOT_ECHO) . KtaiStyle::ADMIN_DIR . '/!',  $_SERVER['REQUEST_URI'])) {
			$sid = $Ktai_Style->admin->get_sid();
			if ($sid) {
				$url = add_query_arg(KtaiStyle_Admin::SESSION_NAME, $sid, ks_admin_url(KTAI_NOT_ECHO));
			} else {
				$url = ks_get_login_url();
			}
			wp_redirect($url);
			exit();
		}
	} elseif (preg_match('!wp-admin/!',  $_SERVER['REQUEST_URI'])) { // cannot use is_admin()
		exit(); // shut out access to non-existing admin screen
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function load_theme_function() {
	if ( file_exists($this->template_dir . 'functions.php') ) {
		include($this->template_dir . 'functions.php');
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function comments_template($file) {
	$comment_template = $this->template_dir . 'comments.php';
	if (file_exists($comment_template)) {
		return $comment_template;
	}
	$default_comment_template = self::$built_in_theme_root . self::DEFAULT_THEME . '/comments.php';
	if (file_exists($default_comment_template)) {
		return $default_comment_template;
	} else {
		return $file; // returns template for current PC theme
	}
}

/* ==================================================
 * @param	none
 * @return	string  $template
 * @since	0.70
 * based on wp-includes/template-loader.php of WP 2.6.5
 */
public function load_template() {
	if ( is_404() && $template = $this->query_template('404')) {
		return $template;
	} elseif ( ks_is_menu() ) {
		if ( $template = $this->menu_template() ) {
			return $template;
		}
		// return NULL;
	} elseif (is_search() && $template = $this->query_template('search')) {
		return $template;
	} elseif (is_tax() && $template = $this->get_taxonomy_template()) {
		return $template;
	} elseif (is_home() && $template = $this->get_home_template()) {
		return $template;
	} elseif (is_attachment() && $template = $this->get_attachment_template()) {
		remove_filter('the_content', 'prepend_attachment');
		return $template;
	} elseif (is_single() && $template = $this->query_template('single')) {
		return $template;
	} elseif (is_page() && $template = $this->get_page_template()) {
		return $template;
	} elseif (is_category() && $template = $this->get_category_template()) {
		return $template;
	} elseif (is_tag() && $template = $this->get_tag_template()) {
		return $template;
	} elseif (is_author() && $template = $this->query_template('author')) {
		return $template;
	} elseif (is_date() && $template = $this->query_template('date')) {
		return $template;
	} elseif (is_archive() && $template = $this->query_template('archive')) {
		return $template;
	} elseif (is_paged() && $template = $this->query_template('paged')) {
		return $template;
	} elseif (file_exists($this->template_dir . 'index.php')) {
		if (is_attachment()) {
			add_filter('the_content', 'prepend_attachment');
		}
		return $this->template_dir . 'index.php';
	}
	return NULL;
}

/* ==================================================
 * @param	array   $template_names
 * @since	2.0.0 
 * based on locate_template() at wp-includes/theme.php of WP 2.9.2
 */
private function locate_template($template_names) {
	if ( !is_array($template_names) || !isset($this->template_dir) ) {
		return '';
	}
	$located = '';
	foreach($template_names as $t) {
		if ( file_exists($this->template_dir . $t)) {
			$located   = $this->template_dir . $t;
			break;
		}
	}

	return $located;
}

/* ==================================================
 * @param	string  $type
 * @return	string  $template
 * @since	0.70
 * based on get_query_template() at wp-includes/theme.php of WP 2.9.2
 */
private function query_template($type) {
	$type = preg_replace( '|[^a-z0-9-]+|', '', $type );
	return apply_filters("{$type}_template", $this->locate_template(array("{$type}.php")));
}

/* ==================================================
 * @param	none
 * @return	string  $template
 * @since	0.97
 * based on get_query_template() at wp-includes/theme.php of WP 2.2.3
 */
private function menu_template() {
	$type = get_query_var('menu');
	if (! preg_match('/^[_a-z0-9]+$/', $type)) {
		return NULL;
	}
	$template = '';
	if (file_exists($this->template_dir . "menu_{$type}.php")) {
		$template = $this->template_dir . "menu_{$type}.php";
	} else {
		if ( !in_array($type, self::$default_menu) ) {
			return NULL;
		} elseif (file_exists(self::$built_in_theme_root . self::DEFAULT_THEME . "/menu_{$type}.php")) {
			$template =       self::$built_in_theme_root . self::DEFAULT_THEME . "/menu_{$type}.php";
		}
	}
	return apply_filters("menu_{$type}_template", $template);
}

/* ==================================================
 * @param	none
 * @return	string  $template
 * @since	2.0.0 
 * based on get_taxonomy_template() at wp-includes/theme.php of WP 2.9.2
 */
private function get_taxonomy_template() {
	$taxonomy = get_query_var('taxonomy');
	$term = get_query_var('term');

	$templates = array();
	if ( $taxonomy && $term ) {
		$templates[] = "taxonomy-$taxonomy-$term.php";
	}
	if ( $taxonomy ) {
		$templates[] = "taxonomy-$taxonomy.php";
	}
	$templates[] = "taxonomy.php";

	$template = $this->locate_template($templates);
	return apply_filters('taxonomy_template', $template);
}

/* ==================================================
 * @param	none
 * @return	string  $template
 * @since	0.70
 * based on get_category_template() at wp-includes/theme.php of WP 2.9.2
 */
private function get_category_template() {
	$cat_id = absint( get_query_var('cat') );
	$category = get_category($cat_id);
	$templates = array();

	if ( !is_wp_error($category) ) {
		$templates[] = "category-{$category->slug}.php";
	}
	$templates[] = "category-$cat_id.php";
	$templates[] = "category.php";

	$template = $this->locate_template($templates);
	return apply_filters('category_template', $template);
}

/* ==================================================
 * @param	none
 * @return	string  $template
 * based on get_tag_template() at wp-includes/theme.php of WP 2.3.1
 */
private function get_tag_template() {
	$tag_id = absint( get_query_var('tag_id') );
	$tag_name = get_query_var('tag');
	$templates = array();
	
	if ( $tag_name ) {
		$templates[] = "tag-$tag_name.php";
	}
	if ( $tag_id ) {
		$templates[] = "tag-$tag_id.php";
	}
	$templates[] = "tag.php";

	$template = $this->locate_template($templates);
	return apply_filters('tag_template', $template);
}

/* ==================================================
 * @param	none
 * @return	string
 * @since	0.70
 * based on get_home_template() at wp-includes/theme.php of WP 2.9.2
 */
private function get_home_template() {
	$template = $this->locate_template(array('home.php', 'index.php'));
	return apply_filters('home_template', $template);
}

/* ==================================================
 * @param	none
 * @return	string
 * @since	0.70
 * based on get_page_template() at wp-includes/theme.php of WP 2.9.2
 */
private function get_page_template() {
	global $wp_query;

	$id = (int) $wp_query->post->ID;
	$template = get_post_meta($id, '_wp_page_template', true);
	$pagename = get_query_var('pagename');

	if (self::DEFAULT_THEME == $template) {
		$template = '';
	}
	$templates = array();
	if ( !empty($template) && !validate_file($template) )
		$templates[] = $template;
	if ( $pagename )
		$templates[] = "page-$pagename.php";
	if ( $id )
		$templates[] = "page-$id.php";
	$templates[] = "page.php";

	return apply_filters('page_template', $this->locate_template($templates));
}

/* ==================================================
 * @param	none
 * @return	string  $template
 * @since	0.70
 * based on get_attachment_template() at wp-includes/theme.php of WP 2.2.3
 */
private function get_attachment_template() {
	global $posts;
	$type = explode('/', $posts[0]->post_mime_type);
	if ($template = $this->query_template($type[0]) )
		return $template;
	elseif ($template = $this->query_template($type[1]) )
		return $template;
	elseif ($template = $this->query_template("$type[0]_$type[1]") )
		return $template;
	else
		return $this->query_template('attachment');
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.70
 */
public function get_header() {
	do_action('get_header');
	if (  file_exists($this->template_dir . 'header.php') ) {
		load_template($this->template_dir . 'header.php');
	}
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.70
 */
public function get_footer() {
	do_action('get_footer');
	if (  file_exists($this->template_dir . 'footer.php') ) {
		load_template($this->template_dir . 'footer.php');
	}
	return;
}

/* ==================================================
 * @param	none
 * @return	array  $themes
 * @since	0.97
 */
public static function installed() {
	self::set_variables();
	$theme_data = get_theme_data(self::$built_in_theme_root . self::DEFAULT_THEME . '/style.css');
	$themes = array();
	if (isset($theme_data['Name'])) {
		$themes[self::DEFAULT_THEME] = $theme_data['Name'] . ' (' . $theme_data['Version'] . ')';
	} else {
		$theme[self::DEFAULT_THEME] = 'Default';
	}
	foreach (glob(self::$built_in_theme_root . '/*', GLOB_ONLYDIR) as $d) {
		if ( !self::valid_theme($d) ) {
			continue;
		}
		if (preg_match('!/([-_.+a-zA-Z0-9]+)/?$!', $d, $filename) && $filename[1] != self::DEFAULT_THEME) {
			$theme_data = get_theme_data($d . '/style.css');
			$themes[$filename[1]] = $theme_data['Name'] . ' (' . $theme_data['Version'] . ')';
		}
	}
	if ( !file_exists(WP_CONTENT_DIR . '/' . self::USER_THEMES_DIR) ) {
		return $themes;
	}
	foreach (glob(WP_CONTENT_DIR . '/' . self::USER_THEMES_DIR . '/*', GLOB_ONLYDIR) as $d) {
		if ( !self::valid_theme($d) ) {
			continue;
		}
		if ( preg_match('!/([-_.+a-zA-Z0-9]+)/?$!', $d, $filename) && !in_array($filename[1], $themes) ) {
			$theme_data = get_theme_data($d . '/style.css');
			$themes[self::USER_THEMES_DIR . '/' . $filename[1]] = $theme_data['Name'] . ' (' . $theme_data['Version'] . ')';
		}
	}
	return $themes;
}


/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public static function preview_theme() {
	if ( ! (isset($_GET['template']) && isset($_GET['preview'])) ) {
		return;
	}
	if ( !current_user_can( 'switch_themes' ) ) {
		return;
	}
	$theme = stripslashes($_GET['template']);
	if ( !self::valid_dir_name($theme) ) {
		return;
	}
	$path = self::complete_theme_dir($theme);
	if ( !self::valid_theme($path) ) {
		return;
	}

	// Prevent theme mods to current theme being used on theme being previewed
	add_filter( 'pre_option_mods_' . get_current_theme(), create_function( '', "return array();" ) );
	add_filter( 'pre_option_mods_' . $theme, create_function( '', "return array();" ) );

	ob_start( 'preview_theme_ob_filter' );
}

// ===== End of class ====================
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.70
 */
function ks_header() {
	global $Ktai_Style;
	$Ktai_Style->theme->get_header();
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.70
 */
function ks_footer() {
	global $Ktai_Style;
	$Ktai_Style->theme->get_footer();
	return;
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $theme
 * @since	2.0.0
 */
function ks_get_theme($echo = false) {
	global $Ktai_Style;
	$theme = apply_filters('ktai_theme', $Ktai_Style->theme->get('theme'));
	if ($echo) {
		echo $theme;
	}
	return $theme;
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $theme
 * @since	2.0.0
 */
function ks_get_theme_directory($echo = false) {
	global $Ktai_Style;
	$theme      = $Ktai_Style->theme->get('theme');
	$theme_dir  = $Ktai_Style->theme->get('template_dir'); // with trailing slash
	$theme_root = $Ktai_Style->theme->get('theme_root'); // with trailing slash
	$theme_dir = apply_filters('ktai_theme_directory', $theme_dir, $theme, $theme_root );
	if ($template_dir) {
		echo $template_dir;
	}
	return $template_dir;
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $url (with tailing slash)
 * @since	0.97
 */
function ks_theme_url($echo = true) {
	global $Ktai_Style;
	$theme      = $Ktai_Style->theme->get('theme');
	$theme_url  = $Ktai_Style->theme->get('template_uri'); // with trailing slash
	$theme_root = $Ktai_Style->theme->get('theme_root'); // with trailing slash
	$theme_url = apply_filters('ktai_theme_url', $Ktai_Style->strip_host($theme_url)
	, $theme, $theme_root );
	if ($echo) {
		echo $theme_url;
	}
	return $theme_url;
}

?>