<?php
/*
Plugin Name: WP Remote Manager Client
Version: 0.7.0.2
Plugin URI: http://wp.remotemanager.me/
Description: This plugin enables the web application "WP Remote Manage" to manage your sites using OAuth authorization for connection between the web application and your sites.
Author: DigitalCube Co. Ltd
Author URI: http://www.digitalcube.jp/
Text Domain: wp-remote-manager-client
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
  Copyright 2012 wokamoto (email : wokamoto1973@gmail.com)

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

$includes = dirname(__FILE__).'/includes/';
if (!class_exists('WP_OAuthProvider'))
	require_once($includes.'class-oauth-provider.php');
if (!class_exists('Remote_Upgrader'))
	require_once ($includes.'class-remote-upgrader.php');
if (!class_exists('WP_Backuper'))
	require_once ($includes.'class-wp-backuper.php');

//**************************************************************************************
// init oauth provider ex
//**************************************************************************************
function wp_remote_manager_client_init() {
	global $oauth_provider, $remote_upgrader, $remote_backuper, $total_backup;

	$textdomain = 'wp_remote_manager_client';

	if (isset($oauth_provider) && function_exists('add_oauth_method')) {
		$oauth_provider->consumer_pin_enabled = TRUE;
		remove_oauth_method('sayHello');

		add_oauth_method('getSiteInfo', array(&$remote_upgrader, 'wp_getSiteInfo'));
		add_oauth_method('getVersions', array(&$remote_upgrader, 'wp_getVersions'));
		add_oauth_method('UserCan', array(&$remote_upgrader, 'wp_currentUserCan'));
		add_oauth_method('getUpdates', array(&$remote_upgrader, 'wp_getUpdates'));
		add_oauth_method('getCoreUpdates', array(&$remote_upgrader, 'wp_getCoreUpdates'));
		add_oauth_method('getPluginUpdates', array(&$remote_upgrader, 'wp_getPluginUpdates'));
		add_oauth_method('getThemeUpdates', array(&$remote_upgrader, 'wp_getThemeUpdates'));
		add_oauth_method('CoreUpdate', array(&$remote_upgrader, 'wp_CoreUpdate'));
		add_oauth_method('DBUpgrade', array(&$remote_upgrader, 'wp_DBUpgrade'));
		add_oauth_method('PluginsUpdate', array(&$remote_upgrader, 'wp_PluginsUpdate'));
		add_oauth_method('ThemesUpdate', array(&$remote_upgrader, 'wp_ThemesUpdate'));

		add_oauth_method('Backup', array(&$remote_backuper, 'wp_backup'));
		add_oauth_method('BackupFiles', array(&$remote_backuper, 'wp_backup_files_info'));

		if (is_admin()) {
			$is_multisite = function_exists('is_multisite') && is_multisite();
			if ( $is_multisite ) {
				remove_action('network_admin_menu', array(&$oauth_provider, 'admin_menu'));
				remove_action('network_admin_menu', array(&$total_backup, 'admin_menu'));
			} else {
				remove_action('admin_menu', array(&$oauth_provider, 'admin_menu'));
				remove_filter('plugin_action_links', array(&$oauth_provider, 'plugin_setting_links'), 10, 2 );
				remove_action('admin_menu', array(&$total_backup, 'admin_menu'));
				remove_filter('plugin_action_links', array(&$total_backup, 'plugin_setting_links'), 10, 2 );
			}

			if (count($oauth_provider->consumers) <= 0) {
				$oauth_provider->activation();
			}

			if (count($oauth_provider->all_access_tokens()) <= 0) {
				add_action('admin_notices', 'wp_remote_manager_client_admin_notice');
			} else {
				add_action($is_multisite ? 'network_admin_menu' : 'admin_menu', 'wp_remote_manager_client_admin_menu');
			}

		}

		if (get_query_var('json'))
			ob_start();
	}
}
add_action('init', 'wp_remote_manager_client_init');

function wp_remote_manager_client_admin_notice() {
	global $oauth_provider;

	$textdomain = 'wp_remote_manager_client';
	$consumers = $oauth_provider->consumers();
	$pin = '';
	foreach ( $consumers as $consumer ) {
		$pin = $consumer->pin;
		break;
	}

	if ( !empty($pin) ) {
		$siteurl = '';
		if ( defined('WP_SITEURL') && '' != WP_SITEURL )
			$siteurl = WP_SITEURL;
		elseif ( function_exists('get_bloginfo') && '' != get_bloginfo('wpurl') )
			$siteurl = get_bloginfo('wpurl');
		else
			$siteurl = get_option('siteurl');
		$siteurl = trailingslashit($siteurl);

		$url = sprintf(
			'http://dashboard.remotemanager.me/remote_access/add?url=%s&pin=%s',
			rawurlencode($siteurl),
			$pin);

		echo '<div class="error"><p>';
		printf(
			__('Activate your PIN at %s!', $textdomain),
			'<a href="'.$url.'" title="WP Remote Manager" target="_blank">'.__('WP Remote Manager',$textdomain).'</a>'
			);
		echo ' : PIN ' . $pin;
		echo '</p></div>';
	}
}

function wp_remote_manager_client_admin_menu() {
	global $oauth_provider, $total_backup;

	$textdomain = 'wp_remote_manager_client';

	$menu_base = basename(dirname(__FILE__));

	// Access Tokens
	$hook = add_menu_page(
		__('WP Remote Manager', $textdomain) ,
		__('WPRM', $textdomain) ,
		'manage_options',
		$menu_base ,
		'wp_remote_manager_client_options_access_tokens'
		);

	$hook = add_submenu_page(
		$menu_base ,
		__('Access Tokens &gt; WP Remote Manager', $textdomain) ,
		__('Access Tokens', $textdomain) ,
		'manage_options',
		$menu_base ,
		'wp_remote_manager_client_options_access_tokens'
		);

	// Total Backup
	$hook = add_submenu_page(
		$menu_base ,
		__('Total Backup &gt; WP Remote Manager', $textdomain) ,
		__('Total Backup', $textdomain) ,
		'manage_options',
		'total-backup' ,
		array($total_backup, 'site_backup')
		);
	add_action('admin_print_scripts-'.$hook, array($total_backup,'add_admin_scripts'));
	add_action('admin_head-'.$hook, array($total_backup,'add_admin_head'));
	add_action('admin_head-'.$hook, array($total_backup,'add_admin_head_main'));
	add_action('admin_print_styles-' . $hook, array($total_backup, 'icon_style'));

	// Total Backup Option
	$hook = add_submenu_page(
		$menu_base ,
		__('Option &gt; WP Remote Manager', $textdomain) ,
		__('Option', $textdomain) ,
		'manage_options',
		'total-backup-options' ,
		array($total_backup, 'option_page')
		);
	add_action('admin_print_scripts-'.$hook, array($total_backup,'add_admin_scripts'));
	add_action('admin_head-'.$hook, array($total_backup,'add_admin_head'));
	add_action('admin_head-'.$hook, array($total_backup,'add_admin_head_option'));
	add_action('admin_print_styles-' . $hook, array($total_backup, 'icon_style'));
}

function wp_remote_manager_client_options_access_tokens() {
	global $current_user, $wpdb, $oauth_provider;

	get_currentuserinfo();
	$username = $current_user->display_name;
	$userid   = $current_user->ID;
	$textdomain = 'wp_remote_manager_client';

	$nonce_action = 'update_options';
	$nonce_name   = '_wpnonce_update_options';

	$out   = '';
	$note  = '';
	$error = 0;

	// revoke access tokens
	if (isset($_POST['revoke_access_token']) && isset($_POST['access_tokens'])) {
		$access_tokens = $_POST['access_tokens'];
		foreach ((array) $access_tokens as $access_token_id) {
			$access_token_id = $wpdb->escape($access_token_id);
			if ( $oauth_provider->delete_access_token($userid, $access_token_id) !== FALSE ) {
				$error++;
				break;
			}
		}
		if ( $error <= 0 )
			$note .= "<strong>".__('Done!', $textdomain)."</strong>";
		else
			$note .= "<strong>".__('Failure!!', $textdomain)."</strong>";
	}

	$out .= '<div class="wrap">'."\n";
	$out .= '<div id="icon-options-general" class="icon32"><br /></div>';
	$out .= '<h2>';
	$out .= __('WP Remote Manager &raquo; Access Tokens', $textdomain);
	$out .= '</h2>'."\n";

	// Access tokens
	$out .= '<form method="post" id="access_tokens">'."\n";
	if (function_exists('wp_nonce_field') )
		$out .= wp_nonce_field($nonce_action, $nonce_name, true, false);

	$access_tokens = $oauth_provider->access_tokens();
	if ( count($access_tokens) > 0 ) {
		$out .= '<p>';
		$out .= '<input type="submit" name="revoke_access_token" class="button-primary" value="'.__('Revoke Access', $textdomain).'" class="button" />';
		$out .= '</p>' . "\n";

		$out .= '<table class="wp-list-table widefat fixed" style="margin-top:0;margin-bottom:3em;">'."\n";

		$title .= '<th class="check-column"></th>';
		$title .= '<th>' . __('Consumer Name', $textdomain) . '</th>';
		$title .= '<th>' . __('Consumer Description', $textdomain) . '</th>';
		$title .= '<th>' . __('Access Key', $textdomain) . '</th>';
		$title .= '<th>' . __('Access Secret', $textdomain) . '</th>';

		$out .= "<thead><tr>{$title}</tr></thead>\n";
		$out .= "<tfoot><tr>{$title}</tr></tfoot>\n";

		$i = 0;
		$alternate = ' class="alternate"';
		$out .= '<tbody>';
		foreach ( (array) $access_tokens as $access_token) {
			$out .= "<tr{$alternate}>";
			$out .= "<td class=\"check-column\"><input type=\"checkbox\" name=\"access_tokens[{$i}]\" value=\"{$access_token->id}\" style=\"margin-left:.5em;\" /></td>";
			$out .= "<td>{$access_token->name}</td>";
			$out .= "<td>{$access_token->description}</td>";
			$out .= "<td><input id=\"key-{$access_token->id}\" onfocus=\"this.select()\" value=\"{$access_token->oauthkey}\" /></td>";
			$out .= "<td><input id=\"secret-{$access_token->id}\" onfocus=\"this.select()\" value=\"{$access_token->secret}\" /></td>";
			$out .= '</tr>' . "\n";
			$i++;
			$alternate = empty($alternate) ? ' class="alternate"' : '';
		}
		$out .= '</tbody>' . "\n";
		$out .= '</table>' . "\n";
	}
	$out .= '</form></div>'."\n";

	// Output
	echo ( !empty($note) ? '<div id="message" class="updated fade"><p>'.$note.'</p></div>'  : '' );
	echo "\n";

	echo ( $error <= 0 ? $out : '' );
	echo "\n";
}

//**************************************************************************************
// Go! Go! Go!
//**************************************************************************************
global $oauth_provider, $remote_upgrader, $remote_backuper;

$textdomain = 'wp_remote_manager_client';
$textdomain_dir = untrailingslashit(basename(dirname(__FILE__))) . '/languages/';
load_plugin_textdomain($textdomain, false, $textdomain_dir);

$oauth_provider  = new WP_OAuthProvider(
	__FILE__,
	'json',
	TRUE,
	__('WP Remote Manager', $textdomain),
	__('"WP Remote Manager" is a web app that Unifying management of multiple WordPress sites.', $textdomain)
	);
$remote_upgrader = new Remote_Upgrader();
$remote_backuper = new WP_Backuper();

// Total Backup
$is_active = FALSE;
foreach ((array) get_option('active_plugins') as $val) {
	if (preg_match('/'.preg_quote('total-backup/total-backup.php', '/').'/i', $val)) {
		$is_active = TRUE;
		break;
	}
}
if ( !$is_active ) {
	$require_file = dirname(__FILE__).'/total-backup.php';
	if ( !class_exists('TotalBackup') && file_exists($require_file) )
		require_once($require_file);
}

if (isset($_GET['oauth']) || isset($_POST['oauth'])) {
	ob_start();
	add_filter('deprecated_argument_trigger_error', create_function('', 'return FALSE;'));
}

if (function_exists('register_activation_hook'))
	register_activation_hook(__FILE__, 'flush_rewrite_rules');
if (function_exists('register_deactivation_hook'))
	register_deactivation_hook(__FILE__, 'flush_rewrite_rules');