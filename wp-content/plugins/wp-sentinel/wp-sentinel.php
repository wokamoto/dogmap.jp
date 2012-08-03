<?php
/*
Plugin Name: WP-Sentinel
Plugin URI: http://lab.evilsocket.net/
Version: 2.0.3
Author: Simone Margaritelli aka evilsocket
Description: WordPress security system.
*/
/***************************************************************************
 *   @brief WP-Sentinel - Wordpress Security System .                      *
 *   @author Simone Margaritelli (aka evilsocket) <evilsocket@gmail.com>   *
 *                       		                                           *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 *   This program is distributed in the hope that it will be useful,       *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *   GNU General Public License for more details.                          *
 *                                                                         *
 *   You should have received a copy of the GNU General Public License     *
 *   along with this program; if not, write to the                         *
 *   Free Software Foundation, Inc.,                                       *
 *   59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             *
 ***************************************************************************/

define( 'WPS_INCLUDE', 1 );

/*
 * Register WP-Sentinel main function.
 */
include_once 'classes/sentinel.class.php';

register_activation_hook( __FILE__, array( 'WPSentinel', 'install' ) );

add_action( 'init', 'wp_sentinel_run', 0 );
/*
 * WP-Sentinel administration menu handler.
 */
include_once 'admin/admin.php';

add_action( 'admin_menu',          'wps_install_menu' );
add_action( 'admin_print_scripts', 'wp_sentinel_enqueue_scripts' );
add_action( 'admin_print_styles',  'wp_sentinel_enqueue_styles' );

/*
 * Enqueue plugin js scripts.
 */
function wp_sentinel_enqueue_scripts()
{
  wp_enqueue_script( 'thickbox' );
  wp_enqueue_script( 'facebox',       WPS_ADMIN_URL.'js/facebox.js', array('jquery') );
  wp_enqueue_script( 'facebox-start', WPS_ADMIN_URL.'js/start.js',   array('facebox') );
  wp_enqueue_script( 'tooltip',       WPS_ADMIN_URL.'js/tooltip.js', array('jquery') );
}
 
/*
 * Enqueue plugin css.
 */ 
function wp_sentinel_enqueue_styles()
{
	wp_enqueue_style( 'thickbox' );
	wp_enqueue_style( 'facebox', WPS_ADMIN_URL.'js/facebox.css' );
	wp_enqueue_style( 'tooltip', WPS_ADMIN_URL.'js/tooltip.css' );
}

/*
 * Main plugin function, ideally run by WP before everything else.
 */
function wp_sentinel_run()
{
  global $wpdb;
    	
  $oSentinel = new WPSentinel( $wpdb );
  $oSentinel->run();
}


?>