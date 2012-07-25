<?php
/*
Plugin Name: Custom Smilies
Plugin URI: http://goto8848.net/projects/custom-smilies/
Description: Personalize your posts and comments using custom smilies. Previously named Custom Smileys. it (older than version  2.0) maintained by <a href="http://onetruebrace.com/2007/11/28/custom-smilies/">QAD</a>.
Author: Crazy Loong
Version: 2.9.2
Author URI: http://goto8848.net

Copyright 2005 - 2008 Crazy Loong  (email : crazyloong@gmail.com)

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
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define('CLCSVER', '2.9.2');

// Pre-2.6 compatibility
if (!defined('WP_CONTENT_URL'))
	define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if (!defined('WP_CONTENT_DIR'))
	define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
if (!defined('WP_PLUGIN_URL'))
	define('WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins');
if (!defined('WP_PLUGIN_DIR'))
	define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');

if (!defined('WP_ABSDIR'))
	define('WP_ABSDIR', substr(str_replace('\\', '/', ABSPATH), 0, -1));
define('CLCSABSPATH', str_replace('\\', '/', dirname(__FILE__)) . '/');
define('CLCSABSFILE', str_replace('\\', '/', dirname(__FILE__)) . '/' . basename(__FILE__));
define('CLCSRELDIR', str_replace(WP_ABSDIR . '/', '', CLCSABSPATH));
define('CLCSRELURL', CLCSRELDIR);
define('CLCSINITABSPATH', CLCSABSPATH . 'init.php');
define('CLCSMGRURL', admin_url('edit.php?page=' . plugin_basename(CLCSABSFILE)));
define('CLCSOPTURL', admin_url('options-general.php?page=' . plugin_basename(CLCSABSFILE)));
define('CLCSURL', site_url(CLCSRELDIR));

if (function_exists('load_plugin_textdomain')) {
	load_plugin_textdomain('custom_smilies', PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/lang');
}

global $wpsmiliestrans;
$wpsmiliestrans = get_option('clcs_smilies');
if ($wpsmiliestrans == false) {
	$wpsmiliestrans = array(
	':mrgreen:' => 'icon_mrgreen.gif',
	':neutral:' => 'icon_neutral.gif',
	':twisted:' => 'icon_twisted.gif',
	  ':arrow:' => 'icon_arrow.gif',
	  ':shock:' => 'icon_eek.gif',
	  ':smile:' => 'icon_smile.gif',
	    ':???:' => 'icon_confused.gif',
	   ':cool:' => 'icon_cool.gif',
	   ':evil:' => 'icon_evil.gif',
	   ':grin:' => 'icon_biggrin.gif',
	   ':idea:' => 'icon_idea.gif',
	   ':oops:' => 'icon_redface.gif',
	   ':razz:' => 'icon_razz.gif',
	   ':roll:' => 'icon_rolleyes.gif',
	   ':wink:' => 'icon_wink.gif',
	    ':cry:' => 'icon_cry.gif',
	    ':eek:' => 'icon_surprised.gif',
	    ':lol:' => 'icon_lol.gif',
	    ':mad:' => 'icon_mad.gif',
	    ':sad:' => 'icon_sad.gif',
	      '8-)' => 'icon_cool.gif',
	      '8-O' => 'icon_eek.gif',
	      ':-(' => 'icon_sad.gif',
	      ':-)' => 'icon_smile.gif',
	      ':-?' => 'icon_confused.gif',
	      ':-D' => 'icon_biggrin.gif',
	      ':-P' => 'icon_razz.gif',
	      ':-o' => 'icon_surprised.gif',
	      ':-x' => 'icon_mad.gif',
	      ':-|' => 'icon_neutral.gif',
	      ';-)' => 'icon_wink.gif',
	       '8)' => 'icon_cool.gif',
	       '8O' => 'icon_eek.gif',
	       ':(' => 'icon_sad.gif',
	       ':)' => 'icon_smile.gif',
	       ':?' => 'icon_confused.gif',
	       ':D' => 'icon_biggrin.gif',
	       ':P' => 'icon_razz.gif',
	       ':o' => 'icon_surprised.gif',
	       ':x' => 'icon_mad.gif',
	       ':|' => 'icon_neutral.gif',
	       ';)' => 'icon_wink.gif',
	      ':!:' => 'icon_exclaim.gif',
	      ':?:' => 'icon_question.gif',
	);
}

global $wp_version;
if (version_compare($wp_version, '2.5', '<')) {
	include('forold.php'); // For 2.3
} else {
	include('common.inc.php');
	if ((version_compare($wp_version, '2.7', '<'))) {
		include('for25.php'); // For 2.5 and 2.6

		add_filter('plugin_action_links', 'add_settings_tab', 10, 4);
		function add_settings_tab($action_links, $plugin_file, $plugin_data, $context) {
			if (strip_tags($plugin_data['Title']) == 'Custom Smilies') {
				$tempstr0 = '<a href="' . wp_nonce_url('edit.php?page=' . $plugin_file) . '" title="' . __('Manage') . '" class="edit">' . __('Manage', 'custom_smilies') . '</a>';
				$tempstr1 = '<a href="' . wp_nonce_url('options-general.php?page=' . $plugin_file) . '" title="' . __('Options') . '" class="edit">' . __('Options', 'custom_smilies') . '</a>';
				array_unshift($action_links, $tempstr0, $tempstr1);
			}
			return $action_links;
		}
	} else {
		if ((version_compare($wp_version, '2.9', '<'))) {
			include('for27.php'); // For 2.7 and 2.8
		} else {
			include('for29.php'); // For 2.9 and above
		}

		add_filter('plugin_action_links', 'add_settings_tab', 10, 4);
		function add_settings_tab($action_links, $plugin_file, $plugin_data, $context) {
			if (strip_tags($plugin_data['Title']) == 'Custom Smilies') {
				//$tempstr0 = '<a href="' . wp_nonce_url('edit.php?page=' . $plugin_file) . '" title="' . __('Manage') . '" class="edit">' . __('Manage', 'custom_smilies') . '</a>';
				$tempstr1 = '<a href="' . wp_nonce_url('options-general.php?page=' . $plugin_file) . '" title="' . __('Options') . '" class="edit">' . __('Options', 'custom_smilies') . '</a>';
				array_unshift($action_links, $tempstr1);
			}
			return $action_links;
		}
	}
}

//$message = '<div class="error"><p>';
//$message .= '</p></div>';
//add_action('admin_notices', create_function('', "echo '$message';"));

register_activation_hook(__FILE__, 'clcs_activate');
function clcs_activate() {
	if (get_option('clcs_smilies') == false) { // Upgrade from older.
		if (file_exists(CLCSINITABSPATH)) {
			include_once(CLCSINITABSPATH);
			if (isset($wpsmiliestrans) && is_array($wpsmiliestrans)) {
				add_option('clcs_smilies', $wpsmiliestrans);
			} else {
				global $wpsmiliestrans;
				$array4db = $wpsmiliestrans;
				update_option('clcs_smilies', $array4db);
			}
			if (is_writeable(CLCSINITABSPATH)) {
				unlink(CLCSINITABSPATH);
			}
		} else {
			$array4db = array();
			update_option('clcs_smilies', $array4db);
		}
	}
	$clcs_options = get_option('clcs_options');
	if ($clcs_options == false) {
		$clcs_options = array(
			'use_action_comment_form' => 0,
			'comment_textarea' => 'comment',
			'smilies_path' => '/wp-includes/images/smilies'
		);
		update_option('clcs_options', $clcs_options);
	} else {
		if (!array_key_exists('comment_textarea', $clcs_options)) {
			$clcs_options['comment_textarea'] = 'comment';
		}
		if (!array_key_exists('use_action_comment_form', $clcs_options)) {
			$clcs_options['use_action_comment_form'] = 0;
		}
		if (!array_key_exists('smilies_path', $clcs_options)) {
			$clcs_options['smilies_path'] = '/wp-includes/images/smilies';
		}
		update_option('clcs_options', $clcs_options);
	}
}
?>