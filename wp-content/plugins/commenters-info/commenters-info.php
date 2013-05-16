<?php
/*
Plugin Name: Commenters Info
Plugin URI: http://wppluginsj.sourceforge.jp/commenters-info/
Description: The aggregate information and list the commenter.
Author: wokamoto
Version: 0.6.7.2
Author URI: http://dogmap.jp/
Text Domain: commenters-info
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2009 - 2012 wokamoto (email : wokamoto1973@gmail.com)

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
 IP2C 2.0.0 - Copyright (C) 2006 Omry Yadan (omry@yadan.net), all rights reserved
  IP2C uses the IP-to-Country Database
  provided by WebHosting.Info (http://www.webhosting.info),
  available from http://ip-to-country.webhosting.info.

 Snoopy - the PHP net client
  Author: Monte Ohrt <monte@ispi.net>
  Copyright (c): 1999-2008 New Digital Group, all rights reserved
  Version: 1.2.4

 PhpConcept Library - Zip Module 2.5
  @license License GNU/LGPL
  @copyright March 2006 Vincent Blavet
  @author Vincent Blavet
  @link http://www.phpconcept.net

 ExplorerCanvas
  Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at
    http://www.apache.org/licenses/LICENSE-2.0
  Copyright 2006 Google Inc.
  link http://excanvas.sourceforge.net/

 circle.js - graph_circle 1.0.1
  Licensed under the Apache License, Version 2.0 (the "License");
  Copyright 2007-2009 futomi  http://www.html5.jp/
  version 1.0.1
  link http://www.html5.jp/library/graph_circle.html

/******************************************************************************
 * Global
 *****************************************************************************/
global $commenters_info;


/******************************************************************************
 * Template Tag commenters_info
 *  usage : <?php if (function_exists('commenters_info')) commenters_info(); ?>
 *****************************************************************************/
function commenters_info($comment_or_ID = '', $before = '', $after = '', $show_country = true, $show_browser = true, $show_ver = false, $separator = '&nbsp;') {
	global $commenters_info;

	if (!isset($commenters_info))
		$commenters_info = new CommentersInfo();
	echo $commenters_info->get_commenters_info($comment_or_ID, $before, $after, $show_country, $show_browser, $show_ver, $separator);
}


/******************************************************************************
 * Template Tag commenters_ranking
 *  usage : <?php if (function_exists('commenters_ranking')) commenters_ranking(10); ?>
 *****************************************************************************/
function commenters_ranking($limit = 5, $show_admin = false, $avatar_size = 16, $before = '<li>', $after = '</li>', $show_country = false, $show_browser = false, $show_ver = false, $separator = '&nbsp;') {
	global $commenters_info;

	if ( !isset($commenters_info) )
		$commenters_info = new CommentersInfo();
	echo $commenters_info->get_ranking($limit, $show_admin, $avatar_size, $before, $after, $show_country, $show_browser, $show_ver, $separator);
}


/******************************************************************************
 * Require
 *****************************************************************************/
if (!class_exists('wokController') || !class_exists('wokScriptManager'))
	require(dirname(__FILE__).'/includes/common-controller.php');

/******************************************************************************
 * CommentersController Class
 *****************************************************************************/
class CommentersInfo extends wokController {
	public $plugin_name = 'commenters-info';
	public $plugin_ver  = '0.6.7';

	const LIST_PER_PAGE = 30;			// Commenters Info List / Page
	const SCHEDULE_HUNDLER = 'commenters_info_get_commenter_list';
//	const IP2CDB_CHK_INTERVAL = 604800;	// 60 * 60 * 24 * 7
//	const IP2CDB_CHK_HUNDLER = 'commenters_info_ip2c_db_check';
	const GRAPH_WIDTH = 600;
	const GRAPH_HEIGHT = 300;

	private $commenters  = array();

	private $_sort_key  = 'last_comment';
	private $_sort_desc = true;

	private $_detect_browsers;
	private $_detect_countries;
	private $_meta_value_cache = array();

	// Deafault Options
	private $options_default = array(
		'title' => '' ,
		'limit' => 5 ,
		'show_admin' => false ,
		'avatar_size' => 16 ,
		'before_list' => '<li>' ,
		'after_list' => '</li>' ,
		'show_country' => false ,
		'show_browser' => false ,
		'show_ver' => false ,
		'separator' => '&nbsp;' ,
		);

	private $not_subdomain_site = array(
		'blog.livedoor.jp' ,
		'blog.goo.ne.jp' ,
		'blog.drecom.jp' ,
		'blog.so-net.ne.jp' ,
		'd.hatena.ne.jp' ,
		'ameblo.jp' ,
		'yaplog.jp' ,
		);

	/**********************************************************
	* Constructor
	***********************************************************/
	function __construct() {
		$this->setPluginDir(__FILE__);
		$this->loadTextdomain('languages');

		$this->options = $this->_init_options($this->getOptions());
		$this->commenters = $this->_init_commenters();

		if (!class_exists('DetectBrowsersController')) {
			require( $this->wp_plugin_dir($this->plugin_dir . '/includes/') . 'detect_browsers.php' );
			$this->_detect_browsers = new DetectBrowsersController();
		}

		if (!class_exists('DetectCountriesController')) {
			require( $this->wp_plugin_dir($this->plugin_dir . '/includes/') . 'detect_countries.php' );
			$this->_detect_countries = new DetectCountriesController();
		}

		// admin dashboard
		if (is_admin()) {
			add_action('admin_menu', array(&$this,'add_admin_menu'), 'administrator');

			add_filter('comment_author', array(&$this,'comment_author'));
			add_action('admin_head', array(&$this,'add_admin_css'));
			wp_enqueue_script( 'gprofiles', 'http://s.gravatar.com/js/gprofiles.js', array( 'jquery' ), 'e', true );
		} else {
			add_action('comment_post',   array(&$this, 'schedule_get_commenters_list'), 10, 2);
		}

		// wp-cron schedule
		add_action( self::SCHEDULE_HUNDLER, array(&$this, 'get_commenters_list'));

//		if ( !$commenters_info->schedule_enabled() ) {
//			wp_schedule_single_event(time(), self::IP2CDB_CHK_HUNDLER);
//		}
//		add_action( self::IP2CDB_CHK_HUNDLER, array(&$this, 'ip2c_db_check'));

		// widget control
		add_action('init', array(&$this, 'register_widget'));

		// activation & deactivation fook
		if ( function_exists('register_activation_hook') ) {
			register_activation_hook(__FILE__, array(&$this, 'activation'));
		}
		if ( function_exists('register_deactivation_hook') ) {
			register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));
		}
	}

	// init options
	private function _init_options($options = ''){
		if (!is_array($options)) {
			$options = array();
		}

		foreach ($this->options_default as $key => $val) {
			$options[$key] = (isset($options[$key]) ? $options[$key] : $val);
		}

		return $options;
	}

	// init commenters
	private function _init_commenters(){
		$commenters = get_option('commenters');
		if ( !is_array($commenters) ) {
			wp_schedule_single_event(time(), self::SCHEDULE_HUNDLER);
		}

		return $commenters;
	}

	//**************************************************************************************
	// plugin activation
	//**************************************************************************************
	public function activation(){
	}

	//**************************************************************************************
	// plugin deactivation
	//**************************************************************************************
	public function deactivation(){
		delete_option('commenters');
		wp_clear_scheduled_hook(self::SCHEDULE_HUNDLER);
		wp_clear_scheduled_hook(self::IP2CDB_CHK_HUNDLER);
	}

	//**************************************************************************************
	// Add Admin Menu
	//**************************************************************************************
	public function add_admin_menu() {
		global $twicon;

		if ( isset($twicon) && isset($twicon->plugin_ver) && version_compare($twicon->plugin_ver, "1.3.0", ">=") ) {
			remove_action('admin_menu', array(&$twicon,'add_admin_menu'));
		}

		$parent = 'edit-comments.php';
		$page_title = __('Commenters information', $this->textdomain_name);
		$menu_title = $page_title;
		$file = plugin_basename(__FILE__);
		$this->admin_action = trailingslashit(get_bloginfo('wpurl')) . 'wp-admin/' . $parent . '?page=' . $file;
		$this->addSubmenuPage(
			$parent,
			$page_title,
			array($this,'optionPage'),
			'level_7',
			$menu_title,
			$file
			);

		add_action('admin_print_scripts', array(&$this,'add_admin_print_scripts'));
		add_action('admin_head-'.$this->admin_hook[$parent], array(&$this,'add_admin_head'));
	}

	//**************************************************************************************
	// Add Admin Head
	//**************************************************************************************
	public function add_admin_print_scripts() {
		// add JS to admin_head
		$this->addjQuery();	// regist jQuery
	}

	public function add_admin_head() {
		// add JS to admin_head
		$js = '';

		$type = ( isset($_GET['list'])  ? $_GET['list'] : 'c' );
		switch($type) {
		case 'c':
			break;
		case 'b':
		case 'p':
		case 'a':
		case 't':
		case 'l':
			$js .= "<!--[if IE]><script type=\"text/javascript\" src=\"{$this->plugin_url}includes/js/excanvas/excanvas.compiled.js\"></script><![endif]-->\n";
			$js .= "<script type=\"text/javascript\" src=\"{$this->plugin_url}includes/js/graph/circle.js\"></script>\n";
			break;
		}

		echo $js;
	}

	public function add_admin_css() {
		if (defined('AKISMET_VERSION') && version_compare(AKISMET_VERSION, "2.5.0", ">=")) {
?>
<style type="text/css">
/* <![CDATA[ */
div.commentersinfo {float:left;}
img.commentersinfo {float:none;margin-right:0;margin-top:0;}
/* ]]> */
</style>
<?php
		}
	}

	// comment author
	public function comment_author($author) {
		global $comment;

		$commenters_info = $this->get_commenters_info($comment, '', '', true, true, true, '&nbsp;', 'commentersinfo');
		if (defined('AKISMET_VERSION') && version_compare(AKISMET_VERSION, "2.5.0", ">=")) {
			$commenters_info = '<div class="commentersinfo">' . $commenters_info . '</div><br />' . "\n";
		}

		$author .= '<br />'."\n" . $commenters_info;
		return $author;
	}

	//**************************************************************************************
	// Option Page
	//**************************************************************************************
	public function optionPage() {
		$type = (
			isset($_GET['list'])
			? $_GET['list']
			: 'c'
			);
		$page = (
			isset($_GET['apage'])
			? abs((int) $_GET['apage'])
			: 1
			);
		$without_blog_owner = (
			isset($_GET['owner'])
			? $_GET['owner'] == 'without'
			: true
			);

		$canvas = '<div><canvas width="' . self::GRAPH_WIDTH . '" height="' . self::GRAPH_HEIGHT . '" id="graph"></canvas></div>' . "\n";

		echo '<div class="wrap">'."\n";

		switch($type) {
		case 'c':
			$display_twitter_id = $this->_display_twitter_id();
			$key  = ( isset($_GET['key'])   ? $_GET['key']  : '');
			$sort = ( isset($_GET['sort'])  ? $_GET['sort'] : '');
			$this->_comment_authors_list($display_twitter_id, $page, $key, $sort);
			break;
		case 'b':
			$this->_comment_browsers_list($page, $without_blog_owner);
			echo $canvas;
			break;
		case 'p':
			$this->_comment_plathome_list($page, $without_blog_owner);
			echo $canvas;
			break;
		case 'a':
			$this->_comment_agentes_list($page, $without_blog_owner);
			echo $canvas;
			break;
		case 't':
			$this->_trackback_browsers_list($page);
			echo $canvas;
			break;
		case 'l':
			$this->_comment_countries_list($page, $without_blog_owner);
			echo $canvas;
			break;
		}

		echo "</div>\n";
	}

	//**************************************************************************************
	// Register widget
	//**************************************************************************************
	public function register_widget() {
		wp_register_sidebar_widget(
			'commenters-ranking' ,
			'commenters-ranking' ,
			array(&$this, 'widgetOutput') ,
			array('classname' => 'widget_' . $this->plugin_name, 'description' => __("Commenters Ranking", $this->textdomain_name))
			);
		wp_register_widget_control(
			'commenters-ranking' ,
			'commenters-ranking' ,
			array(&$this, 'widgetUpdate')
			);
	}

	//**************************************************************************************
	// Widget Register
	//**************************************************************************************
	public function widgetUpdate() {
		$field_info = array(
			'title' => array( 'id' => $this->plugin_name.'-title', 'name' => $this->plugin_name.'-title' ) ,
			'limit' => array( 'id' => $this->plugin_name.'-limit', 'name' => $this->plugin_name.'-limit' ) ,
			'avatar_size' => array( 'id' => $this->plugin_name.'-size', 'name' => $this->plugin_name.'-size' ) ,
			);

		$newoptions = $this->getOptions();
		if (isset($_POST["{$this->plugin_name}-submit"])) {
			$newoptions['title'] = strip_tags(stripslashes($_POST[$field_info['title']['id']]));
			$newoptions['limit'] = intval($_POST[$field_info['limit']['id']]);
			$newoptions['avatar_size'] = intval($_POST[$field_info['avatar_size']['id']]);
		}
		if ( $this->options != $newoptions ) {
			$this->options = $newoptions;
			$this->updateOptions();
		}

		$title = esc_attr($this->options['title']);
		$limit = esc_attr($this->options['limit']);
		$size  = esc_attr($this->options['avatar_size']);
		$this->widgetForm( compact($title, $limit, $size), $field_info );
	}

	public function widgetForm( $instance, $field_info ) {
		if ( function_exists('esc_attr') ) {
			$title = esc_attr($instance['title']);
			$limit = esc_attr($instance['limit']);
			$size  = esc_attr($instance['avatar_size']);
		} else {
			$title = esc_attr($instance['title']);
			$limit = esc_attr($instance['limit']);
			$size  = esc_attr($instance['avatar_size']);
		}

		echo "<p>";
		echo "<label for=\"{$field_info['title']['id']}\">" . __('Title:');
		echo "<input class=\"widefat\" id=\"{$field_info['title']['id']}\" name=\"{$field_info['title']['name']}\" type=\"text\" value=\"{$title}\" />";
		echo "</label>";
		echo "</p>\n";
		echo "<p>";
		echo "<input class=\"widefat\" id=\"{$field_info['limit']['id']}\" name=\"{$field_info['limit']['name']}\" type=\"text\" style=\"width: 3em;\" value=\"{$limit}\" />";
		echo "<label for=\"{$field_info['limit']['id']}\"> : " . __('Max Comemnters', $this->textdomain_name) . '</label>';
		echo "</p>\n";
		echo "<p>";
		echo "<input class=\"widefat\" id=\"{$field_info['avatar_size']['id']}\" name=\"{$field_info['avatar_size']['name']}\" type=\"text\" style=\"width: 3em;\" value=\"{$size}\" />";
		echo "<label for=\"{$field_info['avatar_size']['id']}\"> : " . __('Gravatar size (px)', $this->textdomain_name) . '</label>';
		echo "</p>\n";
		echo "<input type=\"hidden\" id=\"{$this->plugin_name}-submit\" name=\"{$this->plugin_name}-submit\" value=\"1\" />";
	}

	//**************************************************************************************
	// output Widget
	//**************************************************************************************
	public function widgetOutput($args) {
		extract($args, EXTR_SKIP);
		extract($this->options, EXTR_OVERWRITE);

		if ($title == '')
			$title = __("Commenters Ranking", $this->textdomain_name);
		if (intval($limit) == 0)
			$limit = 5;
		if (intval($avatar_size) == 0)
			$avatar_size = 16;
		if (empty($before_list))
			$before_list = '<li>';
		if (empty($after_list))
			$after_list  = '</li>';

		echo $before_widget . "\n";
		echo $before_title . $title . $after_title . "\n";
		echo "<ol>\n";
		echo $this->get_ranking($limit, $show_admin, $avatar_size, $before_list, $after_list, $show_country, $show_browser, $show_ver, $separator);
		echo "</ol>\n";
		echo $after_widget . "\n";
	}

	//**************************************************************************************
	// Schedule : Get Commenters List
	//**************************************************************************************
	public function schedule_get_commenters_list($comment_id, $comment_approved = '') {
		wp_schedule_single_event(time(), self::SCHEDULE_HUNDLER);
	}


	//**************************************************************************************
	// Function (public)
	//**************************************************************************************

	// Get commenters list
	public function get_commenters_list() {
		$commenters = $this->_get_comment_authors($this->commenters);
		ksort($commenters);
		update_option('commenters', $commenters);
		$this->commenters = $commenters;

		return $commenters;
	}

	// Get browser icon
	private function icon_img_tag($src, $alt, $title, $style = 'width:16px;height:16px;', $class = '') {
		return '<img src="'.$src.'" alt="'.esc_attr($alt).'" title="'.esc_attr($title).'" style="'.$style.'" '.(!empty($class) ? 'class="'.$class.'" ' : '').'/>';
	}
	private function get_browser_icon($comment_or_ua, $show_ver = true, $separator = '&nbsp;', $class = '') {
		global $comment;

		$icon_dir = $this->plugin_url . 'images/browsers/' ;
		$browser_icon = '';
		$os_info = $pda_info = $browser_info = $unknown_info = '';
		$os_code = $pda_code = $browser_code = $unknown_code = '';

		// Detect Browser and OS
		if ( is_object($comment_or_ua) ) {
			$comment_agent = $comment_or_ua->comment_agent;
		} elseif ( is_numeric($comment_or_ua) ) {
			$comment = get_comment($comment_or_ua);
			$comment_agent = $comment->comment_agent;
		} elseif ( isset($comment) ) {
			$comment_agent = $comment->comment_agent;
		} else {
			$comment_agent = $comment_or_ua;
		}

		// Get browser icon HTML tag
		if ( !empty($comment_agent) ) {
			list( $browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver ) = $this->_detect_browser($comment_agent);

			$icon_dir = $this->plugin_url . 'images/browsers/' ;
			$os_info = ( trim( $os_name . ( $show_ver ? ' ' . $os_ver : '' ) ) );
			$pda_info = ( trim( $pda_name . ( $show_ver ? ' ' . $pda_ver : '' ) ) );
			$browser_info = ( trim( $browser_name . ( $show_ver ? ' ' . $browser_ver : '' ) ) );
			$unknown_info = ( $show_ver ? $comment_agent : 'UNKNOWN' );

		} elseif ( isset($comment) ) {
			if (preg_match('/(tweet|hatena|delicious|livedoor|buzzurl|friendfeed|googleplus)\-/i', $comment->comment_ID, $matches)) {
				$browser_code = esc_attr($matches[1] );
				$browser_info = $browser_code;
				switch ($browser_code) {
				case 'tweet':
					$browser_info = 'twitter';
					break;
				case 'hatena':
					$browser_info = 'hatena bookmark';
					$browser_code = 'hatena_bookmark';
					break;
				case 'livedoor':
					$browser_info = 'livedoor clip';
					$browser_code = 'livedoor_clip';
					break;
				case 'buzzurl':
					$browser_info = 'Buzzurl';
					break;
				case 'friendfeed':
					$browser_info = 'FriendFeed';
					break;
				case 'googleplus':
					$browser_info = 'Google+';
					$browser_code = 'googleplus';
					break;
				}
			}
			unset($matches);
		}

		$style = 'width:16px;height:16px;';
		if ( is_admin() && defined('AKISMET_VERSION') && version_compare(AKISMET_VERSION, "2.5.0", ">=")) {
			$style .= 'position:relative;';
		}
		if ( !empty($os_info) )
			$browser_icon .= $this->icon_img_tag($icon_dir.$os_code.'.png', $os_info, $os_info, $style, $class) . $separator;
		if ( !empty($pda_info) && $os_code !== $pda_code )
			$browser_icon .= $this->icon_img_tag($icon_dir.$pda_code.'.png', $pda_info, $pda_info, $style, $class) . $separator;
		if ( !empty($browser_info) )
			$browser_icon .= $this->icon_img_tag($icon_dir.$browser_code.'.png', $browser_info, $browser_info, $style, $class);
		if ( empty($browser_icon) )
			$browser_icon .= $this->icon_img_tag($icon_dir.'unknown.png', $unknown_info, $unknown_info, $style, $class);

		return ($browser_icon);
	}

	// Get country flag
	private function get_country_flag($comment_or_IP, $class = '') {
		global $comment;

		// Detect Country Name
		if ( is_object($comment_or_IP) ) {
			$comment_IP = $comment_or_IP->comment_author_IP;
		} elseif ( is_numeric($comment_or_IP) ) {
			$comment = get_comment($comment_or_IP);
			$comment_IP = $comment->comment_author_IP;
		} elseif ( preg_match('/^[\d]+\.[\d]+\.[\d]+\.[\d]+\.$/', $comment_or_IP) ) {
			$comment_IP = $comment_or_IP;
		} elseif ( isset($comment) ) {
			$comment_IP = $comment->comment_author_IP;
		} else {
			$comment_IP = '';
		}
		if ( empty($comment_IP) || $comment_IP == '127.0.0.1' ) {
			return '';
		}

		// Get Country flag icon HTML tag
		list( $country_name, $country_code ) = $this->_detect_country($comment_IP);
		if ( empty($country_code) ) {
			$country_code = 'UNKNOWN';
		}

		$style = 'width:16px;height:11px;';
		if ( is_admin() && defined('AKISMET_VERSION') && version_compare(AKISMET_VERSION, "2.5.0", ">=")) {
			$style .= 'position:relative;';
		}
		$country_icon = $this->icon_img_tag($this->plugin_url.'images/flags/'.strtolower($country_code).'.png', $country_name, $country_name, $style, $class);

		return ($country_icon);
	}

	// Get commenters info
	public function get_commenters_info($comment_or_ID = '', $before = '', $after = '', $show_country = true, $show_browser = true, $show_ver = false, $separator = '&nbsp;', $class='') {
		global $comment;

		if ( !$show_country && !$show_browser ) {
			return;
		}

		if ( empty($comment_or_ID) ) {
			$comment_id = $comment->comment_ID;
		} elseif ( is_object($comment_or_ID) ) {
			$comment_id = $comment_or_ID->comment_ID;
		} elseif ( is_numeric($comment_or_ID) ) {
			$comment_id = $comment_or_ID;
		} else {
			$comment_id = $comment->comment_ID;
		}

		$country_flag = (
			$show_country
			? $this->get_country_flag($comment_id, $class)
			: ''
			);
		$browser_icon = (
			$show_browser
			? $this->get_browser_icon($comment_id, $show_ver, $separator, $class)
			: ''
			);

		return (  $before
			. $country_flag
			. ( !empty($browser_icon) && !empty($country_flag) ? $separator : '' )
			. $browser_icon
			. $after
			);
	}

	// Get commenters ranking
	public function get_ranking($limit = 5, $show_admin = false, $avatar_size = 16, $before = '<li>', $after = '</li>', $show_country = false, $show_browser = false, $show_ver = false, $separator = '&nbsp;') {
		if ( $limit <= 0 ) {
			return;
		}

		// sort
		$commenters = $this->commenters;
		$this->_sort_key = 'count';
		$this->_sort_desc = true;
		uasort($commenters, array(&$this, 'cmp'));

		$commenter_list = '';
		$count = 0;
		foreach ($commenters as $key => $row) {
			$user_id = $row['user_id'];

			if ( $show_admin || empty($user_id) ) {
				$avatar = (
					is_numeric($avatar_size)
					? get_avatar($row['email'], (int) $avatar_size)
					: ''
					);

				$author_name = $row['author'];
				$author_name = esc_attr($author_name);

				$author_url = trim(untrailingslashit( 'http://' != $row['url'] ? $row['url'] : ''));
				$author_url = (
					!empty($author_url)
					? trailingslashit(esc_attr($author_url))
					: ''
					);

				$comment_count = (int) $row['count'];
				$comments      = (int) $row['comments'];
				$trackbacks    = (int) $row['trackbacks'];

				$comment = get_comment($row['comment_id']);
				$country_flag = ( $show_country
					? $this->get_country_flag($comment)
					: '' );
				$browser_icon = ( $show_browser
					? $this->get_browser_icon($comment, $show_ver, $separator)
					: '' );

				$commenter_list .=
					  $before
					. $avatar . ( !empty($avatar) ? $separator : '' )
					. $country_flag . ( !empty($country_flag) ? $separator : '' )
					. $browser_icon . ( !empty($browser_icon) ? $separator : '' )
					. ( !empty($author_url)
						? "<a href=\"$author_url\" title=\"$author_name\">$author_name</a>"
						: $author_name )
					. " ( $comment_count ) "
					. $after . "\n";
				$count++;
			}
			if ($count >= $limit) {
				break;
			}
		}
		unset($row); unset($commenters);

		return $commenter_list;
	}

//	// get wp-cron schedule
//	private function schedule_enabled($schedule_procname = self::IP2CDB_CHK_HUNDLER) {
//		$schedule = $this->_get_schedule($schedule_procname);
//		return ($schedule['enabled']);
//	}

//	// IP to Country DB Check & Update
//	public function ip2c_db_check($ver_check = true) {
//		$time_interval = (int) self::IP2CDB_CHK_INTERVAL;
//		$this->_detect_countries->ip2c_db_ver_check($ver_check);
//		if ($time_interval > 0) {
//			wp_schedule_single_event(time() + $time_interval, self::IP2CDB_CHK_HUNDLER);
//		}
//	}


	//**************************************************************************************
	// Function (private)
	//**************************************************************************************

	// Function _get_comment_row_data
	private function _get_comment_row_data($meta_row) {
		$author = trim($meta_row['comment_author']);
		$email  = trim($meta_row['comment_author_email']);
		$url    = trailingslashit(
			!empty($meta_row['host_name'])
			? 'http://' . $meta_row['host_name']
			: trim('http://' != $meta_row['comment_author_url'] ? $meta_row['comment_author_url'] : '')
			);

		$key_url= untrailingslashit(str_replace('http://', '', str_replace('http://www.', '', $url)));
		$key    = (!empty($email) ? $email : $key_url);

		$comment_count = (int) $meta_row['comment_count'];;
		$trackback_count = (int) $meta_row['trackback_count'];
		$count  = $comment_count + $trackback_count;

		$comment_id = $meta_row['comment_ID'];
		$comment = get_comment($comment_id);
		$post_id = $comment->comment_post_ID;
		$user_id = $comment->user_id;
		$comment_date = $comment->comment_date;
		$ptime  = date('G', strtotime( $comment_date ) );
		$ptime  = (
			abs(time() - $ptime) < 86400
			? sprintf( __('%s ago'), human_time_diff( $ptime ) )
			: mysql2date(__('Y/m/d \a\t g:i A'), $comment_date )
			);
		$plink  = get_permalink($post_id) . '#comment-' . $comment_id;
		$last_comment = "<a href=\"{$plink}\">{$ptime}</a>";

		return array($key, $user_id, $author, $email, $url, $comment_count, $trackback_count, $count, $post_id,$comment_id, $comment_date, $last_comment);
	}

	private function _set_comment_row_data($key, $row_data, $commenters) {
		list($user_id, $author, $email, $url, $count, $comment_count, $trackback_count, $last_comment, $comment_id, $comment_date) = $row_data;

		if ( is_array($commenters) && isset($commenters[$key]) ) {
			$count += (
				isset($commenters[$key]['count'])
				? $commenters[$key]['count']
				: 0
				);
			$comment_count += (
				isset($commenters[$key]['comments'])
				? $commenters[$key]['comments']
				: 0
				);
			$trackback_count += (
				isset($commenters[$key]['trackbacks'])
				? $commenters[$key]['trackbacks']
				: 0
				);
			if ( isset($commenters[$key]['comment_date']) && $comment_date < $commenters[$key]['comment_date'] ) {
				$last_comment = ( isset($commenters[$key]['last_comment']) ? $commenters[$key]['last_comment'] : $last_comment );
				$comment_id   = ( isset($commenters[$key]['comment_id'])   ? $commenters[$key]['comment_id']   : $comment_id   );
				$comment_date = ( isset($commenters[$key]['comment_date']) ? $commenters[$key]['comment_date'] : $comment_date );
			}
		} else if ( !is_array($commenters) ) {
			$commenters = array( $key => array() );
		} else {
			$commenters[$key] = array();
		}

		$commenters[$key]['user_id']      = $user_id;
		$commenters[$key]['author']       = $author;
		$commenters[$key]['email']        = $email;
		$commenters[$key]['url']          = $url;
		$commenters[$key]['count']        = $count;
		$commenters[$key]['comments']     = $comment_count;
		$commenters[$key]['trackbacks']   = $trackback_count;
		$commenters[$key]['last_comment'] = $last_comment;
		$commenters[$key]['comment_id']   = $comment_id;
		$commenters[$key]['comment_date'] = $comment_date;

		return $commenters;
	}

	private function _get_comment_authors($commenters) {
		global $wpdb;

		if (!is_array($commenters))
			$commenters = array();

		$wk_commenters = array();
		foreach ( $commenters as $key => $val ) {
			if ( $val['count'] > 0 ) {
				$wk_commenters[$key] = $val;
				$wk_commenters[$key]['count']      = 0;
				$wk_commenters[$key]['comments']   = 0;
				$wk_commenters[$key]['trackbacks'] = 0;
			}
		}
		$commenters = $wk_commenters;

		$host_names = '';
		foreach ( $this->not_subdomain_site as $host_name )
			$host_names .= (!empty($host_names) ? ',' : '') . "'$host_name'";

		// Get Trackbacks & Pingbacks
		$trackbacks = array();
		$meta_list = $wpdb->get_results(
			  "("
			. " SELECT"
			. "   '' as comment_author_email"
			. "  ,comment_author_url"
			. "  ,SUBSTRING_INDEX(REPLACE(comment_author_url, 'http://', ''), '/', 1) as host_name"
			. "  ,comment_author as comment_author"
			. "  ,0 as comment_count"
			. "  ,COUNT(comment_ID) as trackback_count"
			. "  ,MAX(comment_ID) as comment_ID"
			. " FROM"
			. "  {$wpdb->comments}"
			. " WHERE"
			. "  comment_approved = 1"
			. "  and ( comment_type = 'trackback' or comment_type = 'pingback' )"
			. "  and comment_author_url != ''"
			. "  and SUBSTRING_INDEX(REPLACE(comment_author_url, 'http://', ''), '/', 1) not in ($host_names)"
			. " GROUP BY"
			. "  host_name"
			. " ORDER BY"
			. "  comment_date DESC"
			. ")"
			. " UNION "
			. "("
			. " SELECT"
			. "   '' as comment_author_email"
			. "  ,comment_author_url"
			. "  ,SUBSTRING_INDEX(REPLACE(comment_author_url, 'http://', ''), '/', 2) as host_name"
			. "  ,comment_author as comment_author"
			. "  ,0 as comment_count"
			. "  ,COUNT(comment_ID) as trackback_count"
			. "  ,MAX(comment_ID) as comment_ID"
			. " FROM"
			. "  {$wpdb->comments}"
			. " WHERE"
			. "  comment_approved = 1"
			. "  and ( comment_type = 'trackback' or comment_type = 'pingback' )"
			. "  and comment_author_url != ''"
			. "  and SUBSTRING_INDEX(REPLACE(comment_author_url, 'http://', ''), '/', 1) in ($host_names)"
			. " GROUP BY"
			. "  host_name"
			. " ORDER BY"
			. "  comment_date DESC"
			. ")" ,
			ARRAY_A);
		foreach ( (array) $meta_list as $meta_row) {
			list($key, $user_id, $author, $email, $url, $comment_count, $trackback_count, $count, $post_id, $comment_id, $comment_date, $last_comment) = $this->_get_comment_row_data($meta_row);
			if ( !empty($key) ) {
				$trackbacks = $this->_set_comment_row_data($user_id, $key, array($author, $email, $url, $count, $comment_count, $trackback_count, $last_comment, $comment_id, $comment_date), $trackbacks);
			}
		}
		unset($meta_row); unset($meta_list);

		// Get Comments
		$meta_list = $wpdb->get_results(
			  " SELECT"
			. "   comment_author_email"
			. "  ,comment_author_url"
			. "  ,'' as host_name"
			. "  ,comment_author"
			. "  ,COUNT(comment_ID) as comment_count"
			. "  ,0 as trackback_count"
			. "  ,MAX(comment_post_ID) as comment_post_ID"
			. "  ,MAX(comment_ID) as comment_ID"
			. "  ,MAX(comment_date) as comment_date"
			. " FROM"
			. "  {$wpdb->comments}"
			. " WHERE"
			. "  comment_approved = 1"
			. "  and comment_type != 'trackback' and comment_type != 'pingback'"
			. "  and ( comment_author_email != '' or comment_author_url != '' )"
			. " GROUP BY"
			. "  comment_author_email"
			. " ,comment_author_url"
			. " ORDER BY"
			. "  comment_date DESC" ,
			ARRAY_A);
		foreach ( (array) $meta_list as $meta_row) {
			$author = trim($meta_row['comment_author']);
			$email  = trim($meta_row['comment_author_email']);
			$url    = trailingslashit( trim('http://' != $meta_row['comment_author_url'] ? $meta_row['comment_author_url'] : '') );
			$key_url= untrailingslashit(str_replace('http://', '', str_replace('http://www.', '', $url)));
			$key    = (!empty($email) ? $email : $key_url);
			if ( !empty($key_url) && isset($trackbacks[$key_url]) && $trackbacks[$key_url] !== false ) {
				$user_id         = $trackbacks[$key_url]['user_id'];
				$count           = $trackbacks[$key_url]['count'];
				$comment_count   = $trackbacks[$key_url]['comments'];
				$trackback_count = $trackbacks[$key_url]['trackbacks'];
				$last_comment    = $trackbacks[$key_url]['last_comment'];
				$comment_id      = $trackbacks[$key_url]['comment_id'];
				$comment_date    = $trackbacks[$key_url]['comment_date'];
				$trackbacks[$key_url] = false;
				$commenters = $this->_set_comment_row_data($user_id, $key, array($author, $email, $url, $count, $comment_count, $trackback_count, $last_comment, $comment_id, $comment_date), $commenters);
			}

			list($key, $user_id, $author, $email, $url, $comment_count, $trackback_count, $count, $post_id, $comment_id, $comment_date, $last_comment) = $this->_get_comment_row_data($meta_row);
			if ( !empty($key) )
				$commenters = $this->_set_comment_row_data($key, array($user_id, $author, $email, $url, $count, $comment_count, $trackback_count, $last_comment, $comment_id, $comment_date), $commenters);
		}
		unset($meta_row);
		unset($meta_list);

		// Merge
		foreach ( $trackbacks as $trackback ) {
			if ( $trackback !== false && is_array($trackback) && !empty($trackback['url']) ) {
				$key = $trackback['url'];
				$commenters[$key]['user_id']      = $trackback['user_id'];
				$commenters[$key]['author']       = $trackback['author'];
				$commenters[$key]['email']        = $trackback['email'];
				$commenters[$key]['url']          = $trackback['url'];
				$commenters[$key]['count']        = $trackback['count'];
				$commenters[$key]['comments']     = $trackback['comments'];
				$commenters[$key]['trackbacks']   = $trackback['trackbacks'];
				$commenters[$key]['last_comment'] = $trackback['last_comment'];
				$commenters[$key]['comment_id']   = $trackback['comment_id'];
				$commenters[$key]['comment_date'] = $trackback['comment_date'];
			}
		}
		unset($trackbacks);
		unset($trackback);

		return $commenters;
	}

	// _get_browsers_count_sum
	private function _get_browsers_count_sum($type, $without_blog_owner = true, $comments = true, $trackbacks = false) {
		global $wpdb;

		$plathomes = array();

		// Get Trackbacks & Pingbacks
		$meta_list = $wpdb->get_results(
			  " SELECT"
			. "  comment_agent"
			. " ,count(comment_ID) as count"
			. " FROM"
			. "  {$wpdb->comments}"
			. " WHERE"
			. "  comment_approved = '1'"
			. "  AND comment_agent != ''"
			. ( ( $comments && $trackbacks ) || ( !$comments && !$trackbacks )
				? ''
				: " AND ( comment_type " . ($comments ? '!=' : '=') . " 'trackback' " . ($comments ? 'AND' : 'OR') . " comment_type " . ($comments ? '!=' : '=') . " 'pingback' )" )
			. ( $without_blog_owner ? " AND user_id = 0" : '' )
			. " GROUP BY"
			. "  comment_agent" ,
			ARRAY_A);
		foreach ( (array) $meta_list as $meta_row) {
			$ua = $meta_row['comment_agent'];
			$count = (int) $meta_row['count'];

			list( $browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver ) = $this->_detect_browser($ua);
			if (empty($os_code))
				list( $os_name, $os_code, $os_ver ) = array( !empty($pda_code) ? $pda_name : $browser_name, !empty($pda_code) ? $pda_code : $browser_code, !empty($pda_code) ? $pda_ver : $browser_ver );
			if (empty($browser_code))
				list( $browser_name, $browser_code, $browser_ver ) = array( $os_name, $os_code, $os_ver );

			if (empty($browser_name))
				$browser_name = $ua;
			if (empty($browser_code))
				$browser_code = 'unknown';
			if (empty($browser_ver))
				$browser_ver  = 'UNKNOWN';

			if (empty($os_name))
				$os_name = $ua;
			if (empty($os_code))
				$os_code = 'unknown';
			if (empty($os_ver))
				$os_ver  = 'UNKNOWN';

			switch ($type) {
			case 'b':
				$key = $browser_name;
				if ( !isset($plathomes[$key]) ) {
					$plathomes[$key] = array(
						'name' => $browser_name ,
						'code' => $browser_code ,
						'count' => $count ,
						'version' => array( $browser_ver => $count ) ,
						);
				} else {
					$plathomes[$key]['count'] += $count;
					if ( !isset($plathomes[$key]['version'][$browser_ver]) ) {
						$plathomes[$key]['version'][$browser_ver] = $count;
					} else {
						$plathomes[$key]['version'][$browser_ver] += $count;
					}
				}
				break;
			case 'p':
				$key = $os_name;
				if ( !isset($plathomes[$key]) ) {
					$plathomes[$key] = array(
						'name' => $os_name ,
						'code' => $os_code ,
						'count' => $count ,
						'version' => array( $os_ver => $count ) ,
						);
				} else {
					$plathomes[$key]['count'] += $count;
					if ( !isset($plathomes[$key]['version'][$os_ver]) ) {
						$plathomes[$key]['version'][$os_ver] = $count;
					} else {
						$plathomes[$key]['version'][$os_ver] += $count;
					}
				}
				break;
			case 'a':
				$key = $os_name . ( $os_name != $browser_name ? ' ' . $browser_name : '' );
				if ( !isset($plathomes[$key]) ) {
					$plathomes[$key] = array(
						'p_code' => $os_code ,
						'p_name' => $os_name ,
						'b_code' => $browser_code ,
						'b_name' => $browser_name ,
						'count' => $count ,
						);
				} else {
					$plathomes[$key]['count'] += $count;
				}
				break;
			}
		}
		unset($meta_row); unset($meta_list);

		return $plathomes;
	}

	// _get_country_count_sum
	private function _get_country_count_sum($without_blog_owner = true) {
		global $wpdb;

		$countries = array();
		$meta_list = $wpdb->get_results(
			  " SELECT"
			. "  comment_author_IP"
			. " ,count(comment_ID) as count"
			. " FROM"
			. "  {$wpdb->comments}"
			. " WHERE"
			. "  comment_approved = '1'"
			. "  AND comment_author_IP != ''"
			. "  AND comment_author_IP != '127.0.0.1'"
			. ( $without_blog_owner ? " AND user_id = 0" : '' )
			. " GROUP BY"
			. "  comment_author_IP" ,
			ARRAY_A);

		foreach ( (array) $meta_list as $meta_row) {
			$ip = trim($meta_row['comment_author_IP']);
			if ($ip == '127.0.0.1') $ip = '';

			if ( !empty($ip) ) {
				$count = (int) $meta_row['count'];
				list( $country_name, $country_code ) = $this->_detect_country($ip);
				$key = $country_code;
				if ( !isset($countries[$key]) ) {
					$countries[$key] = array(
						'name'  => $country_name ,
						'code'  => $country_code ,
						'count' => $count ,
						);
				} else {
					$countries[$key]['count'] += $count;
				}
			}
		}
		unset($meta_row); unset($meta_list);

		return $countries;
	}

	// 比較用の関数
	public function cmp($a, $b) {
		switch ($this->_sort_key) {
		case 'author':
			$a_val = strtolower(mb_convert_kana($a[$this->_sort_key], 'asKCV', get_option('blog_charset')));
			$b_val = strtolower(mb_convert_kana($b[$this->_sort_key], 'asKCV', get_option('blog_charset')));
			break;
		case 'email':
		case 'url':
		case 'twitter_id':
			$a_val = strtolower($a[$this->_sort_key]);
			$b_val = strtolower($b[$this->_sort_key]);
			break;
		case 'count':
			$a_val = $a['count'];
			$b_val = $b['count'];
			break;
		case 'last_comment':
		default:
			$a_val = $a['comment_date'];
			$b_val = $b['comment_date'];
			break;
		}
		if ($a_val == $b_val) {
			$a_val = (!empty($a['email']) ? $a['email'] : $a['url']);
			$b_val = (!empty($b['email']) ? $b['email'] : $b['url']);
		}

		if ($a_val == $b_val)
			return 0;
		elseif ($this->_sort_desc)
			return ($a_val > $b_val ? -1 : 1);
		else
			return ($a_val < $b_val ? -1 : 1);
	}

	public function _show_list_type_selection($type = 'c') {
?>
<select name="list">
<option value="c"<?php echo ($type == 'c' ? ' selected="selected"' : ''); ?>><?php _e('Commenters list', $this->textdomain_name); ?></option>
<option value="b"<?php echo ($type == 'b' ? ' selected="selected"' : ''); ?>><?php _e('Browsers summary', $this->textdomain_name); ?></option>
<option value="p"<?php echo ($type == 'p' ? ' selected="selected"' : ''); ?>><?php _e('Plathome summary', $this->textdomain_name); ?></option>
<option value="a"<?php echo ($type == 'a' ? ' selected="selected"' : ''); ?>><?php _e('Browsers + Plathome summary', $this->textdomain_name); ?></option>
<option value="t"<?php echo ($type == 't' ? ' selected="selected"' : ''); ?>><?php _e('Trackbacks summary', $this->textdomain_name); ?></option>
<option value="l"<?php echo ($type == 'l' ? ' selected="selected"' : ''); ?>><?php _e('Location summary', $this->textdomain_name); ?></option>
</select>
<?php
	}

	// Get authors list
	private function _comment_authors_list($display_twitter_id, $page, $key, $sort) {
		// Get Commenters List
		if ( !is_array($this->commenters) )
			$this->commenters = $this->get_commenters_list();

		// Page Links
		$total = count($this->commenters);
		$comment_info_per_page = self::LIST_PER_PAGE;
		$start = ($page - 1) * $comment_info_per_page;

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'apage', '%#%' ) ,
			'format' => '' ,
			'prev_text' => __('&laquo;') ,
			'next_text' => __('&raquo;') ,
			'total' => ceil($total / $comment_info_per_page) ,
			'current' => $page ,
		));

		$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
			number_format_i18n( $start + 1 ),
			number_format_i18n( min( $page * $comment_info_per_page, $total ) ),
			number_format_i18n( $total ),
			$page_links
		);

		// sort
		$commenters = $this->commenters;
		if ( isset( $_GET['key'] ) ) {
			$this->_sort_key  = $_GET['key'];

			$this->_sort_desc = (
				  $this->_sort_key == 'count' || $this->_sort_key == 'last_comment'
				? true
				: false
				);
			$this->_sort_desc = (
				  isset( $_GET['sort'] )
				? $_GET['sort'] == 'desc'
				: $this->_sort_desc
				);
		}
		uasort($commenters, array(&$this, 'cmp'));

		$commenters = array_slice($commenters, $start, $comment_info_per_page);

		// Get Commenters Row
		$row_num = 0;
		$comment_author_row = '';
		foreach ($commenters as $key => $val) {
			$comment_author_row .= $this->_comment_author_row($key, $val, $row_num++, $display_twitter_id);
		}

?>
<div id="icon-edit-comments" class="icon32"><br /></div>
<h2><?php _e('Commenters Info', $this->textdomain_name); ?></h2>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<div class="alignleft actions">
<form id="comments-sort" action="" method="get">
<input type="hidden" name="page" value="<?php echo plugin_basename(__FILE__); ?>" />
<?php echo $this->_show_list_type_selection('c'); ?>
<select name="key">
<option value="author"<?php echo ($this->_sort_key == 'author' ? ' selected="selected"' : ''); ?>><?php _e('Author'); ?></option>
<option value="email"<?php echo ($this->_sort_key == 'email' ? ' selected="selected"' : ''); ?>><?php _e('E-mail'); ?></option>
<option value="url"<?php echo ($this->_sort_key == 'url' ? ' selected="selected"' : ''); ?>><?php _e('URL'); ?></option>
<?php if ($display_twitter_id) : ?>
<option value="twitter_id"<?php echo ($this->_sort_key == 'twitter_id' ? ' selected="selected"' : ''); ?>><?php _e('Twitter ID', $this->textdomain_name); ?></option>
<?php endif; ?>
<option value="count"<?php echo ($this->_sort_key == 'count' ? ' selected="selected"' : ''); ?>><?php _e('Count', $this->textdomain_name); ?></option>
<option value="last_comment"<?php echo ($this->_sort_key == 'last_comment' ? ' selected="selected"' : ''); ?>><?php _e('Last Comment', $this->textdomain_name); ?></option>
</select>
<select name="sort">
<option value="asc"<?php echo (!$this->_sort_desc ? ' selected="selected"' : ''); ?>><?php _e('Ascending order', $this->textdomain_name); ?>&nbsp;</option>
<option value="desc"<?php echo ($this->_sort_desc ? ' selected="selected"' : ''); ?>><?php _e('Descending order', $this->textdomain_name); ?>&nbsp;</option>
</select>
<input type="submit" value="<?php _e('Apply'); ?>" class="button-secondary apply" />
</form>
</div>

<br class="clear" />

</div>

<div class="clear"></div>
<table class="widefat comments fixed" cellspacing="0">
<thead>
	<tr>
	<th scope="col" id="author" class="manage-column column-author" style=""><?php _e('Author'); ?></th>
	<th scope="col" id="email" class="manage-column column-email" style=""><?php _e('E-mail'); ?></th>
	<th scope="col" id="url" class="manage-column column-url" style=""><?php _e('URL'); ?></th>
<?php if ($display_twitter_id) : ?>
	<th scope="col" id="twitter_id" class="manage-column column-twitter" style=""><?php _e('Twitter ID', $this->textdomain_name); ?></th>
<?php endif; ?>
	<th scope="col" id="count" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?><br /><?php _e('(comments / pingbacks)', $this->textdomain_name); ?></th>
	<th scope="col" id="last-comment" class="manage-column column-comment" style=""><?php _e('Last Comment', $this->textdomain_name); ?></th>
	</tr>

</thead>

<tfoot>
	<tr>
	<th scope="col" class="manage-column column-author" style=""><?php _e('Author'); ?></th>
	<th scope="col" class="manage-column column-email" style=""><?php _e('E-mail'); ?></th>
	<th scope="col" class="manage-column column-url" style=""><?php _e('URL'); ?></th>
<?php if ($display_twitter_id) : ?>
	<th scope="col" class="manage-column column-twitter" style=""><?php _e('Twitter ID', $this->textdomain_name); ?></th>
<?php endif; ?>
	<th scope="col" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?><br /><?php _e('(comments / pingbacks)', $this->textdomain_name); ?></th>
	<th scope="col" class="manage-column column-comment" style=""><?php _e('Last Comment', $this->textdomain_name); ?></th>
	</tr>

</tfoot>

<tbody id="the-comment-list" class="list:comment">
<?php echo $comment_author_row; ?>
</tbody>
</table>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<br class="clear" />

</div>

<?php
	}

	// _graph_js
	private function _graph_js($list) {
		$items = ''; $other_count = 0;
		$i = 0;
		foreach ($list as $key => $row) {
			$item = (!empty($items) ? ',' : '') . '["' . $key .'",' . (int) $row['count'] . ']';
			if (count($list) <= 10) {
				$items .= $item;
			} else {
				if ($i < 9)
					$items .= $item;
				else
					$other_count += (int) $row['count'];
			}
			$i++;
		}
		$other_item = ( $other_count > 0
			? ',["'.__('other', $this->textdomain_name).'",'.$other_count.']'
			: ''
			);
		$graph_js =
			'<script type="text/javascript">//<![CDATA[' . "\n" .
			'jQuery(function(){' .
			'var cg = new html5jp.graph.circle("graph");' .
			'var items = [' . $items . $other_item . '];' .
			'var params = {otherCaption:"' . __('other', $this->textdomain_name) . '"};' .
			'cg.draw(items, params);' .
			'});' . "\n" .
			'//]]></script>' . "\n";
		return $graph_js;
	}

	// _comment_author_row
	private function _comment_author_row($key, $row, $row_num, $display_twitter_id) {
		$avatar = get_avatar($row['email'], 32);

		$author_name = $row['author'];
		$author_name = esc_attr($author_name);

		$author_url = ( 'http://' != $row['url'] ? $row['url'] : '');
		$author_url = esc_attr($author_url);
		$author_url_display = untrailingslashit($author_url);
		$author_url_display = str_replace('http://www.', '', $author_url_display);
		$author_url_display = str_replace('http://', '', $author_url_display);
		$author_url_display = esc_attr($author_url_display);

		$author_email = mb_encode_numericentity($row['email'], array(0x0, 0x10000, 0, 0xfffff), get_option('blog_charset'));
		$author_email = esc_attr($author_email);

		$comment_count = (int) $row['count'];
		$comments      = (int) $row['comments'];
		$trackbacks    = (int) $row['trackbacks'];

		$comment = get_comment($row['comment_id']);
		$comment_post_id = $comment->comment_post_ID;
		$comment_country = $this->get_country_flag($comment, true, '&nbsp;', 'commentersinfo');
		$comment_browser = $this->get_browser_icon($comment, true, '&nbsp;', 'commentersinfo');

		$last_comment = 
			$row['last_comment'] . '<br />' .
			$comment_country . '&nbsp;' .
			$comment_browser ;

		$out = '';

		if ($comment_count > 0) {
			$out .=
				"<tr id=\"author-{$row_num}\">" .
				"<td class=\"author column-author\"><strong><span style=\"margin-left:.5em;\">{$avatar}</span>{$author_name}</strong></td>" .
				"<td class=\"column-email\"><a href=\"mailto:{$author_email}\" title=\"" . sprintf( __('e-mail: %s' ), $author_email ) . "\">{$author_email}</a></td>" .
				"<td class=\"column-url\"><a title=\"{$author_url}\" href=\"{$author_url}\">{$author_url_display}</a></td>" ;
			if ($display_twitter_id) {
				$twitter_id = $this->_detect_twitter_id($comment_post_id, $row['comment_id'], $row['email']);
				$twitter_id = esc_attr($twitter_id);
				$out .=
					"<td class=\"column-twitter\">" .
					( !empty($twitter_id)
					? "<a title=\"{$twitter_id} on Twitter\" href=\"http://twitter.com/{$twitter_id}\">{$twitter_id}</a>"
					: "" ) .
					"</td>" ;
			}
			$out .= "<td class=\"column-count\" style=\"text-align:right;\">{$comment_count}<br />( {$comments} / {$trackbacks} )</td>" ;
			$out .= "<td class=\"column-comment\">{$last_comment}</td>" ;
			$out .= "</tr>\n" ;
		}
		return $out;
	}

	// _comment_browsers_list
	private function _comment_browsers_list($page, $without_blog_owner = true, $key = 'count', $sort = 'desc' ) {
		// Get Browsers List
		$browsers = $this->_get_browsers_count_sum('b', $without_blog_owner);

		// Page Links
		$total = count($browsers);
		$browser_info_per_page = self::LIST_PER_PAGE;
		$start = ($page - 1) * $browser_info_per_page;

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'apage', '%#%' ) ,
			'format' => '' ,
			'prev_text' => __('&laquo;') ,
			'next_text' => __('&raquo;') ,
			'total' => ceil($total / $browser_info_per_page) ,
			'current' => $page
		));

		$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
			number_format_i18n( $start + 1 ),
			number_format_i18n( min( $page * $browser_info_per_page, $total ) ),
			number_format_i18n( $total ),
			$page_links
		);

		// sort
		$this->_sort_key  = $key;
		$this->_sort_desc = ( $sort == 'desc' );
		uasort($browsers, array(&$this, 'cmp'));

		// circle graph JavaScript
		echo $this->_graph_js($browsers);

		// Get Browsers Row
		$browsers = array_slice($browsers, $start, $browser_info_per_page);
		$row_num = 0;
		$comment_browser_row = '';
		foreach ($browsers as $key => $val) {
			$comment_browser_row .= $this->_comment_browser_row($key, $val, $row_num++);
		}

?>
<div id="icon-edit-comments" class="icon32"><br /></div>
<h2><?php _e('Commenters Info', $this->textdomain_name); ?></h2>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<div class="alignleft actions">
<form id="comments-sort" action="" method="get">
<input type="hidden" name="page" value="<?php echo plugin_basename(__FILE__); ?>" />
<?php echo $this->_show_list_type_selection('b'); ?>
<select name="owner">
<option value="with"<?php echo (!$without_blog_owner ? ' selected="selected"' : ''); ?>><?php _e('with blog owner', $this->textdomain_name); ?></option>
<option value="without"<?php echo ($without_blog_owner ? ' selected="selected"' : ''); ?>><?php _e('without blog owner', $this->textdomain_name); ?></option>
</select>
<input type="submit" value="<?php _e('Apply'); ?>" class="button-secondary apply" />
</form>
</div>

<br class="clear" />

</div>

<div class="clear"></div>
<table class="widefat fixed" cellspacing="0" style="width:40%;">
<thead>
	<tr>
	<th scope="col" id="browser" class="manage-column column-browser" style=""><?php _e('Browser', $this->textdomain_name); ?></th>
	<th scope="col" id="count" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</thead>

<tfoot>
	<tr>
	<th scope="col" class="manage-column column-browser" style=""><?php _e('Browser', $this->textdomain_name); ?></th>
	<th scope="col" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</tfoot>

<tbody id="the-comment-list" class="list:comment">
<?php echo $comment_browser_row; ?>
</tbody>
</table>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<br class="clear" />

</div>

<?php
	}

	// _comment_browser_row
	private function _comment_browser_row($key, $row, $row_num) {
		$icon_dir = $this->plugin_url . 'images/browsers/' ;
		$browser_name  = esc_attr($row['name']);
		$browser_code  = strtolower($row['code']);
		$browser_count = (int) $row['count'];
		$browser_icon  = "<img src=\"{$icon_dir}{$browser_code}.png\" alt=\"$browser_name\" title=\"$browser_name\" width=\"16\" height=\"16\" />";

		if ($browser_count > 0) {
			$out .=
				"<tr id=\"browser-{$row_num}\">".
				"<td class=\"column-browser\" style=\"white-space:nowrap;\"><span style=\"margin:0 .5em;\">{$browser_icon}</span><strong>{$browser_name}</strong></td>" .
				"<td class=\"column-count\" style=\"text-align:right;\">{$browser_count}</td>" .
				"</tr>\n" ;
		}
		return $out;
	}

	// _comment_plathome_list
	private function _comment_plathome_list($page, $without_blog_owner = true, $key = 'count', $sort = 'desc' ) {
		// Get Plathome List
		$plathomes = $this->_get_browsers_count_sum('p', $without_blog_owner);

		// Page Links
		$total = count($plathomes);
		$plathome_info_per_page = self::LIST_PER_PAGE;
		$start = ($page - 1) * $plathome_info_per_page;

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'apage', '%#%' ) ,
			'format' => '' ,
			'prev_text' => __('&laquo;') ,
			'next_text' => __('&raquo;') ,
			'total' => ceil($total / $plathome_info_per_page) ,
			'current' => $page ,
		));

		$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
			number_format_i18n( $start + 1 ),
			number_format_i18n( min( $page * $plathome_info_per_page, $total ) ),
			number_format_i18n( $total ),
			$page_links
		);

		// sort
		$this->_sort_key  = $key;
		$this->_sort_desc = ( $sort == 'desc' );
		uasort($plathomes, array(&$this, 'cmp'));

		// circle graph JavaScript
		echo $this->_graph_js($plathomes);

		// Get Plathomes Row
		$plathomes = array_slice($plathomes, $start, $plathome_info_per_page);
		$row_num = 0;
		$comment_plathome_row = '';
		foreach ($plathomes as $key => $val) {
			$comment_plathome_row .= $this->_comment_plathome_row($key, $val, $row_num++);
		}

?>
<div id="icon-edit-comments" class="icon32"><br /></div>
<h2><?php _e('Commenters Info', $this->textdomain_name); ?></h2>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<div class="alignleft actions">
<form id="comments-sort" action="" method="get">
<input type="hidden" name="page" value="<?php echo plugin_basename(__FILE__); ?>" />
<?php echo $this->_show_list_type_selection('p'); ?>
<select name="owner">
<option value="with"<?php echo (!$without_blog_owner ? ' selected="selected"' : ''); ?>><?php _e('with blog owner', $this->textdomain_name); ?></option>
<option value="without"<?php echo ($without_blog_owner ? ' selected="selected"' : ''); ?>><?php _e('without blog owner', $this->textdomain_name); ?></option>
</select>
<input type="submit" value="<?php _e('Apply'); ?>" class="button-secondary apply" />
</form>
</div>

<br class="clear" />

</div>

<div class="clear"></div>
<table class="widefat fixed" cellspacing="0" style="width:40%;">
<thead>
	<tr>
	<th scope="col" id="plathome" class="manage-column column-plathome" style=""><?php _e('Plathome', $this->textdomain_name); ?></th>
	<th scope="col" id="count" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</thead>

<tfoot>
	<tr>
	<th scope="col" class="manage-column column-plathome" style=""><?php _e('Plathome', $this->textdomain_name); ?></th>
	<th scope="col" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</tfoot>

<tbody id="the-comment-list" class="list:comment">
<?php echo $comment_plathome_row; ?>
</tbody>
</table>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<br class="clear" />

</div>

<?php
	}

	// _comment_plathome_row
	private function _comment_plathome_row($key, $row, $row_num) {
		$icon_dir = $this->plugin_url . 'images/browsers/' ;
		$plathome_name  = esc_attr($row['name']);
		$plathome_code  = strtolower($row['code']);
		$plathome_count = (int) $row['count'];
		$plathome_icon  = "<img src=\"{$icon_dir}{$plathome_code}.png\" alt=\"$plathome_name\" title=\"$plathome_name\" width=\"16\" height=\"16\" />";

		if ($plathome_count > 0) {
			$out .=
				"<tr id=\"plathome-{$row_num}\">" .
				"<td class=\"column-plathome\" style=\"white-space:nowrap;\"><span style=\"margin:0 .5em;\">{$plathome_icon}</span><strong>{$plathome_name}</strong></td>" .
				"<td class=\"column-count\" style=\"text-align:right;\">{$plathome_count}</td>" .
				"</tr>\n" ;
		}
		return $out;
	}

	// _comment_agentes_list
	private function _comment_agentes_list($page, $without_blog_owner = true, $key = 'count', $sort = 'desc' ) {
		// Get Agentes List
		$agentes = $this->_get_browsers_count_sum('a', $without_blog_owner);

		// Page Links
		$total = count($agentes);
		$agentes_info_per_page = self::LIST_PER_PAGE;
		$start = ($page - 1) * $agentes_info_per_page;

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'apage', '%#%' ) ,
			'format' => '' ,
			'prev_text' => __('&laquo;') ,
			'next_text' => __('&raquo;') ,
			'total' => ceil($total / $agentes_info_per_page) ,
			'current' => $page ,
		));

		$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
			number_format_i18n( $start + 1 ),
			number_format_i18n( min( $page * $agentes_info_per_page, $total ) ),
			number_format_i18n( $total ),
			$page_links
		);

		// sort
		$this->_sort_key  = $key;
		$this->_sort_desc = ( $sort == 'desc' );
		uasort($agentes, array(&$this, 'cmp'));

		// circle graph JavaScript
		echo $this->_graph_js($agentes);

		// Get Agentes Row
		$agentes = array_slice($agentes, $start, $agentes_info_per_page);
		$row_num = 0;
		$comment_agentes_row = '';
		foreach ($agentes as $key => $val) {
			$comment_agentes_row .= $this->_comment_agentes_row($key, $val, $row_num++);
		}

?>
<div id="icon-edit-comments" class="icon32"><br /></div>
<h2><?php _e('Commenters Info', $this->textdomain_name); ?></h2>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<div class="alignleft actions">
<form id="comments-sort" action="" method="get">
<input type="hidden" name="page" value="<?php echo plugin_basename(__FILE__); ?>" />
<?php echo $this->_show_list_type_selection('a'); ?>
<select name="owner">
<option value="with"<?php echo (!$without_blog_owner ? ' selected="selected"' : ''); ?>><?php _e('with blog owner', $this->textdomain_name); ?></option>
<option value="without"<?php echo ($without_blog_owner ? ' selected="selected"' : ''); ?>><?php _e('without blog owner', $this->textdomain_name); ?></option>
</select>
<input type="submit" value="<?php _e('Apply'); ?>" class="button-secondary apply" />
</form>
</div>

<br class="clear" />

</div>

<div class="clear"></div>
<table class="widefat fixed" cellspacing="0" style="width:40%;">
<thead>
	<tr>
	<th scope="col" id="agentes" class="manage-column column-agentes" style=""><?php _e('Browser + Plathome', $this->textdomain_name); ?></th>
	<th scope="col" id="count" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</thead>

<tfoot>
	<tr>
	<th scope="col" class="manage-column column-agentes" style=""><?php _e('Browser + Plathome', $this->textdomain_name); ?></th>
	<th scope="col" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</tfoot>

<tbody id="the-comment-list" class="list:comment">
<?php echo $comment_agentes_row; ?>
</tbody>
</table>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<br class="clear" />

</div>

<?php
	}

	// _comment_agentes_row
	private function _comment_agentes_row($key, $row, $row_num) {
		$icon_dir = $this->plugin_url . 'images/browsers/' ;
		$plathome_name  = esc_attr($row['p_name']);
		$plathome_code  = strtolower($row['p_code']);
		$plathome_icon  = "<img src=\"{$icon_dir}{$plathome_code}.png\" alt=\"$plathome_name\" title=\"$plathome_name\" width=\"16\" height=\"16\" />";
		$browser_name   = esc_attr($row['b_name']);
		$browser_code   = strtolower($row['b_code']);
		$browser_icon   = "<img src=\"{$icon_dir}{$browser_code}.png\" alt=\"$browser_name\" title=\"$browser_name\" width=\"16\" height=\"16\" />";

		$agentes_count = (int) $row['count'];

		if ($agentes_count > 0) {
			$out .=
				"<tr id=\"agentes-{$row_num}\">" .
				"<td class=\"column-agentes\" style=\"white-space:nowrap;\">" .
				"<span style=\"margin:0 .5em;\">{$plathome_icon}</span><strong>{$plathome_name}</strong>" .
				( $browser_name !== $plathome_name
				? "<span style=\"margin:0 .5em;\">{$browser_icon}</span><strong>{$browser_name}</strong>"
				: '' ) .
				"</td>" .
				"<td class=\"column-count\" style=\"text-align:right;\">{$agentes_count}</td>" .
				"</tr>\n" ;
		}
		return $out;
	}

	// _trackback_browsers_list
	private function _trackback_browsers_list($page, $key = 'count', $sort = 'desc' ) {
		// Get Browsers List
		$browsers = $this->_get_browsers_count_sum('b', false, false, true);

		// Page Links
		$total = count($browsers);
		$browser_info_per_page = self::LIST_PER_PAGE;
		$start = ($page - 1) * $browser_info_per_page;

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'apage', '%#%' ) ,
			'format' => '' ,
			'prev_text' => __('&laquo;') ,
			'next_text' => __('&raquo;') ,
			'total' => ceil($total / $browser_info_per_page) ,
			'current' => $page ,
		));

		$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
			number_format_i18n( $start + 1 ),
			number_format_i18n( min( $page * $browser_info_per_page, $total ) ),
			number_format_i18n( $total ),
			$page_links
		);

		// sort
		$this->_sort_key  = $key;
		$this->_sort_desc = ( $sort == 'desc' );
		uasort($browsers, array(&$this, 'cmp'));

		// circle graph JavaScript
		echo $this->_graph_js($browsers);

		// Get Browsers Row
		$browsers = array_slice($browsers, $start, $browser_info_per_page);
		$row_num = 0;
		$trackback_browser_row = '';
		foreach ($browsers as $key => $val)
			$trackback_browser_row .= $this->_comment_browser_row($key, $val, $row_num++);

?>
<div id="icon-edit-comments" class="icon32"><br /></div>
<h2><?php _e('Commenters Info', $this->textdomain_name); ?></h2>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<div class="alignleft actions">
<form id="comments-sort" action="" method="get">
<input type="hidden" name="page" value="<?php echo plugin_basename(__FILE__); ?>" />
<?php echo $this->_show_list_type_selection('t'); ?>
<input type="submit" value="<?php _e('Apply'); ?>" class="button-secondary apply" />
</form>
</div>

<br class="clear" />

</div>

<div class="clear"></div>
<table class="widefat fixed" cellspacing="0" style="width:40%;">
<thead>
	<tr>
	<th scope="col" id="browser" class="manage-column column-browser" style=""><?php _e('Blog Tool', $this->textdomain_name); ?></th>
	<th scope="col" id="count" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</thead>

<tfoot>
	<tr>
	<th scope="col" class="manage-column column-browser" style=""><?php _e('Blog Tool', $this->textdomain_name); ?></th>
	<th scope="col" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</tfoot>

<tbody id="the-comment-list" class="list:comment">
<?php echo $trackback_browser_row; ?>
</tbody>
</table>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<br class="clear" />

</div>

<?php
	}


	// _comment_countries_list
	private function _comment_countries_list($page, $without_blog_owner = true, $key = 'count', $sort = 'desc' ) {
		// Get Browsers List
		$countries = $this->_get_country_count_sum($without_blog_owner);

		// Page Links
		$total = count($browsers);
		$countries_info_per_page = self::LIST_PER_PAGE;
		$start = ($page - 1) * $countries_info_per_page;

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'apage', '%#%' ) ,
			'format' => '' ,
			'prev_text' => __('&laquo;') ,
			'next_text' => __('&raquo;') ,
			'total' => ceil($total / $countries_info_per_page) ,
			'current' => $page ,
		));

		$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
			number_format_i18n( $start + 1 ),
			number_format_i18n( min( $page * $countries_info_per_page, $total ) ),
			number_format_i18n( $total ),
			$page_links
		);

		// sort
		$this->_sort_key  = $key;
		$this->_sort_desc = ( $sort == 'desc' );
		uasort($countries, array(&$this, 'cmp'));

		// circle graph JavaScript
		echo $this->_graph_js($countries);

		// Get Countries Row
		$countries = array_slice($countries, $start, $countries_info_per_page);
		$row_num = 0;
		$comment_country_row = '';
		foreach ($countries as $key => $val) {
			$comment_country_row .= $this->_comment_country_row($key, $val, $row_num++);
		}

?>
<div id="icon-edit-comments" class="icon32"><br /></div>
<h2><?php _e('Commenters Info', $this->textdomain_name); ?></h2>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<div class="alignleft actions">
<form id="comments-sort" action="" method="get">
<input type="hidden" name="page" value="<?php echo plugin_basename(__FILE__); ?>" />
<?php echo $this->_show_list_type_selection('l'); ?>
<select name="owner">
<option value="with"<?php echo (!$without_blog_owner ? ' selected="selected"' : ''); ?>><?php _e('with blog owner', $this->textdomain_name); ?></option>
<option value="without"<?php echo ($without_blog_owner ? ' selected="selected"' : ''); ?>><?php _e('without blog owner', $this->textdomain_name); ?></option>
</select>
<input type="submit" value="<?php _e('Apply'); ?>" class="button-secondary apply" />
</form>
</div>

<br class="clear" />

</div>

<div class="clear"></div>
<table class="widefat fixed" cellspacing="0" style="width:40%;">
<thead>
	<tr>
	<th scope="col" id="country" class="manage-column column-country" style=""><?php _e('Country', $this->textdomain_name); ?></th>
	<th scope="col" id="count" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</thead>

<tfoot>
	<tr>
	<th scope="col" class="manage-column column-country" style=""><?php _e('Country', $this->textdomain_name); ?></th>
	<th scope="col" class="manage-column column-count" style="text-align:right;"><?php _e('Count', $this->textdomain_name); ?></th>
	</tr>
</tfoot>

<tbody id="the-comment-list" class="list:comment">
<?php echo $comment_country_row; ?>
</tbody>
</table>

<div class="tablenav">
<?php if ( $page_links ) echo "<div class=\"tablenav-pages\">$page_links_text</div>"; ?>

<br class="clear" />

</div>

<?php
	}

	// _comment_country_row
	private function _comment_country_row($key, $row, $row_num) {
		$icon_dir = $this->plugin_url . 'images/flags/' ;
		$country_name  = esc_attr($row['name']);
		$country_code  = strtolower($row['code']);
		if ( empty($country_code) ) {
			$country_name = 'UNKNOWN';
			$country_code = strtolower($country_name);
		}

		$country_count = (int) $row['count'];
		$country_icon  = "<img src=\"{$icon_dir}{$country_code}.png\" alt=\"$country_name\" title=\"$country_name\" width=\"16\" height=\"11\" />";

		if ($country_count > 0) {
			$out .=
				"<tr id=\"country-{$row_num}\">" .
				"<td class=\"column-country\" style=\"white-space:nowrap;\"><span style=\"margin:0 .5em;\">{$country_icon}</span><strong>{$country_name}</strong></td>" .
				"<td class=\"column-count\" style=\"text-align:right;\">{$country_count}</td>" .
				"</tr>\n" ;
		}
		return $out;
	}

	// Detect Twitter ID
	private function _display_twitter_id() {
		global $twicon;

		$display = false;
		if ( ( isset($twicon) && isset($twicon->plugin_ver) && version_compare($twicon->plugin_ver, "1.3.0", ">=") ) || defined('QC_NOTIFY_TWITTER') ) {
			$display = true;
		}

		return $display;
	}
	private function _detect_twitter_id($post_id, $comment_id, $email) {
		global $twicon;

		$twitter_id = '';
		if ( isset($twicon) && isset($twicon->plugin_ver) && version_compare($twicon->plugin_ver, "1.3.0", ">=") ) {
			$twitter_id = $twicon->_get_twitter_id($post_id, $comment_id, $email);

		} elseif ( !empty($post_id) && !empty($comment_id) && defined('QC_NOTIFY_TWITTER') ) {
			if ( !isset($this->_meta_value_cache[$post_id]) ) {
				$this->_meta_value_cache[$post_id] = maybe_unserialize(get_post_meta($post_id, QC_NOTIFY_TWITTER, true));
			}
			if ( is_array($this->_meta_value_cache[$post_id]) ) {
				foreach ($this->_meta_value_cache[$post_id] as $key => $val) {
					if (!empty($key) && in_array($comment_id, $val, false)) {
						$twitter_id = $key;
						break;
					}
				}
				unset($val);
			}
		}

		return ($twitter_id !== false ? $twitter_id : '');
	}

	// Detect Browser
	private function _detect_browser($ua) {
		return $this->_detect_browsers->get_info($ua);
	}

	// Detect Country
	private function _detect_country($ip) {
		return $this->_detect_countries->get_info($ip);
	}

	// Get Schedule
	private function _get_schedule($force = false, $schedule_procname = self::IP2CDB_CHK_HUNDLER) {
		$schedule = (!$force ? (array) maybe_unserialize(wp_cache_get("COMMENTERS_INFO_SCHEDULE")) : FALSE);
		if ($schedule !== FALSE) {
			return ($schedule);
		}

		$schedule = array(
			'procname' => '' ,
			'enabled' => FALSE ,
			'text' => '' ,
			'time' => '' ,
			'last_log' => '' ,
		);

		$crons = _get_cron_array();
		if ( empty($crons) ) {
			$schedule['text'] = __('Nothing scheduled.');
		} else {
			foreach ( $crons as $time => $tasks ) {
				foreach ( $tasks as $procname => $task ) {
					if ($procname === self::IP2CDB_CHK_HUNDLER) {
						$schedule['procname'] = $procname;
						$schedule['text'] = '<p>' . sprintf(__('Anytime after <strong>%s</strong> execute tasks.'), date($this->datetime_format, $time)) . '</p>';
						$schedule['time'] = $time;
						$schedule['enabled'] = true;
						break;
					}
				}
				if ($schedule['enabled']) {
					break;
				}
			}
			unset($procname); unset($task);
			unset($time); unset ($tasks);
		}
		unset($crons);

		wp_cache_set("COMMENTERS_INFO_SCHEDULE", maybe_serialize($schedule));

		return ($schedule);
	}
}

/******************************************************************************
 * CommentersWidgetController Class ( for WP2.8+ )
 *****************************************************************************/
if ( class_exists('WP_Widget') && false ) :

class CommentersWidgetController extends WP_Widget {
	public $plugin_name;
	public $textdomain_name;

	/**********************************************************
	* Constructor
	***********************************************************/
	function __construct() {
		global $commenters_info;
		if ( !isset($commenters_info) ) {
			$commenters_info = new CommentersInfo();
		}
		$this->plugin_name = $commenters_info->plugin_name;
		$this->textdomain_name = $commenters_info->textdomain_name;

		$widget_ops = array(
			'classname' => 'widget_' . $this->plugin_name ,
			'description' => __("Commenters Ranking", $this->textdomain_name)
			);
		$this->WP_Widget('commenters-ranking', 'commenters-ranking', $widget_ops);
	}

	//**************************************************************************************
	// output Widget
	//**************************************************************************************
	public function widget( $args, $instance ) {
		global $commenters_info;
		$commenters_info->widgetOutput($args);
	}

	//**************************************************************************************
	// Widget Register
	//**************************************************************************************
	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	public function form( $instance ) {
		global $commenters_info;

		$field_info = array(
			'title' => array( 'id' => $this->get_field_id('title'), 'name' => $this->get_field_name('title') ) ,
			'limit' => array( 'id' => $this->get_field_id('limit'), 'name' => $this->get_field_name('limit') ) ,
			'avatar_size' => array( 'id' => $this->get_field_id('avatar_size'), 'name' => $this->get_field_name('avatar_size') ) ,
			);

		$commenters_info->widgetForm( $instance, $field_info );
	}
}

endif;

/******************************************************************************
 * Go Go Go!
 *****************************************************************************/
$commenters_info = new CommentersInfo();
