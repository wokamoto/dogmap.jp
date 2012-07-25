<?php
/*
Plugin Name: Kougaburari
Version: 0.3.3
Plugin URI: http://wppluginsj.sourceforge.jp/kougaburari/
Description: Slide Show
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: kougaburari
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2010 wokamoto (email : wokamoto1973@gmail.com)

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

 jQuery Cycle Plugin (with Transition Definitions)
  Copyright (c) 2007-2010 M. Alsup
  Version: 2.74 (03-FEB-2010)
  Dual licensed under the MIT and GPL licenses:
  Examples and documentation at: http://jquery.malsup.com/cycle/
*/

global $wp_version, $kougaburari;

//**********************************************************************************
// Defines
//**********************************************************************************
if (!defined('KOUGABURARI_SHORTCODE'))
	define('KOUGABURARI_SHORTCODE', 'kougaburari');
if (!defined('KOUGABURARI_WIDTH'))
	define('KOUGABURARI_WIDTH',     160);
if (!defined('KOUGABURARI_HEIGHT'))
	define('KOUGABURARI_HEIGHT',    120);
if (!defined('KOUGABURARI_COUNT'))
	define('KOUGABURARI_COUNT',     10);

//**********************************************************************************
// Kougaburari Controller
//**********************************************************************************
if (!class_exists('wokController') || !class_exists('wokScriptManager'))
	require(dirname(__FILE__).'/includes/common-controller.php');

class Kougaburari extends wokController {
	var $plugin_name = 'Kougaburari';
	var $plugin_ver  = '0.3.3';

	var $cycle_js    = 'js/jquery.cycle.all.min.js';
	var $cycle_ver   = '2.74';

	var $options_default = array(
		'title'   => 'Slide Show' ,
		'element' => 'slideshow' ,
		'width'   => KOUGABURARI_WIDTH ,
		'height'  => KOUGABURARI_HEIGHT ,
		'count'   => KOUGABURARI_COUNT ,
		'fx'      => 'fade' ,
		'timeout' => 4000 ,
		'speed'   => 1000 ,
		'c_pos'   => 'after' ,
		'id'      => 0 ,
		);

	var $efects = array(
		'blindX',
		'blindY',
		'blindZ',
		'cover',
		'curtainX',
		'curtainY',
		'fade',
		'fadeZoom',
		'growX',
		'growY',
		'none',
		'scrollUp',
		'scrollDown',
		'scrollLeft',
		'scrollRight',
		'scrollHorz',
		'scrollVert',
		'shuffle',
		'slideX',
		'slideY',
		'toss',
		'turnUp',
		'turnDown',
		'turnLeft',
		'turnRight',
		'uncover',
		'wipe',
		'zoom',
		'all',
	);

	var $foot_js = '';

	var $cache_name = ' Cache';

	var $expired = 3000;

	/*
	* Constructor
	*/
	function Kougaburari() {
		$this->__construct();
	}
	function __construct() {
		$this->init(__FILE__);

		$this->options_default['title'] = __('Slide Show', $this->textdomain_name);
		$this->options = $this->initOptions($this->getOptions());
		$this->cache_name = $this->plugin_name . $this->cache_name;

		// widget init ( register sidebar widget )
		add_action( $this->wp28 ? 'widgets_init' : 'init', array(&$this, 'widgetInit') );

		if (!is_admin()) {
			// script load
			add_action( 'wp_print_scripts', array(&$this,'addScripts') );
			add_action( 'wp_print_scripts', array(&$this,'dequeueScripts'), 11 );

			// shortcode handler
			add_shortcode( KOUGABURARI_SHORTCODE, array(&$this,'shortcodeHandler') );
		}
	}

	/*
	* plugin activation
	*/
	function activation(){
	}

	/*
	* plugin deactivation
	*/
	function deactivation(){
		delete_option($this->cache_name);
	}

	/*
	* Init Options
	*/
	function initOptions($options = '') {
		if (!is_array($options))
			$options = array();

		foreach ($this->options_default as $key => $val) {
			if ( isset($options[$key]) ) {
				switch ($key){
				case 'width':
				case 'height':
				case 'count':
				case 'timeout':
				case 'speed':
					$options[$key] = intval($options[$key]);
					$options[$key] = ( $options[$key] > 0 ? $options[$key] : $val );
					break;
				case 'id':
					$options[$key] = intval($options[$key]);
					break;
				case 'fx':
					$options[$key] = ( $this->chkEfect($options[$key]) ? $options[$key] : $val );
					break;
				case 'c_pos':
					$options[$key] = ( $options[$key] !== 'before' ? 'after' : 'before' );
					break;
				case 'title':
				case 'element':
				default:
					break;
				}
			} else {
				$options[$key] = $val;
			}
		}

		return $options;
	}

	/**
	* widget init
	*/
	function widgetInit() {
		global $wp_version;

		if ( version_compare($wp_version, "2.8", ">=") ) {
            // for WordPress 2.8+
			register_widget('Kougaburari_Widget');

		} else {
            // for WordPress 2.8-
			wp_register_sidebar_widget(
				$this->plugin_name ,
				__('Slide Show', $this->textdomain_name) ,
				array(&$this, 'widgetOutput') ,
				array(
					'classname' => 'widget_' . $this->plugin_name ,
					'description' => __('Slide Show', $this->textdomain_name) ,
				)
			);
			wp_register_widget_control(
				$this->plugin_name ,
				$this->plugin_name ,
				array(&$this, 'widgetRegister')
			);
		}
	}

	/**
	* get JavaScript
	*/
	function getScript( $options ) {
		$js_out  =
			"jQuery(function(){" .
			"var a=jQuery('#{$options['element']}-{$options['id']}');" .
			"var b=jQuery('#{$options['element']}-caption-{$options['id']}');" .
			"if(a.length>0){" .
			"a.cycle({" .
			"fx:'{$options['fx']}',speed:{$options['speed']},timeout:{$options['timeout']},pause:1," .
			"before:function(){b.html(jQuery('img',jQuery(this)).attr('title'));}" .
			"});" .
			"if(b.length==0){" .
			"a.".($options['c_pos'] !== 'before' ? 'after' : 'before')."('<div id=\"{$options['element']}-caption-{$options['id']}\" class=\"{$options['element']}-caption\"></div>');" .
			"b=jQuery('#{$options['element']}-caption-{$options['id']}');" .
			"}" .
			"b.html(jQuery('img',a)[0].title);" .
			"}" .
			"});\n" ;

		return($js_out);
	}

	/**
	* get Slide Show Images
	*/
	function getSlideShow( $options ){
		// add JavaScript
		$options = $this->initOptions( $options );
		$img_tag = $this->getImages( $options );
		$this->foot_js .= $this->getScript( $options );

//		$container = "<div id=\"{$options['element']}-{$options['id']}\" class=\"{$options['element']}\" style=\"width:{$options['width']};height:{$options['height']};\">$img_tag</div>\n";
		$container = "<div id=\"{$options['element']}-{$options['id']}\" class=\"{$options['element']}\" style=\"width:{$options['width']}px;height:{$options['height']}px;\">$img_tag</div>\n";
		$caption   = "<div id=\"{$options['element']}-caption-{$options['id']}\" class=\"{$options['element']}-caption\"></div>\n";

		if ( $options['c_pos'] !== 'before' )
			return $container . $caption;
		else
			return $caption . $container;
	}

	/*
	* Script loader
	*/
	function addScripts() {
		$this->addjQuery();    // regist jQuery
		wp_enqueue_script('jquery.cycle', $this->plugin_url.$this->cycle_js, array('jquery'), $this->cycle_ver);

		// set style sheet & JavaScript
		add_action('wp_head', array(&$this, 'addHead'));
	}

	function dequeueScripts() {
		global $wp_scripts;
		$wp_scripts->dequeue('jquery.chrome');
	}

	/*
	* style sheet & JavaScript (Head)
	*/
	function addHead() {
		$css_out = "\n" .
			"<style type=\"text/css\" media=\"all\"> /*<![CDATA[ */\n" .
			".{$this->options['element']} { overflow: hidden; cursor: pointer; }\n" .
			".{$this->options['element']}-box { margin: 0 auto; }\n" .
			".{$this->options['element']} img { margin: 0 auto; padding: 0; border: none; cursor: pointer; }\n" .
			"/* ]]>*/ </style>\n" ;

		echo $css_out;

		// set footer JavaScript
		add_action('wp_footer',array(&$this,'addFooter'));
	}

	/*
	* JavaScript (footer)
	*/
	function addFooter() {
		if ( !empty($this->foot_js) )
			$this->writeScript($this->foot_js, 'footer');
	}

	/*
	* shortcode API (Wordpress 2.5+)
	*/
	function shortcodeHandler($atts, $content = '') {
		extract( shortcode_atts( array(
			'width'   => $this->options_default['width'] ,
			'height'  => $this->options_default['height'] ,
			'count'   => $this->options_default['count'] ,
			'fx'      => $this->options_default['fx'] ,
			'timeout' => $this->options_default['timeout'] ,
			'speed'   => $this->options_default['speed'] ,
			'c_pos'   => $this->options_default['c_pos'] ,
			), $atts
		));

		return $this->getSlideShow(array(
			'id'      => $this->options['id']++ ,
			'width'   => $width ,
			'height'  => $height ,
			'count'   => $count ,
			'fx'      => $fx ,
			'timeout' => $timeout ,
			'speed'   => $speed ,
			'c_pos'   => $c_pos !== 'before' ? 'after' : 'before' ,
		));
	}

	/*
	* Efect check
	*/
	function chkEfect($fx) {
		return ( array_search( $fx, $this->efects, true ) !== FALSE );
	}

	/**
	* Widget register ( for WordPress 2.8- )
	*/
	function widgetRegister() {
		$option_name_pre = $this->plugin_name . '-';

		if (isset($_POST[$option_name_pre . 'submit'])) {
			$newoptions = $this->initOptions(array(
				'id'      => $this->options['id']++ ,
				'element' => $this->options['element'] ,
				'title'   => $_POST[$option_name_pre . 'title'] ,
				'width'   => (int) $_POST[$option_name_pre . 'width'] ,
				'height'  => (int) $_POST[$option_name_pre . 'height'] ,
				'count'   => (int) $_POST[$option_name_pre . 'count'] ,
				'fx'      => $_POST[$option_name_pre . 'fx'] ,
				'timeout' => (int) $_POST[$option_name_pre . 'timeout'] ,
				'speed'   => (int) $_POST[$option_name_pre . 'speed'] ,
				'c_pos'   => ( isset($_POST[$option_name_pre . 'c_pos']) && $_POST[$option_name_pre . 'c_pos'] !== 'before' ? 'after' : 'before' ) ,
			));
			if ( $this->options != $newoptions ) {
				$this->options = $newoptions;
				$this->updateOptions();
			}
		}

		$this->widgetSettingOutput( array(
			'title' => array(
				'id'   => $option_name_pre . 'title' ,
				'name' => $option_name_pre . 'title',
				'val'  => esc_attr($this->options['title']),
			) ,
			'width' => array(
				'id'   => $option_name_pre . 'width',
				'name' => $option_name_pre . 'width',
				'val'  => esc_attr($this->options['width']),
			) ,
			'height' => array(
				'id'   => $option_name_pre . 'height',
				'name' => $option_name_pre . 'height',
				'val'  => esc_attr($this->options['height']),
			) ,
			'count' => array(
				'id'   => $option_name_pre . 'count',
				'name' => $option_name_pre . 'count',
				'val'  => esc_attr($this->options['count']),
			) ,
			'fx' => array(
				'id'   => $option_name_pre . 'fx',
				'name' => $option_name_pre . 'fx',
				'val'  => esc_attr($this->options['fx']),
			) ,
			'timeout' => array(
				'id'   => $option_name_pre . 'timeout',
				'name' => $option_name_pre . 'timeout',
				'val'  => esc_attr($this->options['timeout']),
			) ,
			'speed' => array(
				'id'   => $option_name_pre . 'speed',
				'name' => $option_name_pre . 'speed',
				'val'  => esc_attr($this->options['speed']),
			) ,
			'c_pos' => array(
				'id'   => $option_name_pre . 'caption',
				'name' => $option_name_pre . 'caption',
				'val'  => $this->options['c_pos'] ,
			) ,
		));

		echo "<input type=\"hidden\" id=\"{$option_name_pre}submit\" name=\"{$option_name_pre}submit\" value=\"1\" />\n";
	}

	/**
	* Widget settings form
	*/
	function widgetSettingOutput($args) {
		extract($args, EXTR_SKIP);

		$input_template = '<input class="widefat" id="%s" name="%s" type="text" value="%s" %s/>';
		$label_template = '<label for="%s"> : %s</label>';

		// title
		echo '<p>';
		echo "<label for=\"{$title['name']}\">" . __('Title:');
		printf($input_template, $title['id'], $title['name'], $title['val'], '');
		echo '</label>';
		echo '</p>' . "\n";

		// width
		echo '<p>';
		printf($input_template, $width['id'], $width['name'], $width['val'], 'style="width: 3em;" ');
		printf($label_template, $width['name'], __('Width', $this->textdomain_name));
		echo '</p>' . "\n";

		// height
		echo '<p>';
		printf($input_template, $height['id'], $height['name'], $height['val'], 'style="width: 3em;" ');
		printf($label_template, $height['name'], __('Height', $this->textdomain_name));
		echo '</p>' . "\n";

		// count
		echo '<p>';
		printf($input_template, $count['id'], $count['name'], $count['val'], 'style="width: 3em;" ');
		printf($label_template, $count['name'], __('Count', $this->textdomain_name));
		echo '</p>' . "\n";

		// efect
		echo '<p>';
		echo "<select id=\"{$fx['id']}\" name=\"{$fx['name']}\">";
		foreach ( $this->efects as $efect ) {
			echo "<option value=\"$efect\"".($efect == $fx['val'] ? ' selected="selected"' : '').">$efect</option>";
		}
		echo '</select>';
		printf($label_template, $fx['name'], __('Effect', $this->textdomain_name));
		echo '</p>' . "\n";

		// timeout
		echo '<p>';
		printf($input_template, $timeout['id'], $timeout['name'], $timeout['val'], 'style="width: 3em;" ');
		printf($label_template, $timeout['name'], __('Timeout(Millisecond)', $this->textdomain_name));
		echo '</p>' . "\n";

		// speed
		echo '<p>';
		printf($input_template, $speed['id'], $speed['name'], $speed['val'], 'style="width: 3em;" ');
		printf($label_template, $speed['name'], __('Speed(Millisecond)', $this->textdomain_name));
		echo '</p>' . "\n";

		// c_pos
		echo '<p>';
		echo "<input type=\"radio\" id=\"{$c_pos['id']}_before\" name=\"{$c_pos['name']}\" value=\"before\" ". ($c_pos['val'] === 'before' ? 'checked="checked" ' : '') . '/>'.__('Before', $this->textdomain_name).' ';
		echo "<input type=\"radio\" id=\"{$c_pos['id']}_after\" name=\"{$c_pos['name']}\" value=\"after\" ". ($c_pos['val'] !== 'before' ? 'checked="checked" ' : '') . '/>'.__('After', $this->textdomain_name).' ';
		printf($label_template, $c_pos['name'], __('Caption position', $this->textdomain_name));
		echo '</p>' . "\n";
	}

	/**
	* Widget output
	*/
	function widgetOutput($args, $instance = '') {
		extract($args, EXTR_SKIP);

		if ( !is_array($instance) )
			$instance = $this->options;

		$title = trim($instance['title']);
		if($title == '')
			$title = __('Slide Show', $this->textdomain_name); 

		$instance['id'] = $this->options['id']++;

		echo $before_widget . "\n";
		if (!empty($title))
			echo $before_title . $title . $after_title . "\n";
		echo $this->getSlideShow( $instance );
		echo $after_widget . "\n";
	}

	/**
	* get Kougabu images
	*/
	function getKougabu($count, $width, $height, $id = '', $randomize = true) {
		if ( empty($id) )
			$id = md5($count . $width . $height);

		// cache
		$cache = (array) get_option($this->cache_name);
		if ( isset($cache[$id]) && $cache[$id]['expired'] > time() ) {
			$kougabu_images = maybe_unserialize($cache[$id]['kougabu_images']);
			if ( count($kougabu_images) > 0 ) {
				if ( $randomize )
					shuffle($kougabu_images);
				return $kougabu_images;
			}
		}

		// get kougabu images
		$kougabu_images = ( function_exists('kougabu_get_images')
			? kougabu_get_images(array(
				'max_width'  => $width ,
				'max_height' => $height ,
				'count'      => $count ,
				'before'     => '' ,
				'after'      => '' ,
				'echo'       => false ,
				'array'      => true ,
				'array_ext'  => true ,
			))
			: array()
		);

		if ( count($kougabu_images) > 0 ) {
			if ( $randomize )
				shuffle($kougabu_images);
			foreach ( $kougabu_images as $key => $info ) {
				$href   = esc_attr($info[0]['href']);
				$src    = esc_attr($info[0]['src']);
				$width  = (int) $info[0]['width'];
				$height = (int) $info[0]['height'];
				$alt    = esc_attr($info[0]['alt']);
				$title  = esc_html($info[0]['title']);
				$kougabu_images[$key][] = array(
					'href'   => $href ,
					'src'    => $src ,
					'width'  => (int) (!is_null($width)  ? $width  : KOUGABURARI_WIDTH  ) ,
					'height' => (int) (!is_null($height) ? $height : KOUGABURARI_HEIGHT ) ,
					'alt'    => $alt ,
					'title'  => $title,
				);
			}
		}
		$cache[$id] = array(
			'expired' => time() + $this->expired ,
			'kougabu_images' => maybe_serialize($kougabu_images) ,
		);
		update_option($this->cache_name, $cache);

		return $kougabu_images;
	}

	/**
	* get Images HTML tag
	*/
	function getImages( $options ){
		$options = $this->initOptions( $options );
		$id      = md5( $options['count'] .$options['width'] . $options['height'] . $options['id'] );
		$kougabu_images = $this->getKougabu( $options['count'], $options['width'], $options['height'], $id );
		$img_tag = '';
		if ( count($kougabu_images) > 0 ) {
			foreach ( $kougabu_images as $key => $image ) {
				if ( isset($image[1]) && preg_match('/^s?https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+$/i', $image[1]['src']) ) {
					$img_tag .= '<div';
					$img_tag .= " class=\"{$options['element']}-box\"";
//					$img_tag .= " style=\"display:none;width:{$options['width']};height:{$options['height']};\"";
					$img_tag .= " style=\"display:none;width:{$options['width']}px;height:{$options['height']}px;\"";
					$img_tag .= '>';
					$img_tag .= '<a';
					$img_tag .= ' href="' . addslashes($image[1]['href']) . '"';
					$img_tag .= '>';
					$img_tag .= '<img';
					$img_tag .= ' src="' . addslashes($image[1]['src']) . '"';
//					$img_tag .= ' width="' . (int) $image[1]['width'] . '"';
					$img_tag .= ' width="' . (int) $image[1]['width'] . 'px"';
//					$img_tag .= ' height="' . (int) $image[1]['height'] . '"';
					$img_tag .= ' height="' . (int) $image[1]['height'] . 'px"';
					$img_tag .= ' alt="' . addslashes($image[1]['alt']) . '"';
					$img_tag .= ' title="' . addslashes($image[1]['title']) . '"';
					$img_tag .= ' />';
					$img_tag .= '</a>';
					$img_tag .= '</div>';
				}
			}
		}
		return $img_tag;
	}

	/**
	* get JSON
	*/
	function getJSON() {
		$id     = (int) (isset($_GET['json']) && is_numeric($_GET['json']) ? $_GET['json'] : 0 );
		$count  = (int) (isset($_GET['c']) && is_numeric($_GET['c']) ? $_GET['c'] : KOUGABURARI_COUNT );
		$width  = (int) (isset($_GET['w']) && is_numeric($_GET['w']) ? $_GET['w'] : KOUGABURARI_WIDTH );
		$height = (int) (isset($_GET['h']) && is_numeric($_GET['h']) ? $_GET['h'] : KOUGABURARI_HEIGHT );
		$callback = wp_specialchars(attribute_escape(isset($_GET['callback']) ? $_GET['callback'] : ''));

		$id = md5( $count . $width . $height . $id . $callback );

		$kougabu_images = $this->getKougabu($count, $width, $height, $id);
		if ( count($kougabu_images) > 0 ) {
			$out = '';
			foreach ( $kougabu_images as $key => $image ) {
				if ( isset($image[1]) ) {
					$out .= ( !empty($out) ? ",\n" : '');
					$out .= '{';
					$out .= 'id:"' . $key . '",';
					$out .= 'href:"' . addslashes($image[1]['href']) . '",';
					$out .= 'src:"' . addslashes($image[1]['src']) . '",';
//					$out .= 'width:"' . $image[1]['width'] . '",';
					$out .= 'width:"' . $image[1]['width'] . 'px",';
//					$out .= 'height:"' . $image[1]['height'] . '",';
					$out .= 'height:"' . $image[1]['height'] . 'px",';
					$out .= 'alt:"' . addslashes($image[1]['alt']) . '",';
					$out .= 'title:"' . addslashes($image[1]['title']) . '"';
					$out .= '}';
				}
			}

			$charset = get_bloginfo('charset');
			nocache_headers();
			if ( !empty($callback) ) {
				header("Content-Type: text/javascript; charset=$charset");
				echo "$callback([\n$out\n]);";
			} else {
				header("Content-Type: application/json; charset=$charset");
				echo "[\n$out\n]";
			}
			die();

		} else {
			header("HTTP/1.0 404 Not Found");
			wp_die("404 Not Found");
		}
	}
}

//**********************************************************************************
// Widget Class ( for WordPress 2.8+ )
//**********************************************************************************
if (version_compare($wp_version, "2.8", ">=")) {
class Kougaburari_Widget extends WP_Widget {
	/*
	* Constructor
	*/
	function Kougaburari_Widget() {
		global $kougaburari;

		if (!isset($kougaburari))
			$kougaburari = new KougaburariController;

		$widget_ops = array(
			'classname' => 'widget_' . strtolower($kougaburari->plugin_name) ,
			'description' =>  __('Slide Show', $kougaburari->textdomain_name) ,
		);
		$this->WP_Widget(
			$kougaburari->plugin_name ,
			__('Slide Show', $kougaburari->textdomain_name) ,
			$widget_ops
		);
	}

	/**
	* Widget output
	*/
	function widget($args, $instance) {
		global $kougaburari;

		$kougaburari->widgetOutput($args, $instance);
	}

	/**
	* Widget settings update
	*/
	function update($new_instance, $old_instance) {
		global $kougaburari;

		$instance = $old_instance;
		$new_instance = wp_parse_args(
			(array) $new_instance ,
			$kougaburari->options_default
		);
		$instance = $kougaburari->initOptions($new_instance);

		return $instance;
	}

	/**
	* Widget settings form
	*/
	function form($instance) {
		global $kougaburari;

		$instance = wp_parse_args(
			(array) $instance ,
			$kougaburari->options_default
		);

		$kougaburari->widgetSettingOutput( array(
			'title' => array(
				'id'=>$this->get_field_id('title'),
				'name'=>$this->get_field_name('title'),
				'val'=>attribute_escape($instance['title']),
			) ,
			'width' => array(
				'id'=>$this->get_field_id('width'),
				'name'=>$this->get_field_name('width'),
				'val'=>attribute_escape($instance['width']),
			) ,
			'height' => array(
				'id'=>$this->get_field_id('height'),
				'name'=>$this->get_field_name('height'),
				'val'=>attribute_escape($instance['height']),
			) ,
			'count' => array(
				'id'=>$this->get_field_id('count'),
				'name'=>$this->get_field_name('count'),
				'val'=>attribute_escape($instance['count']),
			) ,
			'fx' => array(
				'id'=>$this->get_field_id('fx'),
				'name'=>$this->get_field_name('fx'),
				'val'=>attribute_escape($instance['fx']),
			) ,
			'timeout' => array(
				'id'=>$this->get_field_id('timeout'),
				'name'=>$this->get_field_name('timeout'),
				'val'=>attribute_escape($instance['timeout']),
			) ,
			'speed' => array(
				'id'=>$this->get_field_id('speed'),
				'name'=>$this->get_field_name('speed'),
				'val'=>attribute_escape($instance['speed']),
			) ,
			'c_pos' => array(
				'id'=>$this->get_field_id('c_pos'),
				'name'=>$this->get_field_name('c_pos'),
				'val'=>attribute_escape($instance['c_pos']),
			) ,
		));

	}
}
}

//**********************************************************************************
// Template tag
//**********************************************************************************
function kougaburari($width = KOUGABURARI_WIDTH, $height = KOUGABURARI_HEIGHT, $count = KOUGABURARI_COUNT, $fx = 'fade', $timeout = 4000, $speed = 1000, $c_pos = 'after', $echo = true) {
	global $kougaburari;

	$slide_show = $kougaburari->getSlideShow(array(
		'width'   => (int) $width ,
		'height'  => (int) $height ,
		'count'   => (int) $count ,
		'fx'      => $fx ,
		'timeout' => (int) $timeout ,
		'speed'   => (int) $speed ,
		'c_pos'   => $c_pos ,
		'id'      => $kougaburari->options['id']++ ,
	));

	if ($echo)
		echo $slide_show;
	else
		return $slide_show;
}

//**********************************************************************************
// Go! Go! Go!
//**********************************************************************************
$kougaburari = new Kougaburari;

// plugin activation & deactivation
if ( function_exists('register_activation_hook') )
	register_activation_hook(__FILE__, array(&$kougaburari, 'activation'));
if ( function_exists('register_deactivation_hook') )
	register_deactivation_hook(__FILE__, array(&$kougaburari, 'deactivation'));
?>