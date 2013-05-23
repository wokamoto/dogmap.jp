<?php
/*
Plugin Name: Feed Template Customize
Plugin URI: http://wordpress.org/extend/plugins/feed-template-customize/
Description: This plugin modifies RSS feeds and ATOM feeds as you want.
Author: wokamoto
Version: 1.0.0.1
Author URI: http://dogmap.jp/
Text Domain: feed-template-customize
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2011 wokamoto (email : wokamoto1973@gmail.com)

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
class feed_template_customize {
	var $feed_type = '';

	function feed_template_customize() {
		$this->__construct();
	}
	function __construct() {
		$this->feed_type = (
			isset($_GET['type'])
			? esc_html($_GET['type'])
			: 'feed'
			);

		remove_filter('do_feed_rdf', 'do_feed_rdf', 10);
		remove_filter('do_feed_rss', 'do_feed_rss', 10);
		remove_filter('do_feed_rss2', 'do_feed_rss2', 10);
		remove_filter('do_feed_atom', 'do_feed_atom', 10);
 
		add_action('do_feed_rdf', array(&$this, 'custom_feed_rdf'), 10, 1);
		add_action('do_feed_rss', array(&$this, 'custom_feed_rss'), 10, 1);
		add_action('do_feed_rss2', array(&$this, 'custom_feed_rss2'), 10, 1);
		add_action('do_feed_atom', array(&$this, 'custom_feed_atom'), 10, 1);
	}

	function get_template_file($template_file) {
		if (function_exists('get_stylesheet_directory') && file_exists( get_stylesheet_directory() . $template_file)) {
			$template_file = get_stylesheet_directory() . $template_file;
		} elseif (function_exists('get_template_directory') && file_exists( get_template_directory() . $template_file)) {
			$template_file = get_template_directory() . $template_file;
		} elseif (file_exists(trailingslashit(dirname(__FILE__)) . $template_file)) {
			$template_file = trailingslashit(dirname(__FILE__)) . $template_file;
		} elseif (file_exists(ABSPATH . WPINC . $template_file)) {
			$template_file = ABSPATH . WPINC . $template_file;
		} else {
			$template_file = ABSPATH . WPINC . str_replace($this->feed_type, 'feed', $template_file);
		}
		return $template_file;
	}

	function custom_feed_rdf() {
		$template_file = "/{$this->feed_type}-rdf.php";
		load_template( $this->get_template_file($template_file) );
	}
 
	function custom_feed_rss() {
		$template_file = "/{$this->feed_type}-rss.php";
		load_template( $this->get_template_file($template_file) );
	}
 
	function custom_feed_rss2( $for_comments ) {
		$template_file = "/{$this->feed_type}-rss2" . ( $for_comments ? '-comments' : '' ) . '.php';
		load_template( $this->get_template_file($template_file) );
	}
 
	function custom_feed_atom( $for_comments ) {
		$template_file = "/{$this->feed_type}-atom" . ( $for_comments ? '-comments' : '' ) . '.php';
		load_template( $this->get_template_file($template_file) );
	}
}
new feed_template_customize();
?>