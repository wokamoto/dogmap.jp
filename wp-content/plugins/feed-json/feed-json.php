<?php
/*
Plugin Name: Feed JSON
Plugin URI: http://wordpress.org/extend/plugins/feed-json/
Description: Adds a new type of feed you can subscribe to. http://example.com/feed/json or http://example.com/?feed=json to anywhere you get a JSON form.
Author: wokamoto
Version: 1.0.9
Author URI: http://dogmap.jp/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2011-2014 (email : wokamoto1973@gmail.com)

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

$feed_json = feed_json::get_instance();
$feed_json->init();

register_activation_hook( __FILE__, array($feed_json, 'add_feed_json_once') );
register_deactivation_hook( __FILE__, array($feed_json, 'remove_feed_json') );

class feed_json {
	static $instance;
	const  JSON_TEMPLATE = 'feed-json.php';

	private function __construct() {}

	public static function get_instance() {
		if( !isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();    
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'init', array( $this, 'add_feed_json') );
		add_action( 'do_feed_json', array( $this, 'do_feed_json'), 10, 1 );
		add_filter( 'template_include', array( $this, 'template_json') );
		add_filter( 'query_vars', array( $this, 'add_query_vars') );
	}

	public function add_feed_json_once() {
		$this->add_feed_json();
	    flush_rewrite_rules();
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
	    flush_rewrite_rules();
	}

	public function add_query_vars($qvars) {
		$qvars[] = 'callback';
		$qvars[] = 'limit';
		return $qvars;
	}

	static public function add_feed_json() {
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
