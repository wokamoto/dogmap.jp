<?php
/*
Plugin Name: Feedback Champuru
Version: 0.5.4
Plugin URI: http://wppluginsj.sourceforge.jp/feedback-champru/
Description: This plugin makes WordPress Comment boisterous adding feedbacks of Twitter, Social Bookmarks and so on.
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: feedback-champuru
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2010-2011 wokamoto (email : wokamoto1973@gmail.com)

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
// Require wp-load.php or wp-config.php
//**********************************************************************************
if( !function_exists('get_option') ) {
	$path = (defined('ABSPATH') ? ABSPATH : dirname(dirname(dirname(dirname(__FILE__)))) . '/');
	require_once(file_exists($path.'wp-load.php') ? $path.'wp-load.php' : $path.'wp-config.php');
}

//**********************************************************************************
// Template tag
//**********************************************************************************
function feedback_type($commenttxt = false, $trackbacktxt = false, $pingbacktxt = false, $tweettext = false, $hatenatext = false, $delisioustext = false, $friendfeedtext = false, $livedoortext = false, $buzzurltext = false, $googletext = false, $googleplustext = false, $echo = true) {
	global $feedback_champru;

	if (!isset($feedback_champru))
		$feedback_champru = new FeedbackChampuru();

	add_filter('get_comment_type', array(&$feedback_champru, 'get_comment_type'));
	$type = get_comment_type();
	remove_filter('get_comment_type', array(&$feedback_champru, 'get_comment_type'));

	switch( $type ) {
		case 'trackback' :
			$text = ( false === $trackbacktxt ? __( 'Trackback' ) : $trackbacktxt );
			break;
		case 'pingback' :
			$text = ( false === $pingbacktxt ? __( 'Pingback' ) : $pingbacktxt );
			break;
		case 'tweet' :
			$text = ( false === $tweettext ? __( 'Tweet', $feedback_champru->textdomain_name ) : $tweettext );
			break;
		case 'hatena' :
			$text = ( false === $hatenatext ? __( 'Hatena Bookmark', $feedback_champru->textdomain_name ) : $hatenatext );
			break;
		case 'delicious' :
			$text = ( false === $delisioustext ? __( 'Delicious', $feedback_champru->textdomain_name ) : $delisioustext );
			break;
		case 'friendfeed' :
			$text = ( false === $friendfeedtext ? __( 'FriendFeed', $feedback_champru->textdomain_name ) : $delisioustext );
			break;
		case 'livedoor' :
			$text = ( false === $livedoortext ? __( 'livedoor Clip', $feedback_champru->textdomain_name ) : $delisioustext );
			break;
		case 'buzzurl' :
			$text = ( false === $buzzurltext ? __( 'Buzzurl', $feedback_champru->textdomain_name ) : $delisioustext );
			break;
		case 'google' :
			$text = ( false === $googletext ? __( 'Google', $feedback_champru->textdomain_name ) : $googletext );
			break;
		case 'googleplus' :
			$text = ( false === $googleplustext ? __( 'Google+', $feedback_champru->textdomain_name ) : $googletext );
			break;
		default :
			$text = ( false === $commenttxt ? _x( 'Comment', 'noun' ) : $commenttxt );
	}

	if ($echo) {
		echo $text;
	} else {
		return $text;
	}
}

//**********************************************************************************
// Feedback Champuru
//**********************************************************************************
class FeedbackChampuru {
	var $plugin_name   = 'feedback-champuru';
	var $plugin_ver    = '0.5.4';

	const SCHEDULE_HANDLER = 'get-feedback-champuru';
	const META_KEY_PRE  = '_feedback_';

	const TWITTER_API   = 'http://search.twitter.com/search.json?q=%s&rpp=100';
	const TOPSY_API     = 'http://otter.topsy.com/trackbacks.json?url=%s&tracktype=tweet&perpage=50';
	const HATENA_API    = 'http://b.hatena.ne.jp/entry/jsonlite/?url=%s';
	const DELICIOUS_API = 'http://feeds.delicious.com/v2/json/url/%s';
	const FRIENDFEED_API= 'http://friendfeed-api.com/v2/url?url=%s';
	const LIVEDOOR_API  = 'http://api.clip.livedoor.com/json/comments?link=%s';
	const BUZZURL_API   = 'http://api.buzzurl.jp/api/posts/get/v1/json/?url=%s';
	const GOOGLE_API    = 'http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=link:%s';
	const GOOGLEPLUS_API= 'http://plus.otter.topsy.com/trackbacks.json?url=%s&tracktype=googleplus__trackback&perpage=50';

	var $plugin_dir, $plugin_file, $plugin_url;
	var $textdomain_name;
	var $option_name;

	var $admin_option, $admin_action, $admin_hook;
	var $note = '';
	var $error = 0;

	var $cache_expired = 30;			// Request cache expired (minutes)
	var $comment_type  = '';

	var $feedbacks     = array('tweet', 'hatena', 'delicious');

	var $icon_cache    = false;
	var $cache_path, $cache_url;

	var $spam_check    = true;

	var $comment_id    = 0;

	/**********************************************************
	* Constructor
	***********************************************************/
	function FeedbackChampuru(){
		$this->__construct();
	}
	function __construct() {
		$this->_set_plugin_dir(__FILE__);
		$this->_load_textdomain();
		$this->_init_options();

		// add admin menu
		if ( is_admin() ) {
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_filter('plugin_action_links', array(&$this, 'plugin_setting_links'), 10, 2 );
		}

		// add filters
		add_filter('comments_array', array(&$this, 'comments_champuru'), 10, 2);
		add_filter('get_comments_number', array(&$this, 'get_comments_number'), 10, 2);
		add_filter('comment_class', array(&$this, 'comment_class'), 10, 4);
		add_filter('get_avatar', array(&$this, 'get_avatar'), 10, 5);
		add_filter('comment_reply_link', array(&$this, 'comment_reply_link'), 10, 4);
		add_filter('edit_comment_link', array(&$this, 'edit_comment_link'), 10, 2);

		add_filter($this->plugin_name.'/cache_expired', array(&$this, 'get_cache_expired'), 10, 2);
		add_filter($this->plugin_name.'/comments_array', array(&$this, 'set_gmt_offset'), 10, 2);

		// percent encode capital letter
		add_filter('post_link', array(&$this, 'percent_encode_capital_letter'));
		add_filter('page_link', array(&$this, 'percent_encode_capital_letter'));
		add_filter('tag_link', array(&$this, 'percent_encode_capital_letter'));
		add_filter('category_link', array(&$this, 'percent_encode_capital_letter'));
		add_filter('preview_post_link', array(&$this, 'percent_encode_capital_letter'));
		add_filter('get_the_guid', array(&$this, 'percent_encode_capital_letter'));

		// admin bar
		add_action('wp_footer', array(&$this, 'wp_footer'), 1);

		// wp-cron schedule
		add_action(self::SCHEDULE_HANDLER, array(&$this, 'get_champuru'));

		// activation & deactivation
		if (function_exists('register_activation_hook')) {
			register_activation_hook(__FILE__, array(&$this, 'activation'));
		}
		if (function_exists('register_deactivation_hook')) {
			register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));
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

		$this->admin_option    = $this->plugin_file;
		$this->admin_action    =
			  trailingslashit(get_bloginfo('wpurl')) . 'wp-admin/'
			. ($this->_check_wp_version("2.7") ? 'options-general.php' : 'admin.php')
			. '?page=' . $this->admin_option;
		$this->admin_hook      = array();
	}

	// load textdomain
	function _load_textdomain( $sub_dir = 'languages', $textdomain_name = '' ) {
		$this->textdomain_name = (empty($textdomain_name) ? $this->plugin_dir : $textdomain_name);
		$plugins_dir = trailingslashit(defined('PLUGINDIR') ? PLUGINDIR : 'wp-content/plugins');
		$abs_plugin_dir = $this->_wp_plugin_dir($this->plugin_dir);
		$sub_dir = ( !empty($sub_dir)
			? preg_replace('/^\//', '', $sub_dir)
			: (file_exists($abs_plugin_dir.'languages') ? 'languages' : (file_exists($abs_plugin_dir.'language') ? 'language' : (file_exists($abs_plugin_dir.'lang') ? 'lang' : '')))
			);
		$textdomain_dir = trailingslashit(trailingslashit($this->plugin_dir) . $sub_dir);

		if ( $this->_check_wp_version("2.6") && defined('WP_PLUGIN_DIR') ) {
			load_plugin_textdomain($this->textdomain_name, false, $textdomain_dir);
		} else {
			load_plugin_textdomain($this->textdomain_name, $plugins_dir . $textdomain_dir);
		}
	}

	// init options
	function _init_options(){
		$this->option_name = $this->plugin_name.' Options';
		$options = (array) get_option($this->option_name);

		// feedback types
		$feedbacks = isset($options['feedbacks']) ? $options['feedbacks'] : $this->feedbacks;
		$this->feedbacks = apply_filters($this->plugin_name.'/feedback_types', $feedbacks);

		// icon cache enabled
		$icon_cache = isset($options['icon_cache']) ? $options['icon_cache'] : $this->icon_cache;
		$this->icon_cache = apply_filters($this->plugin_name.'/icon_cache', $icon_cache);
		$icon_cache_dir   = apply_filters($this->plugin_name.'/icon_cach_dir', 'cache/' . $this->plugin_name);
		$this->cache_path = $this->_wp_content_dir( $icon_cache_dir );
		$this->cache_url  = $this->_wp_content_url( $icon_cache_dir );
		$this->icon_cache = $this->icon_cache && $this->_check_icon_cache_dir($this->cache_path);

		// spam check enabled
		$spam_check = isset($options['spam_check']) ? $options['spam_check'] : $this->spam_check;
		$this->spam_check = apply_filters($this->plugin_name.'/spam_check', $spam_check);
	}

	// Update Options
	function _update_options( $options = array() ) {
		$options = array(
			'feedbacks'  => isset($options['feedbacks'])  ? $options['feedbacks']  : $this->feedbacks  ,
			'icon_cache' => isset($options['icon_cache']) ? $options['icon_cache'] : $this->icon_cache ,
			'spam_check' => isset($options['spam_check']) ? $options['spam_check'] : $this->spam_check ,
			);
		update_option($this->option_name, $options);
	}

	// check icon cache directory
	function _check_icon_cache_dir( $cache_path = '') {
		if ( function_exists('imagepng') ) {
			if( !file_exists( dirname($cache_path) ) )
				@mkdir( dirname($cache_path), 0777 );
			if( !file_exists($cache_path) )
				@mkdir( $cache_path, 0777 );
			$icon_cache = file_exists($cache_path);
		} else {
			$icon_cache = false;
		}
		return ($icon_cache !== false);
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

	// Get post_meta
	function _get_post_meta($post_id, $key) {
		$val = get_post_meta($post_id, $key, true);
		$val = maybe_unserialize($val);
		if (!is_serialized($val) && !is_array($val) && ($_val = base64_decode($val)) !== FALSE) {
			$val = maybe_unserialize($_val);
		}
		return $val;
	}

	// Add or Update post_meta
	function _update_post_meta($post_id, $key, $val) {
		$val = maybe_serialize($val);
		$val = base64_encode($val);
		return (
			add_post_meta($post_id, $key, $val, true) or
			update_post_meta($post_id, $key, $val)
			);
	}

	// Make Nonce field
	function _make_nonce_field($action = -1, $name = "_wpnonce", $referer = true , $echo = true ) {
		if ( !function_exists('wp_nonce_field') )
			return;
		else
			return wp_nonce_field($action, $name, $referer, $echo);
	}

	// json decode
	function json_decode( $string ) {
		if ( function_exists('json_decode') ) {
			return json_decode( $string );
		} else {
			// For PHP < 5.2.0
			if ( !class_exists('Services_JSON') ) {
				require_once( 'includes/class-json.php' );
			}
			$json = new Services_JSON();
			return $json->decode( $string );
		}
	}

	// remote_get
	function remote_get( $url, $args = array() ){
		$ret = wp_remote_get( $url, $args );
		if ( is_array($ret) && isset($ret["body"]) && !empty($ret["body"]) )
			return $ret["body"];
		else
			return false;
	}

	// Get safe url
	function safe_url( $url ){
		$params = preg_split( '/[\?\&]/', $url );
		$url = array_shift($params);
		$query = '';
		foreach ( (array) $params as $param ) {
			if ( strpos( $param, '=' ) !== FALSE ) {
				$key_val = explode( '=', $param );
				$key = rawurlencode(array_shift($key_val));
				$val = rawurlencode($key_val[0]);
				unset($key_val);
				$query .= ( empty($query) ? '?' : '&' ) . $key . '=' . $val;
			} else {
				$query .= ( empty($query) ? '?' : '&' ) . $param;
			}
		}
		unset($params);

		$url = htmlspecialchars( $url.$query, ENT_QUOTES );

		return $url;
	}

	/**********************************************************
	* plugin activation
	***********************************************************/
	function activation(){
		$this->_update_options();
	}

	/**********************************************************
	* plugin deactivation
	***********************************************************/
	function deactivation(){
		wp_clear_scheduled_hook(self::SCHEDULE_HANDLER);
	}

	/**********************************************************
	* Add Admin Menu
	***********************************************************/
	function admin_menu() {
		$this->admin_hook['option'] = add_options_page(
			__('Feedback Champuru', $this->textdomain_name) ,
			__('Feedback Champuru', $this->textdomain_name) ,
			'administrator' ,
			$this->plugin_file ,
			array($this, 'option_page')
			);
	}

	function plugin_setting_links($links, $file) {
		$this_plugin = plugin_basename(__FILE__);
		if ($file == $this_plugin) {
			$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links
		}

		return $links;
	}

	/**********************************************************
	* Champuru!
	***********************************************************/
	function comments_champuru($comments, $post_id){
		$permalink = get_permalink($post_id);
		foreach ($this->feedbacks as $type) {
			$comments = array_merge($comments ,	$this->_get_feedback($type, $post_id, $permalink, false));
		}
		usort($comments, array($this, '_comments_cmp'));

		// set wp-cron schedule
		if ( is_singular() ) {
			$expired = false;
			foreach ($this->feedbacks as $type) {
				$meta_key = self::META_KEY_PRE . $type;
				$cache = $this->_get_post_meta($post_id, $meta_key);
				if ( is_array($cache) ) {
					$expired = $expired || ( isset($cache["expired"]) && (int)$cache["expired"] < time() );
				} else {
					$expired = true;
				}
			}

			if ( $expired ) {
				$transient = $this->plugin_name;
				if (false === ($value = get_transient($transient))) {
					$value = array();
				}
				$value[$post_id] = $permalink;
				set_transient($transient, $value, 5 * 60);
				wp_schedule_single_event(time(), self::SCHEDULE_HANDLER);
			}
		}

		return $comments;
	}

	function get_champuru() {
		$transient = $this->plugin_name;
		if (false !== ($value = get_transient($transient))) {
			foreach ((array)$value as $post_id => $permalink) {
				foreach ($this->feedbacks as $type) {
					$this->_get_feedback($type, $post_id, $permalink);
				}
			}
			delete_transient($transient);
		}
	}

	function _comments_cmp($a, $b){
	    if ($a->comment_date == $b->comment_date)
	        return 0;
		else
			return ( strtotime($a->comment_date) < strtotime($b->comment_date) ? -1 : 1);
	}

	function comment_build($type, $post_id, $author = '', $author_url = '', $datetime = 0, $content = '', $photo_url = '', $comment_id = null){
		$gmt_offset = 3600 * get_option('gmt_offset');
		$comment = (object) array(
			"comment_ID"           => $type . '-' . ($comment_id ? $comment_id : $this->comment_id++) ,
			"comment_post_ID"      => (string) $post_id ,
			"comment_author"       => $author ,
			"comment_author_email" => $photo_url ,
			"comment_author_url"   => $author_url ,
			"comment_author_IP"    => '' ,
			"comment_date"         => date('Y-m-d H:i:s', $datetime + ($type !== 'hatena' ? $gmt_offset : 0 )) ,
			"comment_date_gmt"     => gmdate('Y-m-d H:i:s', $datetime) ,
			"comment_content"      => $content ,
			"comment_karma"        => '0' ,
			"comment_approved"     => '1' ,
			"comment_agent"        => '0' ,
			"comment_type"         => apply_filters($this->plugin_name.'/comment_type', $this->comment_type, $type) ,
			"comment_parent"       => '0' ,
			"user_id"              => '0' ,
			);
		if ($this->spam_check($comment))
			$comment->comment_approved = 'spam';

		return $comment;
	}

	function _get_type_from_ID($comment_id = ''){
		$comment_id = (empty($comment_id)) || is_null($comment_id)
			? get_comment_ID()
			: (!is_null($comment_id) ? $comment_id : '');
		$comment_type = '';
		if (preg_match('/^([^\-]*)\-[\d]+$/', $comment_id, $matches))
			$comment_type = strtolower($matches[1]);
		unset($matches);
		return $comment_type;
	}

	/**********************************************************
	* Get Feedbacks
	***********************************************************/
	function _get_feedback($type, $post_id, $permalink){
		$permalink = $this->percent_encode_capital_letter($permalink);
		if ( !preg_match( '/^https?:\/\//i', $permalink) )
			return array();

		$comments_array = array();

		$cache = $this->_get_cache($type, $post_id);
		$get_new  = (intval($cache["expired"]) < time());

		switch ($type){
		case 'tweet':
			$comments_array = $this->_get_twitter($type, $post_id, $permalink, $get_new);
			break;
		case 'hatena':
			$comments_array = $this->_get_hatena($type, $post_id, $permalink, $get_new);
			break;
		case 'delicious':
			$comments_array = $this->_get_delicious($type, $post_id, $permalink, $get_new);
			break;
		case 'friendfeed':
			$comments_array = $this->_get_friendfeed($type, $post_id, $permalink, $get_new);
			break;
		case 'livedoor':
			$comments_array = $this->_get_livedoor($type, $post_id, $permalink, $get_new);
			break;
		case 'buzzurl':
			$comments_array = $this->_get_buzzurl($type, $post_id, $permalink, $get_new);
			break;
		case 'google':
			$comments_array = $this->_get_googleurl($type, $post_id, $permalink, $get_new);
			break;
		case 'googleplus':
			$comments_array = $this->_get_googleplus($type, $post_id, $permalink, $get_new);
			break;
		default:
			$comments_array = apply_filters($this->plugin_name.'/get_feedback', $comments_array, $type, $post_id, $permalink);
			break;
		}
		$comments_array = (array) apply_filters($this->plugin_name.'/comments_array', $comments_array, $type);

		$comments = array();
		foreach ( $comments_array as $comment ) {
			if ( isset($comment->comment_approved) && $comment->comment_approved == '1' )
				$comments[] = $comment;
		}

		return $comments;
	}

	function _get_cache($type, $post_id) {
		$meta_key = self::META_KEY_PRE . $type;
		$cache = $this->_get_post_meta($post_id, $meta_key);
		if ( isset($cache["expired"]) && isset($cache["comments"]) ) {
			return array('comments' => $cache["comments"], 'expired' => $cache["expired"]);
		} else {
			return array('comments' => array(), 'expired' => 0);
		}
	}

	/**********************************************************
	* Get Twitter Feedbacks (Twitter Search API)
	***********************************************************/
	function _get_twitter($type, $post_id, $permalink, $get_new = false){
		$cache = $this->_get_cache($type, $post_id);
		if ( !$get_new )
			return $cache["comments"];

		$comments = $cache["comments"];
		$response = $this->remote_get(sprintf(self::TWITTER_API, urlencode($permalink)));
		if ($response !== false){
			$json = $this->json_decode($response);
			$list = (isset($json->results) ? $json->results : array());
			foreach ((array) $list as $item){
				$author     = esc_attr($item->from_user_name);
				$author_url = esc_attr(sprintf('https://twitter.com/%s/status/%s', $item->from_user, $item->id_str));
				$datetime   = (int) strtotime($item->created_at);
				$content    = $item->text;
				$photo_url  = esc_attr($item->profile_image_url);

				$content    = apply_filters($this->plugin_name.'/content', $content, $type, $author, $author_url, $datetime, $photo_url, $item);

				if ( $content )
					$comments[$item->id_str] = $this->comment_build($type, $post_id, $author, $author_url, $datetime, $content, $photo_url, $item->id_str);
			}
		}
		$comments = (count($comments) > 0 ? $comments : $cache["comments"]);

		$cache = array(
			"expired" => time() + apply_filters($this->plugin_name.'/cache_expired', $this->cache_expired * 60, $post_id) ,
			"comments" => $comments ,
			);
		$this->_update_post_meta($post_id, self::META_KEY_PRE . $type, $cache);

		$results = array();
		foreach ($comments as $comment) {
			$results[] = $comment;
		}
		return $results;
	}

	/**********************************************************
	* Get Twitter Feedbacks (Topsy)
	***********************************************************/
	function _get_topsy($type, $post_id, $permalink, $get_new = false){
		$cache = $this->_get_cache($type, $post_id);
		if ( !$get_new )
			return $cache["comments"];

		$comments = array();
		$response = $this->remote_get(sprintf(self::TOPSY_API, urlencode($permalink)));
		if ($response !== false){
			$json = $this->json_decode($response);
			$list = (isset($json->response) ? $json->response->list : array());
			foreach ((array) $list as $item){
				$author     = preg_replace('/^http:\/\/topsy\.com\/twitter\/([^\?]*)\?.*$/i', '$1', esc_attr($item->author->topsy_author_url));
				$author_url = esc_attr($item->permalink_url);
				$datetime   = (int) $item->date;
				$content    = esc_attr($item->content);
				$photo_url  = esc_attr($item->author->photo_url);

				$content    = apply_filters($this->plugin_name.'/content', $content, $type, $author, $author_url, $datetime, $photo_url, $item);

				if ( $content )
					$comments[] = $this->comment_build($type, $post_id, $author, $author_url, $datetime, $content, $photo_url);
			}
		}
		$comments = (count($comments) > 0 ? $comments : $cache["comments"]);

		$cache = array(
			"expired" => time() + apply_filters($this->plugin_name.'/cache_expired', $this->cache_expired * 60, $post_id) ,
			"comments" => $comments ,
			);
		$this->_update_post_meta($post_id, self::META_KEY_PRE . $type, $cache);

		return $comments;
	}

	/**********************************************************
	* Get Hatena Bookmark Feedbacks
	***********************************************************/
	function _get_hatena($type, $post_id, $permalink, $get_new = false){
		$cache = $this->_get_cache($type, $post_id);
		if ( !$get_new )
			return $cache["comments"];

		$comments = array();
		$response = $this->remote_get(sprintf(self::HATENA_API, urlencode($permalink)));
		if ($response !== false){
			$json = $this->json_decode($response);
			$list = (isset($json->bookmarks) ? $json->bookmarks : array());
			foreach ((array) $list as $item){
				$author     = esc_attr($item->user);
				$author_url = esc_attr('http://b.hatena.ne.jp/entry/' . str_replace('http://', '', $permalink));
				$datetime   = strtotime(str_replace('/', '-', $item->timestamp));
				$content    = esc_attr($item->comment);
				$photo_url  = 'http://www.hatena.ne.jp/users/' . substr($author, 0, 2) . '/' . $author . '/profile.gif';

				$content    = apply_filters($this->plugin_name.'/content', $content, $type, $author, $author_url, $datetime, $photo_url, $item);

				if ( $content )
					$comments[] = $this->comment_build($type, $post_id, $author, $author_url, $datetime, $content, $photo_url);
			}
		}
		$comments = (count($comments) > 0 ? $comments : $cache["comments"]);

		$cache = array(
			"expired" => time() + apply_filters($this->plugin_name.'/cache_expired', $this->cache_expired * 60, $post_id) ,
			"comments" => $comments ,
			);
		$this->_update_post_meta($post_id, self::META_KEY_PRE . $type, $cache);

		return $comments;
	}

	/**********************************************************
	* Get Delicious Feedbacks
	***********************************************************/
	function _get_delicious($type, $post_id, $permalink, $get_new = false){
		$cache = $this->_get_cache($type, $post_id);
		if ( !$get_new )
			return $cache["comments"];

		$comments = array();
		$response = $this->remote_get(sprintf(self::DELICIOUS_API, md5($permalink)));
		if ($response !== false){
			$json = $this->json_decode($response);
			foreach ((array) $json as $item){
				$author     = esc_attr($item->a);
				$author_url = esc_attr('http://delicious.com/url/' . md5($permalink));
				$datetime   = strtotime($item->dt);
				$content    = esc_attr($item->n);
				$photo_url  = '';

				$content    = apply_filters($this->plugin_name.'/content', $content, $type, $author, $author_url, $datetime, $photo_url, $item);

				if ( $content )
					$comments[] = $this->comment_build($type, $post_id, $author, $author_url, $datetime, $content, $photo_url);
			}
		}
		$comments = (count($comments) > 0 ? $comments : $cache["comments"]);

		$cache = array(
			"expired" => time() + apply_filters($this->plugin_name.'/cache_expired', $this->cache_expired * 60, $post_id) ,
			"comments" => $comments ,
			);
		$this->_update_post_meta($post_id, self::META_KEY_PRE . $type, $cache);

		return $comments;
	}

	/**********************************************************
	* Get FriendFeed Feedbacks
	***********************************************************/
	function _get_friendfeed($type, $post_id, $permalink, $get_new = false){
		$cache = $this->_get_cache($type, $post_id);
		if ( !$get_new )
			return $cache["comments"];

		$comments = array();
		$response = $this->remote_get(sprintf(self::FRIENDFEED_API, urlencode($permalink)));
		if ($response !== false){
			$json_array = $this->json_decode($response);
			foreach ($json_array as $json) {
				$list = $json;
				foreach ((array) $list as $item){
					$author     = esc_attr($item->from->name);
					$author_url = esc_attr($item->url);
					$datetime   = strtotime($item->date);
					$content    = $item->body;
					$photo_url  = 'http://friendfeed-api.com/v2/picture/' . esc_attr($item->from->id) . '?size=large';;

					$content    = apply_filters($this->plugin_name.'/content', $content, $type, $author, $author_url, $datetime, $photo_url, $item);

					if ( $content )
						$comments[] = $this->comment_build($type, $post_id, $author, $author_url, $datetime, $content, $photo_url);
				}
			}
		}
		$comments = (count($comments) > 0 ? $comments : $cache["comments"]);

		$cache = array(
			"expired" => time() + apply_filters($this->plugin_name.'/cache_expired', $this->cache_expired * 60, $post_id) ,
			"comments" => $comments ,
			);
		$this->_update_post_meta($post_id, self::META_KEY_PRE . $type, $cache);

		return $comments;
	}

	/**********************************************************
	* Get livedoor clip Feedbacks
	***********************************************************/
	function _get_livedoor($type, $post_id, $permalink, $get_new = false){
		$cache = $this->_get_cache($type, $post_id);
		if ( !$get_new )
			return $cache["comments"];

		$comments = array();
		$response = $this->remote_get(sprintf(self::LIVEDOOR_API, urlencode($permalink)));
		if ($response !== false){
			$json = $this->json_decode($response);
			$list = (isset($json->Comments) ? $json->Comments : array());
			foreach ((array) $list as $item){
				$author     = esc_attr($item->livedoor_id);
				$author_url = esc_attr('http://clip.livedoor.com/page/' . $permalink);
				$datetime   = $item->created_on;
				$content    = esc_attr($item->notes);
				$photo_url  = 'http://image.profile.livedoor.jp/icon/' . $author . '_60.gif';

				$content    = apply_filters($this->plugin_name.'/content', $content, $type, $author, $author_url, $datetime, $photo_url, $item);

				if ( $content )
					$comments[] = $this->comment_build($type, $post_id, $author, $author_url, $datetime, $content, $photo_url);
			}
		}
		$comments = (count($comments) > 0 ? $comments : $cache["comments"]);

		$cache = array(
			"expired" => time() + apply_filters($this->plugin_name.'/cache_expired', $this->cache_expired * 60, $post_id) ,
			"comments" => $comments ,
			);
		$this->_update_post_meta($post_id, self::META_KEY_PRE . $type, $cache);

		return $comments;
	}

	/**********************************************************
	* Get buzzurl Feedbacks
	***********************************************************/
	function _get_buzzurl($type, $post_id, $permalink, $get_new = false){
		$cache = $this->_get_cache($type, $post_id);
		if ( !$get_new )
			return $cache["comments"];

		$comments = array();
		$response = $this->remote_get(sprintf(self::BUZZURL_API, urlencode($permalink)));
		if ($response !== false){
			$json_array = $this->json_decode($response);
			foreach ($json_array as $json) {
				$list = (isset($json->posts) ? $json->posts : array());
				foreach ((array) $list as $item){
					$author     = esc_attr($item->user_name);
					$author_url = esc_attr('http://buzzurl.jp/entry/' . $permalink);
					$datetime   = strtotime($item->date);
					$content    = esc_attr($item->comment);
					$photo_url  = 'http://buzzurl.jp.eimg.jp/user/' . $author . '/photo.png';

					$content    = apply_filters($this->plugin_name.'/content', $content, $type, $author, $author_url, $datetime, $photo_url, $item);

					if ( $content )
						$comments[] = $this->comment_build($type, $post_id, $author, $author_url, $datetime, $content, $photo_url);
				}
			}
		}
		$comments = (count($comments) > 0 ? $comments : $cache["comments"]);

		$cache = array(
			"expired" => time() + apply_filters($this->plugin_name.'/cache_expired', $this->cache_expired * 60, $post_id) ,
			"comments" => $comments ,
			);
		$this->_update_post_meta($post_id, self::META_KEY_PRE . $type, $cache);

		return $comments;
	}

	/**********************************************************
	* Get Google Feedbacks
	***********************************************************/
	function _get_googleurl($type, $post_id, $permalink, $get_new = false){
		$cache = $this->_get_cache($type, $post_id);
		if ( !$get_new )
			return $cache["comments"];

		$comments = array();
		$response = $this->remote_get(sprintf(self::GOOGLE_API, urlencode($permalink)));
		if ($response !== false){
			$json = $this->json_decode($response);
			$json_array = (isset($json->responseData) ? $json->responseData : array());
			foreach ($json_array as $json) {
				$list = (isset($json->results) ? $json->results : array());
				foreach ((array) $list as $item){
					$author     = esc_attr($item->visibleUrl);
					$author_url = esc_attr($item->url);
					$datetime   = 0;
					$content    = esc_attr($item->content);
					$photo_url  = '';

					$content    = apply_filters($this->plugin_name.'/content', $content, $type, $author, $author_url, $datetime, $photo_url, $item);

					if ( $content )
						$comments[] = $this->comment_build($type, $post_id, $author, $author_url, $datetime, $content, $photo_url);
				}
			}
		}
		$comments = (count($comments) > 0 ? $comments : $cache["comments"]);

		$cache = array(
			"expired" => time() + apply_filters($this->plugin_name.'/cache_expired', $this->cache_expired * 60, $post_id) ,
			"comments" => $comments ,
			);
		$this->_update_post_meta($post_id, self::META_KEY_PRE . $type, $cache);

		return $comments;
	}

	/**********************************************************
	* Get Google+ Feedbacks
	***********************************************************/
	function _get_googleplus($type, $post_id, $permalink, $get_new = false){
		$meta_key = self::META_KEY_PRE . $type;
		$cache = $this->_get_post_meta($post_id, $meta_key);
		if (isset($cache["expired"]) && isset($cache["comments"])) {
			$expired  = (int)$cache["expired"];
			$comments = $cache["comments"];
		} else {
			$expired  = 0;
			$comments = array();
		}
		if ( $expired > time() || !$get_new )
			return $comments;

		$comments = array();
		$response = $this->remote_get(sprintf(self::GOOGLEPLUS_API, urlencode($permalink)));
		if ($response !== false){
			$json = $this->json_decode($response);
			$list = (isset($json->response) ? $json->response->list : array());
			foreach ((array) $list as $item){
				$author     = esc_attr($item->author->display_name);
				$author_url = esc_attr($item->permalink_url);
				$datetime   = (int) $item->date;
				$content    = esc_attr($item->content);
				$photo_url  = esc_attr($item->author->photo_url);

				$content    = apply_filters($this->plugin_name.'/content', $content, $type, $author, $author_url, $datetime, $photo_url, $item);

				if ( $content )
					$comments[] = $this->comment_build($type, $post_id, $author, $author_url, $datetime, $content, $photo_url);
			}
		}
		$comments = (count($comments) > 0 ? $comments : (isset($cache["comments"]) ? $cache["comments"] : $comments));

		$cache = array(
			"expired" => time() + apply_filters($this->plugin_name.'/cache_expired', $this->cache_expired * 60, $post_id) ,
			"comments" => $comments ,
			);
		$this->_update_post_meta($post_id, self::META_KEY_PRE . $type, $cache);

		return $comments;
	}

	/**********************************************************
	* get comments number
	***********************************************************/
	function get_comments_number($count, $post_id = ''){
		$comments = $this->comments_champuru(array(), $post_id);
		return $count + count($comments);
	}

	/**********************************************************
	* comment class
	***********************************************************/
	function comment_class($classes, $class = '', $comment_id = '', $post_id = ''){
		$comment_type = $this->_get_type_from_ID($comment_id);
		if (!empty($comment_type))
			$classes[] = esc_attr($comment_type);
		return $classes;
	}

	/**********************************************************
	* comment type
	***********************************************************/
	function get_comment_type($comment_type){
		global $comment;
		$comment_type = $this->_get_type_from_ID(isset($comment) ? $comment->comment_ID : '');
		if (!empty($comment_type))
			$comment_type = esc_attr($comment_type);
		return $comment_type;
	}

	/**********************************************************
	* get avatar
	***********************************************************/
	function get_avatar($avatar, $id_or_email = '', $size = '96', $default = '', $alt = false){
		global $pagenow, $comment;

		if($pagenow == 'options-discussion.php')
			return $avatar;
		if (!isset($comment))
			return $avatar;

		$type_pattern = '/^('.implode('|',$this->feedbacks).')\-/i';
		if (preg_match('/^https?:\/\//i', $comment->comment_author_email)) {
			$img_url = $comment->comment_author_email;
		} elseif (preg_match($type_pattern, $comment->comment_ID, $matches)) {
			$img_url = site_url(str_replace(ABSPATH, '', dirname(__FILE__))) . '/images/';
			$img_url .= $matches[1] . ( $size <= 24 ? '_16.png' : '_32.png' );
		} else {
			$img_url = '';
		}

		if (!empty($img_url)) {
			if ($this->icon_cache) {
				$cache_file_name = $this->_cache_file_name($img_url, $size);
				$cache_file = $this->cache_path . $cache_file_name;
				$cache_expired = $this->cache_expired * 60;
				$img_url = (
					file_exists($cache_file)
					? $this->cache_url  . $cache_file_name
					: $this->plugin_url . basename(__FILE__) . '?url=' . base64_encode($img_url) . '&amp;size=' . $size
					);
			}

			$avatar = preg_replace(
				  '/^(.*<img [^>]* src=[\'"])http:\/\/[^\'"]+([\'"][^>]*\/>.*)$/i'
				, "$1{$img_url}$2"
				, $avatar);
		}

		return $avatar;
	}

	// _cache_file_name
	function _cache_file_name($img_url, $img_size) {
		return md5($img_url . $img_size) . '.png';
	}

	// Function getImage
	function get_image($img_url, $img_size){
		if(empty($img_url) || parse_url($img_url) === false) {
			header("HTTP/1.0 404 Not Found");
			die();
		}
		if(is_numeric($img_size) === false || $img_size > 96) {
			header("HTTP/1.0 404 Not Found");
			die();
		}

		$cache_file = $this->cache_path . $this->_cache_file_name($img_url, $img_size);
		$cache_expired = $this->cache_expired * 60;

		$image = false;
		if( !file_exists($cache_file) )
			$image = $this->_get_resize_image($img_url, $img_size, $cache_file);

		if ($image === false && file_exists($cache_file))
			$image = imagecreatefrompng($cache_file);

		if ($image !== false) {
			header('Content-Type: image/png');
			header('Expires: '.gmdate('D, d M Y H:i:s', time() + $cache_expired).' GMT');
			imagepng($image);
			imagedestroy($image);
		} else {
			header("HTTP/1.0 404 Not Found");
		}
	}

	// Function _get_resize_image
	function _get_resize_image($img_url, $img_size = 96, $cache_file = '') {
		$imgbin = $this->remote_get($img_url);
		if ($imgbin === false)
			return false;

		$img_resized = @imagecreatetruecolor($img_size, $img_size);
		$bgc = @imagecolorallocate($img_resized, 255, 255, 255);
		@imagefilledrectangle($img_resized, 0, 0, $img_size, $img_size, $bgc);

		$img = @imagecreatefromstring($imgbin);
		if($img === false)
			return ( !file_exists($cache_file) ? $img_resized : false );

		$img_width  = imagesx($img);
		$img_height = imagesx($img);
		@imagecopyresampled(
			$img_resized,
			$img,
			0, 0, 0, 0,
			$img_size, $img_size,
			$img_width, $img_height);

		@imagepng($img_resized, $cache_file);

		return $img_resized;
	}

	function wp_footer(){
		remove_filter('get_avatar', array(&$this, 'get_avatar'), 10, 5);
	}

	/**********************************************************
	* comment reply link
	***********************************************************/
	function comment_reply_link($link, $args = '', $comment = '', $post = ''){
		switch ($this->_get_type_from_ID(isset($comment->comment_ID) ? $comment->comment_ID : get_comment_ID())) {
		case 'tweet' :
		case 'hatena' :
		case 'delicious' :
		case 'friendfeed' :
		case 'livedoor' :
		case 'buzzurl' :
		case 'google' :
		case 'googleplus' :
			$link = '';
			break;
		default :
			break;
		}
		return $link;
	}

	/**********************************************************
	* edit comment link
	***********************************************************/
	function edit_comment_link($link, $comment_id = ''){
		return (preg_match('/^[\d]+$/', !empty($comment_id) ? $comment_id : get_comment_ID()) ? $link : '');
	}

	/**********************************************************
	* percent encode capital letter
	***********************************************************/
	function to_upper($m) {
		return strtoupper($m[0]);
	}
	function percent_encode_capital_letter($uri) {
		return preg_replace_callback('/(%[0-9a-f]{2}?)+/', array(&$this, 'to_upper'), $uri);
	}

	/**********************************************************
	* Get cache expired
	***********************************************************/
	function get_cache_expired($expired, $id = 0){
		$post = &get_post($id);
		$post_date_diff = (time() - strtotime($post->post_date_gmt . ' GMT')) / 60;
		return (int) (
			$post_date_diff > 100 && log($post_date_diff) > 1
			? $expired * log10($post_date_diff)
			: $expired
			);
	}

	/**********************************************************
	* SPAM check
	***********************************************************/
	function spam_check_array( $comments_array, $type = '' ){
		foreach ( (array)$comments_array as $key => $comment ) {
			if ( $comment->comment_approved !== 'spam' && $this->spam_check($comment) ) {
				$comments_array[$key]->comment_approved = 'spam';
			}
		}
		return $comments_array;
	}

	// SPAM check
	function spam_check( $comment ){
		if ( !$this->spam_check ) {
			return false;
		} else {
			return ($this->blacklist_check($comment) || $this->akismet($comment));
		}
	}

	// blacklist check
	function blacklist_check($comment){
		return wp_blacklist_check($comment->author, '', $comment->comment_author_url, $comment->comment_content, '', '');
	}

	// Akismet
	function akismet($comment) {
		global $akismet_api_host, $akismet_api_port;

		if ( !function_exists('akismet_http_post') || !(get_option('wordpress_api_key') || $wpcom_api_key) )
			return false;

		$c = array();
		$author = $author_email = $author_url = $content = '';

		$c['blog'] = get_option( 'home' );
		$c['comment_type'] = $comment->comment_type;
		if ( $permalink = get_permalink() )
			$c['permalink'] = $permalink;
		if ( '' != $comment->comment_author )
			$c['comment_author'] = $comment->comment_author;
		if ( '' != $comment->comment_author_email )
			$c['comment_author_email'] = $comment->comment_author_email;
		if ( '' != $comment->comment_author_url )
			$c['comment_author_url'] = $comment->comment_author_url;
		if ( '' != $comment->comment_content )
			$c['comment_content'] = $comment->comment_content;

		$query_string = '';
		foreach ( $c as $key => $data ) {
			$query_string .= $key . '=' . urlencode( stripslashes( $data ) ) . '&';
		}

		$post_id = $comment->comment_post_ID;
		$meta_key = self::META_KEY_PRE . 'akismet_result';
		$result_key = md5($query_string);

		$akismet_result = (array) $this->_get_post_meta($post_id, $meta_key);
		if ( !isset($akismet_result[$result_key]) ) {
			$response = akismet_http_post( $query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port );
			$akismet_result[$result_key] = ('true' == $response[1] ? true : false);
			$this->_update_post_meta($post_id, $meta_key, $akismet_result );
		}
		return $akismet_result[$result_key];
	}

	/**********************************************************
	* Set gmt offset
	***********************************************************/
	function set_gmt_offset( $comments_array, $type = '' ){
		$gmt_offset = 3600 * get_option('gmt_offset');
		foreach ((array)$comments_array as $key => $comment) {
			if (method_exists($comment, 'comment_date_gmt') && method_exists($comments_array[$key], 'comment_date')) {
				$comment_date_gmt = strtotime($comment->comment_date_gmt);
				switch( $type ) {
					case 'hatena' :
					case 'livedoor' :
					case 'buzzurl' :
						if ($gmt_offset > 0 && $comment->comment_date_gmt === $comment->comment_date) {
							$comment_date_gmt -= 32400;
							$comments_array[$key]->comment_date_gmt = gmdate('Y-m-d H:i:s', $comment_date_gmt);
						}
					case 'tweet' :
					case 'delicious' :
					case 'friendfeed' :
					case 'googleplus' :
						$comments_array[$key]->comment_date = date('Y-m-d H:i:s', $comment_date_gmt + $gmt_offset);
						break;
					default :
						break;
				}
			}
		}
		return $comments_array;
	}

	/**********************************************************
	* option page
	***********************************************************/
	function option_page() {
		if (isset($_POST['options_update'])) {
			if ($this->_check_wp_version("2.5"))
				check_admin_referer("update_options", "_wpnonce_update_options");

			$options = array();

			// feedback types
			$options['feedbacks']  = array();
			if (isset($_POST['tweet']) && $_POST['tweet'] == 'on')
				$options['feedbacks'][] = 'tweet';
			if (isset($_POST['hatena']) && $_POST['hatena'] == 'on')
				$options['feedbacks'][] = 'hatena';
			if (isset($_POST['delicious']) && $_POST['delicious'] == 'on')
				$options['feedbacks'][] = 'delicious';
			if (isset($_POST['friendfeed']) && $_POST['friendfeed'] == 'on')
				$options['feedbacks'][] = 'friendfeed';
			if (isset($_POST['livedoor']) && $_POST['livedoor'] == 'on')
				$options['feedbacks'][] = 'livedoor';
			if (isset($_POST['buzzurl']) && $_POST['buzzurl'] == 'on')
				$options['feedbacks'][] = 'buzzurl';
			if (isset($_POST['google']) && $_POST['google'] == 'on')
				$options['feedbacks'][] = 'google';
			if (isset($_POST['googleplus']) && $_POST['googleplus'] == 'on')
				$options['feedbacks'][] = 'googleplus';
			$this->feedbacks  = apply_filters($this->plugin_name.'/feedback_types', $options['feedbacks']);

			// icon cache enabled
			$options['icon_cache'] = (isset($_POST['icon_cache']) && $_POST['icon_cache'] == 'on' ? true : false);
			$this->icon_cache = apply_filters($this->plugin_name.'/icon_cache', $options['icon_cache']);

			// spam check enabled
			$options['spam_check'] = (isset($_POST['spam_check']) && $_POST['spam_check'] == 'on' ? true : false);
			$this->spam_check = apply_filters($this->plugin_name.'/spam_check', $options['spam_check']);

			// update options
			$this->_update_options($options);

			// Done!
			$this->note .= "<strong>".__('Done!', $this->textdomain_name)."</strong>";

		} elseif(isset($_POST['options_delete'])) {
			if ($this->_check_wp_version("2.5"))
				check_admin_referer("delete_options", "_wpnonce_delete_options");

			// delete options
			$this->_delete_settings();

			// Done!
			$this->note .= "<strong>".__('Done!', $this->textdomain_name)."</strong>";
			$this->error++;
		}

		$out  = '';

		// Add Options
		$out .= '<div class="wrap">'."\n";
		$out .= '<form method="post" id="update_options" action="' . $this->admin_action . '">'."\n";
		$out .= '<h2>' . __('Feedback Champuru Options', $this->textdomain_name) . '</h2><br />'."\n";
		if ($this->_check_wp_version("2.5"))
			$out .= $this->_make_nonce_field("update_options", "_wpnonce_update_options", true, false);

		$out .= '<h3>' . __('Comment Sources', $this->textdomain_name) . '</h3>'."\n";

		$out .= '<table class="optiontable form-table" style="margin-top:0;"><tbody>'."\n";

		$out .= '<tr>'."\n";
		$out .= '<td>';
		$out .= '<input type="checkbox" name="tweet" id="tweet" value="on" style="margin-right:0.5em;"'.(in_array('tweet',$this->feedbacks) ? ' checked="true"' : '').' />';
		$out .= '<label for="tweet" accesskey="t">';
		$out .= __('Tweet', $this->textdomain_name);
		$out .= '</label>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="checkbox" name="hatena" id="hatena" value="on" style="margin-right:0.5em;"'.(in_array('hatena',$this->feedbacks) ? ' checked="true"' : '').' />';
		$out .= '<label for="hatena" accesskey="h">';
		$out .= __('Hatena Bookmark', $this->textdomain_name);
		$out .= '</label>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="checkbox" name="delicious" id="delicious" value="on" style="margin-right:0.5em;"'.(in_array('delicious',$this->feedbacks) ? ' checked="true"' : '').' />';
		$out .= '<label for="delicious" accesskey="d">';
		$out .= __('Delicious', $this->textdomain_name);
		$out .= '</label>';
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>'."\n";
		$out .= '<td>';
		$out .= '<input type="checkbox" name="friendfeed" id="friendfeed" value="on" style="margin-right:0.5em;"'.(in_array('friendfeed',$this->feedbacks) ? ' checked="true"' : '').' />';
		$out .= '<label for="friendfeed" accesskey="f">';
		$out .= __('FriendFeed', $this->textdomain_name);
		$out .= '</label>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="checkbox" name="livedoor" id="livedoor" value="on" style="margin-right:0.5em;"'.(in_array('livedoor',$this->feedbacks) ? ' checked="true"' : '').' />';
		$out .= '<label for="livedoor" accesskey="l">';
		$out .= __('livedoor Clip', $this->textdomain_name);
		$out .= '</label>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="checkbox" name="buzzurl" id="buzzurl" value="on" style="margin-right:0.5em;"'.(in_array('buzzurl',$this->feedbacks) ? ' checked="true"' : '').' />';
		$out .= '<label for="buzzurl" accesskey="b">';
		$out .= __('Buzzurl', $this->textdomain_name);
		$out .= '</label>';
		$out .= '</td>';
		$out .= '</tr>'."\n";
/*
		$out .= '<tr>'."\n";
		$out .= '<td>';
		$out .= '<input type="checkbox" name="googleplus" id="googleplus" value="on" style="margin-right:0.5em;"'.(in_array('googleplus',$this->feedbacks) ? ' checked="true"' : '').' />';
		$out .= '<label for="googleplus" accesskey="f">';
		$out .= __('Google+', $this->textdomain_name);
		$out .= '</label>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '</td>';
		$out .= '</tr>'."\n";
*/
		$out .= '</tbody></table>'."\n";

		$out .= '<h3>' . __('Basic Settings', $this->textdomain_name) . '</h3>'."\n";

		$out .= '<table class="optiontable form-table" style="margin-top:0;"><tbody>'."\n";

		$out .= '<tr>'."\n";
		$out .= '<td>';
		$out .= '<input type="checkbox" name="icon_cache" id="icon_cache" value="on" style="margin-right:0.5em;"'.($this->icon_cache ? ' checked="true"' : '').' />';
		$out .= '<label for="icon_cache" accesskey="i">';
		$out .= __('icon cache enabled', $this->textdomain_name);
		$out .= '</label>';
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>'."\n";
		$out .= '<td>';
		$out .= '<input type="checkbox" name="spam_check" id="spam_check" value="on" style="margin-right:0.5em;"'.($this->spam_check ? ' checked="true"' : '').' />';
		$out .= '<label for="spam_check" accesskey="s">';
		$out .= __('SPAM check enabled', $this->textdomain_name);
		$out .= '</label>';
		$out .= '</td>';
		$out .= '</tr>'."\n";

		$out .= '</tbody></table>'."\n";

		// Add Update Button
		$out .= '<p class="submit">'."\n";
		$out .= '<input type="submit" name="options_update" class="button button-primary" value="' . esc_attr(__('Save Changes')) . '" />'."\n";
		$out .= '</p>'."\n";

		$out .= '</form></div>'."\n";

		// Options Delete
		$out .= "<div class=\"wrap\" style=\"margin-top:2em;\">\n";
		$out .= "<h3>" . __('Uninstall', $this->textdomain_name) . "</h3>\n";
		$out .= "<form method=\"post\" id=\"delete_options\" action=\"".$this->admin_action."\">\n";
		if ($this->_check_wp_version("2.5"))
			$out .= $this->_make_nonce_field("delete_options", "_wpnonce_delete_options", true, false);
		$out .= "<p>" . __('All the settings of &quot;Feedback Champuru&quot; are deleted.', $this->textdomain_name) . "</p>";
		$out .= "<p class=\"submit\">\n";
		$out .= "<input type=\"submit\" name=\"options_delete\" class=\"button button-primary\" value=\"" . esc_attr(__('Delete Options', $this->textdomain_name)) . "\" />";
		$out .= "</p>\n";
		$out .= "</form></div>\n";

		// Output
		if (!empty($this->note))
			echo "<div id=\"message\" class=\"updated fade\"><p>{$this->note}</p></div>\n";

		if ($this->error == 0)
			echo $out . "\n";

		$transient = $this->plugin_name;
		if (false !== ($value = get_transient($transient))) {
			echo "<!--- \n";
			var_dump($value);
			echo "\n---> \n";
		}
	}

	// Delete all option
	function _delete_settings(){
		global $wpdb;

		$wpdb->query($wpdb->prepare(
			"DELETE FROM $wpdb->postmeta WHERE meta_key like %s" ,
			self::META_KEY_PRE . '%'
			)
		);
		delete_option($this->option_name);
	}
}
global $feedback_champru;

$feedback_champru = new FeedbackChampuru();

if ( $feedback_champru->icon_cache && strpos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false && isset($_GET['url']) ) {
	// Get Image from Cache
	$img_url  = $feedback_champru->safe_url(base64_decode($_GET['url']));
	$img_size = (int) (isset($_GET['size']) ? stripslashes($_GET['size']) : 48);

	$feedback_champru->get_image($img_url, $img_size);
}
