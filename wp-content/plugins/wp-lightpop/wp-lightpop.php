<?php
/*
Plugin Name: WP-lightpop
Version: 0.8.5.6
Plugin URI: http://wppluginsj.sourceforge.jp/wp-lightpop/
Description: Add overlay images and videos (and more) to Your Wordpress site.
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: wp-lightpop
Domain Path: /languages/

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

Includes:
 jQuery 1.3.2 - New Wave Javascript
  Copyright (c) 2009 John Resig (jquery.com)
  Dual licensed under the MIT and GPL licenses.

 FLV Media Player 3.16
  The FLV Media Player is licensed under a <a href="http://creativecommons.org/licenses/by-nc-sa/2.0/">Creative Commons License</a>.
  It allows you to use, modify and redistribute the script for free for noncommercial purposes.
  For corporate use, <a href="http://www.jeroenwijering.com/?page=order" title="Order commercial licenses">please apply for a 20 euros commercial license</a>!

*/
if (!class_exists('wokController') || !class_exists('wokScriptManager'))
	require(dirname(__FILE__).'/includes/common-controller.php');

class LightPop extends wokController {
	var $plugin_name  = 'wp-lightpop';

	var $lightpop_js  = 'js/jquery.lightpop-0.8.5.1.min.js';
	var $lightpop_ver = '0.8.5.1';

	var $_options_default, $_videosites, $_frame_type;

	/*
	* Constructor
	*/
	function LightPop() {
		$this->__construct();
	}
	function __construct() {
		$this->init(__FILE__);
		$this->options = $this->_initOptions($this->getOptions());

		if (is_admin()) {
			add_action('admin_menu', array(&$this,'addAdminMenu'));
			add_filter('plugin_action_links', array(&$this, 'pluginActionLinks'), 10, 2 );
			add_filter('image_send_to_editor', array(&$this, 'add_image_send_to_editor'), 10, 8);
		} else {
			add_action('template_redirect', array(&$this,'addScripts'));
			add_action('template_redirect', array(&$this,'dequeueScripts'), 11);
			add_action('wp_head',array(&$this,'addHead'));
			add_action('wp_footer',array(&$this,'addFooter'));
		}
	}

	/*
	* Init Options
	*/
	function _initOptions($wk_options) {
		$this->_videosites = array(
			'YouTube' ,
			'Metacafe' ,
			'LiveLeak' ,
			'GoogleVideo' ,
//			'ifilm' ,
			'Dailymotion' ,
			'superdeluxe' ,
			'nicovideo'
			);
		$this->_frame_type = array(
			0 => array('Border') ,
			1 => array('Default box',          'images/box0.gif', 'images/box0_l.gif', 'images/box0_r.gif', '#FFF', 17) ,
			2 => array('Emboss',               'images/box1.gif', 'images/box1_l.gif', 'images/box1_r.gif', '#FFF',  6) ,
			3 => array('Drop shadow',          'images/box2.gif', 'images/box2_l.gif', 'images/box2_r.gif', '#FFF',  6) ,
			4 => array('Shadow in the inside', 'images/box3.gif', 'images/box3_l.gif', 'images/box3_r.gif', '#FFF',  6) ,
			5 => array('Double shadow',        'images/box4.gif', 'images/box4_l.gif', 'images/box4_r.gif', '#FFF',  9) ,
			6 => array('Detainment of corner', 'images/box5.gif', 'images/box5_l.gif', 'images/box5_r.gif', '#FFF', 16) ,
			9 => array('Original design')
			);
		$this->_options_default = array(
			'overlayBgColor'       => "#000" ,
			'overlayOpacity'       => 0.7 ,
			'contentFrameType'     => 0 ,
			'contentBorder'        => "none" ,
			'contentBgColor'       => "#FFF" ,
			'imageLoading'         => "images/lightpop-ico-loading.gif" ,
			'imageBtnPrev'         => "images/lightpop-btn-prev.gif" ,
			'imageBtnNext'         => "images/lightpop-btn-next.gif" ,
			'imageBtnClose'        => "images/lightpop-btn-close.gif" ,
			'imageBlank'           => "images/lightpop-blank.gif" ,
			'imageBox'             => "images/lightpop-box.gif" ,
			'imageBorderL'         => "images/lightpop-border-l.gif" ,
			'imageBorderR'         => "images/lightpop-border-r.gif" ,
			'containerBorderSize'  => 10 ,
			'boxBorderSize'        => 6 ,
			'containerResizeSpeed' => 400 ,
			'txtImage'             => "Image" ,
			'txtOf'                => "of" ,
			'keyToClose'           => "c" ,
			'keyToPrev'            => "p" ,
			'keyToNext'            => "n" ,
			'flvplayer'            => "player.swf" ,
			'iconAdd'              => 0 ,
			'iconPath'             => "images/" ,
			'setLinkToTitle'       => 0 ,
			'detailsEnabled'       => 1 ,
			'imageEnabled'         => 1 ,
			'imageMaxWidth'        => 0 ,
			'imageMaxHeight'       => 0 ,
			'contentsEnabled'      => 0 ,
			'videoEnabled'         => 1 ,
			'youtubeEnabled'       => 1 ,
			'youtubeWidth'         => 425 ,
			'youtubeHeight'        => 355 ,
			'youtubeParams'        => 'hl=ja' ,
			'metacafeEnabled'      => 1 ,
			'metacafeWidth'        => 400 ,
			'metacafeHeight'       => 345 ,
			'metacafeParams'       => '' ,
			'liveleakEnabled'      => 1 ,
			'liveleakWidth'        => 450 ,
			'liveleakHeight'       => 370 ,
			'liveleakParams'       => '' ,
			'googlevideoEnabled'   => 1 ,
			'googlevideoWidth'     => 400 ,
			'googlevideoHeight'    => 326 ,
			'googlevideoParams'    => 'hl=ja' ,
//			'ifilmEnabled'         => 1 ,
//			'ifilmWidth'           => 448 ,
//			'ifilmHeight'          => 365 ,
//			'ifilmParams'          => '' ,
			'dailymotionEnabled'   => 1 ,
			'dailymotionWidth'     => 420 ,
			'dailymotionHeight'    => 330 ,
			'dailymotionParams'    => '' ,
			'superdeluxeEnabled'   => 1 ,
			'superdeluxeWidth'     => 400 ,
			'superdeluxeHeight'    => 350 ,
			'superdeluxeParams'    => '' ,
			'nicovideoEnabled'     => 1 ,
			'nicovideoWidth'       => 485 ,
			'nicovideoHeight'      => 385 ,
			'nicovideoParams'      => '' ,
			);

		if (!is_array($wk_options))
			$wk_options = array();

		$upload_dir = wp_upload_dir();
		$upload_url = trailingslashit($upload_dir['baseurl']);
		if (!isset($wk_options['targetCSS'])) {
			$wk_options['targetCSS']  = 
				  'a[href' . (preg_match('/^https?:\/\//i', $upload_url) ? '^' : '*') . "={$upload_url}]\n"
				. 'a[href*=.youtube.com/watch]';
		}
		if (!isset($wk_options['postClass'])) {
			$wk_options['postClass']  = '';
		}
		$wk_options['iconPath'] = $this->plugin_url.$this->_options_default['iconPath'];

		foreach ($this->_options_default as $key => $value) {
			if (!isset($wk_options[$key])) {
				switch ($key) {
				case 'imageLoading':
				case 'imageBtnPrev':
				case 'imageBtnNext':
				case 'imageBtnClose':
				case 'imageBlank':
				case 'imageBox':
				case 'imageBlank':
				case 'imageBorderL':
				case 'imageBorderR':
				case 'iconPath':
					$wk_options[$key] = $this->plugin_url.$value;
					break;
				case 'flvplayer':
					$plugin_path = trailingslashit(ABSPATH . $this->plugins_dir . $this->plugin_dir);
					if (file_exists($plugin_path . 'swf/flvplayer.swf'))
						$wk_options[$key] = $this->plugin_url . 'swf/flvplayer.swf';
					elseif (file_exists($plugin_path . 'swf/mediaplayer.swf'))
						$wk_options[$key] = $this->plugin_url . 'swf/mediaplayer.swf';
					elseif (file_exists($plugin_path . 'swf/player.swf'))
						$wk_options[$key] = $this->plugin_url . 'swf/player.swf';
					elseif (file_exists($plugin_path . 'mediaplayer.swf'))
						$wk_options[$key] = $this->plugin_url . 'mediaplayer.swf';
					elseif (file_exists($plugin_path . 'flvplayer.swf'))
						$wk_options[$key] = $this->plugin_url . 'flvplayer.swf';
					else
						$wk_options[$key] = $value;
					break;
				default:
					$wk_options[$key] = $value;
					break;
				}
			} else {
				switch ($key) {
				case 'nicovideoWidth':
				case 'nicovideoHeight':
					if ( $wk_options[$key] < $value )
						$wk_options[$key] = $value;
					break;
				default:
					break;
				}
			}
		}

		$wk_options['targetCSS'] = preg_replace('/(href.*=)["\']*([\w\-\.!~*\'\(\);\/?:\@&=+\$,%#]+)["\']*/i', '$1"$2"', $wk_options['targetCSS']);

		return ($wk_options);
	}

	function _getLightpopOption() {
		$retVal = 
			" imageLoading:'{$this->options['imageLoading']}'" .
			",imageBtnPrev:'{$this->options['imageBtnPrev']}'" .
			",imageBtnNext:'{$this->options['imageBtnNext']}'" .
			",imageBtnClose:'{$this->options['imageBtnClose']}'" .
			",imageBlank:'{$this->options['imageBlank']}'" .
			",flvplayer:'{$this->options['flvplayer']}'";

		switch ($this->options['contentFrameType']) {
		case 0:
			$retVal .= ",contentFrameType:'border'";
			if ($this->options['overlayBgColor'] != $this->_options_default['overlayBgColor']) {
				$retVal .= ",overlayBgColor:'{$this->options['overlayBgColor']}'";
			}
			if ($this->options['contentBorder'] != $this->_options_default['contentBorder']) {
				$retVal .= ",contentBorder:'{$this->options['contentBorder']}'";
			}
			break;

		case 9:
			$retVal .= 
				",contentFrameType:'box'" .
				",overlayBgColor:'{$this->options['overlayBgColor']}'" .
				",boxBorderSize:{$this->options['boxBorderSize']}" .
				",imageBox:'{$this->options['imageBox']}'" .
				",imageBorderL:'{$this->options['imageBorderL']}'" .
				",imageBorderR:'{$this->options['imageBorderR']}'";
			break;

		default:
			$retVal .= ",contentFrameType:'box'";
			if ($this->options['overlayBgColor'] === $this->_options_default['overlayBgColor']) {
				$retVal .= ",overlayBgColor:'{$this->_frame_type[$this->options['contentFrameType']][4]}'";
			} else {
				$retVal .= ",overlayBgColor:'{$this->options['overlayBgColor']}'";
			}
			$retVal .= 
				",boxBorderSize:{$this->_frame_type[$this->options['contentFrameType']][5]}" .
				",imageBox:'{$this->plugin_url}{$this->_frame_type[$this->options['contentFrameType']][1]}'" .
				",imageBorderL:'{$this->plugin_url}{$this->_frame_type[$this->options['contentFrameType']][2]}'" .
				",imageBorderR:'{$this->plugin_url}{$this->_frame_type[$this->options['contentFrameType']][3]}'";
			break;
		}

		$options = array(
			'overlayOpacity' ,
			'containerBorderSize' ,
			'containerResizeSpeed' ,
			);
		foreach($options as $option_key) {
			if (isset($this->options[$option_key]) && $this->options[$option_key] != $this->_options_default[$option_key]) {
				$retVal .= ",{$option_key}:{$this->options[$option_key]}";
			}
		}
		$options = array(
			'contentBgColor' ,
			'txtImage' ,
			'txtOf' ,
			'keyToClose' ,
			'keyToPrev' ,
			'keyToNext' ,
			);
		foreach($options as $option_key) {
			if (isset($this->options[$option_key]) && $this->options[$option_key] != $this->_options_default[$option_key]) {
				$retVal .= ",{$option_key}:'{$this->options[$option_key]}'";
			}
		}

		$retVal .= ($this->options['setLinkToTitle'] == 1 ? ',setLinkToTitle:true' : '');
		$retVal .= ($this->options['iconAdd'] != 1 ? ',iconAdd:false' : '');
		$retVal .= ($this->options['detailsEnabled'] != 1 ? ',detailsEnabled:false' : '');

		// Image
		$retVal .= ',Image:{';
		if (!(isset($this->options['imageEnabled']) && $this->options['imageEnabled']==1)) {
			$retVal .= 'enabled:false';
		} else {
			$retVal .= 'enabled:true';
			$retVal .= ($this->options['iconAdd'] == 1 ? ",icon:'{$this->options['iconPath']}icon-image.png'" : "");
			$retVal .= 
				',size:new Array(' .
				(isset($this->options['imageMaxWidth'])  && $this->options['imageMaxWidth']  != $this->_options_default['imageMaxWidth']  ? $this->options['imageMaxWidth']  : 0 ) . "," .
				(isset($this->options['imageMaxHeight']) && $this->options['imageMaxHeight'] != $this->_options_default['imageMaxHeight'] ? $this->options['imageMaxHeight'] : 0 ) .
				')';
		}
		$retVal .= '}';

		// Video
		$retVal .= ',Video:{';
		if (!(isset($this->options['videoEnabled']) && $this->options['videoEnabled']==1)) {
			$retVal .= 'enabled:false';
		} else {
			$retVal .= 'enabled:true';
			$retVal .= ($this->options['iconAdd'] == 1 ? ",icon:'{$this->options['iconPath']}icon-video.png'" : "");
		}
		$retVal .= '}';

		// Contents
		$retVal .= ',Contents:{';
		$retVal .= 'enabled:false';
/*
		if (!(isset($this->options['contentsEnabled']) && $this->options['contentsEnabled']==1)) {
			$retVal .= 'enabled:false';
		} else {
			$retVal .= 'enabled:true';
			$retVal .= ($this->options['iconAdd'] == 1 ? ",icon:'{$this->options['iconPath']}icon-contents.png'" : "");
			$retVal .= ',iframeEnabled:false';
		}
*/
		$retVal .= '}';

		// Video Sites
		foreach($this->_videosites as $option_key) {
			$retVal .= ','.$option_key.':{';
			if (!(isset($this->options[strtolower($option_key).'Enabled']) && $this->options[strtolower($option_key).'Enabled']==1)) {
				$retVal .= 'enabled:false';
			} else {
				$retVal .= 'enabled:true';
				$retVal .= ($this->options['iconAdd'] == 1 ? ",icon:'{$this->options['iconPath']}icon-".strtolower($option_key).".png'" : "");
				if ((isset($this->options[strtolower($option_key).'Width']) && isset($this->options[strtolower($option_key).'Height']))
				 && ($this->options[strtolower($option_key).'Width'] != $this->_options_default[strtolower($option_key).'Width'] || $this->options[strtolower($option_key).'Height'] != $this->_options_default[strtolower($option_key).'Height'])
				 ) {
					$retVal .= 
						',size:new Array(' .
						$this->options[strtolower($option_key).'Width'] . ',' .
						$this->options[strtolower($option_key).'Height'] .
						')';
				}
				$params = $this->options[strtolower($option_key).'Params'];
				if ($params != '' && $params != $this->_options_default[strtolower($option_key).'Params']) {
					$retVal .= ',param:{';
					$param_count = 0;
					foreach (explode('&', $params) as $param) {
						list($key, $val) = explode('=', $param);
						$retVal .= ($param_count++ > 0 ? ',' : '');
						$retVal .= "'{$key}':'{$val}'";
					}
					$retVal .= '}';
				}
		 	}
			$retVal .= '}';
		}
		$retVal = '{'.$retVal.'}';
		unset($options);

		return $retVal;
	}

	function _getJsOptions($force = false) {
		$out = (!$force ? wp_cache_get("LIGHTPOP_JS_OPTIONS") : false);
		if ($out === false) {
			$this->options['lightpop_option'] = $this->_getLightpopOption();
			$out = "var lightpop={options:{$this->options['lightpop_option']},start:function(){}};\n";
			wp_cache_set("LIGHTPOP_JS_OPTIONS", $out);
		}
		return $out;
	}

	function _getJsLighPopStart($force = false) {
		$out = (!$force ? wp_cache_get("LIGHTPOP_START_JS") : false);
		if ($out === false) {
			$elements = preg_split('/[\n\r]/', apply_filters('css_elements/wp-lightpop.php', $this->options['targetCSS']));

			$out  = "lightpop.start=function(){";
			if (trim($this->options['postClass']) != '') {
				$out .= "jQuery('.".trim($this->options['postClass'])."').each(function(){";
				foreach($elements as $value) {
					if (trim($value) != '') {
						$out .= "jQuery('".trim($value)."', jQuery(this)).lightpop(lightpop.options);";
					}
				}
				$out .= "});";
			} else {
				foreach($elements as $value) {
					if (trim($value) != '') {
						$out .= "jQuery('".trim($value)."').lightpop(lightpop.options);";
					}
				}
			}
			$out .= "};";
			$out .= "jQuery(lightpop.start);\n";

			unset($elements);
			wp_cache_set("LIGHTPOP_START_JS", $out);
		}
		return $out;
	}

	function addScripts() {
		if (!is_admin()) {
			$this->addjQuery();	// regist jQuery
			wp_enqueue_script('jquery.lightpop', $this->plugin_url.$this->lightpop_js, array('jquery'), $this->lightpop_ver);
		}
	}

	function dequeueScripts() {
		global $wp_scripts;
		$wp_scripts->dequeue('jquery.chrome');
	}

	function addHead() {
		$this->writeScript($this->_getJsOptions(), 'head');
	}

	function addFooter() {
		$this->writeScript($this->_getJsLighPopStart(), 'footer');
	}

	function addAdminMenu() {
		$this->addOptionPage(__('LightPop', $this->textdomain_name), array($this,'optionPage'));
		add_action('admin_print_scripts-'.$this->admin_hook['option'], array($this,'addAdminScripts'));
		add_action('admin_head-'.$this->admin_hook['option'], array($this,'addAdminHead'));
	}

	function pluginActionLinks($links, $file) {
		$this_plugin = plugin_basename(__FILE__);
		if ($file == $this_plugin) {
			$settings_link = '<a href="options-general.php?page=' . $this->plugin_file . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links
		}
		return $links;
	}

	function addAdminScripts() {
		$this->addjQuery();	// regist jQuery
	}

	function addAdminHead() {
		$out  = "<style type=\"text/css\">/*<![CDATA[ */\n";
		$out .= "table.optiontable td.noborder{margin-bottom:0;padding-bottom:0;border-bottom-width:0;border-bottom-style:none;}\n";
		$out .= "/* ]]>*/</style>\n";

		$out .= "<script type=\"text/javascript\">//<![CDATA[\n";
		$out .= "//wp-lightpop options page\n";
		$out .= "if (typeof addLoadEvent == 'undefined') addLoadEvent = function(func) {if (typeof jQuery != 'undefined') jQuery(document).ready(func); else if (typeof wpOnload!='function'){wpOnload=func;} else {var oldonload=wpOnload; wpOnload=function(){oldonload();func();}}};\n";
		$out .= "addLoadEvent(function(){\n";

		// Frame Type options
		$out .= "jQuery('select[name^=ap_contentFrameType]').unbind('change').change(function(){";
		$out .= "if (this.value==9) {";
		$out .= "jQuery('#lightpop-box-options').slideDown();";
		$out .= "} else if (jQuery('#lightpop-box-options').css('display')=='block') {";
		$out .= "jQuery('#lightpop-box-options').slideUp();";
		$out .= "}";
		$out .= "return false;";
		$out .= "});\n";

		// Show more options
		$out .= "jQuery('#show-more-options').unbind('click').click(function(){";
		$out .= "if (jQuery('#more-options').css('display') != 'block') {";
		$out .= "jQuery('#more-options').slideDown(function(){";
		$out .= "jQuery('#show-more-options').html('".__('More options', $this->textdomain_name)." &laquo;');";
		$out .= "});";
		$out .= "} else {";
		$out .= "jQuery('#more-options').slideUp(function(){";
		$out .= "jQuery('#show-more-options').html('".__('More options', $this->textdomain_name)." &raquo;');";
		$out .= "});";
		$out .= "}";
		$out .= "return false;";
		$out .= "});\n";

		// Show link type options
		$out .= "jQuery('#show-link-options').unbind('click').click(function(){";
		$out .= "if (jQuery('#link-options').css('display') != 'block') {";
		$out .= "jQuery('#link-options').slideDown(function(){";
		$out .= "jQuery('#show-link-options').html('".__('Options of each Link Type', $this->textdomain_name)." &laquo;');";
		$out .= "});";
		$out .= "} else {";
		$out .= "jQuery('#link-options').slideUp(function(){";
		$out .= "jQuery('#show-link-options').html('".__('Options of each Link Type', $this->textdomain_name)." &raquo;');";
		$out .= "});";
		$out .= "}";
		$out .= "return false;";
		$out .= "});\n";

		$out .= "});\n";
		$out .= "//]]></script>\n";

		echo $out;
	}

	function optionPage() {
		// options update
		if(isset($_POST['ap_options_update'])) {
			// check referer
			if ($this->wp25) check_admin_referer("update_options", "_wpnonce_update_options");

			// strip slashes array
			$_POST = $this->stripArray($_POST);

			// get options
			$this->options['targetCSS'] = $_POST['ap_targetCSS'];
			$this->options['postClass'] = $_POST['ap_postClass'];
			$this->options['overlayBgColor'] = $_POST['ap_overlayBgColor'];
			$this->options['overlayOpacity'] = $_POST['ap_overlayOpacity'];
			$this->options['contentFrameType'] = $_POST['ap_contentFrameType'];
			$this->options['contentBorder'] = $_POST['ap_contentBorder'];
			$this->options['contentBgColor'] = $_POST['ap_contentBgColor'];
			$this->options['imageLoading'] = $_POST['ap_imageLoading'];
			$this->options['imageBtnPrev'] = $_POST['ap_imageBtnPrev'];
			$this->options['imageBtnNext'] = $_POST['ap_imageBtnNext'];
			$this->options['imageBtnClose'] = $_POST['ap_imageBtnClose'];
			$this->options['imageBlank'] = $_POST['ap_imageBlank'];
			$this->options['boxBorderSize'] = $_POST['ap_boxBorderSize'];
			$this->options['imageBox'] = $_POST['ap_imageBox'];
			$this->options['imageBorderL'] = $_POST['ap_imageBorderL'];
			$this->options['imageBorderR'] = $_POST['ap_imageBorderR'];
			$this->options['containerBorderSize'] = $_POST['ap_containerBorderSize'];
			$this->options['containerResizeSpeed'] = $_POST['ap_containerResizeSpeed'];
			$this->options['txtImage'] = $_POST['ap_txtImage'];
			$this->options['txtOf'] = $_POST['ap_txtOf'];
			$this->options['keyToClose'] = $_POST['ap_keyToClose'];
			$this->options['keyToPrev'] = $_POST['ap_keyToPrev'];
			$this->options['keyToNext'] = $_POST['ap_keyToNext'];
			$this->options['flvplayer'] = $_POST['ap_flvplayer'];
			$this->options['iconAdd'] = (isset($_POST['ap_iconAdd']) && $_POST['ap_iconAdd'] == '1' ? 1 : 0);
			$this->options['iconPath'] = $_POST['ap_iconPath'];
			$this->options['setLinkToTitle'] = isset($_POST['ap_setLinkToTitle']) ? 1 : 0;

			$this->options['imageEnabled'] = (isset($_POST['ap_imageEnabled']) && $_POST['ap_imageEnabled'] == '1' ? 1 : 0);
			$this->options['imageMaxWidth'] = $_POST['ap_imageMaxWidth'];
			$this->options['imageMaxHeight'] = $_POST['ap_imageMaxHeight'];

			$this->options['videoEnabled'] = 1;
			$this->options['contentsEnabled'] = 1;

			foreach($this->_videosites as $option_key) {
				if (isset($_POST['ap_'.strtolower($option_key).'Enabled'])) {
					$this->options[strtolower($option_key).'Enabled'] = 1;
					if (isset($_POST['ap_'.strtolower($option_key).'Width']))  $this->options[strtolower($option_key).'Width']  = $_POST['ap_'.strtolower($option_key).'Width'];
					if (isset($_POST['ap_'.strtolower($option_key).'Height'])) $this->options[strtolower($option_key).'Height'] = $_POST['ap_'.strtolower($option_key).'Height'];
					if (isset($_POST['ap_'.strtolower($option_key).'Params'])) $this->options[strtolower($option_key).'Params'] = $_POST['ap_'.strtolower($option_key).'Params'];
				} else {
					$this->options[strtolower($option_key).'Enabled'] = 0;
				}
			}

			$_POST = '';

			// Done!
			$this->_getJsLighPopStart(true);
			$this->_getJsOptions(true);
			$this->updateOptions();
			$this->note .= __('<strong>Done!</strong>', $this->textdomain_name);

		// options delete
		} elseif(isset($_POST['ap_options_delete'])) {
			// check referer
			if ($this->wp25) check_admin_referer("delete_options", "_wpnonce_delete_options");

			// Done!
			$this->deleteOptions();
			$this->options = $this->_initOptions(array());
			$this->note .= __('<strong>Done!</strong>', $this->textdomain_name);
			$this->error++;
		}

		$out  = '';

		// Add Options
		$out .= "<div class=\"wrap\">\n";
		$out .= "<h2>".__('WP-LightPop Options', $this->textdomain_name)."</h2><br />\n";
		$out .= "<form method=\"post\" id=\"update_options\" action=\"".$_SERVER['REQUEST_URI']."\">\n";
		if ($this->wp25) $out .= $this->makeNonceField("update_options", "_wpnonce_update_options", true, false);

		// Add Update Button
		$out .= "<input type=\"submit\" name=\"ap_options_update\" class=\"button-primary\" value=\"".__('Update Options', $this->textdomain_name)." &raquo;\" class=\"button\" style=\"margin-bottom:1em;\" />";
		$out .= "<br clear=\"all\" />";

		$out .= "<table class=\"optiontable form-table\" style=\"margin-top:0;\"><tbody>\n";

		// Target CSS
		$out .= "<tr>";
		$out .= "<td><strong>".__('Target Element', $this->textdomain_name)."</strong></td>";
		$out .= "<td><textarea name=\"ap_targetCSS\" cols=\"100\" rows=\"5\">".$this->options['targetCSS']."</textarea></td>";
		$out .= "</tr>\n";

		// 
		$out .= "<tr>";
		$out .= "<td><strong>".__('Post Class', $this->textdomain_name)."</strong></td>";
		$out .= "<td>";
		$out .= "<input type=\"text\" name=\"ap_postClass\" id=\"ap_postClass\" size=\"20\" value=\"".$this->options['postClass']."\" />";
		$out .= "&nbsp;&nbsp;<strong>".__('Each class makes it to the group.', $this->textdomain_name)."</strong>";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Choose Frame type
		$out .= "<tr>";
		$out .= "<td><strong>".__('Frame type', $this->textdomain_name)."</strong></td>";
		$out .= "<td>";
		$out .= "<select name=\"ap_contentFrameType\">";
		foreach($this->_frame_type as $key => $value) {
			$out .= "<option value=\"".$key."\"".($this->options['contentFrameType'] == $key ? " selected=\"selected\"" : "").">".__($value[0], $this->textdomain_name)."</option>";
		}
		$out .= "</select>";
		$out .= "<div id=\"lightpop-box-options\"".($this->options['contentFrameType'] != 9 ? " style=\"display:none\"" : "").">";
		$out .= "<table>";
		$out .= "<tr>";
		$out .= "<td class=\"noborder\"><strong>".__('Border Size', $this->textdomain_name)."</strong></td>";
		$out .= "<td class=\"noborder\"><input type=\"text\" name=\"ap_boxBorderSize\" id=\"ap_boxBorderSize\" size=\"10\" value=\"".$this->options['boxBorderSize']."\" /></td>";
		$out .= "</tr>";
		$out .= "<tr>";
		$out .= "<td class=\"noborder\"><strong>".__('Box Image path', $this->textdomain_name)."</strong></td>";
		$out .= "<td class=\"noborder\"><input type=\"text\" name=\"ap_imageBox\" id=\"ap_imageBox\" size=\"75\" value=\"".$this->options['imageBox']."\" /></td>";
		$out .= "</tr>";
		$out .= "<tr>";
		$out .= "<td class=\"noborder\"><strong>".__('Border left Image path', $this->textdomain_name)."</strong></td>";
		$out .= "<td class=\"noborder\"><input type=\"text\" name=\"ap_imageBorderL\" id=\"ap_imageBorderL\" size=\"75\" value=\"".$this->options['imageBorderL']."\" /></td>";
		$out .= "</tr>";
		$out .= "<tr>";
		$out .= "<td class=\"noborder\"><strong>".__('Border right Image path', $this->textdomain_name)."</strong></td>";
		$out .= "<td class=\"noborder\"><input type=\"text\" name=\"ap_imageBorderR\" id=\"ap_imageBorderR\" size=\"75\" value=\"".$this->options['imageBorderR']."\" /></td>";
		$out .= "</tr>";
		$out .= "</table></div>";
		$out .= "</td>";
		$out .= "</tr>\n";

		// icon add
		$out .= "<tr>";
		$out .= "<td><strong>".__('Link icon', $this->textdomain_name)."</strong></td>";
		$out .= "<td><input type=\"checkbox\" name=\"ap_iconAdd\" value=\"1\" style=\"margin-right:0.5em;\" ".($this->options['iconAdd']==1?" checked=\"true\"":"")." />".__('Before link', $this->textdomain_name)."</td>";
		$out .= "</tr>\n";

		// set link to title
		$out .= "<tr>";
		$out .= "<td><strong>".__('Set link to title', $this->textdomain_name)."</strong></td>";
		$out .= "<td><input type=\"checkbox\" name=\"ap_setLinkToTitle\" value=\"1\" style=\"margin-right:0.5em;\" ".($this->options['setLinkToTitle']==1?" checked=\"true\"":"")." /></td>";
		$out .= "</tr>\n";

		$out .= "</tbody></table>\n";

		// more options
		$out .= "<p style=\"margin:.5em;clear:both;\"><a href=\"#\" id=\"show-more-options\">".__('More options', $this->textdomain_name)." &raquo;</a></p>\n";

		$out .= "<div id=\"more-options\" style=\"display:none\">\n";
		$out .= "<table class=\"optiontable form-table\" style=\"margin-top:0;\"><tbody>\n";

		// overlay
		$out .= "<tr>";
		$out .= "<td><strong>".__('Overlay', $this->textdomain_name)."</strong></td>";
		$out .= "<td>";
		$out .= "<strong>".__('Background color', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_overlayBgColor\" id=\"ap_overlayBgColor\" size=\"10\" value=\"".$this->options['overlayBgColor']."\" />";
		$out .= "</td>";
		$out .= "<td colspan=2>";
		$out .= "<strong>".__('Opacity', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_overlayOpacity\" size=\"5\" value=\"".$this->options['overlayOpacity']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// content border
		$out .= "<tr>";
		$out .= "<td><strong>".__('Content', $this->textdomain_name)."</strong></td>";
		$out .= "<td colspan=3>";
		$out .= "<strong>".__('Border CSS', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_contentBorder\" size=\"75\" value=\"".$this->options['contentBorder']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Container
		$out .= "<tr>";
		$out .= "<td><strong>".__('Container', $this->textdomain_name)."</strong></td>";
		$out .= "<td>";
		$out .= "<strong>".__('Border size', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_containerBorderSize\" size=\"5\" value=\"".$this->options['containerBorderSize']."\" />";
		$out .= "</td>";
		$out .= "<td>";
		$out .= "<strong>".__('Resize speed', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_containerResizeSpeed\" size=\"5\" value=\"".$this->options['containerResizeSpeed']."\" />";
		$out .= "</td>";
		$out .= "<td>";
		$out .= "<strong>".__('Background color', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_contentBgColor\" size=\"10\" value=\"".$this->options['contentBgColor']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Prev button image
		$out .= "<tr>";
		$out .= "<td><strong>".__('Prev button', $this->textdomain_name)."</strong></td>";
		$out .= "<td colspan=3>";
		$out .= "<strong>".__('Image path', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_imageBtnPrev\" size=\"75\" value=\"".$this->options['imageBtnPrev']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Next button image
		$out .= "<tr>";
		$out .= "<td><strong>".__('Next button', $this->textdomain_name)."</strong></td>";
		$out .= "<td colspan=3>";
		$out .= "<strong>".__('Image path', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_imageBtnNext\" size=\"75\" value=\"".$this->options['imageBtnNext']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Close button image
		$out .= "<tr>";
		$out .= "<td><strong>".__('Close button', $this->textdomain_name)."</strong></td>";
		$out .= "<td colspan=3>";
		$out .= "<strong>".__('Image path', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_imageBtnClose\" size=\"75\" value=\"".$this->options['imageBtnClose']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Loading image
		$out .= "<tr>";
		$out .= "<td><strong>".__('Loading', $this->textdomain_name)."</strong></td>";
		$out .= "<td colspan=3>";
		$out .= "<strong>".__('Image path', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_imageLoading\" size=\"75\" value=\"".$this->options['imageLoading']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Blank image
		$out .= "<tr>";
		$out .= "<td><strong>".__('Blank', $this->textdomain_name)."</strong></td>";
		$out .= "<td colspan=3>";
		$out .= "<strong>".__('Image path', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_imageBlank\" size=\"75\" value=\"".$this->options['imageBlank']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Navigation text
		$out .= "<tr>";
		$out .= "<td><strong>".__('Navigation', $this->textdomain_name)."</strong></td>";
		$out .= "<td colspan=3>";
		$out .= "<strong>".__('text of', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_txtOf\" size=\"5\" value=\"".$this->options['txtOf']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Key navigation
		$out .= "<tr>";
		$out .= "<td><strong>".__('Key bind', $this->textdomain_name)."</strong></td>";
		$out .= "<td>";
		$out .= "<strong>".__('to Prev', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_keyToPrev\" size=\"5\" value=\"".$this->options['keyToPrev']."\" />";
		$out .= "</td>";
		$out .= "<td>";
		$out .= "<strong>".__('to Next', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_keyToNext\" size=\"5\" value=\"".$this->options['keyToNext']."\" />";
		$out .= "</td>";
		$out .= "<td>";
		$out .= "<strong>".__('to Close', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_keyToClose\" size=\"5\" value=\"".$this->options['keyToClose']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// flvplayer
		$out .= "<tr>";
		$out .= "<td><strong>".__('FLV player path', $this->textdomain_name)."</strong></td>";
		$out .= "<td colspan=3><input type=\"text\" name=\"ap_flvplayer\" size=\"75\" value=\"".$this->options['flvplayer']."\" /></td>";
		$out .= "</tr>\n";

		$out .= "</tbody></table></div>\n";

		// link type options
		$out .= "<p style=\"margin:.5em;clear:both;\"><a href=\"#\" id=\"show-link-options\">".__('Options of each Link Type', $this->textdomain_name)." &raquo;</a></p>\n";

		$out .= "<div id=\"link-options\" style=\"display:none\">\n";
		$out .= "<table class=\"optiontable form-table\" style=\"margin-top:0;\"><tbody>\n";

		// Image - Max Width & Max Height
		$out .= "<tr>";
		$out .= "<td><strong>".__('Image', $this->textdomain_name)."</strong></td>";
		$out .= "<td><input type=\"checkbox\" name=\"ap_imageEnabled\" value=\"1\" style=\"margin-right:0.5em;\" ".($this->options['imageEnabled']==1?" checked=\"true\"":"")." /><strong>".__('Enable', $this->textdomain_name)."</strong></td>";
		$out .= "<td>";
		$out .= "<strong>".__('Max Width', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_imageMaxWidth\" size=\"5\" value=\"".$this->options['imageMaxWidth']."\" />";
		$out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$out .= "<strong>".__('Max Height', $this->textdomain_name)."</strong>";
		$out .= "&nbsp;<input type=\"text\" name=\"ap_imageMaxHeight\" size=\"5\" value=\"".$this->options['imageMaxHeight']."\" />";
		$out .= "</td>";
		$out .= "</tr>\n";

		// Video Sites
		foreach($this->_videosites as $option_key) {
			// Width & Height
			$class_name = ($option_key != 'nicovideo' ? ' class="noborder"' : '');
			$out .= "<tr>";
			$out .= "<td{$class_name}><strong>".__($option_key, $this->textdomain_name)."</strong></td>";
			$out .= "<td{$class_name}><input type=\"checkbox\" name=\"ap_".strtolower($option_key)."Enabled\" value=\"1\" style=\"margin-right:0.5em;\" ".($this->options[strtolower($option_key).'Enabled']==1?" checked=\"true\"":"")." /><strong>".__('Enable', $this->textdomain_name)."</strong></td>";
			$out .= "<td{$class_name}>";
			$out .= "<strong>".__('Width', $this->textdomain_name)."</strong>";
			$out .= "&nbsp;<input type=\"text\" name=\"ap_".strtolower($option_key)."Width\" size=\"5\" value=\"".$this->options[strtolower($option_key).'Width']."\" />";
			$out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$out .= "<strong>".__('Height', $this->textdomain_name)."</strong>";
			$out .= "&nbsp;<input type=\"text\" name=\"ap_".strtolower($option_key)."Height\" size=\"5\" value=\"".$this->options[strtolower($option_key).'Height']."\" />";
			$out .= "</td>";
			$out .= "</tr>\n";

			if ($option_key != 'nicovideo') {
				// params
				$out .= "<tr>";
				$out .= "<td></td>";
				$out .= "<td colspan=2>";
				$out .= "<strong>".__('Other Params', $this->textdomain_name)."</strong>";
				$out .= "&nbsp;<input type=\"text\" name=\"ap_".strtolower($option_key)."Params\" size=\"75\" value=\"".$this->options[strtolower($option_key).'Params']."\" />";
				$out .= "</td>";
				$out .= "</tr>\n";
			}
		}

		$out .= "</tbody></table></div>\n";

		// hidden options
		$out .= "<input type=\"hidden\" name=\"ap_iconPath\" value=\"".$this->options['iconPath']."\" />";
		$out .= "<input type=\"hidden\" name=\"ap_txtImage\" value=\"".$this->options['txtImage']."\" />";

		// Add Update Button
		$out .= "<br clear=\"all\" />";
		$out .= "<input type=\"submit\" name=\"ap_options_update\" class=\"button-primary\" value=\"".__('Update Options', $this->textdomain_name)." &raquo;\" class=\"button\" />";
		$out .= "</form></div>\n";

		// Add Options
		$out .= "<div class=\"wrap\" style=\"margin-top:2em;\">\n";
		$out .= "<h2>".__('Uninstall', $this->textdomain_name)."</h2><br />\n";
		$out .= "<form method=\"post\" id=\"delete_options\" action=\"".$this->admin_action."\">\n";
		if ($this->wp25) $out .= $this->makeNonceField("delete_options", "_wpnonce_delete_options", true, false);

		// Delete Button
		$out .= "<input type=\"submit\" name=\"ap_options_delete\" class=\"button-primary\" value=\"".__('Delete Options', $this->textdomain_name)." &raquo;\" class=\"button\" />";
		$out .= "</form></div>\n";

		// Output
		echo (!empty($this->note) ? "<div id=\"message\" class=\"updated fade\"><p>{$this->note}</p></div>\n\n" : '');	// Note snuff
		echo ($this->error > 0 ? '' : $out."\n");	// If any error, dont display body
	}

	function add_image_send_to_editor($html, $id=0, $caption='', $title='', $align='', $url='', $size='', $alt='') {
		$id = ( 0 < (int) $id ) ? 'attachment_' . $id : '';

		if ($alt == '' && preg_match("/ alt=[\"'][^\"']*[\"'] ?/i", $html)) $alt = preg_replace("/^.* alt=[\"']([^\"']*)[\"'] ?.*$/i", "$1", $html);
		if ($title == '' && preg_match("/ title=[\"'][^\"']*[\"'] ?/i", $html)) $title = preg_replace("/^.* title=[\"']([^\"']*)[\"'] ?.*$/i", "$1", $html);
		if ($alt == ''   && $title != '') $alt   = $title;
		if ($title == '' && $alt != '')   $title = $alt;

		if (preg_match("/^<a [^>]*>/i", $html)) {
			$a_tag      = preg_replace("/^<a ([^>]*)>.*$/i", "$1", $html);
			$a_params   = array();

			if (preg_match_all("/([^\s\=]*)\=[\"']([^\"']*)[\"']/i", $a_tag, $matches, PREG_SET_ORDER)) {
				$a_tag = '';
				for ($i=0; $i<count($matches); $i++) {
					$a_params[strtolower($matches[$i][1])] = $matches[$i][2];
				}

				if (!isset($a_params['title']) || $a_params['title'] == '')
					$a_params['title'] = $title;

				$a_tag .= '<a';
				foreach ($a_params as $key => $val) {
					if (preg_match('/(href|title|rel|class)/i', $key))
						$a_tag .= sprintf(" %s=\"%s\"", $key, $val);
				}
				$a_tag .= '>';
			}
			if ($a_tag != '')
				$html = preg_replace("/<a [^>]*>/i", $a_tag, $html);

			unset ($a_params);
			unset ($matches);
		}

	 return $html;
	}
}//class

new LightPop();
?>