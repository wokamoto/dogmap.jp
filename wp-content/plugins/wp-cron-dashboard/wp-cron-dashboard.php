<?php
/*
Plugin Name: WP-Cron Dashboard
Plugin URI: http://wppluginsj.sourceforge.jp/i18n-ja_jp/wp-cron-dashboard/
Description: WP-Cron Dashboard Display for Wordpress
Author: wokamoto
Version: 1.1.5
Author URI: http://dogmap.jp/
Text Domain: wp-cron-dashboard
Domain Path: /languages/

 Based on http://blog.slaven.net.au/archives/2007/02/01/timing-is-everything-scheduling-in-wordpress/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2007-2011 wokamoto (email : wokamoto1973@gmail.com)

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

class CronDashboard {
	var $plugin_dir, $plugin_file;
	var $textdomain_name = 'wp-cron-dashboard';

	/*
	* Constructor
	*/
	function CronDashboard() {
		$this->__construct();
	}
	function __construct() {
		$this->_set_plugin_dir(__FILE__);
		$this->_load_textdomain();
		add_action('admin_menu', array($this, 'add_admin_menu'));
	}

	// set plugin dir
	function _set_plugin_dir( $file = '' ) {
		$file_path = ( !empty($file) ? $file : __FILE__);
		$filename = explode("/", $file_path);
		if(count($filename) <= 1) $filename = explode("\\", $file_path);
		$this->plugin_dir  = $filename[count($filename) - 2];
		$this->plugin_file = $filename[count($filename) - 1];
		unset($filename);
	}

	// load textdomain
	function _load_textdomain( $sub_dir = 'languages' ) {
		global $wp_version;
		$plugins_dir = trailingslashit(defined('PLUGINDIR') ? PLUGINDIR : 'wp-content/plugins');
		$abs_plugin_dir = $this->_wp_plugin_dir($this->plugin_dir);
		$sub_dir = ( !empty($sub_dir)
			? preg_replace('/^\//', '', $sub_dir)
			: (file_exists($abs_plugin_dir.'languages') ? 'languages' : (file_exists($abs_plugin_dir.'language') ? 'language' : (file_exists($abs_plugin_dir.'lang') ? 'lang' : '')))
			);
		$textdomain_dir = trailingslashit(trailingslashit($this->plugin_dir) . $sub_dir);

		if ( version_compare($wp_version, '2.6', '>=') && defined('WP_PLUGIN_DIR') )
			load_plugin_textdomain($this->textdomain_name, false, $textdomain_dir);
		else
			load_plugin_textdomain($this->textdomain_name, $plugins_dir . $textdomain_dir);
	}

	// WP_CONTENT_DIR
	function _wp_content_dir($path = '') {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_DIR')
			? WP_CONTENT_DIR
			: trailingslashit(ABSPATH) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// WP_PLUGIN_DIR
	function _wp_plugin_dir($path = '') {
		return trailingslashit($this->_wp_content_dir('plugins/' . preg_replace('/^\//', '', $path)));
	}

	function add_admin_menu($s) {
		global $wp_version;

		add_submenu_page(
			version_compare($wp_version, "2.7", ">=") ? 'tools.php' : 'edit.php',
			'wp-cron' ,
			__('WP-Cron', $this->textdomain_name) ,
			'administrator' ,
			dirname(__FILE__) ,
			array(&$this, 'wp_cron_menu')
		);
		return $s;
	}

	function wp_cron_menu() {
		global $wp_filter;

		$note = '';
		$out = '';
		$datetime_format = get_option("date_format")." @".get_option("time_format");

		$crons = '';
		if (isset($_POST['submit'])) {
			$crons = $this->_unschedule_event($_POST['time'], $_POST['procname'], $_POST['key']);

			// Note snuff
			$note .= '<div id="message" class="updated fade"><p>';
			$note .= __('Sucessfully unscheduled',$this->textdomain_name)." ";
			$note .= $_POST['procname'];
			$note .= " (".date($datetime_format,$this->get_tz_timestamp($_POST['time'])).")";
			$note .= '</p></div>'."\n";
		}

		$out .= '<div class="wrap">'."\n";
		$out .= '<h2>'.__('Overview of tasks scheduled for WP-Cron',$this->textdomain_name).'</h2>'."\n";

		$out .= $this->show_cron_schedules($datetime_format, $crons);
		$out .= '<br/>'."\n";

		$out .= __('Current date/time is',$this->textdomain_name).": <strong>".current_time('mysql')."</strong>\n";
		$out .= "</div>";

		// Output
		echo $note.$out."\n";
	}

	function _unschedule_event( $timestamp, $hook, $key ) {
		$crons = $this->_get_cron_array();
		unset( $crons[$timestamp][$hook][$key] );
		if ( empty($crons[$timestamp][$hook]) )
			unset( $crons[$timestamp][$hook] );
		if ( empty($crons[$timestamp]) )
			unset( $crons[$timestamp] );
		$this->_set_cron_array( $crons );
		return $crons;
	}

	function _set_cron_array($cron) {
		if ( function_exists('_set_cron_array') ) {
			_set_cron_array( $cron );
		} else {
			$cron['version'] = 2;
			update_option( 'cron', $cron );
		}
	}

	function _get_cron_array() {
		if ( function_exists('_get_cron_array') ) {
			return _get_cron_array();
		} else {
			$cron = get_option('cron');
			if ( !is_array($cron) )
				return false;
			if ( !isset($cron['version']) )
				$cron = $this->_upgrade_cron_array($cron);
			unset($cron['version']);
			return $cron;
		}
	}

	function _upgrade_cron_array($cron) {
		if ( function_exists('_upgrade_cron_array') ) {
			return _upgrade_cron_array($cron);
		} else {
			if ( isset($cron['version']) && 2 == $cron['version'])
				return $cron;
			$new_cron = array();
			foreach ( (array) $cron as $timestamp => $hooks) {
				foreach ( (array) $hooks as $hook => $args ) {
					$key = md5(serialize($args['args']));
					$new_cron[$timestamp][$hook][$key] = $args;
				}
			}
			$new_cron['version'] = 2;
			update_option( 'cron', $new_cron );
			return $new_cron;
		}
	}

	function get_tz_timestamp($timestamp) {
		$utctzobj = timezone_open('UTC');	
		if  ( $tz = get_option('timezone_string') )  
			$tzobj = timezone_open($tz);
		else
			$tzobj = $utctzobj;
		$timeintz = new DateTime(date('Y-m-d H:i:s', $timestamp), $utctzobj);
		date_timezone_set( $timeintz, $tzobj );
		return strtotime( $timeintz->format('Y-m-d H:i:s') );
	}

	function show_cron_schedules($datetime_format = '', $crons = '') {
		$datetime_format =
			$datetime_format == ''
			? get_option("date_format")." @".get_option("time_format")
			: $datetime_format;

		$ans = '';
		$timeslots =
			$crons == ''
			? $this->_get_cron_array()
			: $crons;

		if ( empty($timeslots) ) {
			$ans .= '<div style="margin:.5em 0;width:100%;">';
			$ans .= __('Nothing scheduled',$this->textdomain_name);
			$ans .= '</div>'."\n";
		} else {
			$count = 1;
			foreach ( $timeslots as $time => $tasks ) {
				if (count($tasks) > 0) {
					$ans .= '<div style="margin:.5em 0;width:100%;">';
					$ans .= sprintf(
						__('Anytime after <strong>%s</strong> execute tasks',$this->textdomain_name) ,
						date($datetime_format, $this->get_tz_timestamp($time))
						);
					$ans .= '</div>'."\n";
				}
				foreach ($tasks as $procname => $task) {
					$ans .= '<div id="tasks-'.$count.'" style="margin:.5em;width:70%;">'."\n";

					$ans .= __('Entry #',$this->textdomain_name).$count.': '.$procname."\n";
					if ( function_exists('has_action') ) {
						$ans .= ( has_action( $procname )
							? '<span style="color:green;" >&#8730;</span>'.__(' action exists',$this->textdomain_name)
							: '<span style="color:red;">X</span>'.__(' no action exists with this name',$this->textdomain_name)
							);
					}
					$prockey = '';
					foreach ($task as $key => $args) {
						$prockey = $key;
					}
					// Add in delete button for each entry.
					$ans .= '<form method="post">'."\n";
					$ans .= '<input type="hidden" name="procname" value="'.$procname.'"/>'."\n";
					$ans .= '<input type="hidden" name="time" value="'.$time.'"/>'."\n";
					$ans .= '<input type="hidden" name="key" value="'.$prockey.'"/>'."\n";
					$ans .= '<input name="submit" style="float:right; margin-top: -20px;" type="submit" value="'.__('Delete',$this->textdomain_name).'"/>'."\n";
					$ans .= '</form>'."\n";

					$ans .= "</div>\n";
					$count++;
				}
			}
			unset($timeslots);
		}
		return $ans;
	}
}

new CronDashboard();
