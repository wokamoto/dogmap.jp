<?php
/*
Plugin Name: Simple Tweet
Version: 1.3.8.2
Plugin URI: http://wppluginsj.sourceforge.jp/simple-tweet/
Description: This is a plugin creating a new tweet including a URL of new post on your wordpress.
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: simple-tweet
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2008 - 2011 wokamoto (email : wokamoto1973@gmail.com)

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
 abraham's twitteroauth at master - GitHub
  PHP library for working with Twitter's OAuth API.
  Copyright (c) 2009 Abraham Williams - http://abrah.am - abraham@poseurte.ch
  Documentation: http://wiki.github.com/abraham/twitteroauth/documentation
  Source: http://github.com/abraham/twitteroauth
  Twitter: http://apiwiki.twitter.com
*/

/**************************************************************************************
 * Global
 *************************************************************************************/
global $simple_tweet;


/**************************************************************************************
 * Require Twitter OAuth
 *************************************************************************************/
if ( version_compare(phpversion(), "5.0.0", ">=") && function_exists('curl_init') && !class_exists('TwitterOAuth') )
	require_once(dirname(__FILE__).'/includes/twitterOAuth.php');


/**************************************************************************************
 * Template Tag tweet_this_link
 *  usage : <?php if (function_exists('tweet_this_link')) tweet_this_link(); ?>
 *************************************************************************************/
function tweet_this_link($inreply_to = FALSE, $echo = TRUE) {
	global $simple_tweet;

	if ( !isset($simple_tweet) )
		$simple_tweet = new SimpleTweet();

	$tweet_this = $simple_tweet->tweet_this_link($inreply_to);
	if ( $tweet_this === FALSE )
		return;

	if ( $echo )
		echo $tweet_this;
	else
		return $tweet_this;
}

/**************************************************************************************
 * SimpleTweetController Class
 *************************************************************************************/
class SimpleTweet {
	public $twitter_client_name = 'SimpleTweetWP';
	public $twitter_client_version = '1.3.8';
	public $twitter_client_url = 'http://wordpress.org/extend/plugins/simple-tweet/';

	// Constant
	const TWEET_MAX = 140;
	const TWEET_TIMEOUT = 30;
	const TWEET_HOME_URL = 'http://twitter.com/';
	const TWEET_SENT_URL = 'http://twitter.com/statuses/update.xml';
	const TWEET_OAUTH_CLIENTS_URL = 'http://twitter.com/oauth_clients';
	const TWEET_TINYURL_LIMIT = 15552000;	// 60 * 60 * 24 * 30 * 6
	const TWEET_TINYURL_URL = 'http://tinyurl.com/api-create.php?url=';
	const TWEET_BITLY_URL = 'http://api.bitly.com/v3/shorten?domain=bit.ly&format=xml&login=%s&apiKey=%s&longUrl=';
	const TWEET_BITLY_USER = '';
	const TWEET_BITLY_APIKEY = '';
	const TWEET_JMP_URL = 'http://api.bitly.com/v3/shorten?domain=j.mp&format=xml&login=%s&apiKey=%s&longUrl=';
	const TWEET_JMP_USER = '';
	const TWEET_JMP_APIKEY = '';
	const TWEET_ISGD_URL = 'http://is.gd/api.php?longurl=';

	const TWEET_METAKEY_SID = 'twitter_id';
	const TWEET_METAKEY_RES = '_twet_result';
	const TWEET_METAKEY_URL = '_tiny_url';

	const MESSAGE_FLAG = "simple_tweet_warn";


	// Options
	private $options;
	private $current_user_options;

	// Deafault Options
	private $options_default = array(
//		'user' => '' ,
//		'password' => '' ,
		'separator' => ' ' ,
		'shorten' => TRUE ,
		'tinyurl' => array(FALSE, self::TWEET_TINYURL_URL) ,
		'bitly' => array(FALSE, self::TWEET_BITLY_USER, self::TWEET_BITLY_APIKEY) ,
		'jmp' => array(FALSE, self::TWEET_JMP_USER, self::TWEET_JMP_APIKEY) ,
		'isgd' => array(FALSE, self::TWEET_ISGD_URL) ,
		'other_tinyurl' => array(FALSE, self::TWEET_TINYURL_URL) ,
		'tweet_text' => '' ,
		'tweet_without_url' => FALSE ,
		'add_content' => FALSE ,
		'tweet_this_link' => '' ,
		'tweet_this_text' => '' ,
		'log_write' => FALSE ,
		'activate' => 0 ,
		'deactivate' => 0 ,
		'use_OAuth' => false ,
		'consumer_key' => null ,
		'consumer_secret' => null ,
		'request_token' => null ,
		'request_token_secret' => null ,
		'oauth_token' => null ,
		'access_token' => null ,
		'access_token_secret' => null ,
		'pin' => null ,
		'oauth_reset' => false ,
		);

	private $consumer_key    = null;
	private $consumer_secret = null;
	private $request_token   = null;
	private $request_token_secret = null;
	private $oauth_token     = null;

	// Common Variables
	private $plugin_dir, $plugin_file, $plugin_url;
	private $textdomain_name, $option_name;
	private $admin_option, $admin_action, $admin_hook;
	private $charset;
	private $note, $error;
	private $tweet_msg;
	private $_log;

	//*****************************************************************************
	// Constructor
	//*****************************************************************************
	function __construct() {
		global $user_id;

		$this->_init_variables();
		$this->_load_textdomain();

		$this->option_name = $this->twitter_client_name . " Options";

		list($options, $current_user_options) = $this->_get_options();
		$this->options = $this->_init_options( $options );

                $this->consumer_key    = $this->options['consumer_key'];
                $this->consumer_secret = $this->options['consumer_secret'];

		$this->request_token        = $this->options['request_token'];
		$this->request_token_secret = $this->options['request_token_secret'];

		$this->tweet_msg = '';

		// add admin dashbord
		if (is_admin()) {
			add_action('admin_menu', array(&$this,'admin_menu'));
			add_filter('plugin_action_links', array(&$this, 'plugin_setting_links'), 10, 2 );

			add_action('show_user_profile', array(&$this,'user_profile'));
			add_action('edit_user_profile', array(&$this,'user_profile'));

			add_action('personal_options_update', array(&$this,'user_profile_update'));
			add_action('edit_user_profile_update', array(&$this,'user_profile_update'));

			if (get_transient(self::MESSAGE_FLAG)) {
				add_action("admin_notices", array(&$this, "admin_notice"));
			}

		} else {
			// add content
			add_filter('the_content', array (&$this, 'add_content'));
			//add_filter('the_content', array (&$this, 'content_tweet'));
		}

		// post publish
		add_action('publish_post', array(&$this, 'publish_post'));
		add_action('publish_future_post', array(&$this, 'publish_post'));

		// for ktai-entry
		add_action('publish_phone', array(&$this, 'publish_post'));

		// register activation / deactivation
		if ( function_exists('register_activation_hook') ) {
			register_activation_hook(__FILE__, array(&$this, 'activation'));
		}
		if ( function_exists('register_deactivation_hook') ) {
			register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));
		}
	}

	//*****************************************************************************
	// Common
	//*****************************************************************************

	// check wp version
	private function _check_wp_version($version, $operator = ">=") {
		global $wp_version;
		return version_compare($wp_version, $version, $operator);
	}

	// init variables
	private function _init_variables() {
		$this->charset = get_option('blog_charset');
		$this->_set_plugin_dir(__FILE__);
		$this->note = '';
		$this->error = 0;
	}

	// set plugin dir
	private function _set_plugin_dir( $file = '' ) {
		$file_path = ( !empty($file) ? $file : __FILE__);
		$filename = explode("/", $file_path);
		if(count($filename) <= 1) $filename = explode("\\", $file_path);
		$this->plugin_dir  = $filename[count($filename) - 2];
		$this->plugin_file = $filename[count($filename) - 1];
		$this->plugin_url  = $this->_wp_plugin_url($this->plugin_dir);
		unset($filename);
	}

	// load textdomain
	private function _load_textdomain( $sub_dir = 'languages' ) {
		$this->textdomain_name = 'simple-tweet';
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

	// Get Options
	private function _get_options( $user_id = "" ) {
		$options = get_option( $this->option_name );
		$user_options = array();
		if ( !empty($user_id) ) {
			$user_options = false;
			if (function_exists('get_user_meta')) {
				$user_options = get_user_meta($user_id, $this->option_name, true);
				if (!is_array($user_options)) {
					$user_options = get_usermeta($user_id, $this->option_name);
					update_user_meta($user_id, $this->option_name, $user_options);
				}
			} else {
				$user_options = get_usermeta($user_id, $this->option_name);
			}
			$options = array_merge((array) $options, (array) $user_options);
		}

		if (isset($options['oauth_reset']) && $options['oauth_reset']) {
			$options['pin'] = null;
			$options['oauth_token'] = null;
			$options['access_token'] = null;
			$options['access_token_secret'] = null;
		}

		if (isset($user_options['oauth_reset']) && $user_options['oauth_reset']) {
			$user_options['pin'] = null;
			$user_options['oauth_token'] = null;
			$user_options['access_token'] = null;
			$user_options['access_token_secret'] = null;
		}

		return array($options, $user_options);
	}

	// Set Default Options
	private function _set_default_options($options = '') {
		if (!is_array($options))
			$options = array();

		foreach ($this->options_default as $key => $val)
			$options[$key] = (isset($options[$key]) ? $options[$key] : $val);

		return $options;
	}

	// Handles Add/strips slashes to the given array
	private function _strip_array($array) {
		if( !is_array($array) )
			return stripslashes($array);

		foreach($array as $key => $value)
			$slashed_array[$key] = ( !is_array($value)
				? stripslashes($value)
				: $this->_strip_array($value) );

		return $slashed_array;
	}

	// Make Nonce field
	private function _make_nonce_field($action = -1, $name = "_wpnonce", $referer = true , $echo = true ) {
		return (
			function_exists('wp_nonce_field')
			? wp_nonce_field($action, $name, $referer, $echo)
			: ''
			);
	}

	// Update Options
	private function _update_options() {
		update_option($this->option_name, $this->options);
	}

	// Delete Options
	private function _delete_options() {
		delete_option($this->option_name);

		$users_of_blog = get_users_of_blog();
		foreach ( (array) $users_of_blog as $user ) {
			if (function_exists('delete_user_meta')) {
				delete_user_meta($user->ID, $this->option_name); 
			} else {
				delete_usermeta($user->ID, $this->option_name);
			}
		}
		unset($users_of_blog);

		$this->options = $this->_init_options(array());
	}

	// Get post_meta
	private function _get_post_meta($post_id, $key) {
		return maybe_unserialize(get_post_meta($post_id, $key, true));
	}

	private function _get_post_revisions_meta( $post_id, $key, $type = 'revision' ) {
		if ( !$post = get_post( $post_id ) )
			return array();

		$revisions = array();
		switch ( $type ) {
		case 'autosave' :
			if ( function_exists('wp_get_post_autosave') ) {
				if ( $autosave = wp_get_post_autosave( $post->ID ) )
					$revisions = array( $autosave );
			}
			break;
		case 'revision' : // just revisions - remove autosave later
		case 'all' :
		default :
			if ( function_exists('wp_get_post_revisions') ) {
				if ( !$revisions = wp_get_post_revisions( $post->ID ) )
					$revisions = array();
			}
			break;
		}

		$meta_vals = array();
		foreach ( $revisions as $revision ) {
			$meta_vals[] = $this->_get_post_meta( $revision->ID, $key );
		}
		return $meta_vals;
	}

	// Add or Update post_meta
	private function _update_post_meta($post_id, $key, $val) {
		if (is_array($val))
			$val = maybe_serialize($val);
		return (
			add_post_meta($post_id, $key, $val, true) or
			update_post_meta($post_id, $key, $val)
			);
	}

	// WP_CONTENT_DIR
	private function _wp_content_dir($path = '') {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_DIR')
			? WP_CONTENT_DIR
			: trailingslashit(ABSPATH) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// WP_CONTENT_URL
	private function _wp_content_url($path = '') {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_URL')
			? WP_CONTENT_URL
			: trailingslashit(get_option('siteurl')) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// WP_PLUGIN_DIR
	private function _wp_plugin_dir($path = '') {
		return trailingslashit($this->_wp_content_dir( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	// WP_PLUGIN_URL
	private function _wp_plugin_url($path = '') {
		return trailingslashit($this->_wp_content_url( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	// Add Option Page
	private function _add_option_page($page_title, $function, $capability = 'administrator', $menu_title = '', $file = '') {
		if ($menu_title == '') $menu_title = $page_title;
		if ($file == '') $file = $this->plugin_file;

		$this->admin_option = $file;
		$this->admin_action =
			trailingslashit(get_bloginfo('wpurl')) . 'wp-admin/' .
			( $this->_check_wp_version("2.7") ? 'options-general' : 'admin' ) . '.php' .
			'?page=' . $this->admin_option;
		$this->admin_hook = add_options_page($page_title, $menu_title, $capability, $file, $function);
	}

	private function _strlen($str, $enc = '') {
		if (empty($enc))
			$enc = $this->charset;
		return (
			function_exists('mb_strlen')
			? mb_strlen($str, $enc)
			: strlen($str)
			);
	}

	private function _substr($str, $start, $length, $enc = ''){
		if (empty($enc))
			$enc = $this->charset;
		return (
			function_exists('mb_substr')
			? mb_substr($str, $start, $length, $enc)
			: substr($str, $start, $length)
			);
	}

	//*****************************************************************************
	// Action/Filter hook
	//*****************************************************************************

	// publish post
	public function publish_post($post_id = '') {
		return $this->_do_tweet( $post_id );
	}

	// plugin activation
	public function activation(){
		list($options, $current_user_options) = $this->_get_options();
		$this->options = $this->_init_options( $options );
		$this->options['activate'] = time();
		$this->options['deactivate'] = 0;
		$this->_update_options();
	}

	// plugin deactivation
	public function deactivation(){
		list($options, $current_user_options) = $this->_get_options();
		$this->options = $options;
		if ( is_array($this->options) && count($this->options) > 0) {
			$this->options['activate'] = 0;
			$this->options['deactivate'] = time();
			$this->_update_options();
		}
	}

	// Add Admin Menu
	public function admin_menu() {
		$this->_add_option_page( __('Simple Tweet', $this->textdomain_name), array($this,'option_page'));
	}

	// Add Settig link
	public function plugin_setting_links($links, $file) {
		$this_plugin = plugin_basename(__FILE__);
		if ($file == $this_plugin) {
			$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links
		}

		return $links;
	}

	// Add Content
	public function add_content($content) {
		global $post;

		list($options, $current_user_options) = $this->_get_options( $post->post_author );
		if ( $options['add_content'] )
			$content .= "\n" . $this->tweet_this_link();
		return $content;
	}

	private function content_tweet($content) {
		global $post;

		if ( isset($post) ) {
			$post_time = strtotime($post->post_date_gmt . ' +0000');
			if ( $post_time >= $this->options['activate'] ) {
				$status_id = (string) $this->_get_post_meta($post->ID, self::TWEET_METAKEY_SID);
				$tweet_res = (string) $this->_get_post_meta($post->ID, self::TWEET_METAKEY_RES);
				if ( empty($status_id) && empty($tweet_res) ) {
					$this->_do_tweet( $post->ID );
				}
			}
		}

		return $content;
	}

	// Option Page
	public function option_page() {
		return $this->_option_page();
	}

	//*****************************************************************************
	// init options
	//*****************************************************************************
	private function _init_options($options = ''){
		if (!is_array($options))
			$options = array();

		$this->options_default['tweet_text'] = sprintf(
			__('blogged: %1$s - %2$s', $this->textdomain_name) ,
			'%POST_TITLE%' ,
			'%POST_EXCERPT%'
			);
		$this->options_default['tweet_this_link'] = sprintf(
			__('RT @%1$s: %2$s - %3$s %4$s', $this->textdomain_name) ,
			'%TWITTER_ID%' ,
			'%POST_TITLE%' ,
			'%SITE_NAME%' ,
			'%PERMALINK%'
			);
		$this->options_default['tweet_this_text'] = sprintf(
			'<img src="%1$simg/tweet.gif" title="%2$s" alt="%2$s" />%2$s' ,
			$this->plugin_url ,
			__('Tweet this!', $this->textdomain_name)
			);
		$this->options_default['activate'] = time();

		if ( !isset($options['shorten']) ) {
			if ( isset($options['tinyurl']) ) {
				if ( !is_array($options['tinyurl']) ) {
					$options['shorten'] = $options['tinyurl'];
					$options['tinyurl'] = array( !(function_exists('get_shortlink') || function_exists('wpme_get_shortlink')), self::TWEET_TINYURL_URL );
				} else {
					$options['shorten'] = $options['tinyurl'][0];
				}
			} else {
				$options['shorten'] = true;
				$options['tinyurl'] = array( !(function_exists('get_shortlink') || function_exists('wpme_get_shortlink')), self::TWEET_TINYURL_URL );
			}
		}

		$options = $this->_set_default_options($options);

		return $options;
	}

	//*****************************************************************************
	// Do tweet
	//*****************************************************************************
	private function _do_tweet($post_id = '') {
		if (empty($post_id))
			return false;

		$this->_log = '';
		$post = &get_post($post_id);

		if ('publish' !== $post->post_status )
			return false;

		list($this->options, $this->current_user_options) = $this->_get_options( $post->post_author );

		$post_time = strtotime($post->post_date_gmt . ' +0000');
		$meta_val  = $this->_get_post_meta( $post_id, self::TWEET_METAKEY_SID );
		$meta_vals = $this->_get_post_revisions_meta( $post_id, self::TWEET_METAKEY_SID );
		if ( !empty($meta_val) )
			$meta_vals = array_merge( $meta_vals, (array) $meta_val );
		if ( count($meta_vals) > 0 ) {
			rsort($meta_vals);
			$meta_val = $meta_vals[0];
		} else {
			$meta_val = '';
		}
		unset($meta_vals);

		$this->_log =
			"post_id:{$post_id}\n" .
			"post_time:{$post_time}\n" .
			"activate:{$this->options['activate']}\n" .
			"meta_val:{$meta_val}\n" ;

		if ( empty($meta_val) && $post_time >= $this->options['activate'] ) {
			$this->_log .= "post ID:{$post_id}\n";

			$post_title = $post->post_title;
			$post_excerpt = (!empty($post->post_excerpt) ? $post->post_excerpt : $post->post_content);

			$url = get_permalink($post_id);
//			$tiny = $this->_get_post_meta($post_id, self::TWEET_METAKEY_URL);
//			$tiny_url = ( is_array($tiny) && $tiny['limit'] > time()
//				? $tiny['tiny_url']
//				: '' );
			$tiny_url = $this->_get_shortlink($url, $post_id, $this->options );
			if ( !empty($tiny_url) )
				$this->_update_post_meta(
					$post_id ,
					self::TWEET_METAKEY_URL,
					array(
						'url' => $url ,
						'limit' => time() + self::TWEET_TINYURL_LIMIT ,
						'tiny_url' => $tiny_url
						)
					);
			$permalink = ( $this->options['shorten'] || $this->_strlen($msg . $this->options['separator'] . $url) > self::TWEET_MAX
				? ( !empty($tiny_url) ? $tiny_url : $url )
				: $url );

			$msg = $this->_make_tweet_msg(
				$this->options['tweet_text'] ,
				$this->options['user'] ,
				$post_id ,
				$post_title ,
				$post_excerpt ,
				$permalink
				);

			$permalink = ( !$this->options['tweet_without_url']
				? $this->options['separator'] . $permalink
				: '');
			$tweet_msg = $msg . $permalink;
			if ( $this->_strlen($tweet_msg, $this->charset) >= self::TWEET_MAX )
				$tweet_msg = $this->_substr($msg, 0, self::TWEET_MAX - ($this->_strlen($permalink) + 3)) . '...' . $permalink;
			$this->_log .= "tweet message:{$tweet_msg}\n";

			if ($this->tweet_msg != $tweet_msg) {
				$this->tweet_msg = $tweet_msg;

				$tweet_result = FALSE;
				if ( class_exists('TwitterOAuth') && !is_null($this->consumer_key) && !is_null($this->consumer_secret) && !is_null($this->options['access_token']) && !is_null($this->options['access_token_secret']) ) {
					$tweet_result = $this->_post_twitter_OAuth($tweet_msg, $this->options['access_token'], $this->options['access_token_secret']);
				}
				//if ( $tweet_result === FALSE ) {
				//	$tweet_result = $this->_post_twitter($tweet_msg, $this->options['user'], $this->options['password']);
				//}

				if ( $tweet_result !== FALSE ) {
					$tweet_id = $this->_get_tweet_id($tweet_result);
					$this->_log .= "id:{$tweet_id}\n";
					if ( $this->_update_post_meta($post_id, self::TWEET_METAKEY_SID, $tweet_id) ) {
						$this->_log = "*** OK! ***\n\n" . $this->_log;
					} else {
						$this->_log = "** ERROR **\n\n" . $this->_log;
					}
				} else {
					$this->_log = "** ERROR **\n\n" . $tweet_result . "\n" . $this->_log;
				}

				$this->_update_post_meta($post_id, self::TWEET_METAKEY_RES, $tweet_result);
				set_transient(self::MESSAGE_FLAG, $tweet_result, 60);
			}
		}

		if ( $this->options['log_write'] && !empty($this->_log)) {
			$log_file = $this->_wp_content_dir() . 'simple-tweet.log';
			if (file_exists($log_file)) @unlink($log_file);
			$handle = fopen($log_file, 'w');
			fwrite($handle, $this->_log);
			fclose($handle);
		}
	}


	//*****************************************************************************
	// Post to Twitter (OAuth)!
	//*****************************************************************************
	private function _post_twitter_OAuth( $tweet, $access_token = null, $access_token_secret = null ) {
		if ( !class_exists('TwitterOAuth') || is_null($this->consumer_key) || is_null($this->consumer_secret) )
			return FALSE;

		if ( empty($tweet) || is_null($access_token) || is_null($access_token_secret) )
			return FALSE;

		$oauth = new TwitterOAuth($this->consumer_key, $this->consumer_secret, $access_token, $access_token_secret);
		$result = $oauth->OAuthRequest(self::TWEET_SENT_URL, array("status"=>$tweet), "POST");
		$this->_log .=	"--- OAuth Result ! ---\n" . "results:{$result}\n";
		unset($oauth);

		return $result;
	}

	//*****************************************************************************
	// Post to Twitter!
	//*****************************************************************************
	private function _post_twitter($tweet, $username = '', $password = '') {
		if (empty($tweet) || empty($username) || empty($password))
			return FALSE;

		$result = FALSE;
		$log = '';

		if ( !class_exists('Snoopy') && file_exists(ABSPATH . WPINC . '/class-snoopy.php') )
			require_once(ABSPATH . WPINC . '/class-snoopy.php');

		if ( class_exists('Snoopy') ) {
			$snoop = new Snoopy;
			$snoop->agent = "{$this->twitter_client_name} ver.{$this->twitter_client_version} ({$this->twitter_client_url})";
			$snoop->rawheaders = array(
				'X-Twitter-Client' => $this->twitter_client_name ,
				'X-Twitter-Client-Version' => $this->twitter_client_version ,
				'X-Twitter-Client-URL' => $this->twitter_client_url
				);
			$snoop->user = $username;
			$snoop->pass = $password;
			$snoop->read_timeout = self::TWEET_TIMEOUT;
			$snoop->timed_out = true;
			$snoop->submit(
				self::TWEET_SENT_URL ,
				array(
					'status' => $tweet ,
					'source' => $this->twitter_client_name
					)
				);
			$result = ( strpos($snoop->response_code, '200') !== FALSE
				? $snoop->results
				: FALSE
				);
			$log .=	"--- Class Snoopy Result ! ---\n" .
				"response_code:{$snoop->response_code}" .
				"results:{$snoop->results}\n" .
				"status:{$snoop->status}\n" .
				"error:{$snoop->error}\n";
			unset($snoop);
		}

		if ($result === FALSE) {
			$params = '?status=' . rawurlencode($tweet) .
				  '&source=' . $this->twitter_client_name;
			$result = @file_get_contents(self::TWEET_SENT_URL.$params , false, stream_context_create(array(
				 "http" => array(
					"method" => "POST" ,
					"header" => "Authorization: Basic ". base64_encode($username. ":". $password)
					)
				))
			);
			$log .=	 "\n--- file_get_contents Result ! ---\n"
				."results:{$result}\n";
		}
		$this->_log .= $log;

		return $result;
	}

	//*****************************************************************************
	// Make Tweet Message
	//*****************************************************************************
	private function _make_tweet_msg($text, $twitter_user, $post_id, $post_title = '', $post_excerpt = '', $permalink = '') {
		if ( empty($text) )
			return '';

		$text = str_replace(
			array(
				'%SITE_NAME%' ,
				'%POST_NO%' ,
				'%POST_TITLE%' ,
				'%POST_EXCERPT%' ,
				'%PERMALINK%' ,
				'%TWITTER_ID%' ,
				) ,
			array(
				get_bloginfo('name') ,
				$post_id ,
				$post_title ,
				preg_replace('/[\r\n]+/', '', strip_tags($post_excerpt)) ,
				$permalink ,
				$twitter_user ,
				) ,
			$text
			);

		return $text;
	}

	//*****************************************************************************
	// Check URL
	//*****************************************************************************
	private function _chk_url( $url ) {
		return ( preg_match("/^s?https?:\/\/[-_.!~*'\(\)a-zA-Z0-9;\/?:\@&=+\$,%#]+$/i", $url) > 0 );
	}

	//*****************************************************************************
	// Get Short link
	//*****************************************************************************
	private function _get_shortlink($permalink, $post_id, $options ) {
		$shortlink = $permalink;

		if ( $options['shorten'] ) {
			if ( $options['tinyurl'][0] ) {
				$shortlink = $this->_get_TinyURL($permalink, self::TWEET_TINYURL_URL);
			} elseif ( $options['bitly'][0] ) {
				$shortlink = $this->_get_bitly($permalink, self::TWEET_BITLY_URL, $options['bitly'][1], $options['bitly'][2]);
			} elseif ( $options['jmp'][0] ) {
				$shortlink = $this->_get_bitly($permalink, self::TWEET_JMP_URL, $options['jmp'][1], $options['jmp'][2]);
			} elseif ( $options['isgd'][0] ) {
				$shortlink = $this->_get_TinyURL(rawurlencode($permalink), self::TWEET_ISGD_URL);
			} elseif ( $options['other_tinyurl'][0] ) {
				$shortlink = $this->_get_TinyURL($permalink, $options['other_tinyurl'][1]);
			} elseif ( function_exists('get_shortlink') ) {
				$shortlink = get_shortlink($post_id);
			} elseif ( function_exists('wpme_get_shortlink') ) {
				$shortlink = wpme_get_shortlink($post_id);
			} else {
				$shortlink = $this->_get_TinyURL($permalink);
			}
		}

		return ($this->_chk_url($shortlink) ? $shortlink : $permalink);
	}

	//*****************************************************************************
	// Get bit.ly
	//*****************************************************************************
	private function _get_bitly($url = '', $get_url = self::TWEET_BITLY_URL, $user = self::TWEET_BITLY_USER, $apikey = self::TWEET_BITLY_APIKEY ) {
		if (empty($url) || empty($user) || empty($apikey))
			return $url;

		$result = $this->_get_TinyURL(rawurlencode($url), sprintf($get_url, $user, $apikey));
		if ( preg_match( '/<url>[ \t]*(.*)[ \t]*<\/url>/iUs', $result, $matches ) ) {
			$result = (isset($matches[1]) ? $matches[1] : $url);
		} else {
			$result = $url;
		}
		unset($matches);

		return ($this->_chk_url($result) ? $result : $url);
	}

	//*****************************************************************************
	// Get Tiny URL
	//*****************************************************************************
	private function _get_TinyURL($url = '', $get_url = self::TWEET_TINYURL_URL ) {
		if (empty($url))
			return '';

		$result = '';
		$get_url .= $url;

		if ( function_exists('wp_remote_get') ) {
			$ret = wp_remote_get($get_url);
			if (is_array($ret) && isset($ret["body"]) && !empty($ret["body"]))
				$result = $ret["body"];

		} else {
			if ( !class_exists('Snoopy') && file_exists(ABSPATH . WPINC . '/class-snoopy.php') )
				require_once(ABSPATH . WPINC . '/class-snoopy.php');

			if ( class_exists('Snoopy') ) {
				$snoop = new Snoopy;
				$snoop->read_timeout = self::TWEET_TIMEOUT;
				$snoop->timed_out = true;
				$snoop->fetch($get_url);
				$result = ( strpos($snoop->response_code, '200') !== FALSE
					? $snoop->results
					: ''
					);
				unset($snoop);
			}
		}

		if ( empty($result) ) {
			if ( function_exists('file_get_contents') ) {
				$result = @file_get_contents( $get_url );
			} else {
				$fp = @fopen($get_url, 'r');
				if ( $fp === FALSE ) return $result;
				while(!feof($fp)) {$result .= fread( $fp, 1024 );}
				@fclose($fp);
			}
		}

		return $result;
	}

	//*****************************************************************************
	// Get TweetID
	//*****************************************************************************
	private function _get_tweet_id($result = '') {
		if (empty($result)) {
			return '';
		}

		$tweet_id = '';
//		if ( function_exists('simplexml_load_string') ) {
//			$xml = simplexml_load_string($result);
//			$tweet_id = $xml->id;
//			unset($xml);
//		} elseif ( preg_match_all('/<id>([0-9]+)<\/id>/i', $result, $matches, PREG_PATTERN_ORDER) ) {
//			$tweet_id = $matches[1][0];
//			unset($matches);
//		}

		if ( preg_match_all('/<id>([0-9]+)<\/id>/i', $result, $matches, PREG_PATTERN_ORDER) ) {
			$tweet_id = $matches[1][0];
		}
		unset($matches);

		return $tweet_id;
	}

	//*****************************************************************************
	// Show Option Page
	//*****************************************************************************
	private function _option_page() {
		if (isset($_POST['options_update'])) {
			// Check Nonce Field
			if ( function_exists('check_admin_referer') )
				check_admin_referer("update_options", "_wpnonce_update_options");

			// get post data
			$this->options = $this->_get_post_data( $_POST, $this->options, TRUE );
			$this->consumer_key    = $this->options['consumer_key'];
			$this->consumer_secret = $this->options['consumer_secret'];

			// options update
			$this->_update_options();

			// Done!
			$this->note .= "<strong>".__('Done!', $this->textdomain_name)."</strong>";

		} elseif ( isset($_GET['oauth_token']) ) {
			$request = $this->_strip_array($_GET);

			if ( class_exists('TwitterOAuth') && !is_null($this->consumer_key) && !is_null($this->consumer_secret) && !is_null($this->request_token) && !is_null($this->request_token_secret) ) {
				$oauth_token = $request['oauth_token'];
				if ( $oauth_token !== $this->options['oauth_token'] ) {
					$oauth = new TwitterOAuth($this->consumer_key, $this->consumer_secret, $this->request_token, $this->request_token_secret);
					$token = $oauth->getAccessToken($oauth_token, null);
					$access_token = $token['oauth_token'];
					$access_token_secret = $token['oauth_token_secret'];
					unset($token);
					unset($oauth);
					$this->options['pin']  = null;
					$this->options['oauth_token'] = $this->oauth_token = $oauth_token;
					$this->options['access_token'] = $access_token;
					$this->options['access_token_secret'] = $access_token_secret;
				}
				$this->request_token = $this->request_token_secret = null;
			}

			// options update
			$this->_update_options();

			// Done!
			$this->note .= "<strong>".__('Done!', $this->textdomain_name)."</strong>";

		} elseif ( isset($_POST['options_delete']) ) {
			// Check Nonce Field
			if ( function_exists('check_admin_referer') )
				check_admin_referer("delete_options", "_wpnonce_delete_options");

			// options delete
			$this->_delete_settings();

			// Done!
			$this->note .= "<strong>".__('Done!', $this->textdomain_name)."</strong>";
			$this->error++;
		}

		// Add Options
		$out .= "<div class=\"wrap\">\n";
		$out .= "<h2>".__('Simple Tweet Options', $this->textdomain_name)."</h2><br />\n";
		$out .= "<form method=\"post\" id=\"update_options\" action=\"".$this->admin_action."\">\n";
		$out .= $this->_make_nonce_field("update_options", "_wpnonce_update_options", true, false);

		$out .= $this->_options_table( $this->options, TRUE );

		// Add Update Button
		$out .= "<p style=\"margin-top:1em\"><input type=\"submit\" name=\"options_update\" class=\"button-primary\" value=\"".__('Update Options &raquo;', $this->textdomain_name)."\" class=\"button\" /></p>";
		$out .= "</form></div>\n";

		// Options Delete
		$out .= "<div class=\"wrap\" style=\"margin-top:2em;\">\n";
		$out .= "<h2>" . __('Uninstall', $this->textdomain_name) . "</h2><br />\n";
		$out .= "<form method=\"post\" id=\"delete_options\" action=\"".$this->admin_action."\">\n";
		$out .= $this->_make_nonce_field("delete_options", "_wpnonce_delete_options", true, false);
		$out .= "<p>" . __('Delete all the settings of &quot;Simple Tweet&quot;.', $this->textdomain_name) . "</p>";
		$out .= "<input type=\"submit\" name=\"options_delete\" class=\"button-primary\" value=\"".__('Delete Options &raquo;', $this->textdomain_name)."\" class=\"button\" />";
		$out .= "</form></div>\n";

		// Output
		echo ( !empty($this->note) ? "<div id=\"message\" class=\"updated fade\"><p>{$this->note}</p></div>\n" : '' ) . "\n";
		echo ( $this->error == 0 ? $out : '' ) . "\n";
	}

	public function user_profile( $profileuser ){
		if ( current_user_can('publish_posts') ) {
			list($options, $current_user_options) = $this->_get_options( $profileuser->ID );
			$current_user_options = $this->_init_options( $current_user_options );
			if ( count($current_user_options) > 0 ) {
				echo '<h3 id="simple-tweet">'.__('Simple Tweet Options', $this->textdomain_name)."</h3>\n\n";
				echo $this->_options_table( $current_user_options );
			}
		}
	}

	public function user_profile_update( $user_id ) {
		if ( current_user_can('publish_posts') ) {
			list($options, $current_user_options) = $this->_get_options( $user_id );
			$current_user_options = $this->_init_options( $current_user_options );
			$current_user_options = $this->_get_post_data( $_POST, $current_user_options );
			if (function_exists('update_user_meta')) {
				update_user_meta($user_id, $this->option_name, $current_user_options);
			} else {
				update_usermeta($user_id, $this->option_name, $current_user_options);
			}
		}
	}

	private function _get_post_data( $request, $options = NULL, $is_admin = FALSE ) {
		if ( !is_array($options) )
			$options = array();

		// strip slashes array
		$request = $this->_strip_array($request);

		$options['user']       = $request['twitter_usr'];
		if ( isset($request['twitter_pwd']) && trim($request['twitter_pwd']) !== '' ) {
			$options['password'] = $request['twitter_pwd'];
		}

		$twitter_pin  = $options['pin'];
		$access_token = $options['access_token'];
		$access_token_secret = $options['access_token_secret'];
		if ( class_exists('TwitterOAuth') && !is_null($this->consumer_key) && !is_null($this->consumer_secret) && !is_null($this->request_token) && !is_null($this->request_token_secret) ) {
//			$twitter_pin = $access_token = $access_token_secret = null;
			$twitter_pin = (
				isset($request['twitter_pin']) && !empty($request['twitter_pin'])
				? trim($request['twitter_pin'])
				: null
				);
			if ( !is_null($twitter_pin) && $twitter_pin !== $options['pin'] ) {
				$oauth = new TwitterOAuth($this->consumer_key, $this->consumer_secret, $this->request_token, $this->request_token_secret);
				$token = $oauth->getAccessToken(null, $twitter_pin);
				$access_token = $token['oauth_token'];
				$access_token_secret = $token['oauth_token_secret'];
				unset($token);
				unset($oauth);
				$options['oauth_token'] = $this->oauth_token = null;
				$options['access_token'] = $access_token;
				$options['access_token_secret'] = $access_token_secret;
			} else {
				$twitter_pin  = $options['pin'];
				$access_token = $options['access_token'];
				$access_token_secret = $options['access_token_secret'];
			}
			$this->request_token = $this->request_token_secret = null;
		}
		if ( isset($request['oauth_reset']) && $request['oauth_reset'] == 'on' ) {
			$options['pin'] = $this->options['pin'] = null;
			$options['oauth_token'] = $this->oauth_token = null;
			$options['access_token'] = $this->options['access_token'] = null;
			$options['access_token_secret'] = $this->options['access_token_secret'] = null;
			$options['oauth_reset'] = true;
		} else {
			$options['pin'] = $this->options['pin'] = $twitter_pin;
			$options['access_token'] = $this->options['access_token'] = $access_token;
			$options['access_token_secret'] = $this->options['access_token_secret'] = $access_token_secret;
			$options['oauth_reset'] = false;
		}
		$options['request_token'] = $this->request_token;
		$options['request_token_secret'] = $this->request_token_secret;

		$options['tweet_text'] = $request['tweet_text'];
		$options['separator']  = $request['separator'];

		$shortlink = $tinyurl = $bitly = $jmp = $isgd = $other = false;
		switch ($request['shortlink']) {
		case 'shortlink':
			$shortlink = true;
			break;
		case 'tinyurl':
			$tinyurl = true;
			break;
		case 'bitly':
			$bitly = true;
			break;
		case 'jmp':
			$jmp = true;
			break;
		case 'isgd':
			$isgd = true;
			break;
		case 'other':
			$other = true;
			break;
		default:
			break;
		}
		$options['shorten']    = (isset($request['shorten']) && $request['shorten'] == 'on') ? true : false;
		$options['tinyurl']    = array($tinyurl, self::TWEET_TINYURL_URL);
		$options['bitly']      = array($bitly, $request['bitly_name'], $request['bitly_api']);
		$options['jmp']        = array($jmp, $request['jmp_name'], $request['jmp_api']);
		$options['isgd']       = array($isgd, self::TWEET_ISGD_URL);
		$options['other_tinyurl'] = array($other, $request['other_tinyurl_url']);

		$options['tweet_without_url']  = (isset($request['tweet_without_url']) && $request['tweet_without_url'] == 'on' ? true : false);

		$options['tweet_this_link'] = $request['tweet_this_link'];
		$options['tweet_this_text'] = $request['tweet_this_text'];
		$options['add_content']     = (isset($request['add_content']) && $request['add_content'] == 'on' ? true : false);

		if ( $is_admin ) {
			$options['log_write']   = (isset($request['log_write']) && $request['log_write'] == 'on' ? true : false);
			$options['activate']    = time();
			$options['deactivate']  = 0;

			$consumer_key    = (isset($request['consumer_key']) && !empty($request['consumer_key']) ?  trim($request['consumer_key']) : null);
			$consumer_secret = (isset($request['consumer_secret']) && !empty($request['consumer_secret']) ? trim($request['consumer_secret']) : null);
			if ( $consumer_key !== $options['consumer_key'] || $consumer_secret !== $options['consumer_secret'] ) {
				$this->consumer_key    = $options['consumer_key']    = $consumer_key;
				$this->consumer_secret = $options['consumer_secret'] = $consumer_secret;
			}
		}

		$this->_update_options();

		return $options;
	}

	private function _options_table( $options, $is_admin = FALSE ) {
		$out  = '';

		$out .= "<table class=\"optiontable form-table\" style=\"margin-top:0;\"><tbody>\n";

		if ( class_exists('TwitterOAuth') ) {
			if ( $is_admin || (!is_null($this->consumer_key) && !is_null($this->consumer_secret)) ) {
				$out .= '<tr>';
				$out .= '<th>';
				$out .= __('Twitter OAuth', $this->textdomain_name);
				if ( is_null($options['access_token']) || is_null($options['access_token_secret']) ) {
					$out .= '<br/>'.__('<a href="http://wppluginsj.sourceforge.jp/simple-tweet/simple-tweet-oauth-en/" title="WordPress Plugins/JSeries » Simple Tweet OAuth Setting">OAuth Setting</a>', $this->textdomain_name);
				}
				$out .= '</th>';
				$out .= '<td>';
				$out .= "<table style=\"margin-top:0;\"><tbody>\n";
				if ( $is_admin ) {
					$out .= '<tr>';
					$out .= '<th style="width:120px;padding:0;">'.__('Get Consumer Key', $this->textdomain_name)."</th>";
					$out .= '<td style="padding:0;"><a href="'.self::TWEET_OAUTH_CLIENTS_URL.'" target="_blank">'.__('Applications Using Twitter', $this->textdomain_name).'</a></td>';
					$out .= "</tr>\n";
					$out .= '<tr>';
					$out .= '<th style="width:120px;padding:0;">'.__('Consumer Key', $this->textdomain_name)."</th>";
					$out .= '<td style="padding:0;"><input type="text" name="consumer_key" id="consumer_key" size="50" value="'.$this->consumer_key.'" /></td>';
					$out .= "</tr>\n";
					$out .= "<tr>";
					$out .= '<th style="width:120px;padding:0;">'.__('Consumer Secret', $this->textdomain_name)."</th>";
					$out .= '<td style="padding:0;"><input type="text" name="consumer_secret" id="consumer_secret" size="50" value="'.$this->consumer_secret.'" /></td>';
					$out .= "</tr>\n";
					$out .= '<tr>';
					$out .= '<th style="width:120px;padding:0;">'.__("What's a tweet", $this->textdomain_name)."</th>";
					$out .= '<td style="padding:0;">'.__('Please set the contents tweet in "<a href="profile.php#simple-tweet">your profile</a>"', $this->textdomain_name).'</a></td>';
					$out .= "</tr>\n";

				} elseif ( !is_null($this->consumer_key) && !is_null($this->consumer_secret) ) {
					if ($options['oauth_reset'] || is_null($options['access_token']) || is_null($options['access_token_secret'])) {
						$this->request_token = $this->request_token_secret = null;
						$oauth = new TwitterOAuth($this->consumer_key, $this->consumer_secret);
						$token = $oauth->getRequestToken();
						$this->options['request_token'] = $this->request_token = $token['oauth_token'];
						$this->options['request_token_secret'] = $this->request_token_secret = $token['oauth_token_secret'];
						$request_link = $oauth->getAuthorizeURL($this->request_token);
						$this->_update_options();
						unset($oauth);

						$out .= "<tr>";
						$out .= '<th style="width:120px;padding:0;">'.__('OAuth', $this->textdomain_name)."</th>";
						$out .= "<td style=\"padding:0;\"><a href=\"{$request_link}\" target=\"_blank\">".__('Click on the link to go to twitter to authorize your account.', $this->textdomain_name).'</a></td>';
						$out .= "</tr>\n";

						$out .= "<tr>";
						$out .= '<th style="width:120px;padding:0;">'.__('PIN', $this->textdomain_name)."</th>";
						$out .= "<td style=\"padding:0;\"><input type=\"text\" name=\"twitter_pin\" id=\"twitter_pin\" size=\"50\" value=\"{$options['pin']}\" /></td>";
						$out .= "</tr>\n";
					} else {
						$out .= "<tr>";
						$out .= '<td colspan="2" style="padding:0;">';
						$out .= '<input type="checkbox" name="oauth_reset" id="oauth_reset" value="on" /> ';
						$out .= __('OAuth reset', $this->textdomain_name);
						$out .= "</td>";
						$out .= "</tr>\n";
					}
				}
				$out .= "</tbody></table>\n";
				$out .= "</td>";
				$out .= "</tr>\n";
			}

		} else {
			$out .= "<tr>";
			$out .= "<th>";
			$out .= __('Twitter OAuth', $this->textdomain_name);
			$out .= "</th>";
			$out .= "<td>";
			$out .= __('Twitter OAuth supports PHP5 or later.', $this->textdomain_name);
			$out .= "</td>";
			$out .= "</tr>\n";
		}

/*
		if ( is_null($this->consumer_key) || is_null($this->consumer_secret) || is_null($options['access_token']) || is_null($options['access_token_secret']) ) {
			$out .= "<tr>";
			$out .= '<th>'.__('Twitter ID', $this->textdomain_name)."</th>";
			$out .= "<td>";
			$out .= "<table style=\"margin-top:0;\"><tbody>\n";
			$out .= "<tr>";
			$out .= '<th style="width:120px;padding:0;">'.__('User Name', $this->textdomain_name)."</th>";
			$out .= "<td style=\"padding:0;\"><input type=\"text\" name=\"twitter_usr\" id=\"twitter_usr\" size=\"50\" value=\"{$options['user']}\" /></td>";
			$out .= "</tr>\n";
			$out .= "<tr>";
			$out .= '<th style="width:120px;padding:0;">'.__('Password', $this->textdomain_name)."</th>";
			$out .= "<td style=\"padding:0;\">";
			$out .= "<input type=\"password\" name=\"twitter_pwd\" id=\"twitter_pwd\" size=\"16\" value=\"\" /><br />\n";
			$out .= '<span class="description">';
			$out .= __("If you would like to change the password type a new one. Otherwise leave this blank.");
			$out .= '</span>';
			$out .= "</td>";
			$out .= "</tr>\n";
			$out .= "</tbody></table>\n";
			$out .= "</td>";
			$out .= "</tr>\n";
		}
*/

		if ( !$is_admin ) {
			$out .= "<tr>";
			$out .= "<th>".__('Tweet text', $this->textdomain_name)."</th>";
			$out .= "<td>";
			$out .= "<input type=\"text\" name=\"tweet_text\" id=\"tweet_text\" size=\"100\" value=\"".htmlspecialchars($options['tweet_text'])."\" /> ";
			$out .= "<br />\n";
			$out .= "<input type=\"checkbox\" name=\"tweet_without_url\" id=\"tweet_without_url\" value=\"on\"".($options['tweet_without_url'] ? " checked=\"true\"" : "")." /> ";
			$out .= __('Tweet without Permalink', $this->textdomain_name);
			$out .= "</td>";
			$out .= "</tr>\n";

			$shortlink = $tinyurl = $bitly = $jmp = $isgd = $other = false;
			if ($options['tinyurl'][0]) {
				$tinyurl = true;
			} elseif ($options['bitly'][0]) {
				$bitly = true;
			} elseif ($options['jmp'][0]) {
				$jmp = true;
			} elseif ($options['isgd'][0]) {
				$isgd = true;
			} elseif ($options['other_tinyurl'][0]) {
				$other = true;
			} elseif (!(function_exists('get_shortlink') || function_exists('wpme_get_shortlink'))) {
				$tinyurl = true;
			} else {
				$shortlink = true;
			}
			$out .= "<tr>";
			$out .= "<th>".__('Short Link', $this->textdomain_name)."</th>";
			$out .= "<td>";
			$out .= '<input type="checkbox" name="shorten" id="shorten" value="on" '.($options['shorten'] ? 'checked="checked" ' : '').'/> ';
			$out .= __('Compress Permalink', $this->textdomain_name);
			$out .= "<br />\n";
			if ( function_exists('get_shortlink') && class_exists('ShortLinkMaker') ) {
				$out .= "<input type=\"radio\" name=\"shortlink\" id=\"shortlink\" value=\"shortlink\" ".($shortlink ? 'checked="checked " ' : '')."/> ";
				$out .= '<a href="http://wordpress.org/extend/plugins/short-link-maker/" title="WordPress &gt; Short link maker &laquo; WordPress Plugins">Short link maker</a>';
				$out .= "<br />\n";
			} elseif ( function_exists('wpme_get_shortlink') ) {
				$out .= "<input type=\"radio\" name=\"shortlink\" id=\"shortlink\" value=\"shortlink\" ".($shortlink ? 'checked="checked " ' : '')."/> ";
				$out .= '<a href="http://wordpress.org/extend/plugins/stats/" title="WordPress &gt; WordPress.com Stats &laquo; WordPress Plugins">WordPress.com Stats</a>';
				$out .= "<br />\n";
			}
			$out .= "<input type=\"radio\" name=\"shortlink\" id=\"bitly\" value=\"bitly\" ".($bitly ? 'checked="checked " ' : '')."/> ";
			$out .= '<a href="http://bit.ly/" title="bit.ly, a simple url shortener">bit.ly</a> : ';
			$out .= __('User Name', $this->textdomain_name)."<input type=\"text\" name=\"bitly_name\" id=\"bitly_name\" size=\"20\" value=\"".htmlspecialchars( $options['bitly'][1] )."\" /> ";
			$out .= __('bit.ly API Key', $this->textdomain_name)."<input type=\"text\" name=\"bitly_api\" id=\"bitly_api\" size=\"30\" value=\"".htmlspecialchars( $options['bitly'][2] )."\" /> ";
			$out .= "<br />\n";
			$out .= "<input type=\"radio\" name=\"shortlink\" id=\"jmp\" value=\"jmp\" ".($jmp ? 'checked="checked " ' : '')."/> ";
			$out .= '<a href="http://j.mp/" title="j.mp, a simple url shortener">j.mp</a> : ';
			$out .= __('User Name', $this->textdomain_name)."<input type=\"text\" name=\"jmp_name\" id=\"jmp_name\" size=\"20\" value=\"".htmlspecialchars( $options['jmp'][1] )."\" /> ";
			$out .= __('j.mp API Key', $this->textdomain_name)."<input type=\"text\" name=\"jmp_api\" id=\"jmp_api\" size=\"30\" value=\"".htmlspecialchars( $options['jmp'][2] )."\" /> ";
			$out .= "<br />\n";
			$out .= "<input type=\"radio\" name=\"shortlink\" id=\"tinyurl\" value=\"tinyurl\" ".($tinyurl ? 'checked="checked " ' : '')."/> ";
			$out .= '<a href="http://tinyurl.com/" title="TinyURL.com - shorten that long URL into a tiny URL">TinyURL</a>';
			$out .= "<br />\n";
			$out .= "<input type=\"radio\" name=\"shortlink\" id=\"isgd\" value=\"isgd\" ".($isgd ? 'checked="checked " ' : '')."/> ";
			$out .= '<a href="http://is.gd/" title="is.gd URL Shortener - The Shortest URLs Around">is.gd</a>';
			$out .= "<br />\n";
			$out .= "<input type=\"radio\" name=\"shortlink\" id=\"other\" value=\"other\" ".($other ? 'checked="checked " ' : '')."/> ";
			$out .= __('Other Service', $this->textdomain_name) . ' : ';
			$out .= "<input type=\"text\" name=\"other_tinyurl_url\" id=\"other_tinyurl_url\" size=\"100\" value=\"".htmlspecialchars( !(function_exists('get_shortlink') || function_exists('wpme_get_shortlink')) && $options['other_tinyurl'][1] === self::TWEET_TINYURL_URL ? '' : $options['other_tinyurl'][1])."\" /> ";
			$out .= "</td>";
			$out .= "</tr>\n";

			$out .= "<tr>";
			$out .= "<th>".__('Separator between message and Permalink', $this->textdomain_name)."</th>";
			$out .= "<td><input type=\"text\" name=\"separator\" id=\"separator\" size=\"50\" value=\"{$options['separator']}\" /></td>";
			$out .= "</tr>\n";

			$out .= "<tr>";
			$out .= "<th></th>";
			$out .= "<td>";
			$out .= "<input type=\"checkbox\" name=\"add_content\" id=\"add_content\" value=\"on\" style=\"margin-right:0.5em;\" ".($options['add_content'] ? " checked=\"true\"" : "")." />";
			$out .= __("Add \"Tweet this\" link", $this->textdomain_name);
			$out .= "</td>";
			$out .= "</tr>\n";

			$out .= "<tr>";
			$out .= "<th>".__('Tweet this link', $this->textdomain_name)."</th>";
			$out .= "<td><input type=\"text\" name=\"tweet_this_link\" id=\"tweet_this_link\" size=\"100\" value=\"".htmlspecialchars($options['tweet_this_link'])."\" /></td>";
			$out .= "</tr>\n";

			$out .= "<tr>";
			$out .= "<th>".__('Tweet this text', $this->textdomain_name)."</th>";
			$out .= "<td><input type=\"text\" name=\"tweet_this_text\" id=\"tweet_this_text\" size=\"100\" value=\"".htmlspecialchars($options['tweet_this_text'])."\" /></td>";
			$out .= "</tr>\n";

			$out .= "<tr>";
			$out .= "<th></th>";
			$out .= '<td><span class="description">';
			$out .= __('The following characters are converted respectively.', $this->textdomain_name).'<br />';
			$out .= '%TWITTER_ID% - '.__('Twitter ID', $this->textdomain_name).'<br />';
			$out .= '%SITE_NAME% - '.__('Site Name', $this->textdomain_name).'<br />';
			$out .= '%POST_NO% - '.__('Post No.', $this->textdomain_name).'<br />';
			$out .= '%POST_TITLE% - '.__('Post Title', $this->textdomain_name).'<br />';
			$out .= '%POST_EXCERPT% - '.__('Post Excerpt', $this->textdomain_name).'<br />';
			$out .= '%PERMALINK% - '.__('Permalink', $this->textdomain_name).'<br />';
			$out .= '</span></td>';
			$out .= "</tr>\n";
		}

		if ( $is_admin ) {
			$out .= "<tr>";
			$out .= "<th></th>";
			$out .= "<td>";
			$out .= "<input type=\"checkbox\" name=\"log_write\" id=\"log_write\" value=\"on\"".($options['log_write'] ? " checked=\"true\"" : "")." /> ";
			$out .= __('Output log? (debug mode)', $this->textdomain_name);
			$out .= "</td>";
			$out .= "</tr>\n";
		}

		$out .= "</tbody></table>";

		return $out;
	}

	// delete all settings
	private function _delete_settings() {
		global $wpdb;

		$wpdb->query($wpdb->prepare(
			"DELETE FROM $wpdb->postmeta WHERE meta_key in (%s, %s, %s)" ,
			$wpdb->escape(self::TWEET_METAKEY_SID) ,
			$wpdb->escape(self::TWEET_METAKEY_RES) ,
			$wpdb->escape(self::TWEET_METAKEY_URL)
			)
		);

		$this->_delete_options();
	}

	//*****************************************************************************
	// Get Tweet this Link
	//*****************************************************************************
	public function tweet_this_link($inreply_to = FALSE) {
		global $post;

		if ( !isset($post) )
			return false;

		$post_id = $post->ID;
		$post_title = $post->post_title;
		$post_excerpt = (!empty($post->post_excerpt) ? $post->post_excerpt : $post->post_content);
		list($options, $current_user_options) = $this->_get_options( $post->post_author );

		$status_id = (string) $this->_get_post_meta($post_id, self::TWEET_METAKEY_SID);
//		if ( $inreply_to && empty($status_id) )
//			return false;

		if ( $options['shorten'] ) {
			$tiny = $this->_get_post_meta($post_id, self::TWEET_METAKEY_URL);
			if ( !empty($tiny) && !is_array($tiny) ) {
				$tiny_url = $tiny;
				$tiny = array(
					'url' => get_permalink($post_id) ,
					'limit' => time() + self::TWEET_TINYURL_LIMIT ,
					'tiny_url' => $tiny_url
					);
				$this->_update_post_meta(
					$post_id ,
					self::TWEET_METAKEY_URL,
					$tiny
					);
			} else {
				$tiny_url = ( is_array($tiny) && $tiny['limit'] > time()
					? $tiny['tiny_url']
					: '' );
			}
			if ( empty($tiny_url) || !$this->_chk_url($tiny_url) )
				$tiny_url = '';
			if ( empty($tiny_url) ) {
				$permalink = get_permalink($post_id);
				if ( is_single() ) {
					$tiny_url = $this->_get_shortlink($permalink, $post_id, $options);
					$this->_update_post_meta(
						$post_id ,
						self::TWEET_METAKEY_URL,
						array(
							'url'      => get_permalink($post_id) ,
							'limit'    => time() + self::TWEET_TINYURL_LIMIT ,
							'tiny_url' => $tiny_url
							)
						);
					$permalink = $tiny_url;
				}
			} else {
				$permalink = $tiny_url;
			}
		} else {
			$permalink = get_permalink($post_id);
		}

		$link = $this->_make_tweet_msg( $options['tweet_this_link'], $options['user'], $post_id, $post_title, $post_excerpt, $permalink);
		$text = $this->_make_tweet_msg( $options['tweet_this_text'], $options['user'], $post_id, $post_title, $post_excerpt, $permalink);

		$tweet_this_link = '<a href="' . self::TWEET_HOME_URL .
			'?status=' . rawurlencode($link) .
			( !empty($status_id) ? '&amp;in_reply_to_status_id=' . $status_id : '' ) .
			( $inreply_to && !empty($options['user']) ? '&amp;in_reply_to=' . $options['user'] : '' ).
			'" class="tweet-this" >' .
			$text .
			'</a>';

		return $tweet_this_link;
	}

	//*****************************************************************************
	// Show admin notice
	//*****************************************************************************
	public function admin_notice() {
		$tweet_result = get_transient(self::MESSAGE_FLAG);
		$tweet_id = ($tweet_result !== FALSE ? $this->_get_tweet_id($tweet_result) : '');
		$tweet_text = '';

		if (!empty($tweet_id)) {
			if ( preg_match('/<text>([^<]*)<\/text>/i', $tweet_result, $match) ) {
				$tweet_text = $match[1];
			}
			unset($match);
			$this->_show_message('Simple Tweet: Success! Tweet ID ' . $tweet_id . (!empty($tweet_text) ? '<br />'.$tweet_text : ''));
		} else {
			if ( preg_match('/<error>([^<]*)<\/error>/i', $tweet_result, $match) ) {
				$tweet_text = $match[1];
			}
			unset($match);
			$this->_show_message('Simple Tweet: Error!!! - ' . $tweet_text, true);
		}

		delete_transient(self::MESSAGE_FLAG);
	}

	private function _show_message($message, $errormsg = false) {
		echo '<div id="message" class="'.($errormsg ? 'error' : 'updated fade').'">';
		echo "<p><strong>$message</strong></p></div>\n";
	}
}

/******************************************************************************
 * Go Go Go!
 *****************************************************************************/
$simple_tweet = new SimpleTweet();
?>
