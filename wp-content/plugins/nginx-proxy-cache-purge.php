<?php
/*
Plugin Name: Nginx Proxy Cache Purge for dogmap
Plugin URI: 
Description: Purges the nginx proxy cache when you publish or update a post or page.
Version: 0.9.5
Author: wokamoto
Author URI: http://dogmap.jp/
*/

class NginxProxyCachePurge {
	function __construct() {
		add_action('edit_post', array($this, 'wpselect_cache'));
		add_action('publish_post', array(&$this, 'wpselect_cache'));
		add_action('publish_future_post', array(&$this, 'wpselect_cache'));
		add_action('publish_phone', array(&$this, 'wpselect_cache'));
		add_action('wp_footer', array($this, 'wpselect_footer'));
	}

	function wpselect_cache($post_id) {
		$purge = '/purge';

                #array of purge urls
		$urls = array();

		#post/page purge url
		$link = get_permalink($post_id);
		$parse = parse_url($link);
		$post_url = $parse[scheme].'://'.$parse[host].$purge.$parse[path];
		$urls[] = $post_url;
		$urls[] = $post_url.'@ktai';
		$urls[] = $post_url.'@smartphone';

		#home page purge url
		$home_page = home_url();
		$parse_home = parse_url($home_page);
		$home_page_url = $parse[scheme].'://'.$parse[host].$purge;
		if ($parse_home[path] != '') { 
			$home_page_url = $home_page_url.$parse_home[path].'/';
		} else {
			$home_page_url = $home_page_url.'/';
		}
		$urls[] = $home_page_url;
		$urls[] = $home_page_url.'@ktai';
		$urls[] = $home_page_url.'@smartphone';

		#posts page purge url
		if ( get_option('show_on_front') == 'page' ) {
			$posts_page = get_option('page_for_posts');
			$posts_page_link = get_permalink($posts_page);
			$parse_posts = parse_url($posts_page_link);
			$posts_url = $parse_posts[scheme].'://'.$parse_posts[host].'/purge'.$parse_posts[path];
			$urls[] = $posts_url;
			$urls[] = $posts_url.'@ktai';
			$urls[] = $posts_url.'@smartphone';
		}

		#feed purge url
		$feed_url = $home_page_url.'feed/';
                $urls[] = $feed_url;

		#comments feed purge url
		$comments_feed_url = $home_page_url.'comments/feed/';
                $urls[] = $comments_feed_url;

		#remove duplicate purge urls
		$urls_unique = array_unique($urls);
		foreach ($urls_unique as $uri) {
			wp_remote_get($uri);
		}
	}

	function wpselect_footer() {
		$content = '<!-- Page created in ';
		$content .= timer_stop($display = 0, $precision = 2);
		$content .= ' seconds from ';
		$content .= get_num_queries();
		$content .= ' queries on ';
		$content .= date('m.d.y \@ H:i:s T');
		$content .= ' -->';
		echo $content;
	}
}
new NginxProxyCachePurge();