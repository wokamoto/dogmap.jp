<?php
/*
Plugin Name: VideoPop Plus
Plugin URI: http://wppluginsj.sourceforge.jp/videopop-plus/
Description: Add links to your posts and pages that open Videos in a popup window. Upload & administration via the WP admin panel
Version: 0.8.3
Author: wokamoto
Author URI: http://dogmap.jp/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2007-2010 wokamoto (email : wokamoto1973@gmail.com)

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

Based:
  VideoPop v.1.3.1(http://www.lynk.de/wordpress/videopop/)
  thanks to Marcus Grellert.

Includes:
 jQuery 1.3.2 - New Wave Javascript
  Copyright (c) 2009 John Resig (jquery.com)
  Dual licensed under the MIT and GPL licenses.

 jQuery blockUI plugin (http://malsup.com/jquery/block/)
  Version 2.08 (06/11/2008)
  Copyright (c) 2007-2008 M. Alsup
  Dual licensed under the MIT and GPL licenses:

 FLV Media Player 3.16
  The FLV Media Player is licensed under a <a href="http://creativecommons.org/licenses/by-nc-sa/2.0/">Creative Commons License</a>.
  It allows you to use, modify and redistribute the script for free for noncommercial purposes.
  For corporate use, <a href="http://www.jeroenwijering.com/?page=order" title="Order commercial licenses">please apply for a 20 euros commercial license</a>!
*/
@ini_set("max_execution_time","5000");

if (!defined('WP_CONTENT_DIR'))
	define('WP_CONTENT_DIR', ABSPATH.'wp-content');

if (!class_exists('wokController') || !class_exists('wokScriptManager'))
	require(dirname(__FILE__).'/includes/common-controller.php');

// function getVideoPopObject()
if (!function_exists('getVideoPopObject'))
	require("videopop_link.php");

// =============
// CLASS

class VideoPopPlus extends wokController {
	var $plugin_name  = 'videopop_plus';
	var $plugin_ver   = '0.8.3';

	var $lightpop_js  = 'js/jquery.lightpop-0.8.3.min.js';
	var $lightpop_ver = '0.8.3';

	var $blockUI      = 'js/jquery.blockUI.min.js';
	var $blockUI_ver  = '2.20';

	var $videopop_url, $data_dir, $data_url, $data_txt, $vids, $flvplayer;
	var $admin_manage, $admin_manage_parent;

	var $videosizes;
	var $_wpLightpop_enable = false;

	/*
	* Constructor
	*/
	function VideoPopPlus() {
		$this->__construct();
	}
	function __construct() {
		$this->init(__FILE__);

		$this->videopop_url = $this->plugin_url.'videopop.php';
		$this->data_dir     = (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH.'wp-content') . '/videopop/';
		$this->data_url     = trailingslashit(get_bloginfo('wpurl')) . (defined('WP_CONTENT_DIR') ? basename(WP_CONTENT_DIR) : 'wp-content') . '/videopop/';
		$this->data_txt     = $this->data_dir . 'videopopdata.txt';

		$plugin_path = trailingslashit(ABSPATH . $this->plugins_dir . $this->plugin_dir);
		if (file_exists($plugin_path . 'swf/mediaplayer.swf'))
			$this->flvplayer = 'swf/mediaplayer.swf';
		elseif (file_exists($plugin_path . 'swf/player.swf'))
			$this->flvplayer = 'swf/player.swf';
		elseif (file_exists($plugin_path . 'swf/flvplayer.swf'))
			$this->flvplayer = 'swf/flvplayer.swf';
		elseif (file_exists($plugin_path . 'mediaplayer.swf'))
			$this->flvplayer = 'mediaplayer.swf';
		elseif (file_exists($plugin_path . 'player.swf'))
			$this->flvplayer = 'player.swf';
		else
			$this->flvplayer = 'flvplayer.swf';

		$this->options = $this->_initOptions($this->getOptions());

		$this->admin_manage_parent = ($this->wp27 ? 'upload.php' : 'edit.php');
		$this->admin_option = basename(__FILE__);
		$this->admin_action = trailingslashit(get_bloginfo('wpurl')).'wp-admin/'.($this->wp27 ? 'options-general.php' : 'admin.php').'?page='.$this->admin_option;
		$this->admin_manage = trailingslashit(get_bloginfo('wpurl')).'wp-admin/'.$this->admin_manage_parent.'?page='.$this->admin_option;

		$this->videosizes = array(
			 160 => array('width' => 160, 'height' => 120, 'note' => '')
			,320 => array('width' => 320, 'height' => 240, 'note' => '')
			,640 => array('width' => 640, 'height' => 480, 'note' => '')
			,719 => array('width' => 720, 'height' => 252, 'note' => '')
			,450 => array('width' => 450, 'height' => 120, 'note' => ' [16:9 - 1.78:1]')
			,480 => array('width' => 480, 'height' => 260, 'note' => '')
			,720 => array('width' => 720, 'height' => 405, 'note' => ' [16:9 - 1.78:1]')
			,721 => array('width' => 720, 'height' => 390, 'note' => ' [16:9 - 1.85:1]')
		);

		// wp-lightpop active?
		$this->_wpLightpop_enable = $this->isActive('wp-lightpop.php');

		if (is_admin()) {
			if (preg_match('/(post|page)(\-new)?\.php/i', $_SERVER['PHP_SELF']))
				add_action('admin_print_scripts', array(&$this,'addAdminPrintScripts'));
			add_action('admin_menu', array(&$this,'addAdminMenu'));
			add_filter('plugin_action_links', array(&$this, 'pluginActionLinks'), 10, 2 );
		} else {
			add_action('wp_print_scripts', array(&$this, 'addLightPopScripts'));
			add_action('wp_head',array(&$this, 'addWpHead'));
			add_action('wp_footer',array(&$this, 'addWpFooter'));
			add_filter('css_elements/wp-lightpop.php',array(&$this, 'addLightPopElements'));

			if (function_exists('add_shortcode'))
				add_shortcode('videopop', array(&$this, 'shortcodeHandler'));
			add_filter('the_content', array(&$this, 'addContentFilter'));
		}
	}

	/*
	* Init Options
	*/
	function _initOptions($options = '') {
		if (!is_array($options)) $options = array();

		//----- Options default Value -----
		// User Level Permission -- Subscriber = 0,Contributor = 1,Author = 2,Editor= 7,Administrator = 9
		// Set the user level the user needs to have (at least) to manage/upload/delete videos
		if (!isset($options['user_lvl'])) $options['user_lvl'] = '7';
		if (preg_match('/^([0-9]|10)$/', $options['user_lvl'])) $options['user_lvl'] = 'level_' . $options['user_lvl'];

		// Show Option
		// "lightpop" or "popup"
		if (!isset($options['show_option'])) $options['show_option'] = 'lightpop';

		// class name
		if (!isset($options['class_name'])) $options['class_name'] = 'video';
		if (!isset($options['class_name_with_type'])) $options['class_name_with_type'] = '0';

		// Insert ShortCode ?
		if (!isset($options['ins_shortcode'])) $options['ins_shortcode'] = '1';

		// list Max
		if (!isset($options['post_list_max'])) $options['post_list_max'] = 5;
		if (!isset($options['manage_list_max'])) $options['manage_list_max'] = 10;

		return $options;
	}

	/*
	* Remove dir and all its contents
	*/
	function _removeDir($dir) {
		if(file_exists($dir)) {
			if($objs = glob($dir."/*"))
				foreach($objs as $obj) {
					is_dir($obj) ? rmdir($obj) : unlink($obj);
				}
			rmdir($dir);
		}
	}

	/*
	* Check/Create folder/files
	*/
	function _checkDir() {
		if(file_exists($this->data_txt)) {
			return true;
		} else {
			if(!is_writable((defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH.'wp-content') . '/')) {
				$this->note .= __("To use <strong>VideoPop+</strong>, the directory <strong>&quot;wp-content&quot;</strong> within your WordPress installation on your webserver must be writeable.<br /><br />To change permission, use a FTP program to access your websever and right-click on the directory <strong>&quot;wp-content&quot;</strong>.<br /> Tick all boxes that say &quot;Write&quot; or set the permission to <strong>777</strong>.", $this->textdomain_name);
				$this->error++;
				return false;
			}

			// Create directory
			if(!file_exists($this->data_dir)) {
				if(mkdir($this->data_dir, 0777)!=true) {
					$this->note .= __("The VideoPop+ directory couldn't be created with &quot;mkdir()&quot; on your server. The function could be disabled by your webhost or another restriction is in place. Ask your host.", $this->textdomain_name);
					$this->error++;
					return false;
				}
			}

			// Write default file
			$handle = fopen($this->data_txt, 'w');
			if($handle==false) {
				$this->note .= __("The VideoPop+ files couldn't be created with &quot;fopen()&quot; on your server. The function could be disabled by your webhost or another restriction is in place. Ask your host.", $this->textdomain_name);
				$this->error++;
				return false;
			} else {
				fwrite($handle,'N;');
				fclose($handle);
				return true;
			}
		}//else
	}//func

	/*
	* Open file and fill array with unserialized data
	*/
	function _loadDataText() {
		return unserialize(file_get_contents($this->data_txt));
	}

	/*
	* Save ser. array
	*/
	function _saveDataText($vid_array) {
		$handle = fopen($this->data_txt, 'w');
		fwrite($handle, serialize($vid_array));
		fclose($handle);
	}

	/**
	* Validates form first input
	*/
	function _valInput() {
		switch(TRUE) {
			case (empty($_POST['lynkvp_name'])):
				$this->note .= __('Please enter a Name.', $this->textdomain_name);
				return FALSE; break;
			case (empty($_FILES['lynkvp_file']['size']) && empty($_POST['lynkvp_url'])):
				$this->note .= __('Please choose a Video or enter an URL.', $this->textdomain_name);
				return FALSE; break;
			case (empty($_POST['lynkvp_type'])):
				$this->note .= __('Please choose a Video Type.', $this->textdomain_name);
				return FALSE; break;
			case (empty($_POST['lynkvp_size']) || !isset($this->videosizes[$_POST['lynkvp_size']])):
				if (empty($_POST['lynkvp_size']) || $_POST['lynkvp_size'] == 0) {
					$this->note .= __('Please select a Size.', $this->textdomain_name);
					return FALSE;
				} elseif (preg_match('/[^\d]/', $_POST['lynkvp_width'])) {
					$this->note .= __('Please enter a Width.', $this->textdomain_name);
					return FALSE;
				} elseif(preg_match('/[^\d]/', $_POST['lynkvp_height'])) {
					$this->note .= __('Please enter a Height.', $this->textdomain_name);
					return FALSE; break;
				} else {
					return TRUE;
				}
				break;
			default:
				return TRUE; break;
		}
	}

	function _valInput2() {
		switch(TRUE) {
			case (empty($_POST['lynkvp_name'])):
				$this->note .= __('Please edit again and enter a name.', $this->textdomain_name);
				return FALSE; break;
			case (isset($_POST['lynkvp_type']) && empty($_POST['lynkvp_type'])):
				$this->note .= __('Please edit again and select a video type.', $this->textdomain_name);
				return FALSE; break;
			case (isset($_POST['lynkvp_url']) && empty($_POST['lynkvp_url']) ):
				$this->note .= __('Please edit again and enter an URL.', $this->textdomain_name);
				return FALSE; break;
			case (empty($_POST['lynkvp_size']) || !isset($this->videosizes[$_POST['lynkvp_size']])):
				if (empty($_POST['lynkvp_size']) || $_POST['lynkvp_size'] == 0) {
					$this->note .= __('Please edit again and select a Size.', $this->textdomain_name);
					return FALSE;
				} elseif (preg_match('/[^\d]/', $_POST['lynkvp_width'])) {
					$this->note .= __('Please edit again and enter a Width.', $this->textdomain_name);
					return FALSE;
				} elseif(preg_match('/[^\d]/', $_POST['lynkvp_height'])) {
					$this->note .= __('Please edit again and enter a Height.', $this->textdomain_name);
					return FALSE; break;
				} else {
					return TRUE;
				}
				break;
			default:
				return TRUE; break;
		}
	}

	/**
	* returns selected if value in POST array
	*/
	function _setOptionSelected($option,$value) {
		return ($option == $value ? ' selected="selected"' : '');
	}

	/**
	* returns selected if value in POST array
	*/
	function _getTypeSelect($options="") {
		$retval  = "<tr>\n";
		$retval .= "<td>".__('Video type', $this->textdomain_name).":</td>\n";
		$retval .= "<td>";
		$retval .= "<select name=\"lynkvp_type\" onchange=\"javascript:";
		$retval .= "document.getElementById('Thumbnail_title').style.display=(this.value=='flv'?'block':'none');";
		$retval .= "document.getElementById('Thumbnail_image').style.display=(this.value=='flv'?'block':'none');";
		$retval .= "\">";
		$retval .= " <option value=\"0\"".$this->_setOptionSelected($_POST['lynkvp_type'],'0').">".__('Select video type', $this->textdomain_name)."</option>";
		$retval .= " <option value=\"asf\"".$this->_setOptionSelected($_POST['lynkvp_type'],'asf').">.asf</option>";
		$retval .= " <option value=\"avi\"".$this->_setOptionSelected($_POST['lynkvp_type'],'avi').">.avi</option>";
		$retval .= " <option value=\"mp4\"".$this->_setOptionSelected($_POST['lynkvp_type'],'mp4').">.mp4</option>";
		$retval .= " <option value=\"mpg\"".$this->_setOptionSelected($_POST['lynkvp_type'],'mpg').">.mpg</option>";
		$retval .= " <option value=\"mov\"".$this->_setOptionSelected($_POST['lynkvp_type'],'mov').">.mov</option>";
		$retval .= " <option value=\"rm\"" .$this->_setOptionSelected($_POST['lynkvp_type'],'rm'). ">.rm</option>";
		$retval .= " <option value=\"swf\"".$this->_setOptionSelected($_POST['lynkvp_type'],'swf').">.swf</option>";
		$retval .= (file_exists(dirname(__FILE__).'/'.$this->flvplayer) ? " <option value=\"flv\"".$this->_setOptionSelected($_POST['lynkvp_type'],'flv').">.flv</option>" : '');
		$retval .= " <option value=\"wmv\"".$this->_setOptionSelected($_POST['lynkvp_type'],'wmv').">.wmv</option>";
		$retval .= " <option value=\"3gp\"".$this->_setOptionSelected($_POST['lynkvp_type'],'3gp').">.3gp</option>";
		$retval .= " </select>";
		$retval .= "</td>\n";
		$retval .= "</tr>\n";

		$retval .= "<tr>\n";
		$retval .= "<td><span id=\"Thumbnail_title\" style=\"display:".($_POST['lynkvp_type']!='flv'?'none':'block')."\">".__('Thumbnail', $this->textdomain_name).":</span></td>";
		$retval .= "<td><span id=\"Thumbnail_image\" style=\"display:".($_POST['lynkvp_type']!='flv'?'none':'block')."\"><input type=\"file\" name=\"lynkvp_image_file\" />&nbsp;&nbsp;&nbsp;\n";
		$retval .= __('<strong>or</strong>', $this->textdomain_name)." &nbsp;&nbsp;".__('URL', $this->textdomain_name).":&nbsp;&nbsp;http://<input type=\"text\" name=\"lynkvp_image_url\" style=\"width:400px;\" value=\"".$_POST['lynkvp_image']."\" /></span></td>\n";
		$retval .= "</tr>\n";

		return $retval;
	}

	/**
	* returns Video Link Tag
	*/
	function _getLinkTag($vid, $title = "", $inline = false, $quote = '"') {
		$link_format = '';
		switch($this->options['show_option']) {
		case "lightpop":
			$link_format = '<a href='.$quote.'%1$s?%3$s'.$quote.' title='.$quote.'%2$s'.$quote.'%5$s>%4$s</a>';
			break;
		case "modalbox":
		case "popup":
			$link_format = '<a href='.$quote.'%1$s'.$quote.' title='.$quote.'%2$s'.$quote.' onclick='.$quote.'javascript:VideoPop(%7$d,\'%6$d\');return false;'.$quote.'%5$s>%4$s</a>';
			break;
		case "inline":
			$inline = true;
			break;
		default:
			$link_format = '<a href='.$quote.'%1$s'.$quote.' title='.$quote.'%2$s'.$quote.'%5$s>%4$s</a>';
			break;
		}

		// Open file and fill array with unserialized data
		if (!is_array($this->vids)) {$this->vids = $this->_loadDataText();}

		$retval = "";

		if (isset($this->vids[$vid])) {
			$vtitle = (isset($this->vids[$vid]['lynkvp_name']) ? $this->vids[$vid]['lynkvp_name'] : $title);
			$vsize  = (isset($this->vids[$vid]['lynkvp_size']) ? $this->vids[$vid]['lynkvp_size'] : 320);
			if (isset($this->videosizes[$vsize])) {
				$vwidth = $this->videosizes[$vsize]['width'];
				$vheight= $this->videosizes[$vsize]['height'];
			} else {
				$vwidth = (isset($this->vids[$vid]['lynkvp_width'])  ? $this->vids[$vid]['lynkvp_width']  : 320);
				$vheight= (isset($this->vids[$vid]['lynkvp_height']) ? $this->vids[$vid]['lynkvp_height'] : 240);
			}
			$vtype  = (isset($this->vids[$vid]['lynkvp_type']) ? $this->vids[$vid]['lynkvp_type'] : "");
		} else {
			$vtitle = $title;
			$vsize  = 320;
			$vwidth = $this->videosizes[$vsize]['width'];
			$vheight= $this->videosizes[$vsize]['height'];
			$vtype  = "";
		}

		$wkClassName = "";
		if (isset($this->options['class_name'])) {
			$wkClassName = ($this->options['class_name'] != "" ? ' class='.$quote.$this->options['class_name'].'%s'.$quote : "");
			$vclass = sprintf($wkClassName, (isset($this->options['class_name_with_type']) && $this->options['class_name_with_type'] == "1" ? "-".$vtype : ""));
		}

		if (!$inline) {
			$src = "";
			if ($this->options['show_option'] != "lightpop") {
				$src = $this->videopop_url.'?vid='.$vid;
			} else {
				if(!empty($this->vids[$vid]['lynkvp_url'])) {
					$src = 'http://'.$this->vids[$vid]['lynkvp_url'];
				} elseif(file_exists($this->data_dir . $this->vids[$vid]['lynkvp_id'] . '.' . $this->vids[$vid]['lynkvp_type'])) {
					$src = $this->data_url . $this->vids[$vid]['lynkvp_id'].'.'.$this->vids[$vid]['lynkvp_type'];
				}
			}
			$params = 'width='.$vwidth.'&amp;'.'height='.$vheight;
			if(!empty($this->vids[$vid]['lynkvp_image'])) $params .= '&amp;image=http://'.$this->vids[$vid]['lynkvp_image'];
			$retval = sprintf(
				 $link_format
				,$src
				,$vtitle
				,$params
				,$title
				,$vclass
				,$vid
				,$vwidth
				);
		} elseif ($vclass != "") {
			$link_format = '<div%s>%s<br clear='.$quote.'all'.$quote.' />%s</div>';
			$retval = sprintf(
				 $link_format
				,$vclass
				,str_replace('"', $quote, getVideoPopObject($vid, false, $this->flvplayer, __("Sorry, video not available.", $this->textdomain_name), $this->videosizes))
				,$title
				);
		} else {
			$link_format = '%s<br clear='.$quote.'all'.$quote.' />%s';
			$retval = sprintf(
				 $link_format
				,str_replace('"', $quote, getVideoPopObject($vid, false, $this->flvplayer, __("Sorry, video not available.", $this->textdomain_name), $this->videosizes))
				,$title
				);
		}

		return $retval;
	}

	function _insertEditorJs() {
		$out  = "<script type=\"text/javascript\"> //<![CDATA[\n";
		$out .= "function vpInsertAtCursor(myValue) {";
		$out .= " var win = window;";
		$out .= " var richedit = false;";
		$out .= " var field = document.post.content;";
		$out .= " if (win.tinyMCE) {";
		$out .= "  if (!win){win = top};";
		$out .= "  var tinyMCE = win.tinyMCE;";
		$out .= "  richedit = ".($this->wp25 ? "(jQuery('#edButtonPreview').attr('class') == 'active' )" : "(typeof tinyMCE=='object' && tinyMCE.configs.length > 0 )").";";
		$out .= " }";
		$out .= " if (richedit) {";
		$out .= "  tinyMCE.execCommand('mceInsertContent',false,myValue);";
		$out .= " } else {";
		$out .= "  try {";
		$out .= "   if (typeof FCKeditor != \"undefined\") {";
		$out .= "    var oEditor = FCKeditorAPI.GetInstance('content');";
		$out .= "    oEditor.InsertHtml(myValue);";
		$out .= "   } else if (document.selection) {";
		$out .= "    field.value += myValue;";
		$out .= "   } else if (field.selectionStart || field.selectionStart=='0') {";
		$out .= "    var startPos = field.selectionStart;";
		$out .= "    var endPos = field.selectionEnd;";
		$out .= "    field.value = field.value.substring(0, startPos)+ myValue + field.value.substring(endPos, field.value.length);";
		$out .= "   } else {";
		$out .= "    field.value += myValue;";
		$out .= "   }";
		$out .= "  } catch(e) {";
		$out .= "   alert(e);";
		$out .= "  }";
		$out .= " }";
		$out .= "}\n";
		if ($this->options['ins_shortcode'] == '1') {
			$out .= "function vpEditCode(vp_id,vp_name,vp_caption,vp_inline) {";
			$out .= " return '[videopop vid=\"' + vp_id + '\" vtitle=\"' + vp_name + '\"' + (vp_inline==true?' vinline=\"true\"':'') + ']' + vp_caption + '[/videopop]';";
			$out .= "}\n";
		}
		$out .= "//]]> </script>\n";

		echo $out;
	}//func

	/*
	* Set Videopop Script
	*/
	function addWpHead($unused) {
	}//func

	function addWpFooter($unused) {
		if (!(is_404() || is_page())) {
			global $script_manager;

			$out = '';
			switch($this->options['show_option']) {
			case "lightpop":
				if (!$this->_wpLightpop_enable) {
					$out .= "jQuery(function(){";
					$out .= "var l={";
					$out .= "imageLoading:'".$this->plugin_url."images/lightpop-ico-loading.gif'";
					$out .= ",imageBtnPrev:'".$this->plugin_url."images/lightpop-btn-prev.gif'";
					$out .= ",imageBtnNext:'".$this->plugin_url."images/lightpop-btn-next.gif'";
					$out .= ",imageBtnClose:'".$this->plugin_url."images/lightpop-btn-close.gif'";
					$out .= ",imageBlank:'".$this->plugin_url."images/lightpop-blank.gif'";
					$out .= ",flvplayer:'".$this->plugin_url.$this->flvplayer."'";
					$out .= "};";
					$out .= "jQuery('".$this->getLightPopElements()."').each(function(){jQuery(this).lightpop(l);});";
					$out .= "});\n";
				}
				break;

			case "modalbox":
			case "popup":
				$out .= "function VideoPop(vpsize,vpid){";
				$out .= " url = '".$this->videopop_url."?vid='+vpid;";
				$out .= " xwidth = vpsize+110; xheight = (vpsize/1.3)+94;";
				$out .= " lvp = window.open(url,'','top=100,left=100,status=0,location=0,width='+xwidth+',height='+xheight+'');";
				$out .= " lvp.focus();";
				$out .= "}\n";
				break;

			case "inline":
				break;

			default:
				break;
			}

			if ($out != '')
				$this->writeScript($out, 'footer');
		}
	}//func

	function _getLightPopElements() {
		$elements = "";
		if ($this->options['class_name'] == "") {
			$elements .= "a[@href^={$this->data_url}]";
			$elements .= ',a[@href$=.asf],a[@href*=.asf?]';
			$elements .= ',a[@href$=.avi],a[@href*=.avi?]';
			$elements .= ',a[@href$=.mp4],a[@href*=.mp4?]';
			$elements .= ',a[@href$=.mpg],a[@href*=.mpg?]';
			$elements .= ',a[@href$=.mov],a[@href*=.mov?]';
			$elements .= ',a[@href$=.rm],a[@href*=.rm?]';
			$elements .= ',a[@href$=.flv],a[@href*=.flv?]';
			$elements .= ',a[@href$=.wmv],a[@href*=.wmv?]';
			$elements .= ',a[@href$=.3gp],a[@href*=.3gp?]';
		} elseif ($this->options['class_name_with_type'] == "0") {
			$elements .= 'a.'.$this->options['class_name'];
		} else {
			$elements .= "a[@class^={$this->options['class_name']}]";
		}
		return $elements;
	}

	function addLightPopElements($content = "") {
		if ($this->options['show_option'] == 'lightpop' && $this->_wpLightpop_enable)
			$content .= ($content != '' ? "\n" : '') . $this->_getLightPopElements();
		return $content;
	}

	/*
	* Content Filter
	*/
	function addContentFilter($content) {
		$matched = 0;

		$search_strings  = array();
		$replace_strings = array();

		// VideoPop+ shortcode found
		if (!$this->wp25 && strstr(strtolower($content),'[/videopop]')) {
			$pattern = '/\[videopop(.*)\](.*)\[\/videopop\]/i';
			if(preg_match_all($pattern, $content, $matches)) {
				for ($i=0; $i<count($matches[0]); $i++) {
					// get options
					$vid = 0;
					$options = explode(" v", $matches[1][$i]);
					for ($j=0; $j<count($options); $j++) {
						if (strstr(strtolower($options[$j]),'id')) {
								$vid = preg_replace('/id=[\'"]?(.*)[\'"]?/i', '$1', $options[$j]);
						} elseif (strstr(strtolower($options[$j]),'inline')) {
								$inline = strstr(strtolower($options[$j]),'true') != false;
						}
					}
					unset($options);
					$search_strings[$matched]  = '/' . preg_quote($matches[0][$i], '/') . '/';
					$replace_strings[$matched] = $this->_getLinkTag($vid, $matches[2][$i], $inline);
					$matched++;
				}
			}
			unset($matches);
		}

		// VideoPop tag found
		if (strstr(strtolower($content),'class="lynkvp"')) {
			$pattern = '/<a href="javascript:lynkVideoPop\(([0-9]*),[\'"]?([0-9]*)[\'"]?\);" class="lynkvp">(.*)<\/a>/i';
			if(preg_match_all($pattern, $content, $matches)) {
				for ($i=0; $i<count($matches[0]); $i++) {
					$search_strings[$matched]  = '/' . preg_quote($matches[0][$i], '/') . '/';
					$replace_strings[$matched] = $this->_getLinkTag($matches[2][$i], $matches[3][$i], $inline);
					$matched++;
				}
			}
			unset($matches);
		}

		if ($matched > 0) {
			$content = ($this->blog_charset != "UTF-8" ? mb_convert_encoding($content, "UTF-8", $this->blog_charset) : $content);
			$content = preg_replace($search_strings, $replace_strings, $content);
			$content = ($this->blog_charset != "UTF-8" ? mb_convert_encoding($content, $this->blog_charset, "UTF-8") : $content);
		}
		return $content;
	}//func

	function addLightPopScripts() {
		if (!is_admin() && $this->options['show_option'] == 'lightpop' && !$this->_wpLightpop_enable) {
			$this->addjQuery();	// regist jQuery
			wp_enqueue_script('jquery.lightpop', $this->plugin_url.$this->lightpop_js, array('jquery'), $this->lightpop_ver);
		}
	}

	/*
	* Add shortcode API Handler
	*/
	function shortcodeHandler($atts, $content = null) {
		extract( shortcode_atts( array(
			 'vid'     => '0'
			,'vtitle'  => ''
			,'vinline' => 'false'
			), $atts ) );
		$vinline = (strtolower($vinline) == 'true');
		return $this->_getLinkTag($vid, $vtitle, $vinline);
	}

	/*
	* Add js to admin head
	*/
	function _adminHead() {
		$out  = "<script type=\"text/javascript\"> //<![CDATA[\n";
		$out .= "function VideoPop(vpsize,vpid){";
		$out .= " url = '".$this->videopop_url."?vid='+vpid;";
		$out .= " xwidth = vpsize+110; xheight = (vpsize/1.3)+94;";
		$out .= " lvp = window.open(url,'','top=100,left=100,status=0,location=0,width='+xwidth+',height='+xheight+'');";
		$out .= " lvp.focus();";
		$out .= "}\n";
		$out .= "//]]> </script>\n";

		echo $out;
	}//func

	/*
	* Display option page body
	*/
	function optionPage() {
		$out = $uninstall = '';

		// Check write permissions
		$this->_checkDir();

		// Settings update or delete
		if(isset($_POST['options_update'])) {      // options update
			// strip slashes array
			$_POST = $this->stripArray($_POST);

			$this->options['user_lvl'] = $_POST['vp_userlevel'];
			$this->options['show_option'] = $_POST['vp_show'];
			$this->options['class_name'] = $_POST['vp_classname'];
			$this->options['class_name_with_type'] = $_POST['vp_classwithtype'];
			$this->options['ins_shortcode'] = (isset($_POST['vp_shortcode']) && $_POST['vp_shortcode']=='1' ? '1' : '0');
			$this->updateOptions();

			$_POST = '';
			$this->note .= __('<strong>Done!</strong>', $this->textdomain_name);

		} elseif(isset($_POST['uninst'])) {         // uninstall
			$this->deleteOptions();
			if (file_exists($this->data_dir))
				$this->_removeDir($this->data_dir);
			$this->note .= __('All files and folders have (probably) been deleted. Now click <strong>Plugins</strong> in the admin panel above and <b>Deactivate</b> the VideoPop plugin.', $this->textdomain_name);
			$this->note .= "<br />" . $this->data_dir;
			$this->error++;
			$this->initOptions();
			error_reporting(0);
		}

		// Add Options
		$out .= "<div class=\"wrap\">\n";
		$out .= "<h2>".__('VideoPop+ Options', $this->textdomain_name)."</h2><br />\n";
		$out .= "<form method=\"post\" id=\"update_options\" action=\"".$this->admin_action."\">\n";
		$out .= "<table class=\"optiontable form-table\" style=\"margin-top:0;\"><tbody>\n";

		// Add User Level
		//  Permission -- Subscriber = 0, Contributor = 1, Author = 2, Editor = 7, Administrator = 9
		$out .= "<tr>\n";
		$out .= "<td><strong>".__('User Level', $this->textdomain_name)."</strong></td>";
		$out .= "<td><select name=\"vp_userlevel\">";
		$out .= "<option value=\"0\"".$this->_setOptionSelected($this->options['user_lvl'],'0').">".__('subscriber', $this->textdomain_name)."</option>";
		$out .= "<option value=\"1\"".$this->_setOptionSelected($this->options['user_lvl'],'1').">".__('contributor', $this->textdomain_name)."</option>";
		$out .= "<option value=\"2\"".$this->_setOptionSelected($this->options['user_lvl'],'2').">".__('author', $this->textdomain_name)."</option>";
		$out .= "<option value=\"7\"".$this->_setOptionSelected($this->options['user_lvl'],'7').">".__('editor', $this->textdomain_name)."</option>";
		$out .= "<option value=\"9\"".$this->_setOptionSelected($this->options['user_lvl'],'9').">".__('administrator', $this->textdomain_name)."</option>";
		$out .= "</select>&nbsp;</td>";
		$out .= "<td>".__('Set the user level the user needs to have (at least) to manage/upload/delete videos', $this->textdomain_name)."</td>\n";
		$out .= "</tr>\n";

		// Add Method of display
		$out .= "<tr>\n";
		$out .= "<td><strong>".__('Method of display', $this->textdomain_name)."</strong></td>";
		$out .= "<td><select name=\"vp_show\">";
		$out .= "<option value=\"popup\""   .$this->_setOptionSelected($this->options['show_option'],'popup').">".   __('Pop up', $this->textdomain_name)."</option>";
		$out .= "<option value=\"lightpop\"".$this->_setOptionSelected($this->options['show_option'],'lightpop').">".__('LightPop', $this->textdomain_name)."</option>";
		$out .= "<option value=\"inline\""  .$this->_setOptionSelected($this->options['show_option'],'inline').">".  __('In line', $this->textdomain_name)."</option>";
		$out .= "<option value=\"none\""    .$this->_setOptionSelected($this->options['show_option'],'none').">".    __('The effect none', $this->textdomain_name)."</option>";
		$out .= "</select>&nbsp;</td>";
		$out .= "<td>".__('Please select it from &quot;Pop up&quot;, &quot;LightPop&quot;, &quot;In line&quot;, and &quot;The effect none&quot;.', $this->textdomain_name);
		$out .= "</tr>\n";

		// Add Class Name Setting
		$out .= "<tr style=\"border-style:none;\">\n";
		$out .= "<td style=\"border-style:none;\"><strong>".__('Class name of the link tag', $this->textdomain_name)."</strong></td>";
		$out .= "<td style=\"border-style:none;\"><input type=\"text\" name=\"vp_classname\" value=\"".$this->options['class_name']."\"/>&nbsp;</td>";
		$out .= "<td style=\"border-style:none;\">".__('Please set the class name of the link tag.', $this->textdomain_name)."</td>\n";
		$out .= "</tr>\n";
		$out .= "<tr>\n";
		$out .= "<td></td>";
		$out .= "<td colspan=\"2\"><select name=\"vp_classwithtype\">";
		$out .= "<option value=\"0\"".$this->_setOptionSelected($this->options['class_name_with_type'],'0').">".__('Without File Type', $this->textdomain_name)."</option>";
		$out .= "<option value=\"1\"".$this->_setOptionSelected($this->options['class_name_with_type'],'1').">".__('With File Type', $this->textdomain_name)."</option>";
		$out .= "</select>&nbsp;</td>";
		$out .= "</tr>\n";

		// Insert Editor Option (Shortcode or HTML Tag?)
		$out .= "<tr>\n";
		$out .= "<td><strong>".__('Insert Editor Option', $this->textdomain_name)."</strong></td>";
		$out .= "<td colspan=\"2\"><input type=\"checkbox\" name=\"vp_shortcode\" value=\"1\" style=\"margin-right:0.5em;\" ".($this->options['ins_shortcode']=='1' ? " checked" : "")." />".__('Shortcode is inserted in the editor.', $this->textdomain_name)."</td>";
		$out .= "</tr>\n";
		$out .= "<tr>\n";

		$out .= "</tbody></table>\n";

		// Add Update Button
		$out .= "<div style=\"text-align:right;margin-top:1em;\">";
		$out .= "<input type=\"submit\" name=\"options_update\" value=\"".__('Update Options', $this->textdomain_name)."\" class=\"button\" />";
		$out .= "</div>";
		$out .= "</form></div>\n";

		// Add uninstall
		$out .= "<div class=\"wrap\" style=\"margin-top:2em;\">\n";
		$out .= "<h2>".__('Uninstall', $this->textdomain_name)."</h2><br />\n";
		$out .= "<p>".__('If you want to keep your videos and the popup functionality of your links but want to get rid of the additional menus in the control panel, just deactivate the plugin.<br />For a complete uninstall including all uploaded videos use the uninstall button.', $this->textdomain_name)."</p>\n";
		$out .= "<div style=\"text-align:right;\">";
		$out .= "<form method=\"post\" id=\"uninstall\" action=\"".$this->admin_action."\">\n";
		$out .= "<input type=\"submit\" name=\"uninst\" value=\"".__('Uninstall VideoPop+', $this->textdomain_name)."\" onclick=\"javascript:check=confirm('".__('You are about to delete all your settings and Videos! The links you created with VideoPop will not work after uninstall! Proceed with uninstall?', $this->textdomain_name)."');if(check==false) return false;\" class=\"button\" />\n";
		$out .= "</form>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";

		// Output
		echo (!empty($this->note) ? "<div id=\"message\" class=\"updated fade\"><p>{$this->note}</p></div>\n" : '' )."\n";
		echo ($this->error > 0 ? '' : $out)."\n";
	}//func

	/*
	* Display manage page body
	*/
	function manageVideoPopPlusFiles() {
		// Get List
		if (isset($_GET['get_list'])) {
			$start = 0;
			if (isset($_GET['start']) && is_numeric($_GET['start']))
				$start = $_GET['start'];

			// Open file and fill array with unserialized data
			if (!is_array($this->vids))
				$this->vids = $this->_loadDataText();

			$out .= $this->_videoLists($_GET['get_list'], $this->vids, $start, ($_GET['get_list']=='1' ? $this->options['manage_list_max'] : $this->options['post_list_max']));
			echo $out;
			return;
		}

		// Check write permissions
		if($this->_checkDir()) {

			// IF Video is uploaded
			if ( current_user_can( $this->options['user_lvl'] ) ) {
				if(isset($_POST['lynkvp_upload']) && $this->_valInput()) {
					// defaults
					$vid_url = '';
					$vid_file = '';
					$vid_id = time(); // use timestamp for unique id
					$vid_image_file = '';
					$vid_image_url = '';

					// Save file
					if(is_uploaded_file($_FILES['lynkvp_file']['tmp_name'])) {
						$vid_file = $vid_id.'.'.$_POST['lynkvp_type']; // filename+extension
						if(!move_uploaded_file($_FILES['lynkvp_file']['tmp_name'], $this->data_dir . $vid_file))
							$this->note .= __("The file couldn't be saved on your server", $this->textdomain_name);
					} elseif(!empty($_POST['lynkvp_url'])) {
						$vid_url = ltrim($_POST['lynkvp_url'],'http://');
					}

					// Save Thumbnail file
					if(is_uploaded_file($_FILES['lynkvp_image_file']['tmp_name'])) {
						$vid_image_file = $vid_id.'.thum.'.preg_replace('/^.*\.(gif|jpe?g|png|bmp)$/i', '$1', $_FILES['lynkvp_image_file']['name']); // filename+extension
						if(file_exists($this->data_dir . $vid_image_file)) @unlink($this->data_dir . $vid_image_file);
						if(move_uploaded_file($_FILES['lynkvp_image_file']['tmp_name'], $this->data_dir . $vid_image_file)) {
							$vid_image_url = ltrim($this->data_url . $vid_image_file, 'http://');
						} else {
							$this->note .= __("The file couldn't be saved on your server", $this->textdomain_name);
						}

					} elseif(!empty($_POST['lynkvp_image_url'])) {
						$vid_image_url = ltrim($_POST['lynkvp_image_url'],'http://');
					}

					// Open file and fill array with unserialized data
					if (!is_array($this->vids))
						$this->vids = $this->_loadDataText();

					// Add data to array
					$this->vids[$vid_id]['lynkvp_id'] = $vid_id;
					$this->vids[$vid_id]['lynkvp_filename'] = $vid_file;
					$this->vids[$vid_id]['lynkvp_url'] = $vid_url;
					$this->vids[$vid_id]['lynkvp_type'] = $_POST['lynkvp_type'];
					$this->vids[$vid_id]['lynkvp_size'] = $_POST['lynkvp_size'];
					if (isset($this->videosizes[$_POST['lynkvp_size']])) {
						$this->vids[$vid_id]['lynkvp_width']  = $this->videosizes[$_POST['lynkvp_size']]['width'];
						$this->vids[$vid_id]['lynkvp_height'] = $this->videosizes[$_POST['lynkvp_size']]['height'];
					} else {
						$this->vids[$vid_id]['lynkvp_width']  = $_POST['lynkvp_width'];
						$this->vids[$vid_id]['lynkvp_height'] = $_POST['lynkvp_height'];
					}
					$this->vids[$vid_id]['lynkvp_name'] = $_POST['lynkvp_name'];
					$this->vids[$vid_id]['lynkvp_caption'] = $_POST['lynkvp_caption'];
					$this->vids[$vid_id]['lynkvp_image'] = $vid_image_url;
					$this->vids[$vid_id]['lynkvp_image_filename'] = $vid_image_file;

					// Save ser. array
					$this->_saveDataText($this->vids);

					$this->note .= __('<strong>Done!</strong>', $this->textdomain_name);
					unset($_POST);

				} elseif(isset($_POST['lynkvp_edit2']) && $this->_valInput2()) {
					// Open file and fill array with unserialized data
					if (!is_array($this->vids)) {$this->vids = $this->_loadDataText();}
					$vid_id = $_POST['lynkvp_editid'];
					$vid_image_file = '';
					$vid_image_url = '';

					// Save Thumbnail file
					if(is_uploaded_file($_FILES['lynkvp_image_file']['tmp_name'])) {
						$vid_image_file = $vid_id.'.thum.'.preg_replace('/^.*\.(gif|jpe?g|png|bmp)$/i', '$1', $_FILES['lynkvp_image_file']['name']); // filename+extension
						if(file_exists($this->data_dir . $vid_image_file)) @unlink($this->data_dir . $vid_image_file);
						if(move_uploaded_file($_FILES['lynkvp_image_file']['tmp_name'], $this->data_dir . $vid_image_file))
							$vid_image_url = ltrim($this->data_url . $vid_image_file, 'http://');
						else
							$this->note .= __("The file couldn't be saved on your server", $this->textdomain_name);

					} elseif(!empty($_POST['lynkvp_image_url'])) {
						$vid_image_url = ltrim($_POST['lynkvp_image_url'],'http://');

					} else {
						$vid_image_url = $this->vids[$vid_id]['lynkvp_image'];
						$vid_image_file = $this->vids[$vid_id]['lynkvp_image_filename'];
					}

					// Add data to array
					$this->vids[$vid_id]['lynkvp_id'] = $vid_id;
					$this->vids[$vid_id]['lynkvp_filename'] = $_POST['lynkvp_filename'];
					$this->vids[$vid_id]['lynkvp_url'] = $_POST['lynkvp_url'];
					$this->vids[$vid_id]['lynkvp_type'] = $_POST['lynkvp_type'];
					$this->vids[$vid_id]['lynkvp_size'] = $_POST['lynkvp_size'];
					if (isset($this->videosizes[$_POST['lynkvp_size']])) {
						$this->vids[$vid_id]['lynkvp_width']  = $this->videosizes[$_POST['lynkvp_size']]['width'];
						$this->vids[$vid_id]['lynkvp_height'] = $this->videosizes[$_POST['lynkvp_size']]['height'];
					} else {
						$this->vids[$vid_id]['lynkvp_width']  = $_POST['lynkvp_width'];
						$this->vids[$vid_id]['lynkvp_height'] = $_POST['lynkvp_height'];
					}
					$this->vids[$vid_id]['lynkvp_name'] = $_POST['lynkvp_name'];
					$this->vids[$vid_id]['lynkvp_caption'] = $_POST['lynkvp_caption'];
					$this->vids[$vid_id]['lynkvp_image'] = $vid_image_url;
					$this->vids[$vid_id]['lynkvp_image_filename'] = $vid_image_file;

					// Save ser. array
					$this->_saveDataText($this->vids);

					$this->note .= __('<strong>Done!</strong>', $this->textdomain_name);
					unset($_POST);

				} elseif(isset($_POST['lynkvp_del'])) {
					// Open file and fill array with unserialized data
					if (!is_array($this->vids))
						$this->vids = $this->_loadDataText();
					$a_id = array_flip($_POST['lynkvp_del']);

					// Remove Video
					if(file_exists($this->data_dir . $this->vids[$a_id[__('delete', $this->textdomain_name)]]['lynkvp_filename']))
						@unlink($this->data_dir . $this->vids[$a_id[__('delete', $this->textdomain_name)]]['lynkvp_filename']);
					if(file_exists($this->data_dir . $this->vids[$a_id[__('delete', $this->textdomain_name)]]['lynkvp_image_filename']))
						@unlink($this->data_dir . $this->vids[$a_id[__('delete', $this->textdomain_name)]]['lynkvp_image_filename']);

					foreach($this->vids as $key=>$value) {
						if($key != $a_id[__('delete', $this->textdomain_name)])
							$a_vids2[$key] = $value;
					}
					$this->vids = $a_vids2;
					unset($a_vids2);

					// Save ser. images
					$this->_saveDataText($this->vids);

					$this->note .= __('<strong>Done!</strong>', $this->textdomain_name);
					unset($_POST);

				}
			}

			// IF edit
			if ( current_user_can( $this->options['user_lvl'] ) ) {
				if ( isset($_POST['lynkvp_edit']) ) {
					// Video ID
					$a_id = array_flip($_POST['lynkvp_edit']);

					// Open file and fill array with unserialized data
					if (!is_array($this->vids))
						$this->vids = $this->_loadDataText();

					// Populate POST & strip slashes array
					$_POST = $this->stripArray($this->vids[$a_id[__('edit', $this->textdomain_name)]]);

					$out .= "<div class=\"wrap\" style=\"text-align:left;\">\n";
					$out .= "<h2>".__('Video Edit', $this->textdomain_name)."</h2>\n";

					$out .= "<form method=\"post\" action=\"".$this->admin_manage."\" enctype=\"multipart/form-data\">\n";
					$out .= "<input type=\"hidden\" name=\"lynkvp_type\" value=\"".$_POST['lynkvp_type']."\" />\n";

					$out .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";

					$out .= "<tr>\n";
					$out .= "<td>".__('Name','videopop_plus').":</td>\n";
					$out .= "<td><input type=\"text\" name=\"lynkvp_name\" value=\"".$_POST['lynkvp_name']."\" /></td>\n";
					$out .= "</tr>\n";

					$out .= "<tr>\n";
					$out .= "<td>".__('Caption','videopop_plus').":</td>\n";
					$out .= "<td><input type=\"text\" name=\"lynkvp_caption\" value=\"".$_POST['lynkvp_caption']."\" /></td>\n";
					$out .= "</tr>\n";

					if(!empty($_POST['lynkvp_url'])) {
						$out .= "<tr>\n";
						$out .= "<td>".__('URL', $this->textdomain_name).":</td>\n";
						$out .= "<td>http://<input type=\"text\" name=\"lynkvp_url\" value=\"".$_POST['lynkvp_url']."\" style=\"width:400px;\" /></td>\n";
						$out .= "</tr>\n";

						$out .= $this->_getTypeSelect()."\n";

					} else {
						$out .= "<tr>\n";
						$out .= "<td>".__('Video type', $this->textdomain_name).":</td>\n";
						$out .= "<td>".$_POST['lynkvp_type']."</td>\n";
						$out .= "</tr>\n";

						$out .= "<tr>\n";
						$out .= "<td><span style=\"display:".($_POST['lynkvp_type']!='flv'?'none':'block')."\">";
						$out .= __('Thumbnail', $this->textdomain_name).":";
						$out .= "</span></td>\n";
						$out .= "<td><span style=\"display:".($_POST['lynkvp_type']!='flv'?'none':'block')."\">";
						$out .= "<input type=\"file\" name=\"lynkvp_image_file\" />&nbsp;&nbsp;&nbsp;\n";
						$out .= __('<strong>or</strong>', $this->textdomain_name).' &nbsp;&nbsp;';
						$out .= __('URL', $this->textdomain_name).":&nbsp;&nbsp;";
						$out .= "http:// <input type=\"text\" name=\"lynkvp_image_url\" style=\"width:400px;\" value=\"".$_POST['lynkvp_image']."\" />";
						$out .= "</span></td>\n";
						$out .= "</tr>\n";
					}

					$out .= "<tr>\n";
					$out .= "<td>".__('Size', $this->textdomain_name).":</td>\n";
					$out .= "<td>\n";
					$out .= "<select name=\"lynkvp_size\" onchange=\"javascript:";
					$out .= "document.getElementById('input_size').style.display=(this.value==999?'inline':'none');";
					$out .= "\">";
					$out .= "<option value=\"0\"".$this->_setOptionSelected($_POST['lynkvp_size'],'0').">".__('Select size', $this->textdomain_name)."</option>\n";
					$out .= "<!-- Video sizes, one each line -->\n";
					foreach($this->videosizes as $key=>$value) {
						$out .= "<option value=\"".$key."\"".$this->_setOptionSelected($_POST['lynkvp_size'],$key).">".$value['width']." x ".$value['height'].$value['note']."</option>\n";
					}
					$out .= "<option value=\"999\"".$this->_setOptionSelected($_POST['lynkvp_size'], '999').">".__('Free Size', $this->textdomain_name)."</option>\n";
					$out .= "</select>&nbsp;&nbsp;&nbsp;\n";
					$out .= "<span id=\"input_size\" style=\"display:".($_POST['lynkvp_size']!=999?'none':'inline')."\">\n";
					$out .= __('Width').": <input type=\"text\" name=\"lynkvp_width\" value=\"".$_POST['lynkvp_width']."\" />\n";
					$out .= "&nbsp;x&nbsp;";
					$out .= __('Height').": <input type=\"text\" name=\"lynkvp_height\" value=\"".$_POST['lynkvp_height']."\" />\n";
					$out .= "</span>\n";
					$out .= "</td>\n";
					$out .= "</tr>\n";

					$out .= "</table>\n";

					$out .= "<br />\n";
					$out .= "<input type=\"hidden\" name=\"lynkvp_editid\" value=\"".$_POST['lynkvp_id']."\" />\n";
					$out .= "<input type=\"hidden\" name=\"lynkvp_filename\" value=\"".$_POST['lynkvp_filename']."\" />\n";
					$out .= "<input type=\"submit\" name=\"lynkvp_edit2\" class=\"button\" value=\"".__('Upload', $this->textdomain_name)."\" />\n";
					$out .= "</form>\n";
					$out .= "</div>\n";

					unset($_POST);

				} else {
					// strip slashes array
					$_POST = $this->stripArray($_POST);

					// BLOCK Upload
					$out .= "<div class=\"wrap\" style=\"text-align:left;\">\n";
					$out .= "<h2>".__('Video Upload', $this->textdomain_name)."</h2>\n";

					$out .= "<form method=\"post\" action=\"".$this->admin_manage."\" enctype=\"multipart/form-data\">\n";

					$out .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";

					$out .= "<tr>\n";
					$out .= "<td>".__('Name', $this->textdomain_name).":</td>\n";
					$out .= "<td><input type=\"text\" name=\"lynkvp_name\" value=\"".$_POST['lynkvp_name']."\" /></td>\n";
					$out .= "</tr>\n";

					$out .= "<tr>\n";
					$out .= "<td>".__('Caption', $this->textdomain_name).":</td>\n";
					$out .= "<td><input type=\"text\" name=\"lynkvp_caption\" value=\"".$_POST['lynkvp_caption']."\" /></td>\n";
					$out .= "</tr>\n";

					$out .= "<tr>\n";
					$out .= "<td>".__('Choose video', $this->textdomain_name).":</td>\n";
					$out .= "<td>";
					$out .= "<input type=\"file\" name=\"lynkvp_file\" />&nbsp;&nbsp;&nbsp;\n";
					$out .= __('<strong>or</strong>', $this->textdomain_name)." &nbsp;&nbsp;";
					$out .= __('URL', $this->textdomain_name).":&nbsp;&nbsp;http://<input type=\"text\" name=\"lynkvp_url\" style=\"width:400px;\" value=\"".$_POST['lynkvp_url']."\" />";
					$out .= "</td>\n";
					$out .= "</tr>\n";

					$out .= $this->_getTypeSelect()."\n";

					$out .= "<tr>\n";
					$out .= "<td>".__('Size', $this->textdomain_name).":</td>\n";
					$out .= "<td>\n";
					$out .= "<select name=\"lynkvp_size\" onchange=\"javascript:";
					$out .= "document.getElementById('input_size').style.display=(this.value==999?'inline':'none');";
					$out .= "\">";
					$out .= "<option value=\"0\"".$this->_setOptionSelected($_POST['lynkvp_size'],'0').">".__('Select size', $this->textdomain_name)."</option>\n";
					$out .= "<!-- Video sizes, one each line -->\n";
					foreach($this->videosizes as $key=>$value) {
						$out .= "<option value=\"".$key."\"".$this->_setOptionSelected($_POST['lynkvp_size'],$key).">".$value['width']." x ".$value['height'].$value['note']."</option>\n";
					}
					$out .= "<option value=\"999\"".$this->_setOptionSelected($_POST['lynkvp_size'], "999").">".__('Free Size', $this->textdomain_name)."</option>\n";
					$out .= "</select>&nbsp;&nbsp;&nbsp;\n";
					$out .= "<span id=\"input_size\" style=\"display:".(!isset($this->videosizes[$_POST['lynkvp_size']]) ? 'none' : 'inline')."\">\n";
					$out .= __('Width').": <input type=\"text\" name=\"lynkvp_width\" value=\"".$_POST['lynkvp_width']."\" />\n";
					$out .= "&nbsp;x&nbsp;";
					$out .= __('Height').": <input type=\"text\" name=\"lynkvp_height\" value=\"".$_POST['lynkvp_height']."\" />\n";
					$out .= "</span>\n";
					$out .= "</td>\n";
					$out .= "</tr>\n";

					$out .= "</table>\n";

					$out .= "<br />\n";
					$out .= "<input type=\"submit\" name=\"lynkvp_upload\" class=\"button\" value=\"".__('Upload', $this->textdomain_name)."\" />\n";
					$out .= "</form>\n";
					$out .= "</div>\n";
					unset($_POST);
				}
			}

			// ---------
			// ALWAYS DISPLAYED

			// BLOCK Your Videos
			$out .= "<div class=\"wrap\" style=\"text-align:left;padding-bottom:3em;margin-top:2em;\">\n";
			$out .= "<h2>".__('My Videos', $this->textdomain_name)."</h2>\n";

			// Open file and fill array with unserialized data
			if (!is_array($this->vids)) {$this->vids = $this->_loadDataText();}

			// If any vids uploaded
			if(is_array($this->vids)) {
				// add JS to manage page;
				$this->_adminHead();
				$out .= $this->_videoLists('1', $this->vids, 0, $this->options['manage_list_max']);
			}

			$out .= "</div>\n";
		}

		// Output
		echo (!empty($this->note) ? "<div id=\"message\" class=\"updated fade\"><p>{$this->note}</p></div>\n" : '' )."\n";
		echo ($this->error > 0 ? '' : $out)."\n";
	}//func

	/*
	* Menu on the write pages
	*/
	function editFormAdvanced() {
		// Open file and fill array with unserialized data
		if (!is_array($this->vids)) {
			$this->vids = unserialize(file_get_contents($this->data_txt));
		}

		$edit_html = "";
		if (!function_exists('add_meta_box')) {
			$edit_html  = "<div class=\"dbx-b-ox-wrapper\">\n";
			$edit_html .= "<fieldset id=\"videopop-plus\" class=\"dbx-box\">\n";
			$edit_html .= "<div class=\"dbx-h-andle-wrapper\">\n";
			$edit_html .= "<h3 class=\"dbx-handle\">".__('My Videos', $this->textdomain_name)."</h3>\n";
			$edit_html .= "</div>\n";
			$edit_html .= "<div class=\"dbx-c-ontent-wrapper\">\n";
			$edit_html .= "<div class=\"dbx-content\">";
		}

		$edit_html .= $this->_videoLists('2', $this->vids, 0, $this->options['post_list_max']);

		if (!function_exists('add_meta_box')) {
			$edit_html .= "</div>\n";
			$edit_html .= "</div>\n";
			$edit_html .= "</fieldset>\n";
			$edit_html .= "</div>\n";
			$edit_html .= "<div class=\"dbx-b-ox-wrapper\"></div>\n";
		}

		echo $edit_html;
		$this->_insertEditorJs();
		$this->_adminHead();
	}//func

	/*
	* Video Lists
	*/
	function _videoLists($list_type='1', $vid_list, $start=0, $limit=0) {
		if ($limit==0)
			$limit = ($list_type=='1' ? $this->options['manage_list_max'] : $this->options['post_list_max']);
		if (!is_array($vid_list))
			$vid_list = unserialize(file_get_contents($this->data_txt));
		if (count($vid_list) < 1) return false;
		$vid_list = array_reverse((array) $vid_list);
		$link_format = '<a href="'.$this->videopop_url.'?vid=%1$d" title="%2$s" onclick="javascript:VideoPop(%3$d,\'%1$d\');return false;">%4$s</a>';
		$count = 0;
		$next = false;

		$retval  = "<!--- list start --->\n";
		$retval .= "<div id=\"videopop_list\">\n";
		if ($list_type=='1') {
			$retval .= "<form method=\"post\" action=\"".$this->admin_manage."\">\n";
			$retval .= "<table id=\"VideoLists\" class=\"stats\">";
			$retval .= "<thead><tr>\n";
			$retval .= "<th style=\"text-align:center;\">".__('Name', $this->textdomain_name)."</th>\n";
			$retval .= "<th>".__('Video type', $this->textdomain_name)."</th>\n";
			$retval .= "<th></th>\n";
			$retval .= "</tr></thead>\n";
		} else {
			$retval .= "<table id=\"VideoLists\" style=\"border-collapse:collapse;margin:10px 0;\">";
			$retval .= "<thead><tr>\n";
			$retval .= "<th>&nbsp;</th>\n";
			$retval .= "<th>".__('In line', $this->textdomain_name)."</th>\n";
			$retval .= "<th>".__('Name', $this->textdomain_name)."</th>\n";
			$retval .= "<th>".__('Video type', $this->textdomain_name)."</th>\n";
			$retval .= "</tr></thead>\n";
		}

		$retval .= "<tbody>\n";
		$class = "";
		$root_uri = preg_replace("/^(https?:\/\/[^\/]*\/).*$/i", "$1", trailingslashit(get_bloginfo('wpurl')));
		foreach((array) $vid_list as $key => $a_value) {
			if ($count >= $limit + $start) {
				$next = true;
				break;
			} elseif ($count >= $start) {
				$a_value = $this->stripArray($a_value); // strip slashes
				$src = '../wp-content/videopop/'.$a_value['lynkvp_filename'];
				$is_type = '';
				if(!empty($a_value['lynkvp_url'])) {
					$src = "http://".$a_value['lynkvp_url'];
					$is_type = "<span style=\"font-size:10px;\">URL</span>";
				}
				$retval .= "<tr".$class.">\n";
				if ($list_type=='1') {
					$retval .= "<td>";
					if ( current_user_can( $this->options['user_lvl'] ) ) {
						$retval .= "<input type=\"submit\" value=\"".__('edit', $this->textdomain_name)."\" name=\"lynkvp_edit[".$a_value['lynkvp_id']."]\" class=\"button\" style=\"font-size:10px;\" />&nbsp;&nbsp;&nbsp;";
						$retval .= "<input type=\"submit\" value=\"".__('delete', $this->textdomain_name)."\" name=\"lynkvp_del[".$a_value['lynkvp_id']."]\" onclick=\"javascript:check=confirm('".__('The links you created will not work anymore. Delete?', $this->textdomain_name)."');if(check==false) return false;\" class=\"button\" style=\"font-size:10px;\" />&nbsp;&nbsp;&nbsp;";
					}
					$retval .= sprintf($link_format, $a_value['lynkvp_id'], $a_value['lynkvp_title'], (isset($a_value['lynkvp_width']) ? $a_value['lynkvp_width'] : $a_value['lynkvp_size']), $a_value['lynkvp_name']).'&nbsp;&nbsp;';
					$retval .= "</td>\n";
					$retval .= "<td>".$a_value['lynkvp_type']."&nbsp;&nbsp;</td>\n";
					$retval .= "<td>".$is_type."&nbsp;&nbsp;</td>\n";
					$retval .= "</tr>\n";
					$class = ($class=='' ? ' class="alt"' : '');
				} else {
					$retval .= "<td><input type=\"submit\" value=\"".__('Add to Editor', $this->textdomain_name)."\" name=\"insert_".$a_value['lynkvp_id']."\"";
					if ($this->options['ins_shortcode'] == '1') {
						$retval .= " onclick=\"javascript:vpInsertAtCursor(";
						$retval .= "vpEditCode('".$a_value['lynkvp_id']."','".$a_value['lynkvp_name']."','".$a_value['lynkvp_caption']."',(inline_".$a_value['lynkvp_id'].".checked==true?true:false))";
						$retval .= ");return false;\"";
					} else {
						$retval .= " onclick='javascript:vpInsertAtCursor(";
						$retval .=  "(inline_".$a_value['lynkvp_id'].".checked==false";
//						$retval .= str_replace("<", "<\"+\"", str_replace("=", "=\"+\"", str_replace("&amp;", "&\"+\"amp;", str_replace(
//							 $root_uri
//							,"/"
//						        ,"?\"".$this->_getLinkTag($a_value['lynkvp_id'], $a_value['lynkvp_name'], false, "\\"."\"")."\"".":\"".$this->_getLinkTag($a_value['lynkvp_id'], $a_value['lynkvp_name'], true , "\\"."\"")."\""
//							))));
						$retval .= str_replace(
							 array("<", "=", "&amp;")
							,array("<\"+\"", "=\"+\"", "&\"+\"amp;")
						        ,"?\"".$this->_getLinkTag($a_value['lynkvp_id'], $a_value['lynkvp_name'], false, "\\"."\"")."\"".":\"".$this->_getLinkTag($a_value['lynkvp_id'], $a_value['lynkvp_name'], true , "\\"."\"")."\""
							);
						$retval .= "));return false;'";
					}
					$retval .= " class=\"button\" style=\"font-size:10px;\" /></td>\n";
					$retval .= "<td>&nbsp;&nbsp;<input type=\"checkbox\" name=\"inline_".$a_value['lynkvp_id']."\" value=\"\" /></td>\n";
					$retval .= "<td>".sprintf($link_format, $a_value['lynkvp_id'], $a_value['lynkvp_title'], $a_value['lynkvp_size'], $a_value['lynkvp_name'])."&nbsp;&nbsp;</td>\n";
					$retval .= "<td>".$a_value['lynkvp_type']."&nbsp;&nbsp;</td>\n";
				}
				$retval .= "</tr>\n";
			}
			$count++;
		}
		$retval .= "</tbody>\n";
		$retval .= "</table>\n";
		if ($list_type=='1') $retval .= "</form>\n";

		$retval .= "<div id=\"videopop_navi\" style=\"width:95%;margin:0 auto;\">";
		$retval .= "<span style=\"float:left;\"><input type=\"submit\" value=\"&laquo; ".__('Prev', $this->textdomain_name)."\" name=\"prev\" id=\"prev\" class=\"button\" style=\"visibility:".($start > 0 ? 'visible' : 'hidden').";\" />&nbsp;&nbsp;</span>";
		$retval .= "<span style=\"float:right;\">&nbsp;&nbsp;<input type=\"submit\" value=\"".__('Next', $this->textdomain_name)." &raquo;\" name=\"next\" id=\"next\" class=\"button\" style=\"visibility:".($next==true ? 'visible' : 'hidden').";\" /></span>";
		$retval .= "</div>\n";

		$retval .= "</div>\n";
		$retval .= "<!--- list end --->\n";

		$retval .= "<script type=\"text/javascript\"> /*<![CDATA[ */\n";
		$retval .= "jQuery(function(){\n";
		$retval .= " jQuery('#prev').unbind('submit').unbind('click').click(function(){get_page(".$list_type.",  0, ".$limit.");return false;});\n";
		$retval .= " jQuery('#next').unbind('submit').unbind('click').click(function(){get_page(".$list_type.", ".$limit.", ".$limit.");return false;});\n";
		$retval .= " jQuery('#videopop_list').css({height:(jQuery('#VideoLists').height()>180?jQuery('#VideoLists').height():180) + 20});\n";
		$retval .= " function get_page(list_type, start_count, max_count){\n";
		$retval .= "  if (start_count < 0) {start_count = 0;}\n";
		$retval .= "  jQuery('#videopop_navi').fadeOut('normal');\n";
		$retval .= "  jQuery('#videopop_list').block({\n";
		$retval .= "   message: '<div style=\"margin:0 auto;padding:0 0 0 23px;width:100px;font:normal 12px Arial;background:url(".$this->plugin_url."images/ajax-loader.gif) no-repeat 0 50%;\"><p style=\"margin:3em 0;\">".__('Loading...', $this->textdomain_name)."</p></div>'\n";
		$retval .= "  ,css: {border:\"1px solid #8C8C8C\"}\n";
		$retval .= "  ,overlayCSS: {backgroundColor:'#FFF',opacity:'0.6'}\n";
		$retval .= "  });\n";
		$retval .= "  jQuery.get(\n";
		$retval .= "    '".$this->admin_manage."'\n";
		$retval .= "   ,{'get_list':list_type, 'start':start_count}\n";
		$retval .= "   ,function(responseText){\n";
		$retval .= "     var newList_html = responseText.replace(/[\\r\\n]/g,'').replace(/^.*<\\!\\-+ list start \\-+>(.*?)<\\!\\-+ list end \\-+>.*$/i, '$1');\n";
		$retval .= "     jQuery('tbody', jQuery('#VideoLists')).children().remove();\n";
		$retval .= "     jQuery('#videopop_navi').children().remove();\n";
		$retval .= "     jQuery('tbody', jQuery('#VideoLists')).append(jQuery.trim(newList_html.replace(/^.*<tbody.*?>(.*?)<\\/tbody>.*$/i, '$1')));\n";
		$retval .= "     if (list_type == 1) {\n";
		$retval .= "      jQuery('#VideoLists').children().fadeIn('fast');\n";
		$retval .= "     } else {\n";
		$retval .= "      jQuery('#VideoLists').children().css({visibility:'visible'});\n";
		$retval .= "     }\n";
		$retval .= "     jQuery('#videopop_navi').append(jQuery.trim(newList_html.replace(/^.*<div id=\"videopop_navi\".*?>(.*?)<\\/div>.*$/i, '$1'))).fadeIn('fast');\n";
		$retval .= "     jQuery('#prev').unbind('submit').unbind('click').click(function(){get_page(list_type, start_count - max_count, max_count);return false;});\n";
		$retval .= "     jQuery('#next').unbind('submit').unbind('click').click(function(){get_page(list_type, start_count + max_count, max_count);return false;});\n";
		$retval .= "     jQuery('#videopop_list').unblock();\n";
		$retval .= "    }\n";
		$retval .= "  );\n";
		$retval .= " }\n";
		$retval .= "});\n";
		$retval .= "/*]]>*/ </script>\n";

		unset($vid_list);
		return $retval;
	}//func

	/*
	* Add menu item to WP admin panel
	*/
	function addAdminPrintScripts() {
		// add JS to admin_head
		$this->addjQuery();	// regist jQuery
		wp_enqueue_script('jquery.blockUI', $this->plugin_url.$this->blockUI, array('jquery'), $this->blockUI_ver);
	}//func

	function addAdminMenu() {
		// Upload and Adminstration
		$this->addOptionPage(__('VideoPop+', $this->textdomain_name), array($this,'optionPage'), $this->options['user_lvl']);
		$this->addSubmenuPage($this->admin_manage_parent, __('My Videos', $this->textdomain_name), array($this,'manageVideoPopPlusFiles'), 'upload_files');
		add_action('admin_print_scripts-'.$this->admin_hook[$this->admin_manage_parent], array($this,'addAdminPrintScripts'));

		// add Menu on the Write Pages
		if (function_exists('add_meta_box')) {
			add_meta_box('videopop-plus', __('My Videos', $this->textdomain_name), array($this, 'editFormAdvanced'), 'post', 'normal');
			add_meta_box('videopop-plus', __('My Videos', $this->textdomain_name), array($this, 'editFormAdvanced'), 'page', 'normal');
		} elseif (preg_match('/(post|page)(\-new)?\.php/i', $_SERVER['PHP_SELF'])){
			add_action('dbx_post_advanced', array($this, 'editFormAdvanced'));
			add_action('dbx_page_advanced', array($this, 'editFormAdvanced'));
		}
	}//func

	function pluginActionLinks($links, $file) {
		$this_plugin = plugin_basename(__FILE__);
		if ($file == $this_plugin) {
			$settings_link = '<a href="options-general.php?page=' . $this->plugin_file . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links
		}
		return $links;
	}//func
}//class

// Go, Go, Go!
new VideoPopPlus();
?>