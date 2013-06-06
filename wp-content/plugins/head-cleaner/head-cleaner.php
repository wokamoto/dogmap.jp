<?php
/*
Plugin Name: Head Cleaner
Version: 1.4.2.11
Plugin URI: http://wppluginsj.sourceforge.jp/head-cleaner/
Description: Cleaning tags from your WordPress header and footer.
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: head-cleaner
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
  Copyright 2009 - 2013 wokamoto (email : wokamoto1973@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Includes:
 PHP Simple HTML DOM Parser Ver.1.11
  http://sourceforge.net/projects/simplehtmldom/
  Licensed under The MIT License

 jsmin.php - PHP implementation of Douglas Crockford's JSMin. Ver.1.1.1 (2008-03-02)
  Copyright (c) 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
  Copyright (c) 2008 Ryan Grove <ryan@wonko.com> (PHP port)
  License http://opensource.org/licenses/mit-license.php MIT License
*/

//**************************************************************************************
// Defines
//**************************************************************************************
if (!defined('HC_CACHE_DIR'))
	define('HC_CACHE_DIR', 'cache/head-cleaner');
if (!defined('HC_MAKE_GZ_FILE'))
	define('HC_MAKE_GZ_FILE', false);
if (!defined('HC_RAW_SHORTCODE'))
	define('HC_RAW_SHORTCODE', false);

//**************************************************************************************
if (!defined('ABSPATH') && strstr($_SERVER['PHP_SELF'], '/head-cleaner.php')) {
	include('js_css.php');
	exit();
}

//**************************************************************************************
// Require
//**************************************************************************************
if (!class_exists('wokController') || !class_exists('wokScriptManager'))
	require(dirname(__FILE__).'/includes/common-controller.php');
if (!class_exists('InputValidator'))
	require(dirname(__FILE__).'/includes/class-InputValidator.php');

//**************************************************************************************
// Head Cleaner
//**************************************************************************************
class HeadCleaner extends wokController {
	public $plugin_name  = 'head-cleaner';
	public $plugin_ver   = '1.4.2.11';

	const PRIORITY = 10000;
	const ANALYZE_EXPIRED = 604800;	// 60 * 60 * 24 * 7 [sec.]
	const XMLNS = 'http://www.w3.org/1999/xhtml';
	const XMLNS_OG = 'http://ogp.me/ns#';
	const XMLNS_FB = 'http://www.facebook.com/2008/fbml';
	const IMG_BASE64_MAX_SIZE = 4096;
	const OPTION_SAVE_FILE = false;

	// Deafault Options
	private $options_default = array(
		'foot_js'        => false ,
		'dynamic'        => false ,
		'js_move_foot'   => false ,
		'cache_enabled'  => false ,
		'combined_css'   => false ,
		'combined_js'    => false ,
		'js_minify'      => false ,
		'css_optimise'   => false ,
		'default_media'  => 'all' ,
		'debug_mode'     => false ,
		'filters'        => array('wp_head' => array(), 'wp_footer' => array()) ,
		'priority'       => array('wp_head' => array(), 'wp_footer' => array()) ,
		'head_js'        => array() ,
		'remove_js'      => array() ,
		'rsd_link'       => false ,
		'wlwmanifest_link' => false ,
		'wp_generator'   => false ,
		'analyze_expired'=> 0 ,
		'xml_declaration'=> false ,
		'ie_conditional' => false ,
		'canonical_tag'  => true ,
		'gzip_on'        => false ,
		'use_ajax_libs'  => false ,
		'img_base64'     => false ,
		'add_ogp_tag'    => false ,
		'ogp_default_image' => '' ,
		'og_type_top'    => 'website' ,
		'og_locale'      => '' ,
		'fb_admins'      => '' ,
		'fb_app_id'      => '' ,
		'add_last_modified' => false ,
		'paranoia_mode'  => false ,
		'dns-prefetch'   => true ,
		);

	private $wp_url     = '';
	private $root_url   = '';
	private $self_url   = '';
	private $cache_path = '';
	private $cache_url  = '';

	private $lang;
	private $foot_js_src;

	private $mtime_start;
	private $process_time = 0;
	private $org_len = 0;
	private $ret_len = 0;

	private $filters;
	private $head_js;
	private $no_conflict = array(
		'comment_quicktags' ,       // Comment Quicktags
		'stats_footer' ,            // WordPress.com Stats
		'uga_wp_footer_track' ,     // Ultimate Google Analytics
		'tam_google_analytics::insert_tracking_code' ,  // TaM Google Analytics
		'Ktai_Entry::add_check_messages' ,	// Ktai Entry
		);

	private $default_head_filters = array(
		'HeadCleaner::.*' ,
		'noindex' ,
		'.*lambda_[\d]+' ,
		'rsd_link' ,
		'wlwmanifest_link' ,
		'wp_generator' ,
		);

	private $ob_handlers = array(
		'All_in_One_SEO_Pack::output_callback_for_title' => false ,
		'wpSEO::exe_modify_content' => false ,
		);

	private $img_urls = array();

	private $last_modified = array();

	/**********************************************************
	* Constructor
	***********************************************************/
	function __construct($uninstall = false) {
		$this->init(__FILE__);
		$this->options = $this->_init_options($this->getOptions());
		$this->filters = $this->options['filters'];
		$this->head_js = $this->options['head_js'];

		$this->wp_url   = trailingslashit($this->_get_site_url());
		$this->root_url = preg_replace('/^(https?:\/\/[^\/]*\/).*$/i', '$1', $this->wp_url);
		$this->self_url = $this->wp_plugin_url( basename(dirname(__FILE__)) ) . basename(__FILE__);
		$this->lang     = (defined('WPLANG') ? WPLANG : 'ja');
		$this->charset  = get_option('blog_charset');

		if ($uninstall)
			return;

		$this->last_modified["posts"] = 0;
		$this->last_modified["theme"] = 0;
		$this->_get_filters('wp_head');
		$this->_get_filters('wp_footer');

		// Create Directory for Cache
		if ($this->options['cache_enabled']) {
			$this->cache_path = $this->_create_cache_dir();
			if ($this->cache_path !== false) {
				$this->cache_url = str_replace(ABSPATH, $this->wp_url, $this->cache_path);
			} else {
				$this->options['cache_enabled'] = false;

				$this->options['combined_css']  = false;
				$this->options['combined_js']   = false;

				$this->options['js_minify']     = false;
				$this->options['css_optimise']  = false;
			}
		}

		if (is_admin()) {
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_filter('plugin_action_links', array(&$this, 'plugin_setting_links'), 10, 2 );

		} else {
			// Require PHP Libraries
			$this->_require_libraries();

			// add action
			if ($this->options['add_last_modified']) {
				add_action('template_redirect', array(&$this, 'send_http_header_last_modified'));
			}
			add_action('wp_footer',  array(&$this, 'filters_save'), 11);

			if (!$this->_is_mobile()) {
				// head cleaner
				add_action('get_header', array(&$this, 'head_start'));

				// paranoia mode
				if ($this->options['paranoia_mode']) {
					add_action('wp_head', array(&$this, 'body_start'), self::PRIORITY + 1);
				}

				// footer cleaner
				if ($this->options['foot_js']) {
					add_action('wp_footer', array(&$this, 'footer_start'), 1);
				}
			}

			// [raw] shortcode support
			if (HC_RAW_SHORTCODE) {
				remove_filter('the_content', 'wpautop');
				remove_filter('the_content', 'wptexturize');
				add_filter('the_content', array(&$this, 'raw_formatter'), 99);

				remove_filter('the_excerpt', 'wpautop');
				remove_filter('the_excerpt', 'wptexturize');
				add_filter('the_excerpt', array(&$this, 'raw_formatter'), 99);
			}
		}

		// activation & deactivation & uninstall
		if (function_exists('register_activation_hook')) {
			register_activation_hook(__FILE__, array(&$this, 'activation'));
		}
		if (function_exists('register_deactivation_hook')) {
			register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));
		}
	}

	/**********************************************************
	* Init Options
	***********************************************************/
	private function _init_options($wk_options = '') {
		if (!is_array($wk_options))
			$wk_options = array();

		foreach ($this->options_default as $key => $val) {
			$wk_options[$key] = (isset($wk_options[$key]) ? $wk_options[$key] : $val);
			switch ($key) {
			case 'wp_generator':
			case 'rsd_link':
			case 'wlwmanifest_link':
				if ( isset($wk_options['priority']) && isset($wk_options['priority']['wp_head']) ) {
					if ( isset($wk_options['priority']['wp_head'][$key]) && $wk_options['priority']['wp_head'][$key] < 0 ) {
						$wk_options[$key] = true;
					}
				}
				break;
			case 'og_locale':
				if ( empty($wk_options['og_locale']) ) {
					switch (WPLANG) {
					case 'ja':
						$wk_options['og_locale'] = 'ja_JP';
						break;
					}
				}
				break;
			}
		}

		if (time() > $wk_options['analyze_expired']) {
			$filters  = $this->options_default['filters'];

			$priority = $this->options_default['priority'];
			foreach ($wk_options['priority'] as $tag => $filters) {
				foreach ((array) $filters as $key => $val) {
					if ($val <= 0 || $val > self::PRIORITY)
						$priority[$tag][$key] = $val;
				}
			}
			unset($filters);

			$head_js  = $this->options_default['head_js'];
			foreach ((array) $wk_options['head_js'] as $key => $val) {
				if ($val === false)
					$head_js[$key] = $val;
			}

			$wk_options['filters']  = $filters;
			$wk_options['priority'] = $priority;
			$wk_options['head_js']  = $head_js;
			$wk_options['analyze_expired'] = time() + self::ANALYZE_EXPIRED;
		}

		if ( function_exists('dbgx_trace_var') ) {
			dbgx_trace_var($wk_options);
		}

		return $wk_options;
	}

	/**********************************************************
	* Require Libraries
	***********************************************************/
	private function _require_libraries(){
		$includes = dirname(__FILE__) . '/includes/';

		// PHP Simple HTML DOM Parser
		if (!function_exists('str_get_html')) {
			require($includes . 'simple_html_dom.php' );
		}

		// jsmin.php - PHP implementation of Douglas Crockford's JSMin.
		if ($this->options['js_minify'] && !class_exists('JSMin')) {
			require($includes . 'JSMin.php');
			$this->options['js_minify'] = class_exists('JSMin') && $this->options['js_minify'];
		}

		if ($this->options['css_optimise'] && !class_exists('Minify_CSS')) {
			require($includes . 'CSSMin.php');
			$this->options['css_optimise'] = class_exists('Minify_CSS') && $this->options['css_optimise'];
		}

		// Use Google Ajax Libraries
		if ($this->options['use_ajax_libs']) {
			require($includes . 'regist_ajax_libs.php');
		}
	}

	//**************************************************************************************
	// plugin activation
	//**************************************************************************************
	public function activation(){
		$cache_dir = $this->_create_cache_dir();
		if ( $cache_dir !== false )
			$this->_create_htaccess($cache_dir);

		if (self::OPTION_SAVE_FILE !== false) {
			$option_file = dirname(__FILE__) . '/' . self::OPTION_SAVE_FILE;
			if ( file_exists($option_file) ) {
				$wk_options = unserialize(file_get_contents($option_file));
				if ( $wk_options != $this->options ) {
					$this->options = $wk_options;
					$this->updateOptions();
					@unlink($option_file);
				}
			}
		}
	}

	//**************************************************************************************
	// plugin deactivation
	//**************************************************************************************
	public function deactivation(){
		if (self::OPTION_SAVE_FILE !== false) {
			$wk_options = serialize($this->options);
			$option_file = dirname(__FILE__) . '/' . self::OPTION_SAVE_FILE;
			if ( file_put_contents( $option_file, $wk_options ) && file_exists($option_file) ) {
				$this->_delete_settings();
			}
		}
	}

	//**************************************************************************************
	// plugin uninstall
	//**************************************************************************************
	public function uninstall(){
		$this->_delete_settings();
		$this->_remove_cache_file();
		if (self::OPTION_SAVE_FILE !== false) {
			$option_file = dirname(__FILE__) . '/' . self::OPTION_SAVE_FILE;
			if ( file_exists($option_file) ) {
				@unlink($option_file);
			}
		}
	}

	//**************************************************************************************
	// ob_start for Header
	//**************************************************************************************
	public function head_start(){
		if (!$this->_is_mobile()) {
			$ob_handlers = (array) ob_list_handlers();
			if  (count($ob_handlers) > 0) {
				foreach ($ob_handlers as $ob_handler) {
					if (isset($this->ob_handlers[$ob_handler])) {
						$this->ob_handlers[$ob_handler] = true;
						ob_end_flush();
					}
				}
			}

			ob_start(array(&$this, 'head_cleaner'));
			$this->mtime_start = microtime();

			if (function_exists('rel_canonical') && !$this->options['canonical_tag']) {
				remove_action( 'wp_head', 'rel_canonical' );
			}

			add_action('wp_head', array(&$this, 'end'), self::PRIORITY);
			$this->_get_filters('wp_head');
			$this->_change_filters_priority('wp_head');
		}
	}

	//**************************************************************************************
	// ob_start for body
	//**************************************************************************************
	public function body_start(){
		if (!$this->_is_mobile() && $this->options['paranoia_mode'] && !$this->_is_user_logged_in()) {
			ob_start(array(&$this, 'html_cleaner'));
			add_action('wp_footer', array(&$this, 'end'), 0);
		}
	}

	//**************************************************************************************
	// ob_start for footer
	//**************************************************************************************
	public function footer_start(){
		if (!$this->_is_mobile() && $this->options['foot_js']) {
			$ob_handlers = (array) ob_list_handlers();
			if  (count($ob_handlers) > 0) {
				foreach ($ob_handlers as $ob_handler) {
					if (isset($this->ob_handlers[$ob_handler])) {
						$this->ob_handlers[$ob_handler] = true;
						ob_end_flush();
					}
				}
			}

			ob_start(array(&$this, 'footer_cleaner'));
			$this->mtime_start = microtime();

			add_action('shutdown', array(&$this, 'end'), 1);
			$this->_get_filters('wp_footer');
			$this->_change_filters_priority('wp_footer');
		}
	}

	//**************************************************************************************
	// ob_handler
	//**************************************************************************************
	private function ob_handler($content){
		foreach ($this->ob_handlers as $ob_handler => $enable) {
			if ($enable) {
				$this->ob_handlers[$ob_handler] = false;
				switch ($ob_handler) {
				case 'All_in_One_SEO_Pack::output_callback_for_title':
					global $aiosp;
					if (isset($aiosp))
						$content = $aiosp->rewrite_title($content);
					break;
				case 'wpSEO::exe_modify_content':
					if (isset($GLOBALS['wpSEO'])) {
						$wpSEO = $GLOBALS['wpSEO'];
						$content = $wpSEO->exe_modify_content($content);
					}
					break;
				default :
					break;
				}
			}
		}

		return $content;
	}

	//**************************************************************************************
	// ob_end_flush
	//**************************************************************************************
	public function end(){
		ob_end_flush();
	}

	private function function_enabled( $function_name ) {
		return ( $function_name !== false && !preg_match('/^(' . implode('|', $this->default_head_filters) . ')$/i', $function_name) );
	}

	//**************************************************************************************
	// filters info save
	//**************************************************************************************
	public function filters_save(){
		if ($this->_is_user_logged_in() && $this->_chk_filters_update()) {
			if ( $this->options['filters'] != $this->filters || $this->options['head_js'] != $this->head_js ) {
				$this->options['filters'] = $this->filters;
				$this->options['head_js'] = $this->head_js;
				$this->options['analyze_expired'] = time() + self::ANALYZE_EXPIRED;
				$this->updateOptions();
			}
		}
	}

	//**************************************************************************************
	// head cleaner
	//**************************************************************************************
	public function head_cleaner($buffer) {
		$buffer = $this->_tag_trim($this->ob_handler($buffer));
		$buffer = apply_filters($this->plugin_name.'/pre_head_cleaner', $buffer);
		if (!function_exists('str_get_html')) {
			$ret_val = apply_filters($this->plugin_name.'/head_cleaner', $buffer);
			return $ret_val;
		}

		$head_txt = $buffer;
		$url = $this->_get_permalink();

		$doctype   = 'html';
		$xml_head  = $html_tag  = $head_tag  = '';
		if (preg_match_all('/<(\?xml|html|head) ([^>]*)>/ism', $buffer, $matches, PREG_SET_ORDER)) {
			foreach ((array) $matches as $match) {
				$tag = $this->_tag_trim(preg_replace("/[ \t\r\n]+/ism", ' ', "<{$match[1]} {$match[2]}>"));
				switch ($match[1]){
				case '?xml':
					$doctype  = 'xhtml';
					$xml_head = $tag;
					break;
				case 'html':
					$html_tag = $tag;
					break;
				case 'head':
					$head_tag = $tag;
					break;
				}
			}
			unset($match);
		}
		unset($matches);

		$doctype  = (
			preg_match('/<!DOCTYPE [^>]* XHTML [^>]*>/i', $buffer)
			? 'xhtml'
			: 'html'
			);

		$ret_val      = '';
		$doctype_tag  = '';
		$head_tag     = '';
		$meta_tag     = '';
		$title_tag    = '';
		$base_tag     = '';
		$link_tag     = '';
		$object_tag   = '';
		$other_tag    = '';
		$css_tag      = '';
		$inline_css   = '';
		$script_tag   = '';
		$inline_js    = '';
		$noscript_tag = '';

		// Get <!DOCTYPE> tag
		if (preg_match('/^(<\!DOCTYPE[^>]*>)/ism', $head_txt, $matches)) {
			$doctype_tag = $this->_tag_trim($matches[1]);
		}

		// Get <head> tag and other
		if (preg_match('/(<head[^>]*>[^<]*)(.*)$/ism', $head_txt, $matches)) {
			$head_tag = $this->_tag_trim($matches[1]);
			$head_txt = $this->_tag_trim($matches[2]);
		}
		unset($matches);

		// for IE conditional tag
		list($IE_conditional_tags, $head_txt, $ie6) = $this->_parse_IE_conditional_tags($buffer);
		$IE_conditional_tag_pattern = '/(<\!-+[ \t]*\[if[ \t]*[\(]?[ \t]*(IE|[gl]te?[ \t]+IE)[^\]]+\][ \t]*>)[ \t\r\n]*(.*?)[ \t\r\n]*(<\![ \t]*\[endif\][ \t]*-+>)/ims';
		if (!$this->options['ie_conditional'] && count($IE_conditional_tags) > 0) {
			$html_tag     = '';
			foreach ((array) $IE_conditional_tags as $IE_conditional_tag) {
				if (isset($IE_conditional_tag[0])) {
					$IE_tag = $this->_tag_trim(preg_replace($IE_conditional_tag_pattern, "$1$3$4", $IE_conditional_tag[0]));
					if ( strpos(strtolower($IE_tag), '<html') !== false ) {
						$html_tag .= $IE_tag;
					}
				}
			}
			if (!empty($html_tag)) {
				$html_tag .= '<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->' . "\n";
				$html_tag .= (
					$doctype  == 'xhtml'
					? $this->_html_tag_normalize($this->_tag_trim('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$this->lang.'" lang="'.$this->lang.'">'), $doctype)
					: $this->_html_tag_normalize($this->_tag_trim('<html dir="ltr" lang="'.$this->lang.'">'), $doctype)
					) . "\n";
				$html_tag .= '<!--<![endif]-->' . "\n";
			}
			unset($IE_conditional_tag);

			// Get <html> tag
			if (!empty($html_tag)) {
				$replace = array();
				$pattern = '/<html[^>]*>/ims';
				if (preg_match_all($pattern, $html_tag, $matches)) {
					foreach ($matches[0] as $match) {
						$replace[] = trim($this->_html_tag_normalize($match));
					}
				}
				if (count($replace) > 0 && preg_match_all($pattern, $html_tag, $matches)) {
					$html_tag = str_replace($matches[0], $replace, $html_tag);
				}
				unset($replace);
				unset($matches);
			}

			if (empty($html_tag) && preg_match('/(<html[^>]*>)(.*)$/ism', $buffer, $matches)) {
				$html_tag = $this->_html_tag_normalize($this->_tag_trim($matches[1]), $doctype);
			}
			unset($matches);
		}

		// Get <html> tag and <head> tag
		if ($doctype  == 'xhtml') {
			// xhtml
			if (empty($xml_head)) {
				$xml_head = $this->_tag_trim('<?xml version="1.0" encoding="'.$this->charset.'"?>');
			}
			if (empty($doctype_tag)) {
				$doctype_tag = $this->_tag_trim('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');
			}
			if (empty($html_tag)) {
				$html_tag = $this->_html_tag_normalize($this->_tag_trim('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$this->lang.'" lang="'.$this->lang.'">'), $doctype);
			}
			if (empty($head_tag)) {
				$head_tag = $this->_tag_trim('<head profile="http://gmpg.org/xfn/11">');
			}
		} else {
			// html
			$xml_head = '';
			if (empty($doctype_tag)) {
				$doctype_tag = $this->_tag_trim('<!DOCTYPE html>');
			}
			if (empty($html_tag)) {
				$html_tag = $this->_html_tag_normalize($this->_tag_trim('<html dir="ltr" lang="'.$this->lang.'">'), $doctype);
			}
			if (empty($head_tag)) {
				$this->_tag_trim('<head>');
			}
		}

		if ($this->options['add_ogp_tag']) {
			$head_txt .= $this->_get_ogp_tags();
		}

		// Get Simple DOM Object
		$head_txt = preg_replace_callback(
			array('/(content=)(")([^"]*)(")/i', '/(content=)(\')([^\']*)(\')/i'),
			array(&$this, '_tag_replace'),
			$head_txt
			);
		$dom = str_get_html("<html></head>{$head_txt}</head><body></body></html>");
		if ($dom === false) {
			return ($this->_tag_trim($buffer));
		}

		// ***** Parse Start! *****
		$other_domain = array();
		$meta_tag    = $this->_dom_to_html($dom->find("meta"));
		$title_tag   = $this->_dom_to_html($dom->find("title"), 1);
		$base_tag    = $this->_dom_to_html($dom->find("base"), 1);
		$link_tag    = $this->_dom_to_html($dom->find("link[rel!='stylesheet']"), false, '/alternate stylesheet/i');
		$link_tags   = explode("\n", $link_tag);
		$link_tag    = '';
		foreach ($link_tags as $tag) {
			if ( strpos(strtolower($tag), 'dns-prefetch') !== false ) {
				$href = preg_replace('/^.*href=[\'"]([^\'"]*)[\'"].*$/i', '$1', $tag);
				$domain = preg_replace('/^.*https?:(\/\/[^\/]+)\/?.*$/i', '$1', $href);
				if ( !in_array($domain,$other_domain) )
					$other_domain[] = $domain;
			} else {
				$link_tag .= $tag . "\n";
			}
		}
		if (count($dom->find("link[rel='canonical']")) <= 0 && $this->options['canonical_tag'])
			$link_tag .= $this->_tag_trim('<link rel="canonical" href="' . htmlspecialchars($url, ENT_QUOTES) . '" />');
		list($css_tag, $inline_css) = $this->_parse_stylesheet_tag($dom->find("link[rel*='stylesheet']"), $dom->find("style"), $other_domain);
		list($script_tag, $inline_js, $foot_js) = $this->_parse_script_tag($dom->find("script"), $other_domain);
		$noscript_tag = $this->_dom_to_html($dom->find("noscript"));
		$object_tag   = $this->_dom_to_html($dom->find("object"));
		$object_tag  .= $this->_rdf_convert($dom->find("rdf:RDF"));
		if ( $this->options['dns-prefetch'] && count($other_domain) > 0 ) {
			$dns_prefetch = '';
			foreach ( $other_domain as $domain ) {
				$dns_prefetch .= sprintf('<link rel="dns-prefetch" href="%s" />' . "\n", $domain);
			}
			$link_tag = $dns_prefetch . $link_tag;
		}
		$dom->clear();
		unset($dom);

		// for IE conditional tag
		if (!$this->options['ie_conditional'] && count($IE_conditional_tags) > 0) {
			foreach ((array) $IE_conditional_tags as $IE_conditional_tag) {
				if (isset($IE_conditional_tag[0])) {
					$IE_tag = $this->_tag_trim(preg_replace($IE_conditional_tag_pattern, "$1$3$4", $IE_conditional_tag[0]));
					if ( strpos(strtolower($IE_tag), '<meta') !== false ) {
						$meta_tag   .= $IE_tag;
					} elseif ( strpos(strtolower($IE_tag), '<link') !== false ) {
						$css_tag    .= $IE_tag;
					} elseif ( strpos(strtolower($IE_tag), '<style') !== false ) {
						$inline_css .= $IE_tag;
					} elseif ( strpos(strtolower($IE_tag), '<script') !== false ) {
						$inline_js  .= $IE_tag;
					} elseif ( strpos(strtolower($IE_tag), '<html') !== false ) {
					} elseif ( !empty($IE_tag) ) {
						$object_tag .= $IE_tag;
					}
				}
			}
		}
		unset($IE_conditional_tag);

		// Build!
		$ret_val .=
			$this->_tag_trim($doctype_tag) .
			$this->_tag_trim($html_tag) .
			$this->_tag_trim($head_tag) .
			$this->_tag_trim($meta_tag) .
			$this->_tag_trim($title_tag) .
			$this->_tag_trim($base_tag) .
			$this->_tag_trim($link_tag) .
			$this->_tag_trim($css_tag) .
			$this->_tag_trim($inline_css) .
			(!$this->options['js_move_foot'] ? $this->_tag_trim($script_tag) : '') .
			(!$this->options['js_move_foot'] ? $this->_tag_trim($inline_js)  : '') .
			$this->_tag_trim($noscript_tag) .
			$this->_tag_trim($object_tag) ;

		// JavaScript move footer space
		if ($this->options['js_move_foot'] || !empty($foot_js)) {
			$this->foot_js_src = $foot_js;
			if ( $this->options['js_move_foot'] )
				$this->foot_js_src .= $script_tag . $inline_js;
			$this->foot_js_src = $this->_tag_trim($this->foot_js_src);
		}
		if (!empty($this->foot_js_src)) {
			add_action('wp_footer', array(&$this, 'footer'), 9);
		}

		// dynamic css, js
		if ($this->options['dynamic']) {
			$ret_val = $this->_dynamic_js_css($ret_val);
		}

		// set xml declaration
		$ret_val = (
			$ie6 || !$this->options['xml_declaration']
			? preg_replace('/^<\?xml[^>]*>/i', '', $ret_val)
			: $this->_tag_trim(strpos($ret_val, '<?xml') === false ? $xml_head : '') . $ret_val
			);
		$ret_val = $this->_tag_normalize($ret_val);
		$ret_val = str_replace('&#039;', "'", $ret_val);

		// add debug information
		if ($this->options['debug_mode']) {
			$ret_val .= $this->_get_debug_info($buffer);
		}

		$this->org_len = strlen(bin2hex($buffer));
		$this->ret_len = strlen(bin2hex($ret_val));
		$this->process_time = $this->_microtime_diff($this->mtime_start);
//		$ret_val .= "<!-- {$this->process_time} seconds. {$this->ret_len} / {$this->org_len} (" . (int) (($this->org_len - $this->ret_len) / $this->org_len * 10000) / 100 . "% saving) -->\n";

		if (!$this->_is_user_logged_in() && $this->options['paranoia_mode']) {
			$ret_val = $this->html_cleaner($ret_val);
		}
		
		if ( $doctype !== 'xhtml' )
			$ret_val = preg_replace('# */>#', '>', $ret_val);

		$ret_val = apply_filters($this->plugin_name.'/head_cleaner', $ret_val);
		return $ret_val;
	}

	private function _tag_replace($matches) {
		$content = $matches[2] . esc_attr($matches[3]) . $matches[4];
		return $matches[1] . $content;
	}

	//**************************************************************************************
	// Parse IE conditional tags
	//**************************************************************************************
	private function _parse_IE_conditional_tags($content){
		// Check USER AGENT
		$ua     = trim(strtolower($_SERVER['HTTP_USER_AGENT']));
		$ie     = (strpos($ua, 'msie') !== false && !preg_match('/(gecko|applewebkit|opera|sleipnir|chrome)/i', $ua));
		$ie_ver = ($ie ? preg_replace('/^.*msie +([\d+\.]+);.*$/i', '$1', $ua) : false);
		$ie6    = ($ie ? version_compare($ie_ver, '7.0', '<') : false);

		$IE_conditional_tags = array();
		if ($this->options['ie_conditional']) {
			if ($ie) {
				$IE_conditional_tag_pattern = '/<\!-+[ \t]*[\(]?[ \t]*\[if[ \t]*%s[ \t]*\][ \t]*[\)]?[^>]*>(.*?)<\![ \t]*\[endif\][ \t]*-+>/ism';
				$content = preg_replace(sprintf($IE_conditional_tag_pattern, 'IE'), '$1', $content);

				$replace_patterns = array();
				if (version_compare($ie_ver, '5.5', '<')) {						// IE 5
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '([lg]te[ \t]*|[ \t]*)IE[ \t]*5\.?0?');	// >= 5, <= 5, = 5
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(lte?[ \t]*)IE[ \t]*5\.5');				// <  5.5, <= 5.5
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(lte?[ \t]*)IE[ \t]*[678]\.?0?');		// <  6 - 8, <= 6 - 8
				} elseif (version_compare($ie_ver, '6.0', '<')) {					// IE 5.5
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '([lg]te[ \t]*|[ \t]*)IE[ \t]*5\.5');	// >= 5.5, <= 5.5, = 5.5
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(lte?[ \t]*)IE[ \t]*[678]\.?0?');		// <  6 - 8, <= 6 - 8
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(gte?[ \t]*)IE[ \t]*5\.?0?');			// >  5, >= 5
				} elseif (version_compare($ie_ver, '7.0', '<')) {					// IE 6
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '([lg]te[ \t]*|[ \t]*)IE[ \t]*6\.?0?');	// >= 6, <= 6, = 6
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(lte?[ \t]*)IE[ \t]*[78]\.?0?');		// <  7 - 8, <= 7 - 8
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(gte?[ \t]*)IE[ \t]*5\.?0?');			// >  5, >= 5
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(gte?[ \t]*)IE[ \t]*5\.5');				// >  5.5, >= 5.5
				} elseif (version_compare($ie_ver, '8.0', '<')) {					// IE 7
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '([lg]te[ \t]*|[ \t]*)IE[ \t]*7\.?0?');	// >= 7, <= 7, = 7
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(lte?[ \t]*)IE[ \t]*8\.?0?');			// <  8, <= 8
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(gte?[ \t]*)IE[ \t]*[56]\.?0?');		// >  5 - 6, >= 5 - 6
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(gte?[ \t]*)IE[ \t]*5\.5');				// >  5.5, >= 5.5
				} elseif (version_compare($ie_ver, '9.0', '<')) {					// IE 8
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '([lg]te[ \t]*|[ \t]*)IE[ \t]*8\.?0?');	// >= 8, <= 8, = 8
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(gte?[ \t]*)IE[ \t]*[567]\.?0?');		// >  5 - 7, >= 5 - 7
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(gte?[ \t]*)IE[ \t]*5\.5');				// >  5.5, >= 5.5
				} elseif (version_compare($ie_ver, '10.0', '<')) {					// IE 9
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '([lg]te[ \t]*|[ \t]*)IE[ \t]*9\.?0?');	// >= 9, <= 9, = 9
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(gte?[ \t]*)IE[ \t]*[5678]\.?0?');		// >  5 - 8, >= 5 - 8
					$replace_patterns[] = sprintf($IE_conditional_tag_pattern, '(gte?[ \t]*)IE[ \t]*5\.5');				// >  5.5, >= 5.5
				}

				if (count($replace_patterns) > 0) {
					$content = preg_replace($replace_patterns, '$2', $content);
				}
				unset($replace_patterns);
			}
		} else {
			$IE_conditional_tag_pattern = '/(<\!-+[ \t]*\[if[ \t]*[\(]?[ \t]*(IE|[gl]te?[ \t]+IE)[^\]]+\][ \t]*>)[ \t\r\n]*(.*?)[ \t\r\n]*(<\![ \t]*\[endif\][ \t]*-+>)/ims';
			preg_match_all($IE_conditional_tag_pattern, $content, $IE_conditional_tags, PREG_SET_ORDER);
			$content = preg_replace($IE_conditional_tag_pattern, '', $content);
		}

		return array($IE_conditional_tags, $content, $ie6);
	}

	//**************************************************************************************
	// parse stylesheet tag
	//**************************************************************************************
	private function _parse_stylesheet_tag($elements_linktag, $elements_styletag, &$other_domain){
		$css_tag = '';
		$css_tag_with_id = '';
		$css_tag_with_title = '';
		$inner_css  = '';
		$css_src = array();
		$wk_inline_css = array();
		$inline_css = '';
		$inner_css_src = array();
		if ( !is_array($other_domain) )
			$other_domain = array();

		// css file
		foreach ((array) $elements_linktag as $element) {
			$tag = trim($element->outertext);
			if (strpos($css_tag, $tag) === false) {
				if (strpos($element->href, $this->wp_url) === false) {
					$css_tag .= $this->_tag_trim($tag);
					$domain = preg_replace('/^https?:(\/\/[^\/]+)\/?.*$/i', '$1', $element->href);
					if ( !in_array($domain,$other_domain) )
						$other_domain[] = $domain;
				} elseif ( isset($element->id) && !empty($element->id) ) {
					$css_tag_with_id .= $this->_tag_trim($tag);
				} elseif ( (isset($element->title) && !empty($element->title)) || strtolower($element->rel) == 'alternate stylesheet' ) {
					$css_tag_with_title .= $this->_tag_trim($tag);
				} else {
					$media = trim( isset($element->media)
						? $element->media
						: $this->options['default_media'] );
					$media = ( empty($media) || is_null($media) || $media === false
						? $this->options['default_media']
						: $media );
					$inner_css .= $this->_tag_trim($tag);
					if (!isset($css_src[$media]))
						$css_src[$media] = array();
					$css_src[$media][] = $element->href;
					$inner_css_src[] = $element->href;
				}
			}
		}
		unset($element);

		// inline css
		foreach ((array) $elements_styletag as $element) {
			$media = trim( isset($element->media) ? $element->media : $this->options['default_media'] );
			$media = ( empty($media) || is_null($media) || $media === false ? $this->options['default_media'] : $media );
			$wk_text = $this->_remove_comment($element->innertext, 'css');
			$wk_text = $this->_css_import($wk_text);
			if ( !empty($wk_text) ) {
				if ( !isset($wk_inline_css[$media]) )
					$wk_inline_css[$media] = $this->_tag_trim($wk_text);
				else
					$wk_inline_css[$media] .= $this->_tag_trim($wk_text);
			}
		}

		// make cache file
		if ($this->options['cache_enabled']) {
			if ($this->options['combined_css']) {
				$inner_css = '';
				foreach ($css_src as $media => $val) {
					$inner_css .= $this->_combined_css($val, trim(isset($wk_inline_css[$media]) ? $wk_inline_css[$media] : '' ), $media);
					if (isset($wk_inline_css[$media]))
						unset($wk_inline_css[$media]);
				}
				foreach ($wk_inline_css as $key => $val) {
					$val = trim($val);
					if (!empty($val)) {
						$inner_css .= $this->_combined_css(array(), $val, $key);
					}
				}

			} else {
				if ($this->options['css_optimise'] || $this->options['img_base64']) {
					foreach ($inner_css_src as $path) {
						$real_path = trim(preg_replace('/(\.css)(\?[^\?]*)$/i', '$1', str_replace($this->wp_url, ABSPATH, $path)));
						if (file_exists($real_path)) {
							list($content, $cache_path) = $this->_file_read($real_path, 'css');
							if (file_exists($cache_path)) {
								$cache_url = str_replace(ABSPATH, $this->wp_url, $cache_path);
								$inner_css = str_replace($path, $cache_url, $inner_css);
							}
						} else {
							list($content, $cache_path) = $this->_file_read($path, 'css');
							if (file_exists($cache_path)) {
								$cache_url = str_replace(ABSPATH, $this->wp_url, $cache_path);
								$inner_css = str_replace($path, $cache_url, $inner_css);
							}
						}
					}
				}

				foreach ($wk_inline_css as $key => $val) {
					$val = trim($val);
					if (!empty($val)) {
						$inner_css  .= $this->_combined_inline_css(trim($val), $key);
					}
				}
			}

		} else {
			foreach ($wk_inline_css as $key => $val) {
				$val = trim($val);
				if (!empty($val))
					$inline_css .= $this->_css_tag($val, $key, true);
			}
		}
		unset($wk_inline_css);
		unset($element);

		$css_tag = 
			$this->_tag_trim($css_tag) .
			$this->_tag_trim($inner_css) .
			$this->_tag_trim($css_tag_with_id) .
			$this->_tag_trim($css_tag_with_title) ;

		$css_tag    = $this->_tag_trim($css_tag);
		$inline_css = $this->_tag_trim($inline_css);

		$css_tag = apply_filters($this->plugin_name.'/css_tag', $css_tag);
		$inline_css = apply_filters($this->plugin_name.'/inline_css', $inline_css);
		return array($css_tag, $inline_css);
	}

	//**************************************************************************************
	// parse script tag
	//**************************************************************************************
	private function _parse_script_tag($elements, &$other_domain){
		$script_tag = '';
		$inner_js   = '';
		$inline_js  = '';
		$foot_js    = '';
		$js_src     = array();
		$js_libs    = array();
		$inner_js_src = array();
		if ( !is_array($other_domain) )
			$other_domain = array();

		foreach ((array) $elements as $element) {
			if (!isset($element->src)) {
				$inline_js .= $this->_remove_comment($element->innertext, 'js');
			} else {
				$src = trim($element->src);
				if (!isset($this->head_js[$src]))
					$this->head_js[$src] = true;

				if (array_search( $src, (array) $this->options['remove_js']) === false) {
					$find = false;
					if (preg_match('/\/((prototype|jquery|mootools)\.js)\?ver=([\.\d]+)[^\?]*$/i', $src, $matches)) {
						list($find, $filename, $product, $version) = $matches;
					} elseif (preg_match('/\/((prototype|jquery|mootools)[\-\.](min|[\.\d]+)[^\/]*\.js)\?[^\?]*$/i', $src, $matches)) {
						list($find, $filename, $product, $version) = $matches;
					} elseif (preg_match('/\/scriptaculous\/((builder|controls|dragdrop|effects|wp\-scriptaculous|scriptaculous|slider|sound|unittest)\.js)\?ver\=([\.\d]+)[^\?]*$/i', $src, $matches)) {
						list($find, $filename, $product, $version) = $matches;
						$product = (strpos($product, 'scriptaculous') === false
							? 'scriptaculous/' . $product
							: 'scriptaculous/scriptaculous');
					}
					unset($matches);

					if (strpos($src, $this->wp_url) === false) {
						$domain = preg_replace('/^https?:(\/\/[^\/]+)\/?.*$/i', '$1', $src);
						if ( !in_array($domain,$other_domain) )
							$other_domain[] = $domain;
					}

					if ($find !== false) {
						$version = trim(substr($version, -1) === '.' ? substr($version, 0, -1) : $version);
						if (empty($version))
							$version = preg_replace('/^.*\/([\.\d]+)\/.*$/', '$1', $src);
						if (!preg_match('/^[\.\d]*\d$/', $version))
							$version = '1';
						$js_libs[$product][$version] = $src;
					} elseif ($this->head_js[$src] === false) {
						$foot_js .= $this->_tag_trim($element->outertext);
					} elseif (strpos($src, $this->wp_url) === false) {
						$script_tag .= $this->_tag_trim($element->outertext);
					} else {
						$inner_js .= $this->_tag_trim($element->outertext);
						$js_src[] = $element->src;
						$inner_js_src[] = $element->src;
					}
				}
			}
		}
		unset($element);

		// JavaScript FrameWork (Prototype.js > jQuery > mootools)
		if (count($js_libs) > 0) {
			list($js_libs_src, $wk_inner_js, $wk_outer_js) = $this->_js_framework($js_libs);

			if (count($js_libs_src) > 0)
				$js_src = array_merge($js_libs_src, $js_src);

			$script_tag = $this->_tag_trim($script_tag) . $wk_outer_js;
			$inner_js   = $wk_inner_js . $inner_js;

			unset($js_libs_src);
			unset($js_libs);
		}

		// make chache file
		$inline_js = $this->_tag_trim($inline_js);
		if ($this->options['cache_enabled']) {
			if ($this->options['combined_js']) {
				$inner_js   = $this->_combined_javascript($js_src, trim($inline_js));
				$inline_js  = '';

			} else {
				if ($this->options['js_minify']) {
					foreach ($inner_js_src as $path) {
						$real_path = realpath(preg_replace('/^([^\?]*)([\?]?.*)$/', "$1", str_replace($this->wp_url, ABSPATH, $path)));
						if ($real_path !== false && file_exists($real_path)) {
							list($content, $cache_path) = $this->_file_read($real_path, 'js');
							if (file_exists($cache_path)) {
								$cache_url = str_replace(ABSPATH, $this->wp_url, $cache_path);
								$inner_js = str_replace($path, $cache_url, $inner_js);
							}
						}
					}
				}
				$inline_js  = $this->_combined_inline_javascript(trim($inline_js));
			}

		} else {
			$inline_js  = $this->_script_tag($inline_js, true);
		}

		$script_tag =
			$this->_tag_trim($script_tag) .
			$this->_tag_trim($inner_js) ;

		$script_tag = $this->_tag_trim($script_tag);
		$inline_js  = $this->_tag_trim($inline_js);
		$foot_js    = $this->_tag_trim($foot_js);

		$script_tag = apply_filters($this->plugin_name.'/script_tag', $script_tag);
		$inline_js = apply_filters($this->plugin_name.'/inline_js', $inline_js);
		$foot_js = apply_filters($this->plugin_name.'/foot_js', $foot_js);
		return array($script_tag, $inline_js, $foot_js);
	}

	//**************************************************************************************
	// html cleaner
	//**************************************************************************************
	public function html_cleaner($content) {
		$content = apply_filters($this->plugin_name.'/pre_html_cleaner', $content);
		$ret_val = $this->_html_cleaner_helper($content);

		if(is_singular()) {
			$home_url  = trailingslashit(get_home_url('/'));
			$permalink = $this->_get_permalink();
			$ret_val = str_replace(str_replace($home_url, '/', $permalink), $permalink, $ret_val);
			$ret_val = str_replace(untrailingslashit(get_home_url('/')).untrailingslashit(get_home_url('/')), untrailingslashit(get_home_url('/')), $ret_val);
		}

		$ret_val = apply_filters($this->plugin_name.'/html_cleaner', trim($ret_val));
		return $ret_val ;
	}

	private function _html_cleaner_helper($content) {
		$home_url  = trailingslashit(get_home_url('/'));

		$pattern = '/(' .
			'<(meta [^>]*property="og:(url|image)"' .
			'|link [^>]*rel="(canonical|shortlink)")[^>]*>' .
			'|<pre[^>]*>.*?<\/pre>' .
			'|<script[^>]*>.*?<\/script>' .
			'|<style[^>]*>.*?<\/style>' .
			'|<[^>]*style=["\'][^"\']*["\'][^>]*>' .
			')/ims';
		$replace = array();
		if (preg_match_all($pattern, $content, $matches)) {
			foreach ($matches[0] as $match) {
				if (preg_match('/^(<script[^>]*>)(.*?)(<\/script>)/ims', $match, $wk)) {
					$replace[] =
						trim(str_replace($home_url, '/', $wk[1])) .
						trim($this->js_minify($wk[2])) .
						trim($wk[3]);
				} elseif (preg_match('/^(<style[^>]*>)(.*?)(<\/style>)/ims', $match, $wk)) {
					$replace[] =
						trim(str_replace($home_url, '/', $wk[1])) .
						trim($this->css_optimise($wk[2])) .
						trim($wk[3]);
				} elseif (preg_match('/^(<[^>]*style=["\'])([^"\']*)(["\'][^>]*>)/ims', $match, $wk)) {
					$replace[] =
						trim(str_replace($home_url, '/', $wk[1])) .
						trim(preg_replace('/([:;])[ \t]+/','$1',$wk[2])) .
						trim(str_replace($home_url, '/', $wk[3]));
				} else {
					$replace[] = trim($match);
				}
				unset($wk);
			}
		}
		$ret_val = str_replace($home_url, '/', $content);
		$ret_val = trim(preg_replace(
			array('/[\r\n]/', '/[\t ]+/', '/>[\t ]+</', '/[\t ]+>/'),
			array('', ' ','><', '>'),
			$ret_val
			));
		if (count($replace) > 0 && preg_match_all($pattern, $ret_val, $matches)) {
			$ret_val = str_replace($matches[0], $replace, $ret_val);
		}
		unset($replace);
		unset($matches);

		// remove comments
		$pattern = '/<\![\-]+.*?[\-]+>/ims';
		$IE_tag_pattern = '/\[if[ \t]*[\(]?[ \t]*(IE|[gl]te?[ \t]+IE)[^\]]*[\)]?[^\]]*\]/i';
		$replace = array();
		if (preg_match_all($pattern, $ret_val, $matches)) {
			foreach ($matches[0] as $match) {
				if (preg_match($IE_tag_pattern, $match)) {
					$replace[] = trim($match);
				} else {
					$replace[] = '';
				}
			}
		}
		if (count($replace) > 0 && preg_match_all($pattern, $ret_val, $matches)) {
			$ret_val = str_replace($matches[0], $replace, $ret_val);
		}
		unset($replace);
		unset($matches);

		return $ret_val;
	}

	//**************************************************************************************
	// JavaScript Moved bottom
	//**************************************************************************************
	public function footer(){
		echo $this->foot_js_src;
	}

	//**************************************************************************************
	// footer cleaner
	//**************************************************************************************
	public function footer_cleaner($buffer) {
		$buffer = $this->_tag_trim($this->ob_handler($buffer));
		$buffer = apply_filters($this->plugin_name.'/pre_footer_cleaner', $buffer);
		if (!function_exists('str_get_html')) {
			$ret_val = apply_filters($this->plugin_name.'/footer_cleaner', $buffer);
			return $ret_val;
		}

		$ret_val    = '';
		$script_tag = '';
		$inline_js  = '';
		$other_tag  = '';

		$fotter_txt = preg_replace_callback(
			array('/(onclick=)(")([^"]*)(")/i', '/(onclick=)(\')([^\']*)(\')/i'),
			array(&$this, '_tag_replace'),
			$buffer
			);

		// Get Simple DOM Object
		$dom = str_get_html(
			'<div id="footer">' . $fotter_txt . '</div>'
			);
		if ($dom === false)
			return $buffer;

		// parse
		$inner_js = '';
		$js_src   = array();
		$inner_js_src = array();
		$elements = (array) $dom->find("div#footer *");
		foreach ($elements as $element) {
			switch ($element->tag) {
			case 'script':
				if (!isset($element->src)) {
					$inline_js .= $this->_remove_comment($element->innertext, 'js');
				} else {
					$src = $element->src;
					if (array_search( $src, (array) $this->options['remove_js']) === false) {
						if (strpos($src, $this->wp_url) === false) {
							$script_tag .= $this->_tag_trim($element->outertext);
						} else {
							$inner_js .= $this->_tag_trim($element->outertext);
							$js_src[] = preg_replace('/\.gz$/i', '', $src);
							$inner_js_src[] = $src;
						}
					}
				}
				break;
			default:
				$tag = $this->_tag_trim($element->outertext);
				if (strpos($other_tag, $tag) === false && ! preg_match('/^<\!\-+/', $tag))
					$other_tag .= $tag;
				break;
			}
		}
		unset($element);
		unset($elements);

		$dom->clear();
		unset($dom);

		$script_tag = $this->_tag_trim($script_tag);
		$inline_js  = trim($inline_js);
		if ($this->options['cache_enabled']) {
			if ($this->options['combined_js']) {
				$inner_js    = $this->_combined_javascript($js_src, $inline_js);
				$script_tag .= $inner_js;
				$inline_js   = '';
			} else {
				if ($this->options['js_minify']) {
					foreach ($inner_js_src as $path) {
						$real_path = realpath(preg_replace('/^([^\?]*)([\?]?.*)$/', "$1", str_replace($this->wp_url, ABSPATH, $path)));
						if ($real_path !== false && file_exists($real_path)) {
							list($content, $cache_path) = $this->_file_read($real_path, 'js');
							if (file_exists($cache_path)) {
								$cache_url = str_replace(ABSPATH, $this->wp_url, $cache_path);
								$inner_js = str_replace($path, $cache_url, $inner_js);
							}
						}
					}
				}
				$script_tag .= $inner_js;
				$inline_js   = $this->_combined_inline_javascript($inline_js);
			}
		} else {
			$script_tag .= $inner_js;
			$inline_js   = $this->_script_tag($inline_js, true);
		}

		// build!
		$ret_val .=
			$this->_tag_trim($other_tag) .
			$this->_tag_trim($script_tag) .
			$this->_tag_trim($inline_js) ;

		// dynamic css, js
		if ($this->options['dynamic'])
			$ret_val = $this->_dynamic_js_css($ret_val);

		// tag normalize
		$ret_val = $this->_tag_normalize($ret_val);
		$ret_val = str_replace('&#039;', "'", $ret_val);

		// add debug information
		if ($this->options['debug_mode'])
			$ret_val .= $this->_get_debug_info($buffer);

		$this->org_len += strlen(bin2hex($buffer));
		$this->ret_len += strlen(bin2hex($ret_val));
		$this->process_time += $this->_microtime_diff($this->mtime_start);

//		$ret_val .= $this->_process_time();

		if (preg_match('/<\/body>/i', $buffer)) {
			$ret_val .= '</body>';
		}
		if (preg_match('/<\/html>/i', $buffer)) {
			$ret_val .= '</html>';
		}

		if (!$this->_is_user_logged_in() && $this->options['paranoia_mode']) {
			$ret_val = $this->html_cleaner($ret_val);
		}

		$ret_val = apply_filters($this->plugin_name.'/footer_cleaner', $ret_val);
		return $ret_val;
	}

	//**************************************************************************************
	// WP_CONTENT_DIR
	//**************************************************************************************
	private function _wp_content_dir($path = '') {
		return (!defined('WP_CONTENT_DIR')
			? WP_CONTENT_DIR
			: ABSPATH . 'wp-content'
			) . $path;
	}

	//**************************************************************************************
	// is login?
	//**************************************************************************************
	private function _is_user_logged_in() {
		if (function_exists('is_user_logged_in')) {
			return is_user_logged_in();
		} else {
			global $user;
			return (!empty($user->ID));
		}
	}

	//**************************************************************************************
	// Is mobile ?
	//**************************************************************************************
	private function _is_mobile() {
		$is_mobile = $this->isKtai();

		if ( !$is_mobile && function_exists('bnc_is_iphone') ) {
			global $wptouch_plugin;

			$is_mobile = bnc_is_iphone();
			if ( $is_mobile && isset($wptouch_plugin) ) {
				$is_mobile = isset($wptouch_plugin->desired_view)
					? $wptouch_plugin->desired_view == 'mobile'
					: true;
			}
		}

		return ($is_mobile);
	}

	//**************************************************************************************
	// Get permalink
	//**************************************************************************************
	private function _get_permalink() {
		$url = function_exists('home_url') ? home_url() : get_option('home');
		if (! preg_match('|^(https?://[^/]*)|', $url, $host)) {
			$host[1] = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off')
				? 'http://'
				: 'https://' . $_SERVER['SERVER_NAME'];
		}
		$url = preg_replace( '/\?[^s].*$/i', '', $host[1] . $_SERVER['REQUEST_URI']);
		unset($host);
		return ($url);
	}

	//**************************************************************************************
	// DOM Element -> html tag
	//**************************************************************************************
	private function _dom_to_html($elements, $limit = false, $exclusion = '') {
		$html = '';
		$count = 0;
		$type = '';
		$tags = array();
		foreach ((array) $elements as $element) {
			$tag  = trim($element->outertext);
			$type = strtolower($element->tag);
			if (!($exclusion !== '' && preg_match($exclusion, $tag))) {
				if (strpos($html, $tag) === false) {
					switch($type) {
					case 'meta':
						if (isset($element->name)) {
							$name    = strtolower($element->name);
							$content = trim(
								isset($tags[$type][$name]) && isset($tags[$type][$name]['content'])
								? $tags[$type][$name]['content']
								: '');
							$contents = (
								!empty($content)
								? explode( ',', $content )
								: array() );
							if ( isset($element->content) ) {
								foreach((array) explode(',', $element->content) as $content ) {
									if (array_search($content, $contents) === false) {
										$contents[] = $content;
									}
								}
							}
							$content = implode( ',', $contents );
							unset( $contents );

							$tags[$type][$name] = array(
								'name'    => $name ,
								'content' => $content ,
								);
						} else {
							$html .= $tag . "\n";
						}
						break;

					case 'link':
						if (isset($element->rel)) {
							$name    = strtolower($element->rel);
							$content = (isset($tags[$type][$name]) && isset($tags[$type][$name]['content'])
								 ? $tags[$type][$name]['content']
								 : '');
							$content .= $tag . "\n";

							$tags[$type][$name] = array(
								'name'    => $name ,
								'content' => $content ,
								);
						} else {
							$html .= $tag . "\n";
						}
						break;

					default:
						$html .= $tag . "\n";
						break;
					}
				}

				if ($limit !== false && $count++ >= $limit)
					break;
			}
		}
		unset($element);
		unset($elements);

		foreach ((array) $tags as $tag_type => $contents) {
			switch($tag_type) {
			case 'meta':
				ksort( $contents );
				foreach ((array) $contents as $tag) {
					$html .= "<$type";
					foreach((array) $tag as $key => $val) {
						$html .= " $key=\"$val\"";
					}
					$html .= " />\n";
				}
				unset($tag);
				break;

			case 'link':
			default:
				foreach ((array) $contents as $tag) {
					$html .= $tag['content'];
				}
				unset($tag);
				break;
			}
		}
		unset($tags);
		unset($tag_types);

		$html = $this->_tag_trim($html);

		return $html;
	}

	private function _rdf_convert($elements, $limit = false, $exclusion = '') {
		$html = $this->_dom_to_html($elements, $limit, $exclusion);

		if (!empty($html)) {
			$html = str_replace(
				array(
					'rdf:rdf',
					'rdf:description',
					'rdf:parsetype',
					'foaf:holdsaccount',
					'foaf:onlineaccount',
					'foaf:accountname',
					'foaf:accountservicehomepage',
					),
				array(
					'rdf:RDF',
					'rdf:Description',
					'rdf:parseType',
					'foaf:holdsAccount',
					'foaf:OnlineAccount',
					'foaf:accountName',
					'foaf:accountServiceHomepage',
					),
				$html);
			$html = preg_replace(
				array(
					'/[\t ]+</',
					'/>[\t ]+/',
					),
				array(
					'<',
					'>',
					),
				$html);
		}

		return $html;
	}

	//**************************************************************************************
	// Get absolute url
	//**************************************************************************************
	private function _abs_url($path, $base_path = ''){
		if (preg_match('/^https?:\/\//i', $base_path))
			$base_path = str_replace($this->wp_url, ABSPATH, $base_path);
		$real_path = realpath($base_path . '/' . $path);
		if ( $real_path === false )
			$real_path = $base_path . '/' . $path;

		$abs_url = str_replace(ABSPATH, $this->wp_url, $real_path);
		$abs_url = str_replace('/./', '/', $abs_url);
		$abs_url = preg_replace('/(\/[^\/]*\/)\.\.(\/[^\/]*\/)/', '$2', $abs_url);

		return $abs_url;
	}

	private function _css_url_edit($content, $filename) {
		if (preg_match_all('/url[ \t]*\([\'"]?([^\)]*)[\'"]?\)/i', $content, $matches, PREG_SET_ORDER)) {
			$base_path = dirname($filename);
			$search = array();
			$replace = array();
			foreach ((array) $matches as $match) {
				if (! preg_match('/^https?:\/\//i', trim($match[1]))) {
					$abs_url   = $this->_abs_url(trim($match[1]), $base_path);
					$search[]  = $match[0];
					$replace[] = str_replace(trim($match[1]), $abs_url, $match[0]);
				}
			}
			$content = str_replace($search, $replace, $content);
			unset($match);
			unset($search);
			unset($replace);
		}
		unset ($matches);
		return $content;
	}

	//**************************************************************************************
	// cache filename
	//**************************************************************************************
	private function _cache_filename($filename, $type) {
		$cache_filename = '';
		if (!preg_match('/^https?:\/\//i', $filename)) {
			$filename = realpath($filename);
			$cache_filename = md5($filename . filemtime($filename));
			switch ($type) {
			case 'css':
				$cache_filename = $this->cache_path . 'css/' . $cache_filename . ($this->_base64_img_ok() ? '.b64' : '') . '.css';
				break;
			case 'js':
				$cache_filename = $this->cache_path . 'js/' . $cache_filename . '.js';
				break;
			default:
				$cache_filename = $this->cache_path . $cache_filename;
				break;
			}
		}
		return $cache_filename;
	}

	//**************************************************************************************
	// Read file
	//**************************************************************************************
	private function _file_read($filename, $type = false) {
		$content = false;
		$cache_filename = $this->_cache_filename($filename, $type);

		if (!file_exists($cache_filename)) {
			$src = 
				$type !== false && preg_match('/\.'.preg_quote($type, '/').'[\?]*[^\?]*$/i', $filename)
				? trim(preg_replace('/(\.'.preg_quote($type, '/').')([\?]*[^\?]*)$/i', '$1', str_replace($this->wp_url, ABSPATH, $filename)))
				: $filename
				;
			if (preg_match('/^https?:\/\//i', $src)) {
				$content = $this->_remote_get($src);

			} else {
				$src = realpath($src);
				if ($src !== false && file_exists($src)) {
					$content = @file_get_contents($src);
				} else {
					$content = $this->_remote_get($filename);
				}
			}

			switch ($type) {
			case 'css':
				$content = $this->_css_url_edit($content, $src);
				// URLs of images in CSS will be converted into the data scheme URIs
				if ($this->_base64_img_ok())
					$content = preg_replace_callback('/url\((https?:\/\/.*?\.(jpe?g|png|gif|bmp|ico)[^\)]*)\)/i', array(&$this, '_base64_replace'), $content);
				// Optimise CSS
				if ($this->options['css_optimise'])
					$content = $this->_css_optimise($content);
				$content = apply_filters($this->plugin_name.'/css_content', $content);
				break;

			case 'js':
				// Minified JavaScript
				if ($this->options['js_minify'])
					$content = $this->_js_minify($content);
				$content = apply_filters($this->plugin_name.'/js_content', $content);
				break;

			default:
				$content = apply_filters($this->plugin_name.'/content', $content);
				break;
			}
			$content = trim($content);
			$this->_file_write($cache_filename, $content, $this->options['gzip_on'] || HC_MAKE_GZ_FILE);

		} else {
			$content = @file_get_contents($cache_filename);
		}

		$this->org_len += strlen(bin2hex($content));
		return array($content, $cache_filename);
	}

	//**************************************************************************************
	// Read files
	//**************************************************************************************
	private function _files_read($files, $type = 'js') {
		$text = '';
		foreach ((array) $files as $filename) {
			list($content, $cache_path) = $this->_file_read($filename, $type);
			$text .= trim($content) . "\n\n";
		}
		$text = $this->_tag_trim($text);

		return $text . (!empty($text) ? "\n" : '');
	}

	//**************************************************************************************
	// Write file
	//**************************************************************************************
	private function _file_write($filename, $content = '', $gzip = true) {
		if (!empty($content)) {
			$this->ret_len += strlen(bin2hex($content));

			$handle = @fopen($filename, 'w');
			@fwrite($handle, $content);
			@fclose($handle);

			return ($gzip
				? $this->_file_gzip($filename, $content)
				: file_exists($filename) );

		} else {
			return false;
		}
	}

	//**************************************************************************************
	// Write gzip file
	//**************************************************************************************
	private function _file_gzip($filename, $content = '') {
		if (file_exists($filename) && file_exists($filename . '.gz'))
			if (filemtime($filename) < filemtime($filename . '.gz'))
				return true;

		if (function_exists('gzopen')) {
			if (empty($content)) {
				$handle  = @fopen($filename, 'r');
				$content = @fread($handle, filesize($filename));
				@fclose($handle);
			}

			if (!empty($content)) {
				$handle = @gzopen($filename . '.gz', 'w9');
				@gzwrite($handle, $content);
				@gzclose($handle);
			}

			return (file_exists($filename . '.gz'));

		} else {
			return false;
		}
	}

	//**************************************************************************************
	// JavaScript FrameWork (Prototype.js > scriptaculous > jQuery > jQuery.noConflict > mootools)
	//**************************************************************************************
	private function _js_framework($js_libs) {
		$prototype = isset($js_libs['prototype']);
		$jquery    = isset($js_libs['jquery']);
		$mootools  = isset($js_libs['mootools']);
		$js_libs_src = array();
		$wk_inner_js = '';
		$wk_outer_js = '';

		// Prototype.js 1.6.0.3
		if ($prototype) {
			list($src, $ver) = $this->_newer_version_src($js_libs['prototype']);
			if (!empty($src)) {
				if (version_compare($ver, '1.6.0.3', '<='))
					$src = $this->plugin_url . 'includes/js/prototype-1.6.0.3.min.js';

				$wk_outer_js .= $this->_script_tag($src);
			}
		}

		// scriptaculous 1.8.2
		if (isset($js_libs['scriptaculous/scriptaculous'])) {
			if (!$prototype) {
				$prototype = true;
				$src = $this->plugin_url . 'includes/js/prototype-1.6.0.3.min.js';
				$wk_outer_js .= $this->_script_tag($src);
			}
			$scriptaculous = array(
				'scriptaculous/scriptaculous' ,
				'scriptaculous/controls' ,
				'scriptaculous/dragdrop' ,
				'scriptaculous/effects' ,
				'scriptaculous/slider' ,
				'scriptaculous/sound' ,
				'scriptaculous/unittest' ,
				);
			foreach ($scriptaculous as $product) {
				if (isset($js_libs[$product])) {
					list($src, $ver) = $this->_newer_version_src($js_libs[$product]);
					if (!empty($src)) {
						if (version_compare($ver, '1.8.2', '<='))
							$src = $this->plugin_url . 'includes/js/' . $product . '.min.js';
						$wk_outer_js .= $this->_script_tag($src);
					}
				}
			}
			unset ($scriptaculous);
		}

		if ($jquery) {
			list($src, $ver) = $this->_newer_version_src($js_libs['jquery']);
			if (!empty($src)) {
				if (version_compare($ver, '1.4.2', '<'))
					$src = $this->plugin_url . 'includes/js/jquery-1.4.2.min.js';

				if ($prototype || $mootools || strpos($src, $this->wp_url) === false) {
					$wk_outer_js .= $this->_script_tag($src);
				} else {
					$js_libs_src[] = $src;
					$wk_inner_js .= $this->_script_tag($src);
				}

				// jQuery noConflict
				if ($prototype || $mootools) {
					$src = $this->plugin_url . 'includes/js/jquery.noconflict.js';
					if (strpos($src, $this->wp_url) === false) {
						$wk_outer_js .= $this->_script_tag($src);
					} else {
						$js_libs_src[] = $src;
						$wk_inner_js .= $this->_script_tag($src);
					}
				}
			}
		}

		// mootools 1.2.1
		if ($mootools) {
			list($src, $ver) = $this->_newer_version_src($js_libs['mootools']);
			if (!empty($src)) {
				if (version_compare($ver, '1.2.1', '<='))
					$src = $this->plugin_url . 'includes/js/mootools-1.2.1-core-yc.js';
				if ($prototype || $jquery || strpos($src, $this->wp_url) === false) {
					$wk_outer_js .= $this->_script_tag($src);
				} else {
					$js_libs_src[] = $src;
					$wk_inner_js .= $this->_script_tag($src);
				}
			}
		}

		return array($js_libs_src, $wk_inner_js, $wk_outer_js);
	}

	//**************************************************************************************
	// Combined CSS
	//**************************************************************************************
	private function _combined_css($styles, $css = '', $media = '') {
		$html = '';
		$longfilename = '';
		$files = array();

		if (empty($media))
			$media = $this->options['default_media'];

		foreach ((array) $styles as $style) {
			$src = trim(preg_replace('/(\.css|\.php)(\?[^\?]*)$/i', '$1', str_replace($this->wp_url, ABSPATH, $style)));
			if (file_exists($src)) {
				$filename = (preg_match('/\.css$/i', $src) ? $src : $style);
				$longfilename .= $filename . filemtime($src);
				$files[] = $filename;
			} else {
				$html .= $this->_css_tag($styles, $media);
			}
		}

		$md5_filename = md5($longfilename . $css);
		$longfilename = 'css/' . $md5_filename . ($this->_base64_img_ok() ? '.b64' : '') . '.css';
		$filename = $this->cache_path . $longfilename;
		if (! file_exists($filename) ) {
			$css = $this->_files_read($files, 'css') . "\n\n" . $css;
			// Optimise CSS
			if ($this->options['css_optimise'])
				$css = $this->_css_optimise($css);
			if (!empty($css))
				$this->_file_write($filename, $css, $this->options['gzip_on'] || HC_MAKE_GZ_FILE );
		}
		$fileurl = $this->cache_url . $longfilename;

		if (file_exists($filename))
			$html .= $this->_css_tag($fileurl, $media);

		return $html;
	}

	//**************************************************************************************
	// Combined inline CSS
	//**************************************************************************************
	private function _combined_inline_css($css, $media = 'all') {
		if (empty($css))
			return '';

		if (empty($media))
			$media = 'all';

		$html = '';
		$md5_filename = md5($css);
		$longfilename = 'css/'. $md5_filename . ($this->_base64_img_ok() ? '.b64' : '') . '.css';

		// Optimise CSS
		if ($this->options['css_optimise'])
			$css = $this->_css_optimise($css);

		$filename = $this->cache_path . $longfilename;
		if (!file_exists($filename) && !empty($css))
			$this->_file_write($filename, $css, $this->options['gzip_on'] || HC_MAKE_GZ_FILE);

		$fileurl = $this->cache_url . $longfilename;

		if (file_exists($filename))
			$html .= $this->_css_tag($fileurl, $media);

		return $html;
	}

	//**************************************************************************************
	// Combined JavaScript
	//**************************************************************************************
	private function _combined_javascript($javascripts, $script = '') {
		$html = '';
		$longfilename = '';
		$files = array();

		foreach ((array) $javascripts as $javascript) {
			$src = trim(preg_replace('/(\.js|\.php)(\?[^\?]*)$/i', '$1', str_replace($this->wp_url, ABSPATH, $javascript)));
			if (file_exists($src)) {
				$filename = (preg_match('/\.js$/i', $src) ? $src : $javascript);
				$longfilename .= $filename . filemtime($src);
				$files[] = $filename;
			} else {
				$html .= $this->_script_tag($javascript);
			}
		}

		$md5_filename = md5($longfilename . $script);
		$longfilename = 'js/' . $md5_filename . '.js';
		$filename = $this->cache_path . $longfilename;
		if (! file_exists($filename) ) {
			$script = $this->_files_read($files, 'js') . "\n\n" . $script;
			if ($this->options['js_minify'])
				$script = $this->_js_minify($script);		// Minified JavaScript
			if (!empty($script))
				$this->_file_write($filename, $script, $this->options['gzip_on'] || HC_MAKE_GZ_FILE);
		}

		$fileurl = $this->cache_url . $longfilename;

		if (file_exists($filename))
			$html .= $this->_script_tag($fileurl);

		return $html;
	}

	//**************************************************************************************
	// Combined inline JavaScript
	//**************************************************************************************
	private function _combined_inline_javascript($script) {
		if (empty($script))
			return '';

		$html = '';
		$md5_filename = md5($script);
		$longfilename = 'js/' . $md5_filename . '.js';

		// Minified JavaScript
		if ($this->options['js_minify'])
			$script = $this->_js_minify($script);

		$filename = $this->cache_path . $longfilename;
		if (!file_exists($filename) && !empty($script) )
			$this->_file_write($filename, $script, $this->options['gzip_on'] || HC_MAKE_GZ_FILE);

		$fileurl = $this->cache_url . $longfilename;

		if (file_exists($filename))
			$html .= $this->_script_tag($fileurl);

		return $html;
	}

	//**************************************************************************************
	// Remove comment
	//**************************************************************************************
	private function _remove_comment($text, $type) {
		$text = trim($text);

		$comment_pattern = '/^[ \t]*\/(?:\*(?:.|\n)*?\*\/|\/.*)/m';

		switch ($type) {
		case 'css':			// inline CSS
			$text = trim(preg_replace(
					array($comment_pattern, '/^[ \t]+/m', '/[ \t]+$/m') ,
					array('', '', '') ,
					$text)
				);
			break;

		case 'js':			// inline JavaScript
			$text = trim(preg_replace(
					array($comment_pattern, '/^[ \t]+/m', '/[ \t]+$/m', '/^<\!\-+/', '/-+>$/') ,
					array('', '', '', '', '') ,
					$text)
					);
			break;
		}

		return ($this->_tag_trim($text) . "\n");
	}

	//**************************************************************************************
	// Get Newer version
	//**************************************************************************************
	private function _newer_version_src($js_libs, $limit_ver = '') {
		$src = '';
		$ver = '0.0';
		foreach ((array) $js_libs as $key => $val) {
			if ( version_compare( $key, $ver, '>') ) {
				$src = $val;
				$ver = $key;
			}
		}

		return array($src, $ver);
	}

	//**************************************************************************************
	// Minified JavaScript
	//**************************************************************************************
	public function js_minify($buffer) {
		$js = $buffer;
		if ( class_exists('JSMin') ) {
			$js = JSMin::minify($js);
		} else if( class_exists('JSMinPlus') ) {
			$js = JSMinPlus::minify($js);
		}
		$js = trim($js);
		return $js . (!empty($js) ? "\n" : '');
	}

	private function _js_minify($buffer) {
		$js = $this->_tag_trim($buffer);
		if ($this->options['js_minify']) {
			$js = $this->js_minify($js);
		}
		return apply_filters($this->plugin_name.'/js_minify', $js);
	}

	//**************************************************************************************
	// Optimise CSS
	//**************************************************************************************
	public function css_optimise($buffer) {
		$css = $buffer;
		if (class_exists('Minify_CSS')) {
			$css = Minify_CSS::minify($css);
		}
		$css = trim($css);
		return $css . (!empty($css) ? "\n" : '');
	}

	public function css_import($css) {
		if (preg_match_all('/@import[ \t]*url[ \t]*\([\'"]?([^\)\'"]*)[\'"]?\);?/i', $css, $matches, PREG_SET_ORDER)) {
			$search = array();
			$replace = array();
			foreach ((array) $matches as $match) {
				$filename  = str_replace($this->wp_url, ABSPATH, trim($match[1]));
				list($content, $cache_path) = $this->_file_read(file_exists($filename) ? $filename : $match[1], 'css');
				$content = $this->_css_url_edit($content, $filename);
				if ($this->options['css_optimise']) {
					$content = $this->css_optimise($content);
				}
				if (preg_match('/@import[ \t]*url[ \t]*\([\'"]?[^\)\'"]*[\'"]?\);?/i', $content)) {
					$content = $this->css_import($content);
				}
				$search[]  = $match[0];
				$replace[] = $content;
			}
			$css = str_replace($search, $replace, $css);
			unset($match);
			unset($search);
			unset($replace);
		}
		unset($matches);

		return $css;
	}

	private function _css_optimise($buffer, $merge = true) {
		$css = $this->_tag_trim($buffer);
		if ($this->options['css_optimise']) {
			$css = $this->css_optimise($css);
		}
		if ( $merge ) {
			$css = str_replace("\n\n", "\n", $this->css_import($css));
		}
		$css = trim($css);

		return apply_filters($this->plugin_name.'/css_optimise', $css);
	}

	private function _css_import($css) {
		return $this->css_import($css);
	}

	//**************************************************************************************
	// remote get
	//**************************************************************************************
	private function _remote_get( $url, $args = array() ){
		if ( function_exists('wp_remote_get') ) {
			$ret = wp_remote_get( $url, $args );
			if ( is_array($ret) && isset($ret["body"]) && !empty($ret["body"]) )
				return $ret["body"];
		} else {
			return @file_get_contents($url);
		}

		return false;
	}

	//**************************************************************************************
	// BASE64 IMAGE
	//**************************************************************************************
	private function _base64_replace($matches) {
		$img_enc = $this->_base64_img($matches[1]);

		return (
			$img_enc != $matches[1]
			? str_replace($matches[1], $img_enc, $matches[0])
			: $matches[0]
			);
	}
	private function _get_image_mime($file_name) {
		$img_mime = false;

		if (preg_match('/https?:\/\/.*?\.(jpe?g|png|gif|bmp|ico)/i', $file_name, $matches)) {
			$img_type = strtolower(trim($matches[1]));
			if ($img_type == 'jpg')
				$img_type = 'jpeg';
			elseif ($img_type == 'ico')
				$img_type = 'x-icon';
			$img_mime = 'image/'.$img_type;
		} elseif (false !== ($img_type = exif_imagetype($file_name))) {
			$img_mime = image_type_to_mime_type($img_type);
		}
		unset($matches);

		return $img_mime;
	}
	private function _base64_img($img_url) {
		if (!$this->_base64_img_ok())
			return $img_url;

		if (isset($img_urls[$img_url])) {
			$img_enc = $img_urls[$img_url];
		} else {
			$img_bin  = $this->_remote_get($img_url);
			$img_enc  = base64_encode($img_bin);
			if (false !== ($img_mime = $this->_get_image_mime($img_url))) {
				$img_enc = (
					$img_mime != ''
					? "data:{$img_mime};base64,{$img_enc}"
					: $img_url
					);
				$img_enc = (
					$img_enc != $img_url && strlen($img_enc) <= self::IMG_BASE64_MAX_SIZE
					? $img_enc
					: $img_url
					);
			} else {
				$img_enc = $img_url;
			}
			$img_urls[$img_url] = $img_enc;
		}

		return $img_enc;
	}

	private function _base64_img_ok() {
		global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone;

		if ($this->options['img_base64'])
			return ($is_gecko || $is_opera || $is_safari || $is_chrome);
		else
			return false;
	}

	//**************************************************************************************
	// Create cache dir
	//**************************************************************************************
	private function _create_cache_dir($cache_dir = '') {
		if (empty($cache_dir))
			$cache_dir  = HC_CACHE_DIR;
		$cache_dir = $this->_wp_content_dir('/' . trailingslashit($cache_dir) );

		$mode = 0777;
		if( !file_exists(dirname($cache_dir)) )
			@mkdir(dirname($cache_dir), $mode);
		if( !file_exists($cache_dir) )
			@mkdir($cache_dir, $mode);
		if( !file_exists($cache_dir.'/css/') )
			@mkdir($cache_dir.'/css/', $mode);
		if( !file_exists($cache_dir.'/js/') )
			@mkdir($cache_dir . '/js/', $mode);

		return (file_exists($cache_dir) ? $cache_dir : false);
	}

	//**************************************************************************************
	// Remove cache file
	//**************************************************************************************
	private function _remove_cache_file($cache = 'cache', $plugin = 'head-cleaner') {
		$cache_dir = ( !empty($this->cache_path)
			? $this->cache_path
			: $this->_wp_content_dir("/$cache/$plugin/")
			);
		$this->_remove_all_file($cache_dir . 'css');
		$this->_remove_all_file($cache_dir . 'js');
	}

	//**************************************************************************************
	// Remove files
	//**************************************************************************************
	private function _remove_all_file($dir, $rmdir = false) {
		if(file_exists($dir)) {
			if($objs = glob($dir."/*")) {
				foreach((array) $objs as $obj) {
					is_dir($obj)
					? $this->_remove_all_file($obj, true)
					: @unlink($obj);
				}
				unset($objs);
			}
			if ($rmdir) rmdir($dir);
		}
	}

	//**************************************************************************************
	// Create .htaccess
	//**************************************************************************************
	private function _create_htaccess($dir) {
		if (!file_exists($dir))
			return false;

		$rewrite_base = trailingslashit(str_replace(ABSPATH, '/', $dir));

		$text   = '# BEGIN Head Cleaner' . "\n"
			. '<IfModule mod_rewrite.c>' . "\n"
			. 'RewriteEngine On' . "\n"
			. 'RewriteBase ' . $rewrite_base . "\n"
			. 'RewriteCond %{HTTP:Accept-Encoding} gzip' . "\n"
			. 'RewriteCond %{REQUEST_FILENAME} "\.(css|js)$"' . "\n"
			. 'RewriteCond %{REQUEST_FILENAME} !"\.gz$"' . "\n"
			. 'RewriteCond %{REQUEST_FILENAME}.gz -s' . "\n"
			. 'RewriteRule .+ %{REQUEST_URI}.gz [L]' . "\n"
			. '</IfModule>' . "\n"
			. '# END Head Cleaner' . "\n";
		$filename = trailingslashit($dir) . '.htaccess';

		if ( $this->options['gzip_on'] ) {
			if (!file_exists($filename)) {
				return $this->_file_write($filename, $text, false);
			} else {
				list($content, $cache_path) = $this->_file_read($filename);
				if ($content !== false) {
					if (strpos($content, '# BEGIN Head Cleaner') === false && strpos($content, 'RewriteRule .+ %{REQUEST_URI}.gz') === false) {
						$text = $content . "\n" . $text;
						return $this->_file_write($filename, $text, false);
					} else {
						return true;
					}
				} else {
					return false;
				}
			}
		} else {
			if ( file_exists($filename) ) {
				list($content, $cache_path) = $this->_file_read($filename);
				if ($content !== false) {
					$content = trim(preg_replace('/# BEGIN Head Cleaner.*# END Head Cleaner/ism', '', $content));
					if ( $text === $content || $content === '') {
						@unlink($filename);
						return true;
					} else {
						return $this->_file_write($filename, $content . "\n", false);
					}
				} else {
					return false;
				}
			} else {
				return true;
			}
		}
	}

	//**************************************************************************************
	// Add Admin Menu
	//**************************************************************************************
	public function admin_menu() {
		$this->addOptionPage(__('Head Cleaner'), array($this, 'option_page'));
		add_action('admin_print_scripts-'.$this->admin_hook['option'], array($this,'add_admin_scripts'));
		add_action('admin_head-'.$this->admin_hook['option'], array($this,'add_admin_head'));
	}

	public function plugin_setting_links($links, $file) {
		if (method_exists($this, 'addPluginSettingLinks')) {
			$links = $this->addPluginSettingLinks($links, $file);
		} else {
			$this_plugin = plugin_basename(__FILE__);
			if ($file == $this_plugin) {
				$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
				array_unshift($links, $settings_link); // before other links
			}
		}
		return $links;
	}

	//**************************************************************************************
	// Show Option Page
	//**************************************************************************************
	public function add_admin_scripts() {
		wp_enqueue_script('jquery');
	}

	public function add_admin_head() {
		$out = <<< EOT
<style type="text/css">
.optiontable td {line-height:25px;}
</style>
<script type="text/javascript">
jQuery(function(a){if(a("#cache_enabled").attr("checked"))a("td.more-options").show();else a("td.more-options").hide();a("#cache_enabled").click(function(){if(a("#cache_enabled").attr("checked"))a("td.more-options").show();else a("td.more-options").hide()});if(a("#add_ogp_tag").attr("checked"))a("td.ogp-options").show();else a("td.ogp-options").hide();a("#add_ogp_tag").click(function(){if(a("#add_ogp_tag").attr("checked"))a("td.ogp-options").show();else a("td.ogp-options").hide()})});
</script>
EOT;

/*
jQuery(function($){
	if ($('#cache_enabled').attr('checked'))
		$('td.more-options').show();
	else
		$('td.more-options').hide();
	$('#cache_enabled').click(function(){
		if ($('#cache_enabled').attr('checked'))
			$('td.more-options').show();
		else
			$('td.more-options').hide();
	});

	if ($('#add_ogp_tag').attr('checked'))
		$('td.ogp-options').show();
	else
		$('td.ogp-options').hide();
	$('#add_ogp_tag').click(function(){
		if ($('#add_ogp_tag').attr('checked'))
			$('td.ogp-options').show();
		else
			$('td.ogp-options').hide();
	});
});
*/
		echo $out;
	}

	private function _checkbox($id, $style = 'margin-right:0.5em;', $flag = '') {
		if ( empty($flag) )
			$flag = is_bool($this->options[$id]) ? $this->options[$id] : false;
		return sprintf(
			'<input type="checkbox" name="%1$s" id="%1$s" value="on" %2$s %3$s/>' ,
			$id, 
			( !empty($style) ? 'style="'.$style.'"' : '' ) ,
			( $flag ? 'checked="true" ' : '' )
			);
	}

	private function _input_text($id, $style = 'margin-left:0.5em;', $value = '' ) {
		if ( empty($value) )
			$value = is_string($this->options[$id]) ? $this->options[$id] : '';
		return sprintf(
			'<input type="text" name="%1$s" id="%1$s" %2$s value="%3$s" />' ,
			$id, 
			( !empty($style) ? "style=\"{$style}\"" : '' ) ,
			$value
			);
	}

	public function option_page() {
		if ($this->_chk_filters_update()) {
			$this->options['filters'] = $this->filters;
			$this->options['head_js'] = $this->head_js;
		}

		$iv = new InputValidator('POST');
		$iv->set_rules('options_update', 'required');
		$iv->set_rules('remove_cache',   'required');
		$iv->set_rules('options_delete', 'required');
		if ( !is_wp_error($iv->input('options_update')) ) {
			if ($this->wp25)
				check_admin_referer("update_options", "_wpnonce_update_options");

			// Update options
			$this->_options_update($_POST);
			$this->note .= "<strong>".__('Done!', $this->textdomain_name)."</strong>";

		} elseif( !is_wp_error($iv->input('remove_cache')) ) {
			if ($this->wp25)
				check_admin_referer("remove_cache", "_wpnonce_remove_cache");

			// Remove all cache files
			$this->_remove_cache_file();
			$this->note .= "<strong>".__('Done!', $this->textdomain_name)."</strong>";

		} elseif( !is_wp_error($iv->input('options_delete')) ) {
			if ($this->wp25)
				check_admin_referer("delete_options", "_wpnonce_delete_options");

			// options delete
			$this->_delete_settings();
			$this->note .= "<strong>".__('Done!', $this->textdomain_name)."</strong>";
			$this->error++;

		} else {
			$this->activation();
		}
		unset($iv);

		$out  = '';

		// Add Options
		$out .= '<div class="wrap">'."\n";
		$out .= '<form method="post" id="update_options" action="'.$this->admin_action.'">'."\n";
		$out .= '<div id="icon-options-general" class="icon32"><br></div>';
		$out .= '<h2>'.__('Head Cleaner Options', $this->textdomain_name).'</h2>'."\n";
		if ($this->wp25)
			$out .= $this->makeNonceField("update_options", "_wpnonce_update_options", true, false);

		$out .= '<table class="optiontable form-table" style="margin-top:0;"><tbody>'."\n";

		$out .= '<tr>';
		$out .= '<td>';
		$out .= $this->_checkbox('cache_enabled');
		$out .= __('CSS and JavaScript are cached on the server.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td class="more-options">';
		$out .= $this->_checkbox('dynamic');
		$out .= __('CSS and JS are dynamically generated.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td class="more-options">';
		$out .= __('Default media attribute applied to CSS.', $this->textdomain_name);
		$out .= $this->_input_text('default_media');
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td class="more-options">';
		$out .= $this->_checkbox('combined_css');
		$out .= __('Two or more CSS is combined.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td class="more-options">';
		$out .= $this->_checkbox('css_optimise');
		$out .= __('CSS is optimized.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td class="more-options">';
		$out .= $this->_checkbox('img_base64');
		$out .= __('URLs of images in CSS will be converted into the data scheme URIs.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td class="more-options">';
		$out .= $this->_checkbox('combined_js');
		$out .= __('Two or more JavaScript is combined.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td class="more-options">';
		$out .= $this->_checkbox('js_minify');
		$out .= __('JavaScript is minified.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td class="more-options">';
		$out .= $this->_checkbox('foot_js');
		$out .= __('Bottom JavaScript is combined, too.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td>';
		$out .= $this->_checkbox('js_move_foot');
		$out .= __('Put JavaScripts at the Bottom.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td>';
		$out .= $this->_checkbox('use_ajax_libs');
		$out .= __('Use Google Ajax Libraries.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td>';
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td>';
		$out .= $this->_checkbox('xml_declaration');
		$out .= __('Add XML Declaration.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td>';
		$out .= $this->_checkbox('canonical_tag');
		$out .= __('Add canonical tag.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td>';
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td>';
		$out .= $this->_checkbox('add_ogp_tag');
		$out .= __('Add OGP(Open Graph Protocol) tags.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td colspan="2" class="ogp-options">';
		$out .= __('OGP default image URL.', $this->textdomain_name);
		$out .= $this->_input_text('ogp_default_image', 'width:256px;margin-left:0.5em;');
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td class="ogp-options" colspan="3">';
		$out .= '<span style="margin-right:.5em;">';
		$out .= __('og:type (top page)', $this->textdomain_name);
		$out .= $this->_input_text('og_type_top');
		$out .= '</span>';
		$out .= '<span style="margin-right:.5em;">';
		$out .= __('og:locale', $this->textdomain_name);
		$out .= $this->_input_text('og_locale');
		$out .= '</span>';
		$out .= '<span style="margin-right:.5em;">';
		$out .= __('fb:admins', $this->textdomain_name);
		$out .= $this->_input_text('fb_admins');
		$out .= '</span>';
		$out .= '<span style="margin-right:.5em;">';
		$out .= __('fb:app_id', $this->textdomain_name);
		$out .= $this->_input_text('fb_app_id');
		$out .= '</span>';
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td>';
		$out .= $this->_checkbox('wp_generator');
		$out .= __('Remove generator tag.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td>';
		$out .= $this->_checkbox('rsd_link');
		$out .= __('Remove RSD link tag.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td>';
		$out .= $this->_checkbox('wlwmanifest_link');
		$out .= __('Remove wlwmanifest link tag.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td>';
		$out .= $this->_checkbox('ie_conditional');
		$out .= __('Remove IE Conditional Tag.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td>';
		$out .= '</td>';
		$out .= '<td>';
//		$out .= $this->_checkbox('gzip_on');
//		$out .= __('gzip compress to CSS and JS.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td>';
		$out .= $this->_checkbox('add_last_modified');
		$out .= __('Add Last modified.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '<td colspan="2">';
		$out .= $this->_checkbox('paranoia_mode');
		$out .= __('Enabling "<strong>paranoia mode</strong>". Cut waste from the HTML and tries to minimize it.', $this->textdomain_name);
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<td>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= $this->_checkbox('debug_mode');
		$out .= __('Debug mode', $this->textdomain_name);
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '</tbody></table>';

		// Active Filters
		$out .= "<div style=\"margin-top:2em;\" id=\"active_filters\">\n";
		$out .= '<h3>' . __('Active Filters', $this->textdomain_name) . '</h3>'."\n" ;
		$out .= '<table><tbody>'."\n";
		$out .= '<tr>';
		$out .= '<th style="padding:0 .5em;">' . __("Don't process!", $this->textdomain_name) . '</th>'."\n";
		$out .= '<th style="padding:0 .5em;">' . __('Remove', $this->textdomain_name) . '</th>'."\n";
		$out .= '<th>' . __('Head filters', $this->textdomain_name) . '</th>'."\n";
		if ($this->options['debug_mode'])
			$out .= '<th>' . __('Priority', $this->textdomain_name) . '</th>'."\n";
		$out .= '</tr>'."\n";
		$head_filters = array();
		foreach ((array) $this->options['filters']['wp_head'] as $function_name => $priority) {
			if ($priority < self::PRIORITY) {
				if (isset($this->options['priority']['wp_head'][$function_name]))
					$priority = (int) $this->options['priority']['wp_head'][$function_name];
				if (!isset($head_filters[$priority]))
					$head_filters[$priority] = array();
				$head_filters[$priority][] = $function_name;
			}
		}
		ksort($head_filters, SORT_NUMERIC);
		$i = 0;
		foreach ($head_filters as $priority => $filters) {
			foreach ($filters as $function_name){
				if ( $this->function_enabled($function_name) ) {
					$out .= '<tr>';
					$out .= '<th><input type="checkbox" name="head_filters['.$i.']" value="'.$function_name.'"'.($priority > self::PRIORITY ? ' checked="true"' : '').' /></th>';
					$out .= '<th><input type="checkbox" name="head_remove['.$i.']" value="'.$function_name.'"'.($priority <= 0 ? ' checked="true"' : '').' /></th>';
					$out .= '<td>'.$function_name.'</td>';
					if ($this->options['debug_mode'])
						$out .= '<td>( '.$priority.' )</td>';
					$out .= '</tr>'."\n";
					$i++;
				}
			}
		}
		unset($filters);
		unset($head_filters);

		if ($this->options['foot_js'] === true) {
			$out .= '<tr><td colspan="3">&nbsp;</td></tr>'."\n";
			$out .= '<tr>';
			$out .= '<th style="padding:0 .5em;">' . __("Don't process!", $this->textdomain_name) . '</th>'."\n";
			$out .= '<th style="padding:0 .5em;">' . __('Remove', $this->textdomain_name) . '</th>'."\n";
			$out .= '<th>' . __('Bottom filters', $this->textdomain_name) . '</th>'."\n";
			if ($this->options['debug_mode'])
				$out .= '<th>' . __('Priority', $this->textdomain_name) . '</th>'."\n";
			$out .= '</tr>'."\n";
			$footer_filters = array();
			foreach ((array) $this->options['filters']['wp_footer'] as $function_name => $priority) {
				if ($priority < self::PRIORITY) {
					if (isset($this->options['priority']['wp_footer'][$function_name]))
						$priority = (int) $this->options['priority']['wp_footer'][$function_name];
					if (!isset($footer_filters[$priority]))
						$footer_filters[$priority] = array();
					$footer_filters[$priority][] = $function_name;
				}
			}
			ksort($footer_filters, SORT_NUMERIC);
			$i = 0;
			foreach ($footer_filters as $priority => $filters) {
				foreach ($filters as $function_name){
					if ( $this->function_enabled($function_name) ) {
						$out .= '<tr>';
						$out .= '<th><input type="checkbox" name="foot_filters['.$i.']" value="'.$function_name.'"'.($priority > self::PRIORITY ? ' checked="true"' : '').' /></th>';
						$out .= '<th><input type="checkbox" name="foot_remove['.$i.']" value="'.$function_name.'"'.($priority <= 0 ? ' checked="true"' : '').' /></th>';
						$out .= '<td>'.$function_name.'</td>';
						if ($this->options['debug_mode'])
							$out .= '<td>( '.$priority.' )</td>';
						$out .= '</tr>'."\n";
						$i++;
					}
				}
			}
			unset($filters);
			unset($footer_filters);
		}
		$out .= '</tbody></table>';
		$out .= '</div>'."\n";

		// Active JavaScripts
		$out .= '<div style="margin-top:2em;" id="active_javascripts">'."\n";
		$out .= '<h3>' . __('Active JavaScripts', $this->textdomain_name) . '</h3>'."\n" ;
		$out .= '<table><tbody>'."\n";
		$out .= '<tr>';
		$out .= '<th style="padding:0 .5em;">' . __('Move to footer', $this->textdomain_name) . '</th>'."\n";
		$out .= '<th style="padding:0 .5em;">' . __('Remove', $this->textdomain_name) . '</th>'."\n";
		$out .= '<th>' . __('JavaScripts', $this->textdomain_name) . '</th>'."\n";
		foreach ($this->options['head_js'] as $javascript => $value) {
			$remove = (array_search( $javascript, (array)$this->options["remove_js"] ) !== false);
			$out .= '<tr>';
			$out .= '<th><input type="checkbox" name="head_js['.$i.']" value="'.$javascript.'"'.($value === false  ? ' checked="true"' : '').' /></th>';
			$out .= '<th><input type="checkbox" name="remove_js['.$i.']" value="'.$javascript.'"'.($remove !== false ? ' checked="true"' : '').' /></th>';
			$out .= '<td>'.$javascript.'</td>';
			$i++;
		}
		$out .= '</tbody></table>';
		$out .= '</div>'."\n";

		// Add Update Button
		$out .= '<p style="margin-top:1em">';
		$out .= '<input type="submit" name="options_update" class="button-primary" value="'.__('Update Options', $this->textdomain_name).' &raquo;" class="button" />';
		$out .= '</p>';
		$out .= '</form></div>'."\n";

		// Cache Delete
		$out .= '<div class="wrap" style="margin-top:2em;">'."\n";
		$out .= '<h2>' . __('Remove all cache files', $this->textdomain_name) . '</h2>'."\n";
		$out .= '<form method="post" id="remove_cache" action="'.$this->admin_action.'">'."\n";
		if ($this->wp25)
			$out .= $this->makeNonceField("remove_cache", "_wpnonce_remove_cache", true, false);
		$out .= '<p>' . __('All cache files are removed.', $this->textdomain_name) . '</p>';
		$out .= '<input type="submit" name="remove_cache" class="button-primary" value="'.__('Remove All Cache Files', $this->textdomain_name).' &raquo;" class="button" />';
		$out .= '</form></div>'."\n";

		// Options Delete
		$out .= '<div class="wrap" style="margin-top:2em;">'."\n";
		$out .= '<h2>' . __('Uninstall', $this->textdomain_name) . '</h2>'."\n";
		$out .= '<form method="post" id="delete_options" action="'.$this->admin_action.'">'."\n";
		if ($this->wp25)
			$out .= $this->makeNonceField("delete_options", "_wpnonce_delete_options", true, false);
		$out .= '<p>' . __('All the settings of &quot;Head Cleaner&quot; are deleted.', $this->textdomain_name) . '</p>';
		$out .= '<input type="submit" name="options_delete" class="button-primary" value="'.__('Delete Options', $this->textdomain_name).' &raquo;" class="button" />';
		$out .= '</form></div>'."\n";

		// Output
		echo ( !empty($this->note)
			? '<div id="message" class="updated fade"><p>'.$this->note.'</p></div>'
			: '' );
		echo "\n";

		echo ( $this->error == 0 ? $out : '' );
		echo "\n";

	}

	//**************************************************************************************
	// options update
	//**************************************************************************************
	private function _checkbox_val( $check_array, $check_key, $check_val = 'on' ) {
		return (isset($check_array[$check_key]) && $check_array[$check_key] === $check_val);
	}
	private function _options_update($recv_param) {
		$fields = array(
			'head_js',
			'remove_js',
			'head_filters',
			'head_remove',
			'foot_filters',
			'foot_remove',
			'xml_declaration',
			'ie_conditional',
			'canonical_tag',
			'add_ogp_tag',
			'ogp_default_image',
			'og_type_top',
			'og_locale',
			'fb_admins',
			'fb_app_id',
			'add_last_modified',
			'foot_js',
			'dynamic',
			'js_move_foot',
			'cache_enabled',
			'combined_css',
			'css_optimise',
			'default_media',
			'img_base64',
			'combined_js',
			'js_minify',
			'paranoia_mode',
			'gzip_on',
			'use_ajax_libs',
			'debug_mode',
			'wp_generator',
			'rsd_link',
			'wlwmanifest_link',
			);

		$iv = new InputValidator($recv_param);
		foreach ( $fields as $field ) {
			switch ($field) {
			case 'head_js':
			case 'remove_js':
			case 'head_filters':
			case 'head_remove':
			case 'foot_filters':
			case 'foot_remove':
				$iv->set_rules($field, array('trim','esc_html','required'));
				break;
			case 'ogp_default_image':
			//	$iv->set_rules($field, array('trim','esc_html','url'));
			//	break;
			case 'og_type_top':
			case 'og_locale':
			case 'fb_admins':
			case 'fb_app_id':
				$iv->set_rules($field, array('trim','esc_html'));
				break;
			case 'default_media':
				$iv->set_rules($field, array('trim','esc_html'));
				break;
			default:
				$iv->set_rules($field, 'bool');
			}
		}
		$options = $iv->input($fields);
		$options['remove_js'] = is_wp_error($options['remove_js']) ? array() : (array)$options['remove_js'];
		unset($iv);

		// head js
		$head_js   = is_wp_error($options['head_js']) ? array() : (array)$options['head_js'];
		unset($options['head_js']);
		if ( function_exists('dbgx_trace_var') ) {
			dbgx_trace_var($head_js);
		}
		foreach ( (array) $this->options['head_js'] as $javascript => $value ) {
			if ( array_search($javascript, $head_js) !== false )
				$this->options['head_js'][$javascript] = false;
			else
				$this->options['head_js'][$javascript] = true;
		}
		unset($head_js);

		// wp_head
		$tag = 'wp_head';
		$all_filters  = array_merge(
			(array)$this->options['filters'][$tag],
			array(
				'wp_generator' => 10 ,
				'rsd_link' => 10 ,
				'wlwmanifest_link' => 10 ,
			));
		$head_filters = is_wp_error($options['head_filters']) ? array() : (array)$options['head_filters'];
		$head_remove  = is_wp_error($options['head_remove'])  ? array() : (array)$options['head_remove'];
		unset($options['head_filters']);
		unset($options['head_remove']);
		if ( function_exists('dbgx_trace_var') ) {
			dbgx_trace_var($head_filters);
			dbgx_trace_var($head_remove);
		}
		foreach ( $all_filters as $function_name => $priority ) {
			switch ($function_name) {
			case 'wp_generator':
			case 'rsd_link':
			case 'wlwmanifest_link':
				if ( $options[$function_name] )
					$this->options['priority'][$tag][$function_name] = -1;
				elseif ( isset($this->options['priority'][$tag][$function_name]) )
					unset($this->options['priority'][$tag][$function_name]);
				break;
			default:
				if ( array_search($function_name, $head_filters) !== false )
					$this->options['priority'][$tag][$function_name] = self::PRIORITY + 1;
				elseif ( array_search($function_name, $head_remove) !== false )
					$this->options['priority'][$tag][$function_name] = -1;
				elseif ( isset($this->options['priority'][$tag][$function_name]) )
					unset($this->options['priority'][$tag][$function_name]);
			}
			if ( !$this->function_enabled($function_name) && isset($this->options['filters'][$tag][$function_name]) )
				unset($this->options['filters'][$tag][$function_name]);
		}
		unset($all_filters);
		unset($head_filters);
		unset($head_remove);

		// wp_footer
		$tag = 'wp_footer';
		$all_filters  = (array)$this->options['filters'][$tag];
		$foot_filters = is_wp_error($options['foot_filters']) ? array() : (array)$options['foot_filters'];
		$foot_remove  = is_wp_error($options['foot_remove'])  ? array() : (array)$options['foot_remove'];
		unset($options['foot_filters']);
		unset($options['foot_remove']);
		if ( function_exists('dbgx_trace_var') ) {
			dbgx_trace_var($foot_filters);
			dbgx_trace_var($foot_remove);
		}
		foreach ( $all_filters as $function_name => $priority ) {
			if ( array_search($function_name, $foot_filters) !== false )
				$this->options['priority'][$tag][$function_name] = self::PRIORITY + 1;
			elseif ( array_search($function_name, $foot_remove) !== false )
				$this->options['priority'][$tag][$function_name] = -1;
			elseif ( isset($this->options['priority'][$tag][$function_name]) )
				unset($this->options['priority'][$tag][$function_name]);
			if ( !$this->function_enabled($function_name) && isset($this->options['filters'][$tag][$function_name]) )
				unset($this->options['filters'][$tag][$function_name]);
		}
		unset($all_filters);
		unset($foot_filters);
		unset($foot_remove);

		$options_org = $this->options;
		foreach ( $options_org as $key => $option ) {
			if ( isset($options[$key]) && !is_wp_error($options[$key]) && $option !== $options[$key] ) {
				$this->options[$key] = $options[$key];
			}
			switch ($key) {
			case 'ogp_default_image':
				if ( is_wp_error($option) )
					$this->options[$key] = '';
				break;
			}
		}
		if ( function_exists('dbgx_trace_var') ) {
			dbgx_trace_var($this->options);
		}
		unset($options);

		// options update
		$this->updateOptions();

		// create .htaccess file
		$cache_dir = $this->_create_cache_dir();
		if ( $cache_dir !== false )
			$this->_create_htaccess($cache_dir);

		return $this->options;
	}

	//**************************************************************************************
	// Delete Settings
	//**************************************************************************************
	private function _delete_settings() {
		$this->deleteOptions();
		//$this->_remove_cache_file();
		$this->options = $this->_init_options();

		return $this->options;
	}

	//**************************************************************************************
	// Get function name
	//**************************************************************************************
	private function _get_function_name($function) {
		$retval = (is_object($function[0])
			? (get_class($function[0]) !== false ? get_class($function[0]) : $function[0]) . '::' . $function[1]
			: $function );
		return (!is_object($retval) && !is_array($retval) ? $retval : false);
	}

	//**************************************************************************************
	// Get Filters
	//**************************************************************************************
	private function _get_filters($tag = '') {
		global $wp_filter;

		if (empty($tag) && function_exists('current_filter'))
			$tag = current_filter();

		if (!isset($this->filters[$tag]))
			$this->filters[$tag] = array();

		$active_filters = (isset($wp_filter[$tag])
			? (array) $wp_filter[$tag]
			: array());
		foreach ($active_filters as $priority => $filters) {
			foreach ($filters as $filter) {
				$function_name = $this->_get_function_name($filter['function']);
				if ( $this->function_enabled($function_name) ) {
					if (!isset($this->filters[$tag]))
						$this->filters[$tag] = array();
					$this->filters[$tag][$function_name] = $priority;
				}
			}
		}
		unset($active_filters);

		return $this->filters;
	}

	//**************************************************************************************
	// Filters update Check
	//**************************************************************************************
	private function _chk_filters_update(){
		$retval = false;
		foreach ( $this->filters as $tag => $filters ) {
			if ( isset($this->options['filters'][$tag]) ) {
				foreach ( $filters as $function_name => $priority) {
					$retval = ( !isset($this->options['filters'][$tag][$function_name]) );
					if ($retval)
						break;
				}
			} else {
				$retval = true;
				break;
			}
		}
		unset ($filters);

		foreach ( $this->head_js as $key => $val ) {
			if ( is_array($this->options['head_js']) && !isset($this->options['head_js'][$key]) ) {
				$retval = true;
				break;
			}
		}

		return $retval;
	}

	//**************************************************************************************
	// Filters priority change
	//**************************************************************************************
	private function _change_filters_priority($tag = ''){
		global $wp_filter;

		if (empty($tag) && function_exists('current_filter'))
			$tag = current_filter();

		$active_filters = (isset($wp_filter[$tag])
			? $wp_filter[$tag]
			: array() );
		$custom_priority = (isset($this->options['priority'][$tag])
			? $this->options['priority'][$tag]
			: array() );
		foreach ($this->no_conflict as $function_name) {
			$custom_priority[$function_name] = self::PRIORITY + 1;
		}
		foreach ($active_filters as $priority => $filters) {
			foreach ($filters as $filter) {
				$function_name = $this->_get_function_name($filter['function']);
				if ( isset($custom_priority[$function_name]) && $custom_priority[$function_name] != $priority) {
					remove_filter( $tag, $filter['function'], $priority);
					if ($custom_priority[$function_name] > 0)
						add_filter( $tag, $filter['function'], $custom_priority[$function_name]);
				}
			}
		}
		unset($custom_priority);
		unset($active_filters);
	}

	//**************************************************************************************
	// get site url
	//**************************************************************************************
	private function _get_site_url( $blog_id = null, $path = '', $scheme = null ) {
		if (function_exists('get_site_url'))
			return get_site_url($blog_id, $path, $scheme);

		// should the list of allowed schemes be maintained elsewhere?
		$orig_scheme = $scheme;
		if ( !in_array( $scheme, array( 'http', 'https' ) ) ) {
			$force_ssl_login = function_exists('force_ssl_login') && force_ssl_login();
			$force_ssl_admin = function_exists('force_ssl_admin') && force_ssl_admin();
			if ( ( 'login_post' == $scheme || 'rpc' == $scheme ) && ( $force_ssl_login || $force_ssl_admin ) )
				$scheme = 'https';
			elseif ( ( 'login' == $scheme ) && $force_ssl_admin )
				$scheme = 'https';
			elseif ( ( 'admin' == $scheme ) && $force_ssl_admin )
				$scheme = 'https';
			else
				$scheme = ( function_exists('is_ssl') && is_ssl() ? 'https' : 'http' );
		}

		if ( empty( $blog_id ) || !( function_exists('is_multisite') && is_multisite() ) )
			$url = get_option( 'siteurl' );
		else
			$url = ( !function_exists('get_blog_option') ? get_option( 'siteurl' ) : get_blog_option( $blog_id, 'siteurl' ));

		$url = str_replace( 'http://', $scheme.'://', $url );

		if ( !empty( $path ) && is_string( $path ) && strpos( $path, '..' ) === false )
			$url .= '/' . ltrim( $path, '/' );

		return apply_filters( 'site_url', $url, $path, $orig_scheme, $blog_id );
	}

	//**************************************************************************************
	// raw shortcode
	//**************************************************************************************
	public function raw_formatter($content) {
		$new_content = '';
		$pattern_full = '{(\[raw\].*?\[/raw\])}is';
		$pattern_contents = '{\[raw\](.*?)\[/raw\]}is';
		$pieces = preg_split($pattern_full, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($pieces as $piece) {
			$new_content .= preg_match($pattern_contents, $piece, $matches)
				? $matches[1]
				: wptexturize(wpautop($piece)) ;
		}

		return $new_content;
	}

	//**************************************************************************************
	// Debug Information
	//**************************************************************************************
	private function _microtime_diff($start, $end=NULL) {
		if( !$end )
			$end= microtime();
		list($start_usec, $start_sec) = explode(" ", $start);
		list($end_usec, $end_sec) = explode(" ", $end);
		$diff_sec= intval($end_sec) - intval($start_sec);
		$diff_usec= floatval($end_usec) - floatval($start_usec);
		return floatval( $diff_sec ) + $diff_usec;
	}

	private function _get_debug_info($buffer, $tag = '') {
		$ret_val = $this->_process_time();
		$this->mtime_start = microtime();

		$ret_val .= "<!--\n";

		$ret_val .= "***** Original ***********************************\n"
			 .  str_replace(array('<', '>'), array('＜', '＞'), $buffer) . "\n"
			 .  "**************************************************\n\n";

		$ret_val .= "***** Filters ************************************\n";
		if (empty($tag) && function_exists('current_filter'))
			$tag = current_filter();
		$active_filters = (isset($this->filters[$tag])
			? $this->filters[$tag]
			: array());
		foreach ($active_filters as $function_name => $priority) {
			if (strpos($function_name, 'HeadCleaner') === false && strpos($function_name, 'noindex') === false)
				$ret_val .= "    ($priority) : $function_name\n";
		}
		$ret_val .= "**************************************************\n\n";

		$ret_val .= "***** ob_list_handlers ***************************\n";
		if (function_exists('ob_list_handlers')) {
			$active_handlers = ob_list_handlers();
			foreach ($active_handlers as $handler) {
				$ret_val .= "   $handler\n";
			}
			unset($active_handlers);
		}
		$ret_val .= "**************************************************\n\n";

		$ret_val .= "--> \n";

		return $ret_val;
	}

	private function _process_time() {
		return sprintf(
			"<!-- %s sec. %d / %d (%s saving) -- %s -->\n" ,
			round($this->process_time, 4) ,
			$this->ret_len ,
			$this->org_len ,
			($this->org_len > 0 ? round(($this->org_len - $this->ret_len) / $this->org_len * 100, 2) . '%' : '') ,
			"{$this->plugin_name} ({$this->plugin_ver})"
			);
	}

	//**************************************************************************************
	// utility
	//**************************************************************************************
	private function _dynamic_js_css($content) {
		return preg_replace(
			'/' . preg_quote($this->cache_url, '/') . '(js|css)\/([^\.]*)\.(js|css)/i' ,
			$this->wp_plugin_url(basename(dirname(__FILE__))) . "js_css.php?f=$2&amp;t=$3" ,
			$content
			);
	}

	private function _tag_normalize($content) {
//		$content = str_replace($this->root_url, '/', $content);
		$content = preg_replace(
			array( "/[\s]+([^<>\=]+)\=['\"]([^'\"]*)['\"]/i", '/[\n\r]+/i', '/(<\/[^>]+>)[ \t]*(<[^>]+)/i' ) ,
			array( ' $1="$2"', "\n", "$1\n$2" ),
			$content
			);
		$content = $this->_tag_trim($content);
		return $content;
	}

	private function _tag_trim($content) {
		$content = trim($content);
		return ( !empty($content) ? $content . "\n" : '' );
	}

	private function _html_tag_normalize($html_tag, $doctype = 'xhtml') {
		$xmlns = self::XMLNS;
		$xml_lang = $this->lang;
		$dir = 'ltr';
		$lang = $this->lang;
		$class = '';
		$xmlns_og = '';
		$xmlns_fb = '';

		if (preg_match_all('/ +([^ ]*)=[\'"]([^\'"]*)[\'"]/', $html_tag, $matches, PREG_SET_ORDER)) {
			foreach ((array) $matches as $match) {
				switch ($match[1]){
				case 'xmlns':
					$xmlns = $match[2];
					break;
				case 'xml:lang':
					$xml_lang = $match[2];
					break;
				case 'lang':
					$lang = $match[2];
					break;
				case 'dir':
					$dir = $match[2];
					break;
				case 'class':
					$class = $match[2];
					break;
				case 'xmlns:og':
					$xmlns_og = $match[2];
					break;
				case 'xmlns:fb':
					$xmlns_fb = $match[2];
					break;
				}
			}
			unset($match);
		}
		unset($matches);

		if ( empty($html_tag) ) {
			$html_tag  = $this->_tag_trim(
				'<html ' .
				($doctype === 'xhtml'
				? "xmlns=\"{$xmlns}\" xml:lang=\"{$xml_lang}\" lang=\"{$lang}\""
				: "dir=\"{$dir}\" lang=\"{$lang}\"") .
				(!empty($class)    ? " class=\"{$class}\"" : '') .
				(!empty($xmlns_og) ? " xmlns:og=\"{$xmlns_og}\"" : '') .
				(!empty($xmlns_fb) ? " xmlns:fb=\"{$xmlns_fb}\"" : '') .
				'>');
		}

		if ($this->options['add_ogp_tag']) {
			if (!preg_match('/xmlns:og="[^"]+"/i', $html_tag)) {
				$html_tag = preg_replace('/(<html[^>]*)>/i', '$1 xmlns:og="'.(!empty($xmlns_og) ? $xmlns_og : self::XMLNS_OG).'">', $html_tag);
			}
//			if (!preg_match('/xmlns:fb="[^"]+"/i', $html_tag)) {
//				$html_tag = preg_replace('/(<html[^>]*)>/i', '$1 xmlns:fb="'.(!empty($xmlns_fb) ? $xmlns_fb : self::XMLNS_FB).'">', $html_tag);
//			}
		}

		return $html_tag;
	}

	private function _css_tag($src, $media = '', $inline_css = false) {
		$css_tag = '';
		if (!empty($src)) {
			if (empty($media))
				$media = $this->options['default_media'];
			$css_tag = $inline_css
				? sprintf('<style type="text/css" media="%s">/*<![CDATA[ */%s/* ]]>*/</style>', $media, "\n".$src."\n")
				: sprintf('<link rel="stylesheet" type="text/css" href="%s" media="%s" />', $src, $media) ;
		}
		return $this->_tag_trim($css_tag);
	}

	private function _script_tag($src, $inline_js = false) {
		$script_tag = '';
		if (!empty($src)) {
			$script_tag = $inline_js
				? sprintf('<script type="text/javascript">//<![CDATA[%s//]]></script>', "\n".$src."\n")
				: sprintf('<script type="text/javascript" src="%s"></script>', $src) ;
		}
		return $this->_tag_trim($script_tag);
	}

	private function _get_ogp_tags() {
		$site_name = get_bloginfo('name');
		$url = $this->_get_permalink();
		$title = $title = trim(wp_title('', false));
		$thumb = '';
		$excerpt = '';
		$type = $this->options['og_type_top'];

		if ( is_home() || is_front_page() ) {
			$excerpt = get_bloginfo('description');
			$title = $site_name;
			$type = $this->options['og_type_top'];

		} elseif( is_singular() ) {
			global $wpmp_conf, $post;

			// get the title
			$id = get_the_ID();
			if (!isset($post))
				$post = &get_post($id);

			// get the thumbnail
			$thumb = '';
			if ( function_exists('has_post_thumbnail') && has_post_thumbnail($id) ) {
				$thumb = preg_replace("/^.*['\"](https?:\/\/[^'\"]*)['\"].*/i","$1",get_the_post_thumbnail($id));
			} else {
				$attachments = get_children(array(
					'post_parent' => $id ,
					'post_type' => 'attachment' ,
					'post_mime_type' => 'image' ,
					'orderby' => 'menu_order' ,
					));
				foreach ($attachments as $attachment) {
					$image_src = wp_get_attachment_image_src($attachment->ID);
					$thumb = (isset($image_src[0]) ? $image_src[0] : '');
					unset($image_src);
					break;
				}
				unset($attachments);
			}
			if (empty($thumb) && preg_match_all('/<img .*src=[\'"]([^\'"]+)[\'"]/', $post->post_content, $matches, PREG_SET_ORDER)) {
				$thumb = $matches[0][1];
			}
			unset($matches);

			// get the excerpt
			$excerpt =
				!post_password_required($post)
				? get_the_excerpt()
				: __('There is no excerpt because this is a protected post.');
			if (empty($excerpt)) {
				$strwidth = (
					isset($wpmp_conf["excerpt_mblength"])
					? $wpmp_conf["excerpt_mblength"]
					: 255
					);
				$excerpt = strip_tags($post->post_content);
				$excerpt = trim(preg_replace(
					array('/[\n\r]/', '/\[[^\]]+\]/'),
					array('', ' '),
					$excerpt
					));
				$excerpt = (
					function_exists('mb_strimwidth')
					? mb_strimwidth($excerpt, 0, $strwidth, '...', $this->charset)
					: ( strlen($excerpt) > $strwidth ? substr($excerpt, 0, $strwidth - 3) . '...' : $excerpt)
					);
				$excerpt = apply_filters('get_the_excerpt', $excerpt);
			}
			$type = 'article';
		}

		if ( empty($thumb) && !empty($this->options['ogp_default_image']) )
			$thumb = $this->options['ogp_default_image'];

		$ogp_tag = '<meta property="%s" content="%s" />' . "\n";
		$ogp_tags = '';
		if ( !empty($this->options['og_locale']) )
			$ogp_tags .= sprintf($ogp_tag, 'og:locale', $this->options['og_locale']);
		if ( !empty($type) )
			$ogp_tags .= sprintf($ogp_tag, 'og:type', esc_html($type));
		if ( !empty($site_name) )
			$ogp_tags .= sprintf($ogp_tag, 'og:site_name', esc_html($site_name));
		if ( !empty($url) )
			$ogp_tags .= sprintf($ogp_tag, 'og:url', esc_html($url));
		if ( !empty($title) )
			$ogp_tags .= sprintf($ogp_tag, 'og:title', esc_html($title));
		if ( !empty($thumb) )
			$ogp_tags .= sprintf($ogp_tag, 'og:image', esc_html($thumb));
		if ( !empty($excerpt) )
			$ogp_tags .= sprintf($ogp_tag, 'og:description', esc_html($excerpt));
		if ( !empty($this->options['fb_admins']) )
			$ogp_tags .= sprintf($ogp_tag, 'fb:admins', $this->options['fb_admins']);
		if ( !empty($this->options['fb_app_id']) )
			$ogp_tags .= sprintf($ogp_tag, 'fb:app_id', $this->options['fb_app_id']);

		return $ogp_tags;
	}

	/**********************************************************
	* Send HTTP Header (Last-Modified or 304 Not Modified)
	***********************************************************/
	public function send_http_header_last_modified() {
		if ($this->_is_user_logged_in()) {
			return false;
		}

		$last_modified = $this->get_last_modified();
		if ($last_modified !== false) {
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified . ' GMT'));

			$modified_since = $this->_get_modified_since();
			if ($modified_since >= $last_modified) {
				header("HTTP/1.0 304 Not Modified");
				die();
			}

			if (!is_feed()) {
				add_action('wp_head',  array(&$this, 'last_modified_meta_tag'));
			}
		}
	}

	/**********************************************************
	* Get Last Modified Time
	***********************************************************/
	public function last_modified_meta_tag() {
		$last_modified = $this->get_last_modified();
		if ($last_modified !== false) {
			$last_modified = gmdate('D, d M Y H:i:s', $last_modified . ' GMT');
			$last_modified_meta_tag = "<meta http-equiv=\"Last-Modified\" content=\"$last_modified\" />\n";
			echo $last_modified_meta_tag;
		} else {
			return false;
		}
	}

	/**********************************************************
	* Get Last Modified Time
	***********************************************************/
	public function get_last_modified() {
		global $posts, $post;

		$posts_last_modified = $this->_posts_last_modified(
			!is_singular()
			? $posts
			: array($post)
			);

		$theme_last_modified = (
			!is_feed()
			? $this->_theme_last_modified()
			: 0
			);

		$last_modified = ( $posts_last_modified > $theme_last_modified
			? $posts_last_modified
			: $theme_last_modified
			);

		return ( $last_modified !== false && $last_modified > 0
			? $last_modified
			: false
			);
	}

	private function _posts_last_modified($posts) {
		if (!is_array($posts))
			return 0;

		if ($this->last_modified["posts"] === 0) {
			$last_modified = 0;

			foreach ((array) $posts as $post) {
				// get last post modified time
				$post_update =strtotime(mysql2date('Y-m-d H:i:s',$post->post_modified_gmt));
				if ($last_modified < $post_update)
					$last_modified = $post_update;

				// get last comment modified time
				$post_comments = get_comments(array(
					"status"  => "approve",
					"order"   => "DESC",
					"number"  => 1,
					"post_id" => $post->ID,
					));
				foreach ((array) $post_comments as $post_comment) {
					$comment_update =strtotime(mysql2date('Y-m-d H:i:s',$post_comment->comment_date_gmt));
					if ($last_modified < $comment_update)
						$last_modified = $comment_update;
				}
				unset($post_comment); unset($post_comments);
			}
			unset($post);

			$this->last_modified["posts"] = ( $last_modified > 0 ? $last_modified : 0);
		}

		return $this->last_modified["posts"];
	}

	private function _theme_last_modified() {
		if ($this->last_modified["theme"] === 0) {
			$last_modified = filemtime(ABSPATH . 'index.php');

			if (!is_feed()) {
				$theme_dir = untrailingslashit(get_template_directory());
				opendir($theme_dir);
				while($filename = readdir()) {
					if (preg_match('/¥.php$/i', $filename)) {
						$modified = filemtime( "{$theme_dir}/{$filename}" );
						if ($last_modified < $modified)
							$last_modified = $modified;
					}
				}
			}
			closedir();

			$this->last_modified["theme"] = ( $last_modified > 0 ? $last_modified : 0);
		}

		return $this->last_modified["theme"];
	}

	/**********************************************************
	* Get Request Header (If-Modified-Since)
	***********************************************************/
	private function _get_modified_since() {
		$requestHeaders = null;

		if (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
		} elseif (class_exists('HttpRequest')) {
			$requestHeaders = HttpRequest::getHeaders();
		}

		$modified_since = ( !empty($requestHeaders)
			? $requestHeaders['If-Modified-Since']
			: $_SERVER['HTTP_IF_MODIFIED_SINCE']
			);
		unset($requestHeaders);

		$date = $this->parse_http_date($modified_since);

		return ($date !== false
			? $date["timestamp"]
			: false
			);
	}

	/**********************************************************
	* based on parse_http_date()
        *  http://www.arielworks.net/articles/2004/0125a
	***********************************************************/
	private function parse_http_date( $string_date ) {

		$define_month = array(
			"01" => "Jan", "02" => "Feb", "03" => "Mar",
			"04" => "Apr", "05" => "May", "06" => "Jun",
			"07" => "Jul", "08" => "Aug", "09" => "Sep",
			"10" => "Oct", "11" => "Nov", "12" => "Dec"
			);

		if( preg_match( "/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun), ([0-3][0-9]) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) ([0-9]{4}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) GMT$/", $string_date, $temp_date ) ) {
			$date["hour"] = $temp_date[5];
			$date["minute"] = $temp_date[6];
			$date["second"] = $temp_date[7];
			$date["month"] = array_search( $temp_date[3], $define_month );
			$date["day"] = $temp_date[2];
			$date["year"] = $temp_date[4];

		} elseif( preg_match( "/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday), ([0-3][0-9])-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-([0-9]{2}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) GMT$/", $string_date, $temp_date ) ) {
			$date["hour"] = $temp_date[5];
			$date["minute"] = $temp_date[6];
			$date["second"] = $temp_date[7];
			$date["month"] = array_search( $temp_date[3], $define_month );
			$date["day"] = $temp_date[2];
			$date["year"] = ($temp_date[4] > 70 ? 1900 : 2000) + $temp_date[4];

		} elseif( preg_match( "/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) ([0-3 ][0-9]) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) ([0-9]{4})$/", $string_date, $temp_date ) ) {
			$date["hour"] = $temp_date[4];
			$date["minute"] = $temp_date[5];
			$date["second"] = $temp_date[6];
			$date["month"] = array_search( $temp_date[2], $define_month );
			$date["day"] = str_replace( " ", 0, $temp_date[3] );
			$date["year"] = $temp_date[7];

		} else {
			return false;
		}

		$date["timestamp"] = gmmktime( $date["hour"], $date["minute"], $date["second"], $date["month"], $date["day"], $date["year"] );

		return $date;
	}
}

//**************************************************************************************
// Go! Go! Go!
//**************************************************************************************
global $head_cleaner;

if (!defined('WP_UNINSTALL_PLUGIN'))
	$head_cleaner = new HeadCleaner(false);
else
	$head_cleaner = new HeadCleaner(true);
