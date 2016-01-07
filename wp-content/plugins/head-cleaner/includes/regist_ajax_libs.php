<?php
/*
Regist Google Ajax libs

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
*/
global $wp_version;

if (!defined('AJAX_LIBS_GOOGLE'))
	define('AJAX_LIBS_GOOGLE', true);
if (!defined('AJAX_LIBS_YUI'))
	define('AJAX_LIBS_YUI', true);

$jquery_ver = '1.8.3';
if (version_compare($wp_version, "3.5", ">="))
	$jquery_ver = '1.8.3';
elseif (version_compare($wp_version, "3.4.1", ">="))
	$jquery_ver = '1.7.2';
elseif (version_compare($wp_version, "3.3", ">="))
	$jquery_ver = '1.7.1';
elseif (version_compare($wp_version, "3.2", ">="))
	$jquery_ver = '1.6.1';
elseif (version_compare($wp_version, "3.1", ">="))
	$jquery_ver = '1.4.4';
elseif (version_compare($wp_version, "3.0", ">="))
	$jquery_ver = '1.4.2';
elseif (version_compare($wp_version, "2.8", ">="))
	$jquery_ver = '1.3.2';
elseif (version_compare($wp_version, "2.6", ">="))
	$jquery_ver = '1.2.6';
elseif (version_compare($wp_version, "2.5", ">="))
	$jquery_ver = '1.2.3';

if (AJAX_LIBS_GOOGLE) {
	define('GOOGLE_JS_API_URL',           'http://www.google.com/jsapi');
	define('AJAX_LIBS_GOOGLE_URL',        'http://ajax.googleapis.com/ajax/libs/');
	define('AJAX_LIBS_PROTOTYPE_JS_VER',  '1.6.0.3');
	define('AJAX_LIBS_SCRIPTACULOUS_VER', '1.8.2');
	define('AJAX_LIBS_JQUERY_VER',        $jquery_ver);
	define('AJAX_LIBS_JQUERY_UI_VER',     '1.7');
	define('AJAX_LIBS_MOOTOOLS_VER',      '1.11');
	define('AJAX_LIBS_DOJO_VER',          '1.1.1');
	define('AJAX_LIBS_PROTOTYPE_JS',      AJAX_LIBS_GOOGLE_URL.'prototype/'.AJAX_LIBS_PROTOTYPE_JS_VER.'/prototype.js');
	define('AJAX_LIBS_SCRIPTACULOUS',     AJAX_LIBS_GOOGLE_URL.'scriptaculous/'.AJAX_LIBS_SCRIPTACULOUS_VER.'/');
	define('AJAX_LIBS_JQUERY',            AJAX_LIBS_GOOGLE_URL.'jquery/'.AJAX_LIBS_JQUERY_VER.'/jquery.min.js');
	define('AJAX_LIBS_JQUERY_UI',         AJAX_LIBS_GOOGLE_URL.'jqueryui/'.AJAX_LIBS_JQUERY_UI_VER.'/jquery-ui.min.js');
	define('AJAX_LIBS_MOOTOOLS',          AJAX_LIBS_GOOGLE_URL.'mootools/'.AJAX_LIBS_MOOTOOLS_VER.'/mootools-yui-compressed.min.js');
	define('AJAX_LIBS_DOJO',              AJAX_LIBS_GOOGLE_URL.'dojo/'.AJAX_LIBS_DOJO_VER.'/dojo.xd.min.js');
}

if (AJAX_LIBS_YUI) {
	define('AJAX_LIBS_YUI_VER',           '2.7.0');
	define('AJAX_LIBS_YUI_URL',           'http://yui.yahooapis.com/'.AJAX_LIBS_YUI_VER.'/build/');
}

//**************************************************************************************
// register_script
//**************************************************************************************
function register_script( $handle, $src = '', $deps = false, $ver = false ) {
	if (!class_exists('WP_Scripts'))
		return false;

	global $wp_scripts, $wp_version;

	if (!is_a($wp_scripts, 'WP_Scripts'))
		$wp_scripts = new WP_Scripts();

	if (version_compare($wp_version, "2.6", ">=")) {
		if (isset($wp_scripts->registered[$handle])) {
			if (version_compare($wp_scripts->registered[$handle]->ver, $ver, '<=')) {
				if ($src  != '')     $wp_scripts->registered[$handle]->src  = $src;
				if (is_array($deps)) $wp_scripts->registered[$handle]->deps = $deps;
				if ($ver  != false)  $wp_scripts->registered[$handle]->ver  = $ver;
			}
		} else {
			wp_register_script($handle, $src, $deps, $ver);
		}
	} else {
		if (isset($wp_scripts->scripts[$handle])) {
			if (version_compare($wp_scripts->scripts[$handle]->ver, $ver, '<=')) {
				if ($src  != '')     $wp_scripts->scripts[$handle]->src  = $src;
				if (is_array($deps)) $wp_scripts->scripts[$handle]->deps = $deps;
				if ($ver  != false)  $wp_scripts->scripts[$handle]->ver  = $ver;
			}
		} else {
			wp_register_script($handle, $src, $deps, $ver);
		}
	}
}

//**************************************************************************************
// dequeue_script
//**************************************************************************************
function dequeue_script( $handle ) {
	global $wp_scripts;

	if (!class_exists('WP_Scripts'))
		return false;

	if (!is_a($wp_scripts, 'WP_Scripts'))
		$wp_scripts = new WP_Scripts();
	$wp_scripts->dequeue( $handle );
}

//**************************************************************************************
// enqueue_ajax_lib_stylesheets
//**************************************************************************************
if (function_exists('wp_enqueue_style')) :

function enqueue_ajax_lib_stylesheets() {
	global $wp_scripts;

	if ( !is_a($wp_scripts, 'WP_Scripts') )
		return;

	$stylesheets = array(
		'yui',
		'yui-reset',
		'yui-base',
		'yui-fonts',
		'yui-grids',
		'yui-container',
		'yui-menu',
		'yui-autocomplete',
		'yui-button',
		'yui-calendar',
		'yui-colorpicker',
		'yui-datatable',
		'yui-editor',
		'yui-imagecropper',
		'yui-layout',
		'yui-resize',
		'yui-tabview',
		'yui-treeview',
		'yui-logger',
		'yui-profilerviewer',
		);
	foreach ( $stylesheets as $value ) {
		if ( array_search( $value, $wp_scripts->queue ) != false )
			wp_enqueue_style($value);
	}
}
add_action('wp_print_scripts', 'enqueue_ajax_lib_stylesheets', 1);

endif;

//**************************************************************************************
// script src cleanup
//**************************************************************************************
function script_src_cleanup($src) {
	if (strstr($src, GOOGLE_JS_API_URL) != false || strstr($src, AJAX_LIBS_GOOGLE_URL) != false || strstr($src, AJAX_LIBS_YUI_URL) != false)
		$src = preg_replace("/(\?|\&|\&(amp|#038);)ver=.*$/i", "", $src);
	return $src;
}
add_filter('script_loader_src', 'script_src_cleanup');

//**************************************************************************************
// jQuery noConflict
//**************************************************************************************
function cs_handlejqueryconflict($args) {
	$jquerypos = array_search('jquery', $args);
	if(false !== $jquerypos && in_array('prototype', $args)) {
		$url = trailingslashit(str_replace(ABSPATH, trailingslashit(get_bloginfo('wpurl')), dirname(__FILE__)));
		wp_register_script('jquery.noconflict', $url . 'js/jquery.noconflict.js' ,array('jquery'));
		array_splice( $args, $jquerypos+1, 0, 'jquery.noconflict' );
	}
	return $args;
}
add_filter('print_scripts_array', 'cs_handlejqueryconflict');

//**************************************************************************************
// Google AJAX Libraries
//**************************************************************************************
function hc_googlo_ajax_libraries() {
	if (AJAX_LIBS_GOOGLE && class_exists('WP_Scripts')) :
	register_script('jsapi', GOOGLE_JS_API_URL);

	// prototype
	//    name: prototype
	//    versions: 1.6.0.3
	//    load request: google.load("prototype", "1.6.0.3");
	//    path: http://ajax.googleapis.com/ajax/libs/prototype/1.6.0.3/prototype.js
	//    site: http://www.prototypejs.org/
	register_script('prototype', AJAX_LIBS_PROTOTYPE_JS, array(), AJAX_LIBS_PROTOTYPE_JS_VER);

	// script.aculo.us
	//    name: scriptaculous
	//    versions: 1.8.2
	//    load request: google.load("scriptaculous", "1.8.2");
	//    path: http://ajax.googleapis.com/ajax/libs/scriptaculous/1.8.2/scriptaculous.js
	//    site: http://script.aculo.us/
	//    note: this library depends on prototype. before loading this module, you must load prototype e.g.:
	register_script('scriptaculous-root', AJAX_LIBS_SCRIPTACULOUS.'scriptaculous.js', array('prototype'), AJAX_LIBS_SCRIPTACULOUS_VER);
	register_script('scriptaculous-builder', AJAX_LIBS_SCRIPTACULOUS.'builder.js', array('scriptaculous-root'), AJAX_LIBS_SCRIPTACULOUS_VER);
	register_script('scriptaculous-dragdrop', AJAX_LIBS_SCRIPTACULOUS.'dragdrop.js', array('scriptaculous-builder', 'scriptaculous-effects'), AJAX_LIBS_SCRIPTACULOUS_VER);
	register_script('scriptaculous-effects', AJAX_LIBS_SCRIPTACULOUS.'effects.js', array('scriptaculous-root'), AJAX_LIBS_SCRIPTACULOUS_VER);
	register_script('scriptaculous-slider', AJAX_LIBS_SCRIPTACULOUS.'slider.js', array('scriptaculous-effects'), AJAX_LIBS_SCRIPTACULOUS_VER);
	register_script('scriptaculous-sound', AJAX_LIBS_SCRIPTACULOUS.'sound.js', array( 'scriptaculous-root' ), AJAX_LIBS_SCRIPTACULOUS_VER);
	register_script('scriptaculous-controls', AJAX_LIBS_SCRIPTACULOUS.'controls.js', array('scriptaculous-root'), AJAX_LIBS_SCRIPTACULOUS_VER);
	register_script('scriptaculous', '', array('scriptaculous-dragdrop', 'scriptaculous-slider', 'scriptaculous-controls'), AJAX_LIBS_SCRIPTACULOUS_VER);

	// jQuery
	//    name: jquery
	//    versions: 1.4.2
	//    load request: google.load("jquery", "1.4.2");
	//    extras: uncompressed:true, e.g., google.load("jquery", "1.4", {uncompressed:true});
	//    path: http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js
	//    path(u): http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.js
	//    site: http://jquery.com/
	register_script('jquery', AJAX_LIBS_JQUERY, array(), AJAX_LIBS_JQUERY_VER);
	register_script('jquery.ui', AJAX_LIBS_JQUERY_UI, array('jquery'), AJAX_LIBS_JQUERY_UI_VER);

	// mootools
	//    name: mootools
	//    versions: 1.11
	//    load request: google.load("mootools", "1.11");
	//    extras: uncompressed:true, e.g., google.load("mootools", "1.11", {uncompressed:true});
	//    path: http://ajax.googleapis.com/ajax/libs/mootools/1.11/mootools-yui-compressed.js
	//    path(u): http://ajax.googleapis.com/ajax/libs/mootools/1.11/mootools.js
	//    site: http://mootools.net/ 
	register_script('mootools', AJAX_LIBS_MOOTOOLS, array(), AJAX_LIBS_MOOTOOLS_VER);


	// dojo
	//    name: dojo
	//    versions: 1.1.1
	//    load request: google.load("dojo", "1.1.1");
	//    extras: uncompressed:true, e.g., google.load("dojo", "1.1.1", {uncompressed:true});
	//    path: http://ajax.googleapis.com/ajax/libs/dojo/1.1.1/dojo/dojo.xd.js
	//    path(u): http://ajax.googleapis.com/ajax/libs/dojo/1.1.1/dojo/dojo.xd.js.uncompressed.js
	//    site: http://dojotoolkit.org/ 
	register_script('dojo', AJAX_LIBS_DOJO, array(), AJAX_LIBS_DOJO_VER);

	endif;

	//**************************************************************************************
	// YUI Libraries
	//**************************************************************************************
	if (AJAX_LIBS_YUI && class_exists('WP_Scripts')) :
	// YUI
	//    name: yui
	//    versions: 2.7.0
	//    path: http://yui.yahooapis.com/2.7.0/build/yahoo/yahoo-min.js
	//    site: http://developer.yahoo.com/yui/

	// YUI Core
	register_script('yui-core', AJAX_LIBS_YUI_URL.'yahoo/yahoo-min.js', array(), AJAX_LIBS_YUI_VER);
	register_script('yui-dom', AJAX_LIBS_YUI_URL.'dom/dom-min.js', array(), AJAX_LIBS_YUI_VER);
	register_script('yui-event', AJAX_LIBS_YUI_URL.'event/event-min.js', array(), AJAX_LIBS_YUI_VER);
	register_script('yui', '', array('yui-core', 'yui-dom', 'yui-event'), AJAX_LIBS_YUI_VER);
	if (function_exists('wp_register_style')) {
		wp_register_style('yui-reset', AJAX_LIBS_YUI_URL.'reset/reset-min.css', array(), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-base', AJAX_LIBS_YUI_URL.'base/base-min.css', array(), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-fonts', AJAX_LIBS_YUI_URL.'fonts/fonts-min.css', array(), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-grids', AJAX_LIBS_YUI_URL.'grids/grids-min.css', array(), AJAX_LIBS_YUI_VER);
		wp_register_style('yui', '', array('yui-reset', 'yui-base', 'yui-fonts', 'yui-grids'), AJAX_LIBS_YUI_VER);
	}

	// YUI Utilities
	register_script('yui-element', AJAX_LIBS_YUI_URL.'element/element-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-animation', AJAX_LIBS_YUI_URL.'animation/animation-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-connection', AJAX_LIBS_YUI_URL.'connection/connection-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-cookie', AJAX_LIBS_YUI_URL.'cookie/cookie-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-datasource', AJAX_LIBS_YUI_URL.'datasource/datasource-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-dragdrop', AJAX_LIBS_YUI_URL.'dragdrop/dragdrop-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-get', AJAX_LIBS_YUI_URL.'get/get-min.js', array('yui-core'), AJAX_LIBS_YUI_VER);
	register_script('yui-history', AJAX_LIBS_YUI_URL.'history/history-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-imageloader', AJAX_LIBS_YUI_URL.'imageloader/imageloader-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-json', AJAX_LIBS_YUI_URL.'json/json-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-resize', AJAX_LIBS_YUI_URL.'resize/resize-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-selector', AJAX_LIBS_YUI_URL.'selector/selector-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-yuiloader', AJAX_LIBS_YUI_URL.'yuiloader/yuiloader-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);

	// YUI's UI Controls
	register_script('yui-container', AJAX_LIBS_YUI_URL.'container/container-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-menu', AJAX_LIBS_YUI_URL.'menu/menu-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-autocomplete', AJAX_LIBS_YUI_URL.'autocomplete/autocomplete-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-button', AJAX_LIBS_YUI_URL.'button/button-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-calendar', AJAX_LIBS_YUI_URL.'calendar/calendar-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-charts', AJAX_LIBS_YUI_URL.'charts/charts-experimental-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-colorpicker', AJAX_LIBS_YUI_URL.'colorpicker/colorpicker-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-datatable', AJAX_LIBS_YUI_URL.'datatable/datatable-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-editor', AJAX_LIBS_YUI_URL.'editor/editor-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-imagecropper', AJAX_LIBS_YUI_URL.'imagecropper/imagecropper-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-layout', AJAX_LIBS_YUI_URL.'layout/layout-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-slider', AJAX_LIBS_YUI_URL.'slider/slider-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-tabview', AJAX_LIBS_YUI_URL.'tabview/tabview-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-treeview', AJAX_LIBS_YUI_URL.'treeview/treeview-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-uploader', AJAX_LIBS_YUI_URL.'uploader/uploader-experimental-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	if (function_exists('wp_register_style')) {
		wp_register_style('yui-container', AJAX_LIBS_YUI_URL.'container/assets/skins/sam/container.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-menu', AJAX_LIBS_YUI_URL.'menu/assets/skins/sam/menu.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-autocomplete', AJAX_LIBS_YUI_URL.'autocomplete/assets/skins/sam/autocomplete.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-button', AJAX_LIBS_YUI_URL.'button/assets/skins/sam/button.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-calendar', AJAX_LIBS_YUI_URL.'calendar/assets/skins/sam/calendar.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-colorpicker', AJAX_LIBS_YUI_URL.'colorpicker/assets/skins/sam/colorpicker.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-datatable', AJAX_LIBS_YUI_URL.'datatable/assets/skins/sam/datatable.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-editor', AJAX_LIBS_YUI_URL.'editor/assets/skins/sam/editor.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-imagecropper', AJAX_LIBS_YUI_URL.'imagecropper/assets/skins/sam/imagecropper.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-layout', AJAX_LIBS_YUI_URL.'layout/assets/skins/sam/layout.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-resize', AJAX_LIBS_YUI_URL.'resize/assets/skins/sam/resize.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-tabview', AJAX_LIBS_YUI_URL.'tabview/assets/skins/sam/tabview.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-treeview', AJAX_LIBS_YUI_URL.'treeview/assets/skins/sam/treeview.css', array('yui'), AJAX_LIBS_YUI_VER);
	}

	// YUI Developer Tools: Logging, Testing and Profiling
	register_script('yui-logger', AJAX_LIBS_YUI_URL.'logger/logger-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-profiler', AJAX_LIBS_YUI_URL.'profiler/profiler-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-profilerviewer', AJAX_LIBS_YUI_URL.'profilerviewer/profilerviewer-beta-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	register_script('yui-yuitest', AJAX_LIBS_YUI_URL.'yuitest/yuitest-min.js', array('yui'), AJAX_LIBS_YUI_VER);
	if (function_exists('wp_register_style')) {
		wp_register_style('yui-logger', AJAX_LIBS_YUI_URL.'logger/assets/skins/sam/logger.css', array('yui'), AJAX_LIBS_YUI_VER);
		wp_register_style('yui-profilerviewer', AJAX_LIBS_YUI_URL.'profilerviewer/assets/skins/sam/profilerviewer.css', array('yui'), AJAX_LIBS_YUI_VER);
	}

	endif;
}
add_action('init', 'hc_googlo_ajax_libraries');