<?php
/*
Plugin Name: Spirits and Goblins
Version: 0.3.0
Plugin URI: https://github.com/wokamoto/spirits-and-goblins
Description: This plugin enables 2-step verification using one-time password when you log in your WordPress.
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: spirits-and-goblins
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
  Copyright 2013 wokamoto (email : wokamoto1973@gmail.com)

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
if ( !class_exists('SpiritsAndGoblins_Admin') )
	require(dirname(__FILE__).'/includes/class-SpiritsAndGoblins_Admin.php');
if ( !class_exists('SpiritsAndGoblins') )
	require(dirname(__FILE__).'/includes/class-SpiritsAndGoblins.php');

load_plugin_textdomain(SpiritsAndGoblins::TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');

// Go Go Go!
$spirits_and_goblins = new SpiritsAndGoblins(SpiritsAndGoblins_Admin::get_option());

register_activation_hook(__FILE__, array($spirits_and_goblins, 'activate'));
register_deactivation_hook(__FILE__, array($spirits_and_goblins, 'deactivate'));

if (is_admin())
	new SpiritsAndGoblins_Admin();
