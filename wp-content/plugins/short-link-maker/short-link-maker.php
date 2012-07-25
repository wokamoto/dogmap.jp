<?php
/*
Plugin Name: Short link maker
Version: 0.1.5.5
Plugin URI: http://wppluginsj.sourceforge.jp/short-link-maker/
Description: This is a plugin creating a shorter URL of post, page and media permalink.
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: short-link-maker
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2009 - 2010 wokamoto (email : wokamoto1973@gmail.com)

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
*/

//**********************************************************************************
// short link
//**********************************************************************************
class ShortLinkMaker {
	var $plugin_dir, $plugin_file, $plugin_url;
	var $textdomain_name, $option_name;
	var $disits, $valids;
	var $mac_n;

	//*****************************************************************************
	// Constructor
	//*****************************************************************************
	function ShortLinkMaker() {
		$this->__construct();
	}
	function __construct() {
		$this->_set_plugin_dir(__FILE__);
		$this->_load_textdomain();
		$this->disits = array(
			'0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
			'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
			'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
			'u', 'v', 'w', 'x', 'y', 'z',
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
			'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
			'U', 'V', 'W', 'X', 'Y', 'Z',
		);
		$this->max_n = count($this->disits);
		$this->valids = array();
		for ( $i = 0; $i < $this->max_n; $i++ ) {
			$this->valids[$this->disits[$i]] = $i;
		}

	}

	// set plugin dir
	function _set_plugin_dir( $file = '' ) {
		$file_path = ( !empty($file) ? $file : __FILE__);
		$filename = explode("/", $file_path);
		if(count($filename) <= 1) $filename = explode("\\", $file_path);
		$this->plugin_dir  = $filename[count($filename) - 2];
		$this->plugin_file = $filename[count($filename) - 1];
		$this->plugin_url  = $this->_wp_plugin_url($this->plugin_dir);
		unset($filename);
	}

	// load textdomain
	function _load_textdomain( $sub_dir = 'languages' ) {
		$this->textdomain_name = $this->plugin_dir;
		$plugins_dir = trailingslashit(defined('PLUGINDIR') ? PLUGINDIR : 'wp-content/plugins');
		$abs_plugin_dir = $this->_wp_plugin_dir($this->plugin_dir);
		$sub_dir = ( !empty($sub_dir)
			? preg_replace('/^\//', '', $sub_dir)
			: (file_exists($abs_plugin_dir.'languages') ? 'languages' : (file_exists($abs_plugin_dir.'language') ? 'language' : (file_exists($abs_plugin_dir.'lang') ? 'lang' : '')))
			);
		$textdomain_dir = trailingslashit(trailingslashit($this->plugin_dir) . $sub_dir);

		if ( $this->_check_wp_version("2.6") && defined('WP_PLUGIN_DIR') )
			load_plugin_textdomain($this->textdomain_name, false, $textdomain_dir);
		else
			load_plugin_textdomain($this->textdomain_name, $plugins_dir . $textdomain_dir);
	}

	// check wp version
	function _check_wp_version($version, $operator = ">=") {
		global $wp_version;
		return version_compare($wp_version, $version, $operator);
	}

	// WP_CONTENT_DIR
	function _wp_content_dir($path = '') {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_DIR')
			? WP_CONTENT_DIR
			: trailingslashit(ABSPATH) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// WP_CONTENT_URL
	function _wp_content_url($path = '') {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_URL')
			? WP_CONTENT_URL
			: trailingslashit(get_option('siteurl')) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// WP_PLUGIN_DIR
	function _wp_plugin_dir($path = '') {
		return trailingslashit($this->_wp_content_dir( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	// WP_PLUGIN_URL
	function _wp_plugin_url($path = '') {
		return trailingslashit($this->_wp_content_url( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	function n_to_dec( $num_str, $n ) {
		if ( ! (preg_match('/^\d+$/', $n) && $n >= 2 && $n <= $this->max_n) ) return FALSE;
		if ( ! preg_match('/^[\da-zA-Z]+$/', $num_str) ) return FALSE;

		$dec = 0;
		for ( $i = 0; $i < strlen($num_str); $i++ ) {
			 $dec += $this->valids[ substr($num_str, ($i + 1) * -1, 1) ] * pow( $n, $i );
		}

		return $dec;
	}

	function dec_to_n( $dec, $n ) {
		if ( ! (preg_match('/^\d+$/', $n) && $n >= 2 && $n <= $this->max_n) ) return FALSE;
		if ( ! preg_match('/^\d+$/', $dec) ) return FALSE;
		if ( $dec == 0 ) return $dec;

		$num_str = '';
		while ( $dec !== 0 ) {
			$num_str =  $this->disits[$dec % $n] . $num_str;
			$dec = (int) ($dec / $n);
		}

		return $num_str;
	}

	function get_shortlink($post_id=''){
		if ( preg_match('/http:[^\?]*\?p=([0-9]+)/i', $post_id, $matches) ) {
			$post_id = (int) $matches[1];
		}

		if ( !is_singular() && !is_numeric($post_id) ) {
			return $post_id;
		}

		if ( empty($post_id) ) {
			global $post;
			$post_id = $post->ID;
		}

		if ( !is_numeric($post_id) ) {
			return $post_id;
		} else {
			$siteurl = trailingslashit(get_option('siteurl'));
			$id = $this->dec_to_n($post_id, $this->max_n);

			return $siteurl . ($id !== FALSE ? $id : "?p=$post_id");
		}
	}

	function _strlen($string, $charset = ''){
		if (empty($charset))
			$charset = get_option('blog_charset');
		return (function_exists('mb_strlen') ? mb_strlen($string, $charset) : strlen($string));
	}

	function _substr($string, $start, $length, $charset = ''){
		if (empty($charset))
			$charset = get_option('blog_charset');
		return (function_exists('mb_substr') ? mb_substr($string, $start, $length, $charset) : substr($string, $start, $length, $charset));
	}

	function get_tweet_link( $title='', $link = '' ) {
		global $post;

		if ( empty($title) )
			$title = $post->post_title;
		if ( empty($link) )
			$link = $this->get_shortlink($post->ID);

		$charset = get_option('blog_charset');

		$title_length = $this->_strlen($title, $charset);
		$link_length  = $this->_strlen($link,  $charset);

		$excess = 140 - ($title_length + $link_length);
		$title = ( $excess < 0
			? $this->_substr($title, 0, $title_length - ($excess * -1 + 4), $charset) . '... '
			: $title . ' '
			);

		return '<a'
			. ' href="http://twitter.com/home/?status=' . urlencode($title . $link) . '"'
			. ' title="' . __("Tweet this", $this->textdomain_name) . ' &#8220;' . htmlspecialchars($title . $link) . '&#8221;"'
			. ' target="_blank">'
			. __("Tweet this", $this->textdomain_name)
			. '</a>';
	}

	function add_html_header(){
		global $post;

		if ( is_singular() && !headers_sent() ) {
			$short_link = $this->get_shortlink($post->ID);
			header("Link: <$short_link>; rel=shorturl");

			add_action('wp_head', array(&$this, 'add_head'));
			if ( function_exists('rel_canonical') )
				remove_action('wp_head', 'rel_canonical');
			if ( function_exists('wp_shortlink_wp_head') )
				remove_action('wp_head', 'wp_shortlink_wp_head');
		}
	}

	function add_head(){
		global $post;

		if ( is_singular() ) {
			$canonicalurl = get_permalink($post->ID);
			$short_link = $this->get_shortlink($post->ID);
			echo "<link rel=\"canonical\" href=\"$canonicalurl\" />\n";
			echo "<link rel=\"shortlink\" href=\"$short_link\" />\n";
		}
	}

	function redirect_shortlink($redirect_url, $requested_url){
		remove_filter('redirect_canonical', array(&$this, 'redirect_shortlink') );

		if (empty($requested_url))
			return $redirect_url;

		$siteurl = trailingslashit(get_option('siteurl'));
		$pattern = '/^' . preg_quote($siteurl, '/') . '([0-9a-zA-Z]+)$/';
		if ( preg_match_all( $pattern, $requested_url, $matches ) ) {
			$link_id = $matches[1][0];
			$post_id = $this->n_to_dec($link_id, $this->max_n);
			if ( $post_id !== FALSE ) {
				$redirect_url = get_permalink($post_id);
			}
			if ( empty($redirect_url) ) {
				$redirect_url = ( preg_match('/[\d]+/', $link_id)
					? get_permalink($link_id)
					: get_permalink(hexdec($link_id))
				);
			}
			add_filter('redirect_canonical', create_function( '', "return '$redirect_url';"));
		}
		unset($matches);

		return $redirect_url;
	}

	function &parse_query($query) {
		$link_id = $query->query_vars["pagename"];
		$post_id = (int) $query->query_vars["page_id"];

		$siteurl = trailingslashit(get_option('siteurl'));
		$pattern = '/^' . preg_quote($siteurl, '/') . '([0-9a-zA-Z]+)$/';
		if ( !preg_match( $pattern, $link_id ) ) {
			// build the URL in the address bar
			$requested_url  = is_ssl() ? 'https://' : 'http://';
			$requested_url .= $_SERVER['HTTP_HOST'];
			$requested_url .= $_SERVER['REQUEST_URI'];
			if ( preg_match_all( $pattern, $requested_url, $matches ) ) {
				$link_id = $matches[1][0];
			}
			unset($matches);
		}

		if ( (empty($post_id) || $post_id === 0) && preg_match( '/^[0-9a-zA-Z]+$/', $link_id ) ) {
			$post_id = $this->n_to_dec($link_id, $this->max_n);
			if ( $post_id !== FALSE ) {
				$redirect_url = get_permalink($post_id);
				if ( !empty($redirect_url) && $redirect_url !== FALSE ) {
					$query->query_vars["page_id"] = $post_id;
					$query->is_404 = false;
				}
			}
		}

		return $query;
	}

	function add_post_row_actions($actions, $post) {
		$short_link = $this->get_shortlink($post->ID);
		$actions['tweet_this'] = $this->get_tweet_link( $post->post_title, $short_link );
		return $actions;
	}

	function add_media_row_actions($actions, $post) {
		global $title;
		$short_link = $this->get_shortlink($post->ID);
		$actions['tweet_this'] = $this->get_tweet_link( $title, $short_link );
		return $actions;
	}

	function admin_menu_box() {
		if (function_exists("add_meta_box")) {
			add_meta_box("short-link_id", __("Short link", $this->textdomain_name), array(&$this, "admin_menu_inner_box"), "post", "side");
			add_meta_box("short-link_id", __("Short link", $this->textdomain_name), array(&$this, "admin_menu_inner_box"), "page", "side");
		} else {
			add_action("dbx_post_advanced", array(&$this, "admin_menu_old_box"));
			add_action("dbx_page_advanced", array(&$this, "admin_menu_old_box"));
		}
	}

	function admin_menu_old_box() {
		global $post;
		echo '<div class="dbx-b-ox-wrapper">' . "\n";
		echo '<fieldset id="short-link_fieldsetid" class="dbx-box">' . "\n";
		echo '<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">' . 
		      "Short link" . "</h3></div>";   
		echo '<div class="dbx-c-ontent-wrapper"><div class="dbx-content">';
		$this->admin_menu_inner_box($post);
		echo "</div></div></fieldset></div>\n";
	}

	function admin_menu_inner_box($post) {
		if ( $post->ID <= 0 ) {
			echo '<p>' . __("Please publish to get Short links.", $this->textdomain_name) . '</p>';
			return;
		}

		$post_id = $post->ID;

		$short_link = $this->get_shortlink( $post_id );
		$tweet_link = $this->get_tweet_link( $post->post_title, $short_link );

		echo '<p><strong>' . __('Short link', $this->textdomain_name) . ":</strong> <a href=\"{$short_link}\">{$short_link}</a></p>";
		echo "<p><strong>$tweet_link &raquo;</strong></p>";
	}

	function get_shortlink_handler($shortlink, $id, $context, $allow_slugs) {
		if ( is_singular() )
			$shortlink = $this->get_shortlink($id);
		return $shortlink;
	}
}
global $shortlink;

$shortlink = new ShortLinkMaker();

if ( is_admin() ) {
	add_filter('post_row_actions', array(&$shortlink, 'add_post_row_actions'), 10, 2);
	add_filter('page_row_actions', array(&$shortlink, 'add_post_row_actions'), 10, 2);
	add_filter('media_row_actions', array(&$shortlink, 'add_media_row_actions'), 10, 2);
	add_action('admin_menu', array(&$shortlink, 'admin_menu_box'));
} else {
	add_action('parse_query', array(&$shortlink, 'parse_query'));
	add_action('wp', array(&$shortlink, 'add_html_header'));
	add_filter('redirect_canonical', array(&$shortlink, 'redirect_shortlink'), 10, 2 );
}
add_filter('get_shortlink', array(&$shortlink, 'get_shortlink_handler'), 11, 4);

if ( !function_exists('get_shortlink') ) {
	function get_shortlink($post_id=''){
		global $shortlink;

		if ( !isset($shortlink) )
			$shortlink = new ShortLinkMaker();

		return $shortlink->get_shortlink($post_id);
	}
}

if ( !function_exists('the_shortlink') ) {
	function the_shortlink($post_id=''){
		global $shortlink;

		if ( !isset($shortlink) )
			$shortlink = new ShortLinkMaker();

		echo $shortlink->get_shortlink($post_id);
	}
}
?>