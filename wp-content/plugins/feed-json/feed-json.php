<?php
/*
Plugin Name: Feed JSON
Plugin URI: http://wordpress.org/extend/plugins/feed-json/
Description: Adds a new type of feed you can subscribe to. http://example.com/feed/json or http://example.com/?feed=json to anywhere you get a JSON form.
Author: wokamoto
Version: 1.0.6
Author URI: http://dogmap.jp/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2011-2013 (email : wokamoto1973@gmail.com)

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

class feed_json {
	static $instance;
	const  JSON_TEMPLATE = 'feed-json.php';

	public function __construct() {
		self::$instance = $this;

		add_action('init', array($this, 'add_feed_json'));
		add_action('do_feed_json', array($this, 'do_feed_json'), 10, 1);
		add_filter('template_include', array($this, 'template_json'));
		add_filter('query_vars', array($this, 'add_query_vars'));

		if (function_exists('register_activation_hook')) {
			register_activation_hook(__FILE__, array($this, 'add_feed_json_once'));
			register_deactivation_hook(__FILE__, array($this, 'remove_feed_json'));
		} else {
			$plugin_basename = plugin_basename(__FILE__);
			add_action('activate_' . $plugin_basename, array($this, 'add_feed_json_once'));
			add_action('deactivate_' . $plugin_basename, array($this, 'remove_feed_json'));
		}
	}

	public function add_feed_json_once() {
		global $wp_rewrite;
		$this->add_feed_json();
		$wp_rewrite->flush_rules();
	}

	public function remove_feed_json() {
		global $wp_rewrite;
		$feeds = array();
		foreach ( $wp_rewrite->feeds as $feed ) {
			if ( $feed !== 'json' ) {
				$feeds[] = $feed;
			}
		}
		$wp_rewrite->feeds = $feeds;
		$wp_rewrite->flush_rules();
	}

	public function add_query_vars($qvars) {
	  $qvars[] = 'callback';
	  $qvars[] = 'limit';
	  return $qvars;
	}

	public function add_feed_json() {
		add_feed('json', array($this, 'do_feed_json'));
	}

	public function do_feed_json() {
		load_template($this->template_json(dirname(__FILE__) . '/template/' . self::JSON_TEMPLATE));
	}

	public function template_json( $template ) {
		$template_file = false;
		if (get_query_var('feed') === 'json') {
			if (function_exists('get_stylesheet_directory') && file_exists(get_stylesheet_directory() . '/' . self::JSON_TEMPLATE)) {
				$template_file = get_stylesheet_directory() . '/'. self::JSON_TEMPLATE;
			} elseif (function_exists('get_template_directory') && file_exists(get_template_directory() . '/' . self::JSON_TEMPLATE)) {
				$template_file = get_template_directory() . '/' . self::JSON_TEMPLATE;
			} elseif (file_exists(dirname(__FILE__) . '/template/' . self::JSON_TEMPLATE)) {
				$template_file = dirname(__FILE__) . '/template/' . self::JSON_TEMPLATE;
			}
		}

		$template_file = ($template_file !== false ? $template_file : $template);
		return apply_filters( 'feed-json-template-file', $template_file );
	}
}
new feed_json();
