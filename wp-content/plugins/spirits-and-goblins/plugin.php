<?php
/*
Plugin Name: Spirits and Goblins
Version: 0.3.2
Plugin URI: https://github.com/wokamoto/spirits-and-goblins
Description: This plugin enables 2-step verification using one-time password when you log in your WordPress.
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: spirits-and-goblins
Domain Path: /languages/
Support PHP Version: 5.4

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
  Copyright 2013-2014 wokamoto (email : wokamoto1973@gmail.com)

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
$this_plugin_info = get_file_data( __FILE__, array(
	'version' => 'Version',
	'text_domain' => 'Text Domain',
	'domain_path' => 'Domain Path',
	'minimum_php' => 'Support PHP Version',
	));

// Require PHP ver.5.4 or higer
if ( version_compare(phpversion(), $this_plugin_info['minimum_php']) >= 0) {
	if ( !class_exists('SpiritsAndGoblins_Admin') )
		require(dirname(__FILE__).'/includes/class-SpiritsAndGoblins_Admin.php');
	if ( !class_exists('SpiritsAndGoblins') )
		require(dirname(__FILE__).'/includes/class-SpiritsAndGoblins.php');

	load_plugin_textdomain( $this_plugin_info['text_domain'], false, dirname(plugin_basename(__FILE__)) . $this_plugin_info['domain_path']);

	// Go Go Go!
	$spirits_and_goblins = SpiritsAndGoblins::get_instance();
	$spirits_and_goblins->init();
	register_activation_hook(__FILE__, array($spirits_and_goblins, 'activate'));
	register_deactivation_hook(__FILE__, array($spirits_and_goblins, 'deactivate'));

	if (is_admin()) {
		$spirits_and_goblins_admin = SpiritsAndGoblins_Admin::get_instance();
		$spirits_and_goblins_admin->init();
	}

} else {
	$plugin_notice = sprintf(__('Oops, this plugin will soon require PHP %s or higher.', $this_plugin_info['text_domain']), $this_plugin_info['minimum_php']);
	register_activation_hook(__FILE__, create_function('', "deactivate_plugins('".plugin_basename( __FILE__ )."'); wp_die('{$plugin_notice}');"));
}

unset($this_plugin_info);