<?php
/*
Plugin Name: Google Maps Anywhere
Version: 1.2.6.3
Plugin URI: http://wppluginsj.sourceforge.jp/googlemaps-anywhere/
Description: Add Google Maps to your posts and pages.(Only for WordPress 2.5+)
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: google-maps-anywhere
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2008 - 2011 wokamoto (email : wokamoto1973@gmail.com)

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

// Wordpress 2.5+
global $wp_version;
if (version_compare($wp_version, "2.5", "<"))
	return false;

if (!defined('GM_ANYWHERE_DEBUG_MODE'))
	define('GM_ANYWHERE_DEBUG_MODE',	false);	// debug mode
if (!defined('GM_ANYWHERE_SV_ENABLE'))
	define('GM_ANYWHERE_SV_ENABLE',		true);	// Google Maps street view enabled
if (!defined('GM_ANYWHERE_EV_ENABLE'))
	define('GM_ANYWHERE_EV_ENABLE',		false);	// Google Earth view enabled

//if (!defined('GM_ANYWHERE_API_KEY'))
//	define('GM_ANYWHERE_API_KEY',		'XXXXX');
if (!defined('GM_ANYWHERE_JSAPI_URL'))
	define('GM_ANYWHERE_JSAPI_URL',		'http://www.google.com/jsapi');
if (!defined('GM_ANYWHERE_MAP_URL'))
	define('GM_ANYWHERE_MAP_URL',		'http://maps.google.com/maps');
if (!defined('GM_ANYWHERE_STATICMAP_URL'))
	define('GM_ANYWHERE_STATICMAP_URL',	'http://maps.google.com/staticmap');

if (!defined('GM_ANYWHERE_SHORTCODE'))
	define('GM_ANYWHERE_SHORTCODE',		'googlemap');
if (!defined('GM_ANYWHERE_ELEMENT'))
	define('GM_ANYWHERE_ELEMENT',		'div.googlemaps');
if (!defined('GM_ANYWHERE_META_FIELD_NAME'))
	define('GM_ANYWHERE_META_FIELD_NAME',	'Lat_Long');

// User Level Permission -- Subscriber = 0,Contributor = 1,Author = 2,Editor= 7,Administrator = 9
if (!defined('GM_ANYWHERE_OPTION_ACCESS_LEVEL'))
	define('GM_ANYWHERE_OPTION_ACCESS_LEVEL',9);	// Option Access Level

// default Value
if (!defined('GM_ANYWHERE_DEFAULT_LAT'))
	define('GM_ANYWHERE_DEFAULT_LAT',	35.5000);
if (!defined('GM_ANYWHERE_DEFAULT_LNG'))
	define('GM_ANYWHERE_DEFAULT_LNG',	139.0000);
if (!defined('GM_ANYWHERE_DEFAULT_WIDTH'))
	define('GM_ANYWHERE_DEFAULT_WIDTH',	'300px');
if (!defined('GM_ANYWHERE_DEFAULT_HEIGHT'))
	define('GM_ANYWHERE_DEFAULT_HEIGHT',	'150px');
if (!defined('GM_ANYWHERE_DEFAULT_ZOOM'))
	define('GM_ANYWHERE_DEFAULT_ZOOM',	15);
if (!defined('GM_ANYWHERE_DEFAULT_TYPE'))
	define('GM_ANYWHERE_DEFAULT_TYPE',	'G_NORMAL_MAP');
if (!defined('GM_ANYWHERE_DEFAULT_ALIGN'))
	define('GM_ANYWHERE_DEFAULT_ALIGN', 'none');

// default Value (street view) -- Tokyo Tower
if (!defined('GM_ANYWHERE_SV_DEFAULT_LAT'))
	define('GM_ANYWHERE_SV_DEFAULT_LAT',	35.657927);
if (!defined('GM_ANYWHERE_SV_DEFAULT_LNG'))
	define('GM_ANYWHERE_SV_DEFAULT_LNG',	139.745684);
if (!defined('GM_ANYWHERE_SV_DEFAULT_YAW'))
	define('GM_ANYWHERE_SV_DEFAULT_YAW',	0);
if (!defined('GM_ANYWHERE_SV_DEFAULT_PITCH'))
	define('GM_ANYWHERE_SV_DEFAULT_PITCH',	0);
if (!defined('GM_ANYWHERE_SV_DEFAULT_ZOOM'))
	define('GM_ANYWHERE_SV_DEFAULT_ZOOM',	0);

// default Value (earth view) -- Tokyo Tower
if (!defined('GM_ANYWHERE_EV_DEFAULT_LAT'))
	define('GM_ANYWHERE_EV_DEFAULT_LAT',	35.657927);
if (!defined('GM_ANYWHERE_EV_DEFAULT_LNG'))
	define('GM_ANYWHERE_EV_DEFAULT_LNG',	139.745684);
if (!defined('GM_ANYWHERE_EV_DEFAULT_ALTITUDE'))
	define('GM_ANYWHERE_EV_DEFAULT_ALTITUDE',	0);
if (!defined('GM_ANYWHERE_EV_DEFAULT_HEADING'))
	define('GM_ANYWHERE_EV_DEFAULT_HEADING',	0);
if (!defined('GM_ANYWHERE_EV_DEFAULT_STRAIGHT'))
	define('GM_ANYWHERE_EV_DEFAULT_STRAIGHT',	60);
if (!defined('GM_ANYWHERE_EV_DEFAULT_RANGE'))
	define('GM_ANYWHERE_EV_DEFAULT_RANGE',	5000);

// mobile width & height
if (!defined('GM_ANYWHERE_MOBILE_WIDTH'))
	define('GM_ANYWHERE_MOBILE_WIDTH',	128);
if (!defined('GM_ANYWHERE_MOBILE_HEIGHT'))
	define('GM_ANYWHERE_MOBILE_HEIGHT',	80);

// edit width & height
if (!defined('GM_ANYWHERE_EDIT_WIDTH'))
	define('GM_ANYWHERE_EDIT_WIDTH',	620);
if (!defined('GM_ANYWHERE_EDIT_HEIGHT'))
	define('GM_ANYWHERE_EDIT_HEIGHT',	335);

if (!class_exists('wokController') || !class_exists('wokScriptManager'))
	require(dirname(__FILE__).'/includes/common-controller.php');

class GoogleMapsAnywhere extends wokController {
	var $plugin_name = 'google-maps-anywhere';
	var $plugin_js   = 'js/google-maps-anywhere.min.js';
	var $plugin_ver  = '1.2.6';

	// Deafault Options
	var $options_default = array(
		  'map_default' => array(
			'lat'    => GM_ANYWHERE_DEFAULT_LAT ,
			'lng'    => GM_ANYWHERE_DEFAULT_LNG ,
			'width'  => GM_ANYWHERE_DEFAULT_WIDTH ,
			'height' => GM_ANYWHERE_DEFAULT_HEIGHT ,
			'zoom'   => GM_ANYWHERE_DEFAULT_ZOOM ,
			'type'   => GM_ANYWHERE_DEFAULT_TYPE ,
			'align'  => GM_ANYWHERE_DEFAULT_ALIGN ,
			) ,
		'sv_default' => array(
			'lat'    => GM_ANYWHERE_SV_DEFAULT_LAT ,
			'lng'    => GM_ANYWHERE_SV_DEFAULT_LNG ,
			'yaw'    => GM_ANYWHERE_SV_DEFAULT_YAW ,
			'pitch'  => GM_ANYWHERE_SV_DEFAULT_PITCH ,
			'zoom'   => GM_ANYWHERE_SV_DEFAULT_ZOOM ,
			) ,
		'ev_default' => array(
			'lat'      => GM_ANYWHERE_EV_DEFAULT_LAT ,
			'lng'      => GM_ANYWHERE_EV_DEFAULT_LNG ,
			'altitude' => GM_ANYWHERE_EV_DEFAULT_ALTITUDE ,
			'heading'  => GM_ANYWHERE_EV_DEFAULT_HEADING ,
			'straight' => GM_ANYWHERE_EV_DEFAULT_STRAIGHT ,
			'range'    => GM_ANYWHERE_EV_DEFAULT_RANGE ,
			) ,
		'mapsURL' => 'http://maps.google.com/maps' ,
		);

	var $api_key = false;
	var $added_footer = false;
	var $lang;

	var $js_load = false;

	/*
	* Constructor
	*/
	function GoogleMapsAnywhere() {
		$this->__construct();
	}
	function __construct() {
		$this->init(__FILE__);

		// Wordpress 2.5+
		if (!$this->wp25)
			return false;

		$this->lang = (defined('WPLANG') ? WPLANG : 'ja');
		$this->setAPIKey();

		$wk_options = $this->getOptions();
		$this->options = $this->_init_options($wk_options);
		if (!is_array($wk_options))
			$this->updateOptions();

		if ( is_admin() ) {
			// set JavaScript (admin head)
			add_action('admin_print', array(&$this, 'addAdminScripts'));
			add_action('admin_head',  array(&$this, 'addAdminHead'));

			// media button support
			add_action('media_buttons', array(&$this, 'addMediaButton'), 20);

			add_action('media_upload_googlemaps', 'media_upload_googlemaps');
			if (defined('GM_ANYWHERE_SV_ENABLE') && GM_ANYWHERE_SV_ENABLE)
				add_action('media_upload_googlemaps_streetview', 'media_upload_googlemaps_streetview');
			if (defined('GM_ANYWHERE_EV_ENABLE') && GM_ANYWHERE_EV_ENABLE)
				add_action('media_upload_googlemaps_eathview', 'media_upload_googlemaps_eathview');
			add_action('media_upload_googlemaps_option', 'media_upload_googlemaps_option');

			if ($this->api_key != false) {
				add_action('admin_head_media_upload_googlemaps_form', array($this, 'addMediaHead'));
				if (defined('GM_ANYWHERE_SV_ENABLE') && GM_ANYWHERE_SV_ENABLE)
					add_action('admin_head_media_upload_googlemaps_streetview_form', array($this, 'addMediaHeadSv'));
				if (defined('GM_ANYWHERE_EV_ENABLE') && GM_ANYWHERE_EV_ENABLE)
					add_action('admin_head_media_upload_googlemaps_earthview_form', array($this, 'addMediaHeadEv'));
			}

			// admin_menu
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_filter('plugin_action_links', array(&$this, 'plugin_setting_links'), 10, 2 );

		} elseif ($this->api_key != false) {
			// script loader
			add_action('template_redirect', array(&$this,'addScripts'));
			//add_action('template_redirect', array(&$this,'dequeueScripts'), 11);

			// set style sheet & JavaScript
			add_action('wp_head', array(&$this, 'addHead'));

			// shortcode API
			add_shortcode(GM_ANYWHERE_SHORTCODE, array(&$this, 'shortcodeHandler'));
			add_filter('the_content', array(&$this, 'parse_shortcodes'), 7);
			add_filter('the_excerpt', array(&$this, 'parse_shortcodes'), 7);

			// for Ktai style
			if ( !class_exists('Lightweight_Google_Maps') ) {
				add_filter('the_content', array(&$this, 'addGoogleMap'), 5);
				add_filter('the_excerpt', array(&$this, 'addGoogleMap'), 5);
			}
			add_filter('image_to_link/ktai_style.php', array(&$this, 'keepGmapImage'), 10, 3);
		}

	}

	/*
	* Init Options
	*/
	function _init_options($options = '') {
		if (!is_array($options))
			$options = array();

		$this->options_default['mapsURL'] = __('http://maps.google.com/maps', $this->textdomain_name);
		$wk_options = get_option(' Options');
		if (is_array($wk_options)) {
			foreach ($this->options_default as $key => $val) {
				$this->options_default[$key] = (isset($wk_options[$key]) ? $wk_options[$key] : $val);
			}
		}

		foreach ($this->options_default as $key => $val) {
			$options[$key] = (isset($options[$key]) ? $options[$key] : $val);
		}

		return $options;
	}

	/*
	* get Google Maps API Key
	*/
	function setAPIKey() {
		$this->api_key = get_option('googlemaps_api_key');
		if (!$this->api_key)
			$this->api_key = get_option('yf_google_api_key');
		if (!$this->api_key)
			$this->api_key = (defined('GM_ANYWHERE_API_KEY') ? GM_ANYWHERE_API_KEY : false);
		return $this->api_key;
	}

	/*
	* Script loader
	*/
	function addScripts() {
		$this->js_load = $this->haveShortCode();
		if (!$this->js_load)
			return;
		$this->addjQuery();	// regist jQuery
		wp_enqueue_script('google.loader', GM_ANYWHERE_JSAPI_URL.'?key='.$this->api_key);
		add_filter('script_loader_src', array($this,'scriptSrcCleanup'));
	}

	//function dequeueScripts() {
	//	global $wp_scripts;
	//	$wp_scripts->dequeue('jquery.chrome');
	//}

	function scriptSrcCleanup($src) {
		return (strstr($src, GM_ANYWHERE_JSAPI_URL) != false ? preg_replace("/(\?|\&|\&(amp|#038);)ver=.*$/i", "", $src) : $src);
	}

	/*
	* style sheet & JavaScript (Head)
	*/
	function addHead() {
		if (!$this->js_load)
			return;

		global $Lw_GoogleMaps;

		$element = (
			defined('LWGM_EACH_MAP_CLASS')
			? 'div.'.LWGM_EACH_MAP_CLASS
			: GM_ANYWHERE_ELEMENT
			);

		$css_out = '';
		$js_out  = '';

		if (!isset($Lw_GoogleMaps) || (defined('GM_ANYWHERE_DEBUG_MODE') && GM_ANYWHERE_DEBUG_MODE)) {
			$css_out .= '<style type="text/css" media="all"> /*<![CDATA[ */'."\n";
			$css_out .= $element.'{margin:1em auto;width:'.$this->options['map_default']['width'].';height:'.$this->options['map_default']['height'].';clear:both;border:1px solid #999;line-height:1.25em;text-align:center;overflow:hidden;}'."\n";
			$css_out .= $element.' img, '. $element.' object {margin:0;padding:0;border:0 none;max-width:none;max-height:none;background-color:transparent;}'."\n";
			$css_out .= $element.' div {overflow:visible;}'."\n";
			$css_out .= $element.' .infowindow {text-align:left;font-size:0.88em;}'."\n";
			$css_out .= $element.' p {margin:0;text-indent:0;text-align:left;font-size:0.75em;}'."\n";
			$css_out .= '.gmalign-left {margin:1em 0;float:left;}'."\n";
			$css_out .= '.gmalign-right {margin:1em 0;float:right;}'."\n";
			$css_out .= '/* ]]>*/ </style>'."\n";
		}

		$js_out .= 'var googlemapsAnywhereL10n = {';
		$js_out .= ' language:"'.$this->lang.'"';
		$js_out .= ',markerTitle:"'.__('Move to the Google Maps.', $this->textdomain_name).'"';
		$js_out .= ',cssPath:"'.$element.'"';
		$js_out .= ',errMsgNoData:"' .__('Error: ', $this->textdomain_name).__("No panorama data was found.", $this->textdomain_name).'"';
		$js_out .= ',errMsgNoFlash:"'.__('Error: ', $this->textdomain_name).__("Flash doesn't appear to be supported by your browser.", $this->textdomain_name).'"';
		$js_out .= ',errMsgUnknown:"'.__('Error: ', $this->textdomain_name).__("Unknown Error.", $this->textdomain_name).'"';
		$js_out .= ',mapsURL:"'.$this->options['mapsURL'].'"';
		$js_out .= '};'."\n";

		if ($css_out != '')
			echo $css_out;
		if ($js_out != '')
			$this->writeScript($js_out, 'head');
	}

	/*
	* JavaScript (Footer)
	*/
	function addFooter() {
		if (!$this->js_load || $this->added_footer)
			return;

		$element = (
			  defined('LWGM_EACH_MAP_CLASS')
			? 'div.'.LWGM_EACH_MAP_CLASS
			: GM_ANYWHERE_ELEMENT
			);

		$tag_out = '';
		$js_out  = '';

		$tag_out .= "<script type=\"text/javascript\" src=\"{$this->plugin_url}{$this->plugin_js}\"></script>\n";

		if ($tag_out != '')
			echo $tag_out;
		if ($js_out != '')
			$this->writeScript($js_out, 'footer');

		$this->added_footer = true;
	}

	/*
	* JavaScript (Admin Head)
	*/
	function addAdminScripts() {
		$this->addjQuery();	// regist jQuery
	}

	function addAdminHead() {
		if (strstr($_SERVER['PHP_SELF'], 'post.php') || strstr($_SERVER['PHP_SELF'], 'page-new.php') || strstr($_SERVER['PHP_SELF'], 'post-new.php') || strstr($_SERVER['PHP_SELF'], 'page.php')) {
			$out  = "<script type=\"text/javascript\">//<![CDATA[ \n";
			$out .= "function send_meta_value(k, v) {";
			$out .= "jQuery('#metakeyinput').val(k);";
			$out .= "jQuery('#metavalue').val(v);";
			$out .= "jQuery('#addmetasub').trigger('click');";
			$out .= "}\n";
			$out .= "var gm_anyehere = {";
			$out .= " params:{point:false, zoom:false, pov:false, placename:'', width:'".$this->options['map_default']['width']."',height:'".$this->options['map_default']['height']."'}";
			$out .= ",set:function(n, v){this.params[n] = v}";
			$out .= ",get:function(n){return this.params[n]}";
			$out .= "};\n";
			$out .= "// ]]></script>\n";
			echo $out;
		}
	}

	/*
	* Wordpress 2.5 - media button support
	*/
	function addMediaButton() {
		global $post_ID, $temp_ID;

		if (!preg_match('/(post|page)(\-new)?\.php/i', $_SERVER['PHP_SELF']))
			return;

		$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);
		$media_upload_iframe_src = "media-upload.php?post_id={$uploading_iframe_ID}";

		$type = "googlemaps";
		$tab  = ($this->api_key != false || !current_user_can(GM_ANYWHERE_OPTION_ACCESS_LEVEL)
			? "googlemaps"
			: "googlemaps_option"
			);
		$googlemaps_iframe_src = apply_filters('media_upload_googlemaps_iframe_src', "{$media_upload_iframe_src}&amp;type={$type}&amp;tab={$tab}");
		$googlemaps_title = __('Add Google Maps', $this->textdomain_name);
		$link_markup = "<a href=\"{$googlemaps_iframe_src}&amp;TB_iframe=true&amp;keepThis=true&amp;height=500&amp;width=640\" class=\"thickbox\" title=\"{$googlemaps_title}\"><img src=\"{$this->plugin_url}images/googlemaps-media.gif\" alt=\"{$googlemaps_title}\" /></a>\n";

		echo $link_markup;
	}

	function googlemapsMediaBrowse() {
		global $type, $tab;

		if ($this->api_key === false)
			return;

		$post_id = intval(
			isset($_POST['post_id'])
			? $_POST['post_id']
			: ( isset($_GET['post_id']) ? $_GET['post_id'] : '0' )
			);
		$form_action_url = trailingslashit(get_bloginfo('wpurl'))."wp-admin/media-upload.php?type={$type}&amp;tab={$tab}&amp;post_id={$post_id}";
		$callback = "type_form_{$type}";

		$width  = GM_ANYWHERE_EDIT_WIDTH;
		$height = GM_ANYWHERE_EDIT_HEIGHT;
		$lat    = $this->options['map_default']['lat'];
		$lng    = $this->options['map_default']['lng'];
		$zoom   = 4;
		$default_align = GM_ANYWHERE_DEFAULT_ALIGN;

?>
<div id="map" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;margin:0.5em auto 0px;padding:0px;"></div>

<form method="post" action="<?php echo attribute_escape($form_action_url); ?>" class="media-upload-form type-form validate" id="<?php echo $type; ?>-form">
	<table class="optiontable form-table" style="margin-top:0"><tbody>
		<tr>
			<th valign="top" scope="row" class="label" style="padding:0">
				<p><label for="align"><?php _e('Place', $this->textdomain_name); ?></label></p>
			</th>
			<td class="field" style="padding:0" colspan="2">
				<?php _e('Place Name', $this->textdomain_name); ?>: <input type="text" name="placename" value="" />
				<br />
				<?php _e('Lat', $this->textdomain_name); ?>: <input type="text" name="lat" value="" />
				<?php _e('Lng', $this->textdomain_name); ?>: <input type="text" name="lng" value="" />
			</td>
		</tr>
		<tr class="align">
			<th valign="top" scope="row" class="label" style="padding:0">
				<p><label for="align"><?php _e('Alignment'); ?></label></p>
			</th>
			<td class="field" style="padding:0;text-align:left;" colspan="2">
				<input name="align" id="align-none" value="none" type="radio"<?php echo ($default_align == 'none' ? ' checked="checked"' : ''); ?> />
				<label for="align-none" class="align image-align-none-label"><?php _e('None'); ?></label>
				<input name="align" id="align-left" value="left" type="radio"<?php echo ($default_align == 'left' ? ' checked="checked"' : ''); ?> />
				<label for="align-left" class="align image-align-left-label"><?php _e('Left'); ?></label>
				<input name="align" id="align-center" value="center" type="radio"<?php echo ($default_align == 'center' ? ' checked="checked"' : ''); ?> />
				<label for="align-center" class="align image-align-center-label"><?php _e('Center'); ?></label>
				<input name="align" id="align-right" value="right" type="radio"<?php echo ($default_align == 'right' ? ' checked="checked"' : ''); ?> />
				<label for="align-right" class="align image-align-right-label"><?php _e('Right'); ?></label>
			</td>
		</tr>
		<tr>
			<th valign="top" scope="row" class="label" style="padding:0">
				<p><label for="align"><?php _e('Size'); ?></label></p>
			</th>
			<td class="field" style="padding:0" colspan="2">
				<?php _e('Width', $this->textdomain_name); ?>: <input type="text" name="width" size="7" value="" />
				<?php _e('Height', $this->textdomain_name); ?>: <input type="text" name="height" size="7" value="" />
				<?php _e('Zoom', $this->textdomain_name); ?>: <input type="text" name="zoom" size="7" value="" />
			</td>
		</tr>
		<tr>
			<th valign="top" scope="row" class="label" style="padding:0">
				<p><label for="align"><?php _e('KML', $this->textdomain_name); ?></label></p>
			</th>
			<td class="field" style="padding:0">
				<input type="text" name="kml" size="60" value="" />
			</td>
			<td style="padding:0 10px">
				<input type="submit" class="button button-primary" name="insertonlybutton" value="<?php echo attribute_escape(__('Insert into Post')); ?>" /><br />
			</td>
		</tr>
		<tr>
			<th valign="top" scope="row" class="label" style="padding:10px 0">
				<p><label for="align"><?php _e('Custom Fields'); ?></label></p>
			</th>
			<td class="field" style="padding:10px 0">
				<?php echo attribute_escape(__('Key')); ?>: <input type="text" name="metakeyname" size="7" value="<?php echo (defined('GEO_META_FIELD_NAME') ? GEO_META_FIELD_NAME : GM_ANYWHERE_META_FIELD_NAME); ?>" />
				<?php echo attribute_escape(__('Value')); ?>: <input type="text" name="metavalue" value="" />
			</td>
			<td style="padding:10px">
				<input type="submit" class="button button-primary" name="sendmetavalbutton" value="<?php echo attribute_escape(__('Add Custom Field')); ?>" />
			</td>
		</tr>
	</tbody></table>
</form>

<script type="text/javascript">// <![CDATA[
var onload_callback = function(func){
	if (jQuery.browser.mozilla) {
		var ver = jQuery.browser.version.split('.');
		if ( Number(ver.length > 1 ? ver[0] + '.' + ver[1] : ver[0]) < 1.9 && top.jQuery('iframe').size() > 0 ) {
			var loadEvent = top.jQuery('iframe#TB_iframeContent')[0].onload;
			window.onload = function(){loadEvent(); func();};
		} else {
			google.setOnLoadCallback(func);
		}
	} else {
		google.setOnLoadCallback(func);
	}
};

onload_callback(function() {
	var gmap, gmarker, ggeoxml;

	var cur_point = new google.maps.LatLng(
		 top.gm_anyehere.get('point')!=false ? top.gm_anyehere.get('point').lat : <?php echo $lat; ?>
		,top.gm_anyehere.get('point')!=false ? top.gm_anyehere.get('point').lng : <?php echo $lng; ?>
		);
	var cur_zoom = Number(top.gm_anyehere.get('zoom'));
	var cur_place = top.gm_anyehere.get('placename');

	var cur_width = top.gm_anyehere.get('width');
	var cur_height = top.gm_anyehere.get('height');

	var cur_kml = top.gm_anyehere.get('kml');

	var cur_align = top.gm_anyehere.get('align');
	if ( cur_align == '')
		cur_align = '<?php echo GM_ANYWHERE_DEFAULT_ALIGN; ?>';

	if (cur_point == false)
		cur_point = new GLatLng(<?php echo $lat; ?>, <?php echo $lng; ?>);
	if (cur_zoom == false)
		cur_zoom = <?php echo $zoom; ?>;

	var form = jQuery('#<?php echo $type; ?>-form').submit(function() {return false;});
	var in_width = jQuery('input[name=width]:first', form);
	var in_height = jQuery('input[name=height]:first', form);
	var in_placename = jQuery('input[name=placename]:first', form);
	var in_lat = jQuery('input[name=lat]:first', form);
	var in_lng = jQuery('input[name=lng]:first', form);
	var in_zm  = jQuery('input[name=zoom]:first', form);
	var in_kml = jQuery('input[name=kml]:first', form);
	var in_align = jQuery('input[name=align]', form);
	var in_metaval = jQuery('input[name=metavalue]:first', form);
	var in_metakey = jQuery('input[name=metakeyname]:first', form);

	var to_number = function(v) { return Number(isNaN(v) ? v.replace(/[^\-\d\.]/, '') : v); };
	var check_url = function(v) { return (/s?https?:\/\/[-_.!~*\'\(\)a-zA-Z0-9;\/?:\@&=+\$,%#]+/i).test(v); };

	var set_params = function(lat, lng, zm, place, width, height, kml, align) {
		lat = to_number(lat);
		lng = to_number(lng);
		zm  = to_number(zm);
		cur_point  = new GLatLng(lat, lng);
		cur_zoom   = zm;
		cur_place  = place;
		cur_width  = width;
		cur_height = height;
		cur_kml    = ( check_url(kml) ? kml : '' );
		cur_align  = align;
		top.gm_anyehere.set('point', {lat:lat, lng:lng});
		top.gm_anyehere.set('zoom', cur_zoom);
		top.gm_anyehere.set('placename', cur_place);
		top.gm_anyehere.set('width',  cur_width);
		top.gm_anyehere.set('height', cur_height);
		top.gm_anyehere.set('kml', cur_kml);
		top.gm_anyehere.set('align', cur_align);
	}

	var point_changed = function(){
		cur_point = new GLatLng(to_number(in_lat.val()), to_number(in_lng.val()));
		gmap.setCenter(cur_point, cur_zoom);
		gmarker.setPoint(cur_point);
		in_lat.val(cur_point.lat());
		in_lng.val(cur_point.lng());
		in_metaval.val(cur_point.toUrlValue());
		set_params(lat, lng, cur_zoom, cur_place, cur_width, cur_height, in_kml.val(), in_align.val());
	}

	in_width.change(function(){
		top.gm_anyehere.set('width', jQuery(this).val());
	}).val(cur_width);
	in_height.change(function(){
		top.gm_anyehere.set('height', jQuery(this).val());
	}).val(cur_height);
	in_placename.change(function(){
		top.gm_anyehere.set('placename', jQuery(this).val());
	}).val(cur_place);
	in_lat.change(point_changed).val(cur_point.lat());
	in_lng.change(point_changed).val(cur_point.lng());
	in_zm.change(function(){
		if (!isNaN(in_zm.val())) {
			cur_zoom = Number(isNaN(jQuery(this).val()) ? jQuery(this).val().replace(/[^0-9\.]/, '') : jQuery(this).val());
			gmap.setCenter(cur_point, cur_zoom);
			in_zm.val(gmap.getZoom());
			top.gm_anyehere.set('zoom', gmap.getZoom());
		} else {
			jQuery(this).val(cur_zoom);
		}
	}).val(cur_zoom);
	in_kml.change(function(){
		if ( typeof ggeoxml !== 'undefined' )
			gmap.removeOverlay(ggeoxml);
		var kml = jQuery(this).val();
		if ( check_url(kml) ) {
			ggeoxml = new GGeoXml(kml);
			gmap.addOverlay(ggeoxml);
			top.gm_anyehere.set('kml', kml);
		} else {
			top.gm_anyehere.set('kml', '');
		}
	}).val(cur_kml);
	in_align.change(function(){
		cur_align = jQuery(this).val();
		top.gm_anyehere.set('align', cur_align);
	});
	if ( cur_align != '<?php echo GM_ANYWHERE_DEFAULT_ALIGN;?>' )
		jQuery('#align-' + cur_align).attr('checked','checked');
	in_metaval.val(cur_point.toUrlValue());

	gmap = new GMap2(document.getElementById("map"), {size: new GSize(<?php echo $width; ?>, <?php echo $height; ?>)});

	gmap.setCenter(cur_point, cur_zoom);
	gmap.addMapType(G_PHYSICAL_MAP);
	gmap.setMapType(<?php echo $this->options['map_default']['type']; ?>);

	gmarker = new GMarker(cur_point, {draggable: true});
	gmap.addOverlay(gmarker);

	GEvent.addListener(gmap, 'click', function(overlay, point) {
		if (point) {
			gmarker.setPoint(point);
			in_lat.val(point.lat());
			in_lng.val(point.lng());
			in_zm.val(gmap.getZoom());
			in_metaval.val(cur_point.toUrlValue());
			set_params(point.lat(), point.lng(), gmap.getZoom(), in_placename.val(), in_width.val(), in_height.val(), in_kml.val());
		}
	});

	GEvent.addListener(gmarker, 'dragend', function(overlay) {
		cur_point = gmarker.getLatLng();
		if (cur_point) {
			in_lat.val(cur_point.lat());
			in_lng.val(cur_point.lng());
			in_zm.val(gmap.getZoom());
			in_metaval.val(cur_point.toUrlValue());
			set_params(cur_point.lat(), cur_point.lng(), gmap.getZoom(), in_placename.val(), in_width.val(), in_height.val(), in_kml.val());
		}
	});

	GEvent.addListener(gmap, "zoomend", function(oldVal, newVal){
		cur_zoom = newVal;
		top.gm_anyehere.set('zoom', cur_zoom);
	});

	gmap.addControl(new GLargeMapControl());
	gmap.addControl(new GMapTypeControl());
	gmap.addControl(new GOverviewMapControl());
	<?php //if (defined('GM_ANYWHERE_SV_ENABLE') && GM_ANYWHERE_SV_ENABLE) echo "gmap.addOverlay(new GStreetviewOverlay());\n"; ?>
	gmap.addControl(new google.maps.LocalSearch({onSearchCompleteCallback: function(searcher){
		if (searcher.results.length >= 1) {
			cur_point = new GLatLng(searcher.results[0].lat, searcher.results[0].lng);
			var placename = searcher.results[0].addressLines[searcher.results[0].addressLines.length - 1];
			gmap.setCenter(cur_point, <?php echo $this->options['map_default']['zoom']; ?>);
			gmarker.setPoint(cur_point);
			in_placename.val(placename);
			in_lat.val(cur_point.lat());
			in_lng.val(cur_point.lng());
			in_zm.val(gmap.getZoom());
			in_metaval.val(cur_point.toUrlValue());
			set_params(cur_point.lat(), cur_point.lng(), gmap.getZoom(), placename, in_width.val(), in_height.val(), in_kml.val());
		}
	}}));

	if ( check_url(cur_kml) ) {
		if ( typeof ggeoxml !== 'undefined' )
			gmap.removeOverlay(ggeoxml);
		ggeoxml = new GGeoXml(cur_kml);
		gmap.addOverlay(ggeoxml);
	}

	jQuery('input[name=insertonlybutton]:first', form).unbind('click').click(function(){
		var gmaptypes = Array(
			 {name:gmap.getMapTypes()[0].getName(true), type:'G_NORMAL_MAP'}
			,{name:gmap.getMapTypes()[1].getName(true), type:'G_SATELLITE_MAP'}
			,{name:gmap.getMapTypes()[2].getName(true), type:'G_HYBRID_MAP'}
			,{name:gmap.getMapTypes()[3].getName(true), type:'G_PHYSICAL_MAP'}
			);
		cur_point = gmarker.getPoint();
		var lat = cur_point.lat();
		var lng = cur_point.lng();
		var zm  = gmap.getZoom();
		var placename = ( in_placename.val() != '' ? in_placename.val() : cur_point.toUrlValue() );
		var width  = in_width.val();
		var height = in_height.val();
		var maptype = gmaptypes[0].type;
		var kml = in_kml.val();
		if ( !check_url(kml) ) {
			kml = '';
			in_kml.val('');
		}
		var align = cur_align;
		for (var i = 0; i < gmaptypes.length; i++) {
			if (gmaptypes[i].name == gmap.getCurrentMapType().getName(true)) {
				maptype = gmaptypes[i].type;
				break;
			}
		}
		var gmap_val  = '[<?php echo GM_ANYWHERE_SHORTCODE; ?>'
			+ ' lat="' + lat + '"'
			+ ' lng="' + lng + '"'
			+ ( align != '<?php echo GM_ANYWHERE_DEFAULT_ALIGN; ?>' ? ' align="' + align + '"' : '')
			+ ' width="' + width + '"'
			+ ' height="' + height + '"'
			+ ' zoom="' + zm + '"'
			+ ' type="' + maptype + '"'
			+ ( kml != '' ? ' kml="' + kml + '"' : '' )
			+ ']'
			+ placename
			+ '[/<?php echo GM_ANYWHERE_SHORTCODE; ?>]';
		top.send_to_editor(gmap_val);
		set_params(lat, lng, zm, placename, width, height, kml, align);
		try {GUnload();} catch(e) {}
		top.tb_remove();
		return false;
	});

	jQuery('input[name=sendmetavalbutton]:first', form).unbind('click').click(function(){
		var meta_key = in_metakey.val();
		var meta_val = in_metaval.val();
		set_params(cur_point.lat(), cur_point.lng(), cur_zoom, ( in_placename.val() != '' ? in_placename.val() : cur_point.toUrlValue() ), in_width.val(), in_height.val(), in_kml.val(), in_align.val());
		top.send_meta_value(meta_key, meta_val);
		try {GUnload();} catch(e) {}
		top.tb_remove();
		return false;
	});
});
// ]]></script>
<?php
	}

	function googlemapsMediaStreetViewBrowse() {
		global $type, $tab;

		if ($this->api_key == false)
			return;
		if (!(defined('GM_ANYWHERE_SV_ENABLE') && GM_ANYWHERE_SV_ENABLE))
			return;

		$post_id = intval(
			isset($_POST['post_id'])
			? $_POST['post_id']
			: ( isset($_GET['post_id']) ? $_GET['post_id'] : '0' )
			);
		$form_action_url = trailingslashit(get_bloginfo('wpurl'))."wp-admin/media-upload.php?type={$type}&amp;tab={$tab}&amp;post_id={$post_id}";
		$callback = "type_form_{$type}";

		$width  = GM_ANYWHERE_EDIT_WIDTH;
		$height = GM_ANYWHERE_EDIT_HEIGHT;
		$lat    = $this->options['sv_default']['lat'];
		$lng    = $this->options['sv_default']['lng'];
		$default_align = GM_ANYWHERE_DEFAULT_ALIGN;

?>
<div id="map" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;margin:0.5em auto 0px;padding:0px;"></div>

<form method="post" action="<?php echo attribute_escape($form_action_url); ?>" class="media-upload-form type-form validate" id="<?php echo $type; ?>-form">
	<table class="optiontable form-table" style="margin-top:0"><tbody>
		<tr>
			<th valign="top" scope="row" class="label" style="padding:0">
				<p><label for="align"><?php _e('Place', $this->textdomain_name); ?></label></p>
			</th>
			<td class="field" style="text-align:left;padding:0;">
				<?php _e('Place Name', $this->textdomain_name); ?>: <input type="text" name="placename" value="" />
				<br />
				<?php _e('Lat', $this->textdomain_name); ?>: <input type="text" name="lat" value="" />
				<?php _e('Lng', $this->textdomain_name); ?>: <input type="text" name="lng" value="" />
				<br />
				<?php _e('Yaw', $this->textdomain_name); ?>: <input type="text" name="yaw" value="" />
				<?php _e('Pitch', $this->textdomain_name); ?>: <input type="text" name="pitch" value="" />
			</td>
		</tr>
		<tr class="align">
			<th valign="top" scope="row" class="label" style="padding:0">
				<p><label for="align"><?php _e('Alignment'); ?></label></p>
			</th>
			<td class="field" style="text-align:left;padding:0;">
				<?php _e('Alignment'); ?>:
				<input name="align" id="align-none" value="none" type="radio"<?php echo ($default_align == 'none' ? ' checked="checked"' : ''); ?> />
				<label for="align-none" class="align image-align-none-label"><?php _e('None'); ?></label>
				<input name="align" id="align-left" value="left" type="radio"<?php echo ($default_align == 'left' ? ' checked="checked"' : ''); ?> />
				<label for="align-left" class="align image-align-left-label"><?php _e('Left'); ?></label>
				<input name="align" id="align-center" value="center" type="radio"<?php echo ($default_align == 'center' ? ' checked="checked"' : ''); ?> />
				<label for="align-center" class="align image-align-center-label"><?php _e('Center'); ?></label>
				<input name="align" id="align-right" value="right" type="radio"<?php echo ($default_align == 'right' ? ' checked="checked"' : ''); ?> />
				<label for="align-right" class="align image-align-right-label"><?php _e('Right'); ?></label>
			</td>
		</tr>
		<tr>
			<th valign="top" scope="row" class="label" style="padding:0">
				<p><label for="align"><?php _e('Size'); ?></label></p>
			</th>
			<td class="field" style="text-align:left;padding:0;">
				<?php _e('Width', $this->textdomain_name); ?>: <input type="text" name="width" size="7" value="" />
				<?php _e('Height', $this->textdomain_name); ?>: <input type="text" name="height" size="7" value="" />
				<?php _e('Zoom', $this->textdomain_name); ?>: <input type="text" name="zoom" size="7" value="" />
			</td>
		</tr>
		<tr>
			<th valign="top" scope="row" class="label" style="padding:0">
			</th>
			<td style="padding:0 10px">
				<input type="submit" class="button button-primary" name="insertonlybutton" value="<?php echo attribute_escape(__('Insert into Post')) ?>" /><br />
			</td>
		</tr>
	</tbody></table>
</form>

<script type="text/javascript">// <![CDATA[
if(typeof google.maps=="undefined") google.load("maps","2",{"language":"<?php echo $this->lang; ?>"});
var onload_callback = function(func){
	if (jQuery.browser.mozilla) {
		var ver = jQuery.browser.version.split('.');
		if ( Number(ver.length > 1 ? ver[0] + '.' + ver[1] : ver[0]) < 1.9 && top.jQuery('iframe').size() > 0 ) {
			var loadEvent = top.jQuery('iframe#TB_iframeContent')[0].onload;
			window.onload = function(){loadEvent(); func();};
		} else {
			google.setOnLoadCallback(func);
		}
	} else {
		google.setOnLoadCallback(func);
	}
};

onload_callback(function() {
	var gmapPanoClient, gmapPano;

	var cur_point = new google.maps.LatLng(
		 top.gm_anyehere.get('point')!=false ? top.gm_anyehere.get('point').lat : <?php echo $lat; ?>
		,top.gm_anyehere.get('point')!=false ? top.gm_anyehere.get('point').lng : <?php echo $lng; ?>
		);
	var cur_pov   = (
		top.gm_anyehere.get('pov')!=false
		? top.gm_anyehere.get('pov')
		: { yaw: <?php echo $this->options['sv_default']['yaw']; ?>, pitch: <?php echo $this->options['sv_default']['pitch']; ?>, zoom : <?php echo $this->options['sv_default']['zoom']; ?> }
		);

	var cur_width = top.gm_anyehere.get('width');
	var cur_height = top.gm_anyehere.get('height');
	var cur_place = top.gm_anyehere.get('placename');

	var cur_align = top.gm_anyehere.get('align');
	if ( cur_align == '')
		cur_align = '<?php echo GM_ANYWHERE_DEFAULT_ALIGN; ?>';

	gmapPanoClient = new google.maps.StreetviewClient();
	gmapPano = new google.maps.StreetviewPanorama(document.getElementById("map"));

	var form = jQuery('#<?php echo $type; ?>-form').submit(function() {return false;});
	var in_width = jQuery('input[name=width]:first', form);
	var in_height = jQuery('input[name=height]:first', form);
	var in_placename = jQuery('input[name=placename]:first', form);
	var in_lat = jQuery('input[name=lat]:first', form);
	var in_lng = jQuery('input[name=lng]:first', form);
	var in_yaw = jQuery('input[name=yaw]:first', form);
	var in_pth = jQuery('input[name=pitch]:first', form);
	var in_zm  = jQuery('input[name=zoom]:first', form);
	var in_align = jQuery('input[name=align]', form);

	var to_number = function(v) { return Number(isNaN(v) ? v.replace(/[^\-\d\.]/, '') : v); }

	var set_params = function(lat, lng, yaw, pth, zm, place, width, height, align) {
		lat = to_number(lat);
		lng = to_number(lng);
		yaw = to_number(yaw);
		pth = to_number(pth);
		zm  = to_number(zm);
		cur_point  = new google.maps.LatLng(lat, lng);
		cur_pov    = { yaw:yaw, pitch:pth, zoom:zm };
		cur_place  = place;
		cur_width  = width;
		cur_height = height;
		cur_align  = align;
		top.gm_anyehere.set('point', {lat:lat, lng:lng});
		top.gm_anyehere.set('pov', cur_pov);
		top.gm_anyehere.set('width',  cur_width);
		top.gm_anyehere.set('height', cur_height);
		top.gm_anyehere.set('placename', cur_place);
		top.gm_anyehere.set('align', cur_align);
	}

	var movePanorama = function(panoData){
		if (typeof panoData.location != 'undefined') {
			gmapPano.setLocationAndPOV(panoData.location.latlng, cur_pov);
			var latlng = panoData.location.latlng.toUrlValue().split(',');
			set_params(latlng[0], latlng[1], cur_pov.yaw, cur_pov.pitch, cur_pov.zoom, in_placename.val(), in_width.val(), in_height.val());
			in_lat.val(latlng[0]);
			in_lng.val(latlng[1]);
			in_yaw.val(cur_pov.yaw);
			in_pth.val(cur_pov.pitch);
			in_zm.val(cur_pov.zoom);
		} else {
			alert("<?php _e("Error: ", $this->textdomain_name); _e("No panorama data was found.", $this->textdomain_name); ?>");
		}
	}

	var point_changed = function(){
		cur_point = new google.maps.LatLng(to_number(in_lat.val()), to_number(in_lng.val()));
		gmapPanoClient.getNearestPanorama(cur_point, movePanorama);
	}

	var pov_changed = function(){
		cur_pov = {yaw:to_number(in_yaw.val()), pitch:to_number(in_pth.val()), zoom:to_number(in_zm.val())};
		gmapPanoClient.getNearestPanorama(cur_point, movePanorama);
	}

	in_width.change(function(){top.gm_anyehere.set('width', jQuery(this).val());}).val(cur_width);
	in_height.change(function(){top.gm_anyehere.set('height', jQuery(this).val());}).val(cur_height);
	in_placename.change(function(){top.gm_anyehere.set('placename', jQuery(this).val());}).val(cur_place);
	in_lat.change(point_changed);
	in_lng.change(point_changed);
	in_yaw.change(pov_changed);
	in_pth.change(pov_changed);
	in_zm.change(pov_changed);
	in_align.change(function(){
		cur_align = jQuery(this).val();
		top.gm_anyehere.set('align', cur_align);
	});
	if ( cur_align != '<?php echo GM_ANYWHERE_DEFAULT_ALIGN; ?>' )
		jQuery('#align-' + cur_align).attr('checked','checked');

	google.maps.Event.addListener(gmapPano, "initialized", function(val){
		in_lat.val(val.latlng.lat());
		in_lng.val(val.latlng.lng());
		set_params(val.latlng.lat(), val.latlng.lng(), cur_pov.yaw, cur_pov.pitch, cur_pov.zoom, in_placename.val(), in_width.val(), in_height.val());
	});

	google.maps.Event.addListener(gmapPano, "yawchanged", function(val){
		cur_pov.yaw = val;
		top.gm_anyehere.set('pov', cur_pov);
		in_yaw.val(val);
	});

	google.maps.Event.addListener(gmapPano, "pitchchanged", function(val){
		cur_pov.pitch = val;
		top.gm_anyehere.set('pov', cur_pov);
		in_pth.val(val);
	});

	google.maps.Event.addListener(gmapPano, "zoomchanged", function(val){
		cur_pov.zoom = val;
		top.gm_anyehere.set('pov', cur_pov);
		in_zm.val(val);
	});

	google.maps.Event.addListener(gmapPano, "error", function(errorCode) {
		switch (errorCode) {
		case NO_NEARBY_PANO:
			alert("<?php _e("Error: ", $this->textdomain_name); _e("No panorama data was found.", $this->textdomain_name); ?>");
			break;
		case FLASH_UNAVAILABLE:
			alert("<?php _e("Error: ", $this->textdomain_name); _e("Flash doesn't appear to be supported by your browser.", $this->textdomain_name); ?>");
			break;
		default:
			alert("<?php _e("Error: ", $this->textdomain_name); _e("Unknown Error.", $this->textdomain_name); ?>");
			break;
		}
		return;
	});

	gmapPanoClient.getNearestPanorama(cur_point, movePanorama);

	jQuery('input[name=insertonlybutton]:first', form).unbind('click').click(function(){
		var lat = Number(isNaN(in_lat.val()) ? in_lat.val().replace(/[^0-9\.]/, '') : in_lat.val());
		var lng = Number(isNaN(in_lng.val()) ? in_lng.val().replace(/[^0-9\.]/, '') : in_lng.val());
		var yaw = Number(isNaN(in_yaw.val()) ? in_yaw.val().replace(/[^0-9\.]/, '') : in_yaw.val());
		var pth = Number(isNaN(in_pth.val()) ? in_pth.val().replace(/[^0-9\.]/, '') : in_pth.val());
		var zm  = Number(isNaN(in_zm.val())  ? in_zm.val().replace(/[^0-9\.]/, '')  : in_zm.val());
		var placename = in_placename.val();
		var width  = in_width.val();
		var height = in_height.val();
		var align  = cur_align;
		var maptype = 'STREETVIEW';
		var gmap_val  = '[<?php echo GM_ANYWHERE_SHORTCODE; ?>'
			+ ' lat="' + lat + '"'
			+ ' lng="' + lng + '"'
			+ ' yaw="' + yaw + '"'
			+ ' pitch="' + pth + '"'
			+ ' zoom="' + zm + '"'
			+ ' width="' + width + '"'
			+ ' height="' + height + '"'
			+ ( align != '<?php echo GM_ANYWHERE_DEFAULT_ALIGN; ?>' ? ' align="' + align + '"' : '')
			+ ' type="' + maptype + '"'
			+ ']'
			+ (placename != '' ? placename : cur_point.toUrlValue())
			+ '[/<?php echo GM_ANYWHERE_SHORTCODE; ?>]';
		set_params(lat, lng, yaw, pth, zm, placename, width, height, align);
		top.send_to_editor(gmap_val);
		google.maps.Unload();
		top.tb_remove();
		return false;
	});
});
// ]]></script>
<?php
	}

	function googlemapsMediaEarthViewBrowse() {
		global $type, $tab;

		if ($this->api_key == false)
			return;
		if (!(defined('GM_ANYWHERE_EV_ENABLE') && GM_ANYWHERE_EV_ENABLE))
			return;

		$post_id = intval(
			isset($_POST['post_id'])
			? $_POST['post_id']
			: ( isset($_GET['post_id']) ? $_GET['post_id'] : '0' )
			);
		$form_action_url = trailingslashit(get_bloginfo('wpurl'))."wp-admin/media-upload.php?type={$type}&amp;tab={$tab}&amp;post_id={$post_id}";
		$callback = "type_form_{$type}";

		$width  = GM_ANYWHERE_EDIT_WIDTH;
		$height = GM_ANYWHERE_EDIT_HEIGHT;
		$lat    = $this->options['ev_default']['lat'];
		$lng    = $this->options['ev_default']['lng'];
		$default_align = GM_ANYWHERE_DEFAULT_ALIGN;
	}

	function googlemapsOptionBrowse() {
		global $type, $tab;

		if ( !current_user_can(GM_ANYWHERE_OPTION_ACCESS_LEVEL) )
			return;

		if (isset($type) && isset($tab)) {
			$post_id = intval(
				isset($_POST['post_id'])
				? $_POST['post_id']
				: ( isset($_GET['post_id']) ? $_GET['post_id'] : '0' )
				);
			$form_action_url = trailingslashit(get_bloginfo('wpurl'))."wp-admin/media-upload.php?type={$type}&amp;tab={$tab}&amp;post_id={$post_id}";
			$callback = "type_form_$type";
		} else {
			$form_action_url = $this->admin_action;
		}

		$msg = "<div id=\"message\" class=\"updated fade\" style=\"margin-top:1em;\"><p><strong>%s</strong></p></div>\n";
		if (isset($_POST['info_update'])) {
			check_admin_referer($this->nonce);
			$this->api_key = $this->upate_options();
			if ($this->api_key != false) echo sprintf($msg, __('Done!', $this->textdomain_name));
		}
		if ($this->api_key == false) {
			echo sprintf($msg, __('Please Enter Your Google Maps API Key', $this->textdomain_name));
		}

?>
<div class="wrap">
	<form method="post" action="<?php echo attribute_escape($form_action_url); ?>" class="media-upload-form type-form validate" id="<?php echo $type; ?>-form">
		<?php $this->makeNonceField($this->nonce); ?>
		<h2 id="apikey"><?php _e('Your Google Maps API Key', $this->textdomain_name); ?></h2>
		<table class="optiontable form-table"><tbody>
			<tr>
				<th width="20%" scope="row"><label for="googlemaps_api_key"><?php _e('API Key', $this->textdomain_name); ?>:</label></th>
				<td><input type="text" name="googlemaps_api_key" id="googlemaps_api_key" size="50" value="<?php echo ($this->api_key !== FALSE ? $this->api_key : ''); ?>" /><br /><?php echo sprintf(__("(don't have one? get one <a href=%s>here</a>)", $this->textdomain_name), '"http://www.google.com/apis/maps/signup.html"'); ?></td>
			</tr>
<?php if ($this->api_key !== false) { ?>
			<tr>
				<th><?php _e('Current API Key', $this->textdomain_name); ?>:</th>
				<td><em><?php echo $this->api_key; ?></em></td>
			</tr>
<?php } ?>
		</tbody></table>

		<h2 id="options"><?php _e('Option', $this->textdomain_name); ?></h2>
		<table class="optiontable form-table"><tbody>
			<tr>
				<th scope="row"><label><?php _e('Default Size', $this->textdomain_name); ?></label></th>
				<td><label for="map_width"><?php _e('Width', $this->textdomain_name); ?>:</label><input type="text" name="map_width" id="map_width" value="<?php echo $this->options['map_default']['width']; ?>" /></td>
				<td><label for="map_height"><?php _e('Height', $this->textdomain_name); ?>:</label><input type="text" name="map_height" id="map_height" value="<?php echo $this->options['map_default']['height']; ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e('Initial position <br />(Google Maps)', $this->textdomain_name); ?></label></th>
				<td><label for="map_lat"><?php _e('Lat', $this->textdomain_name); ?>:</label><input type="text" name="map_lat" id="map_lat" value="<?php echo $this->options['map_default']['lat']; ?>" /></td>
				<td><label for="map_lng"><?php _e('Lng', $this->textdomain_name); ?>:</label><input type="text" name="map_lng" id="map_lng" value="<?php echo $this->options['map_default']['lng']; ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e('Initial position <br />(Street View)', $this->textdomain_name); ?></label></th>
				<td><label for="sv_lat"><?php _e('Lat', $this->textdomain_name); ?>:</label><input type="text" name="sv_lat" id="sv_lat" value="<?php echo $this->options['sv_default']['lat']; ?>" /></td>
				<td><label for="sv_lng"><?php _e('Lng', $this->textdomain_name); ?>:</label><input type="text" name="sv_lng" id="sv_lng" value="<?php echo $this->options['sv_default']['lng']; ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="maps_url"><?php _e('Google Maps URL', $this->textdomain_name); ?>:</label></th>
				<td colspan="2"><input type="text" name="maps_url" id="maps_url" size="50" value="<?php echo $this->options['mapsURL']; ?>" /></td>
			</tr>
		</tbody></table>

		<div class="submit">
			<input type="hidden" name="action" value="update" />
			<input type="submit" name="info_update" class="button-primary" value="<?php _e('Update Options', $this->textdomain_name); ?> &raquo;" />
		</div>
	</form>
</div>
<?php
	}

	function modifyMediaTab($tabs) {
		$tabs = array(
			'googlemaps' =>  __('Google Maps', $this->textdomain_name)
			);

		if (defined('GM_ANYWHERE_SV_ENABLE') && GM_ANYWHERE_SV_ENABLE)
			$tabs['googlemaps_streetview'] =  __('Street View', $this->textdomain_name);

		if (defined('GM_ANYWHERE_EV_ENABLE') && GM_ANYWHERE_EV_ENABLE)
			$tabs['googlemaps_eathview'] =  __('Eath View', $this->textdomain_name);

		if (current_user_can( GM_ANYWHERE_OPTION_ACCESS_LEVEL ))
			$tabs['googlemaps_option'] =  __('Settings');

		return $tabs;
	}

	function addMediaHead() {
		if ($this->api_key == false)
			return;

		$post_id = intval(
			isset($_POST['post_id'])
			? $_POST['post_id']
			: ( isset($_GET['post_id']) ? $_GET['post_id'] : '0' )
			);
		$google_uds_url = 'http://www.google.com/uds';
?>
<style type="text/css">/* <![CDATA[ */
	@import url("<?php echo $google_uds_url; ?>/css/gsearch.css");
	@import url("<?php echo $google_uds_url; ?>/solutions/localsearch/gmlocalsearch.css");
	body {margin:0px; padding:0px;}
	#map {border:1px solid #979797;width:100%;height:575px;}
/* ]]> */</style>
<script src="<?php echo GM_ANYWHERE_JSAPI_URL; ?>?key=<?php echo $this->api_key; ?>" type="text/javascript" charset="utf-8"></script>
<script src="<?php echo GM_ANYWHERE_MAP_URL; ?>?file=api&amp;v=2&amp;hl=<?php echo $this->lang; ?>&amp;key=<?php echo $this->api_key; ?>" type="text/javascript" charset="utf-8"></script>
<script src="<?php echo $google_uds_url; ?>/api?file=uds.js&amp;v=1.0&amp;hl=<?php echo $this->lang; ?>&amp;key=<?php echo $this->api_key; ?>" type="text/javascript"></script>
<script src="<?php echo $google_uds_url; ?>/solutions/localsearch/gmlocalsearch.js" type="text/javascript"></script>
<script type="text/javascript">// <![CDATA[
	var post_id = <?php echo (int) $post_id; ?>;
// ]]></script>
<?php
	}

	function addMediaHeadSv() {
		if ($this->api_key == false)
			return;
		if (!(defined('GM_ANYWHERE_SV_ENABLE') && GM_ANYWHERE_SV_ENABLE))
			return;

		$post_id = intval(
			isset($_POST['post_id'])
			? $_POST['post_id']
			: ( isset($_GET['post_id']) ? $_GET['post_id'] : '0' )
			);
?>
<style type="text/css">/* <![CDATA[ */
	body {margin:0px; padding:0px;}
	#map {border:1px solid #979797;width:100%;height:575px;}
/* ]]> */</style>
<script src="<?php echo GM_ANYWHERE_JSAPI_URL; ?>?key=<?php echo $this->api_key; ?>" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">// <![CDATA[
	var post_id = <?php echo (int) $post_id; ?>;
// ]]></script>
<?php
	}

	function addMediaHeadEv() {
		if ($this->api_key == false)
			return;
		if (!(defined('GM_ANYWHERE_EV_ENABLE') && GM_ANYWHERE_EV_ENABLE))
			return;

		$post_id = intval(
			isset($_POST['post_id'])
			? $_POST['post_id']
			: ( isset($_GET['post_id']) ? $_GET['post_id'] : '0' )
			);
?>
<style type="text/css">/* <![CDATA[ */
	body {margin:0px; padding:0px;}
	#map {border:1px solid #979797;width:100%;height:575px;}
/* ]]> */</style>
<script src="<?php echo GM_ANYWHERE_JSAPI_URL; ?>?key=<?php echo $this->api_key; ?>" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">// <![CDATA[
	var post_id = <?php echo (int) $post_id; ?>;
// ]]></script>
<?php
	}

	/*
	* update options
	*/
	function upate_options() {
		$_POST = $this->stripArray($_POST);

		$gmap_api_key = (
			isset($_POST['googlemaps_api_key'])
			? $_POST['googlemaps_api_key']
			: false
			);
		if ($gmap_api_key) {
			update_option('googlemaps_api_key', $gmap_api_key);
			delete_option('yf_google_api_key');
		} elseif ($gmap_api_key = get_option('yf_google_api_key')) {
			update_option('googlemaps_api_key', $gmap_api_key);
			delete_option('yf_google_api_key');
		} else {
			$gmap_api_key = false;
		}

		// options update
		$this->options['map_default']['lat']    = (isset($_POST['map_lat'])    ? $_POST['map_lat']    : $this->options['map_default']['lat']);
		$this->options['map_default']['lng']    = (isset($_POST['map_lng'])    ? $_POST['map_lng']    : $this->options['map_default']['lng']);
		$this->options['sv_default']['lat']     = (isset($_POST['sv_lat'])     ? $_POST['sv_lat']     : $this->options['sv_default']['lat']);
		$this->options['sv_default']['lng']     = (isset($_POST['sv_lng'])     ? $_POST['sv_lng']     : $this->options['sv_default']['lng']);
		$this->options['map_default']['width']  = (isset($_POST['map_width'])  ? $_POST['map_width']  : $this->options['map_default']['width']);
		$this->options['map_default']['height'] = (isset($_POST['map_height']) ? $_POST['map_height'] : $this->options['map_default']['height']);
		$this->options['mapsURL']               = (isset($_POST['maps_url'])   ? $_POST['maps_url']   : $this->options['mapsURL']);
		$this->updateOptions();

		return $gmap_api_key;
	}

	/*
	* parse shortcodes
	*/
	function parse_shortcodes( $content ) {
		global $shortcode_tags;

		$shortcode_tags_org = $shortcode_tags;
		remove_all_shortcodes();

		add_shortcode('code', array(&$this, GM_ANYWHERE_SHORTCODE));
		$content = do_shortcode( $content );

		$shortcode_tags = $shortcode_tags_org;

		return $content;
	}

	/*
	* Wordpress 2.5 - New shortcode API
	*/
	function shortcodeHandler($atts, $content = '') {
		extract( shortcode_atts( array(
			'lat'    => $this->options['map_default']['lat'] , 'ktai_lat' => '' ,
			'lng'    => $this->options['map_default']['lng'] , 'ktai_lng' => '' ,
			'yaw'    => $this->options['sv_default']['yaw'] ,
			'pitch'  => $this->options['sv_default']['pitch'] ,
			'zoom'   => $this->options['map_default']['zoom'] , 'ktai_zoom' => '' ,
			'width'  => $this->options['map_default']['width'] ,
			'height' => $this->options['map_default']['height'] ,
			'type'   => $this->options['map_default']['type'] ,
			'kml'    => '' ,
			'align'  => $this->options['map_default']['align'] ,
			'altitude' => $this->options['ev_default']['altitude'] ,
			'heading'  => $this->options['ev_default']['heading'] ,
			'straight' => $this->options['ev_default']['straight'] ,
			'range'    => $this->options['ev_default']['range'] ,
			), $atts ) );

		if ( $this->isKtai() ) {
			$lat  = ( !empty($ktai_lat)  ? $ktai_lat  : $lat );
			$lng  = ( !empty($ktai_lng)  ? $ktai_lng  : $lng );
			$zoom = ( !empty($ktai_zoom) ? $ktai_zoom : $zoom );
		}

		// add JavaScript
		add_action('wp_footer', array($this, 'addFooter'));

		return $this->createHTMLSrc($lat, $lng, $yaw, $pitch, $zoom, $width, $height, $type, $content, $kml, $align, $altitude, $heading, $straight, $range);
	}

	function getImgSrc($lat = GM_ANYWHERE_DEFAULT_LAT, $lng = GM_ANYWHERE_DEFAULT_LNG, $yaw = GM_ANYWHERE_SV_DEFAULT_YAW, $pitch = GM_ANYWHERE_SV_DEFAULT_PITCH, $zoom = GM_ANYWHERE_DEFAULT_ZOOM, $width = GM_ANYWHERE_DEFAULT_WIDTH, $height = GM_ANYWHERE_DEFAULT_HEIGHT, $type = GM_ANYWHERE_DEFAULT_TYPE) {
		$width  = (int) $width;
		$height = (int) $height;

		$url  = GM_ANYWHERE_STATICMAP_URL;
		$url .= '?markers='.$lat.','.$lng.',red';
		$url .= '&amp;zoom='.$zoom;
		$url .= '&amp;size='.$width.'x'.$height;
		$url .= '&amp;key='.$this->api_key;
		$url .= '&amp;hl='.$this->lang;
		return $url;
	}

	function getMapLink($lat = GM_ANYWHERE_DEFAULT_LAT, $lng = GM_ANYWHERE_DEFAULT_LNG, $yaw = GM_ANYWHERE_SV_DEFAULT_YAW, $pitch = GM_ANYWHERE_SV_DEFAULT_PITCH, $zoom = GM_ANYWHERE_DEFAULT_ZOOM, $width = GM_ANYWHERE_DEFAULT_WIDTH, $height = GM_ANYWHERE_DEFAULT_HEIGHT, $type = GM_ANYWHERE_DEFAULT_TYPE, $kml = "", $altitude = GM_ANYWHERE_EV_DEFAULT_ALTITUDE, $heading = GM_ANYWHERE_EV_DEFAULT_HEADING, $straight = GM_ANYWHERE_EV_DEFAULT_STRAIGHT, $range = GM_ANYWHERE_EV_DEFAULT_RANGE) {
		$latlng = $lat.','.$lng;
		$query  = $latlng;

		$url  = GM_ANYWHERE_MAP_URL;
		$url .= '?f=q';
		$url .= '&amp;hl='.$this->lang;
		$url .= '&amp;geocode=';
		$url .= '&amp;q='.$query;
		$url .= '&amp;ie=UTF8';
		$url .= '&amp;ll='.$latlng;
		$url .= '&amp;t=h';

		if ( strtoupper($type) === 'STREETVIEW' ) {
			$url .= '&amp;z='.$this->options['map_default']['zoom'];
			$url .= '&amp;layer=c';
			$url .= '&amp;cbll='.$latlng;
			$url .= '&amp;cbp=1,'.$yaw.',,'.$zoom.','.$pitch;
		} elseif ( strtoupper($type) === 'EARTHVIEW' ) {
			$url .= '&amp;z='.$this->options['map_default']['zoom'];
			$url .= '&amp;a='.$altitude;
			$url .= '&amp;h='.$heading;
			$url .= '&amp;s='.$straight;
			$url .= '&amp;r='.$range;
		} else {
			$url .= '&amp;z='.$zoom;
		}

		switch ($type) {
		case 'NORMAL':
		case 'G_NORMAL_MAP':
			$url .= '&amp;t=n';
			break;
		case 'SATELLITE':
		case 'G_SATELLITE_MAP':
		case 'HYBRID':
		case 'G_HYBRID_MAP':
			$url .= '&amp;t=h';
			break;
		case 'PHYSICAL':
		case 'G_PHYSICAL_MAP':
			$url .= '&amp;t=p';
			break;
		};
		$url .= '&amp;maptype='.$type;

		if ( !preg_match('/^https?:\/\//i', $kml) && !empty($kml) )
			$kml = site_url($kml);

		if ( preg_match('/s?https?:\/\/[-_.!~*\'\(\)a-zA-Z0-9;\/?:\@&=+\$,%#]+/i', $kml) !== false && !empty($kml) )
			$url .= '&amp;kml='.rawurlencode($kml);

		return $url;
	}

	function createHTMLSrc($lat = GM_ANYWHERE_DEFAULT_LAT, $lng = GM_ANYWHERE_DEFAULT_LNG, $yaw = GM_ANYWHERE_SV_DEFAULT_YAW, $pitch = GM_ANYWHERE_SV_DEFAULT_PITCH, $zoom = GM_ANYWHERE_DEFAULT_ZOOM, $width = GM_ANYWHERE_DEFAULT_WIDTH, $height = GM_ANYWHERE_DEFAULT_HEIGHT, $type = GM_ANYWHERE_DEFAULT_TYPE, $content= "", $kml = "", $align = GM_ANYWHERE_DEFAULT_ALIGN, $altitude = GM_ANYWHERE_EV_DEFAULT_ALTITUDE, $heading = GM_ANYWHERE_EV_DEFAULT_HEADING, $straight = GM_ANYWHERE_EV_DEFAULT_STRAIGHT, $range = GM_ANYWHERE_EV_DEFAULT_RANGE) {
		list($element, $class) = split("\.", (defined('LWGM_EACH_MAP_CLASS') ? 'div.'.LWGM_EACH_MAP_CLASS : GM_ANYWHERE_ELEMENT), 2);
		switch (strtolower($align)) {
		case 'left':
		case 'center':
		case 'right':
			$class .= ' gmalign-'.strtolower($align);
			break;
		case GM_ANYWHERE_DEFAULT_ALIGN:
		default:
			break;
		}

		$ret_val = "";

		if (! $this->isKtai() ) {
			$img_width  = (int) (strstr(strtoupper($width), 'PX') ? $width  : $this->options['map_default']['width']);
			$img_height = (int) (strstr(strtoupper($height),'PX') ? $height : $this->options['map_default']['height']);
			$img_src  = $this->getImgSrc($lat, $lng, $yaw, $pitch, $zoom, $img_width, $img_height, $type);
			$map_link = $this->getMapLink($lat, $lng, $yaw, $pitch, $zoom, $img_width, $img_height, $type, $kml, $altitude, $heading, $straight, $range);

			$style = "width:{$width};height:{$height};";

			$ret_val .=
				(!is_feed() ? "<{$element} class=\"{$class}\" style=\"{$style}\">" : '') .
				"<a href=\"{$map_link}\" title=\"{$content}\">";
			$ret_val .= strtoupper($type) != 'STREETVIEW'
				? "<img src=\"{$img_src}\" alt=\"{$content}\" title=\"{$content}\" />"
				: $content ;
			$ret_val .= "</a>" . (!is_feed() ? "</{$element}>\n" : '');

		} elseif ( strtoupper($type) != 'STREETVIEW' ) {
			global $Lw_GoogleMaps;
			if (isset($Lw_GoogleMaps)) {
				$img_width  = $Lw_GoogleMaps->get('width');
				$img_height = $Lw_GoogleMaps->get('height');
			} elseif (class_exists('LWGM_Mobile')) {
				$Lwgm = new LWGM_Mobile();
				$img_width  = $Lwgm->width;
				$img_height = $Lwgm->height;
				unset($Lwgm);
			} else {
				$img_width  = GM_ANYWHERE_MOBILE_WIDTH;
				$img_height = GM_ANYWHERE_MOBILE_HEIGHT;
			}
			$img_src = $this->getImgSrc($lat, $lng, $yaw, $pitch, $zoom, $img_width, $img_height, $type);

			$ret_val .= '<div align="center">';
			if ( function_exists('ks_is_image_inline') && ks_is_image_inline() ) {
				$ret_val .= "<img src=\"{$img_src}\" alt=\"{$content}\" title=\"{$content}\" />";
			} else {
				$ret_val .= "[<a href=\"$img_src\" title=\"{$content}\">";
				$ret_val .= __('Map of this location', $this->textdomain_name).' : '.$content;
				$ret_val .= "</a>]";
			}
			$ret_val .= "</div>\n";
		}

		return $ret_val;
	}

	/*
	* for Ktai Style
	*/
	function keepGmapImage($rep, $org, $src) {
		if (preg_match('/^'.preg_quote(GM_ANYWHERE_STATICMAP_URL, '/').'/i', $src))
			$rep = $org;
		return $rep;
	}

	/*
	* have short code
	*/
	function haveShortCode() {
		global $wp_query;

		if (is_admin())
			return false;

		$posts   = $wp_query->posts;
		$pattern = '/\[' . GM_ANYWHERE_SHORTCODE . '[^\]]*\]/im';
		$found   = false;
		$hasTeaser = !( is_single() || is_page() );
		foreach($posts as $post) {
			if (isset($post->post_content)) {
				$post_content = isset($post->post_content) ? $post->post_content : '';
				if ( $hasTeaser && preg_match('/<!--more(.*?)?-->/', $post_content, $matches) ) {
					$content = explode($matches[0], $post_content, 2);
					$post_content = $content[0];
				}

				if ( !empty($post_content) && preg_match($pattern, $post_content) ) {
					$found = true;
				}

				if ( !$found && (is_single() || is_page()) ) {
					$meta_val = get_post_meta($post->ID, GM_ANYWHERE_META_FIELD_NAME, true);
					if ( !empty($meta_val) ) {
						$found = true;
					}

					/*
					* Patch to also check custom field values for the existence of a shortcode
					*   Thx Gary Jones!
					*/
					if ( !$found ) {
						$custom_fields = (array)get_post_custom($post->ID);
						foreach ($custom_fields as $key => $val) {
							if( preg_match($pattern, $val[0])) {
								$found = true;
								break;
							}
						}
					}
					/* End Patch */
				}
			}

			if ( $found )
				break;
		}
		unset($posts);
		return $found;
	}

	/*
	* Add Admin Menu
	*/
	function admin_menu() {
		$this->addOptionPage(__('Google Maps Anywhere', $this->textdomain_name), array($this, 'googlemapsOptionBrowse'));
	}
	function plugin_setting_links($links, $file) {
		if (method_exists($this, 'addPluginSettingLinks')) {
			$links = $this->addPluginSettingLinks($links, $file);
		} else {
			$this_plugin = plugin_basename(__FILE__);
			if ($file == $this_plugin) {
				$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
				array_unshift($links, $settings_link); // before other links
			}
		}
		return $links;
	}

	function addGoogleMap($content) {
		if ( is_single() && !class_exists('Lightweight_Google_Maps') ) {
			global $post;
			$latlng = get_post_meta($post->ID, 'Lat_Long', true);
			if ( !empty($latlng) ) {
				list($lat,$long) = split(',', $latlng);
				$content .= '<p>'
					. "[googlemap lat=\"{$lat}\" lng=\"{$long}\" zoom=\"13\" type=\"G_NORMAL_MAP\"]{$lat},{$long}[/googlemap]"
					. '</p>';
			}
		}
		return $content;
	}
}

if (is_admin()) {
	function media_upload_googlemaps() {
		global $gm_anywhere, $wp_version;
		if (!isset($gm_anywhere))
			return;

		wp_iframe('media_upload_googlemaps_form');
	}
	function media_upload_googlemaps_form() {
		global $gm_anywhere;
		if (!isset($gm_anywhere))
			return;

		add_filter('media_upload_tabs', array(&$gm_anywhere, 'modifyMediaTab'));
		echo "<style type=\"text/css\">.gmls-results-popup {background:#FFF none repeat scroll 0 0;}</style>\n";
		echo "<div id=\"media-upload-header\">\n";
		media_upload_header();
		echo "</div>\n";
		$gm_anywhere->googlemapsMediaBrowse();
	}

	function media_upload_googlemaps_streetview() {
		global $gm_anywhere, $wp_version;
		if (!isset($gm_anywhere))
			return;
		if (!(defined('GM_ANYWHERE_SV_ENABLE') && GM_ANYWHERE_SV_ENABLE))
			return;

		wp_iframe('media_upload_googlemaps_streetview_form');
	}
	function media_upload_googlemaps_streetview_form() {
		global $gm_anywhere;
		if (!isset($gm_anywhere))
			return;
		if (!(defined('GM_ANYWHERE_SV_ENABLE') && GM_ANYWHERE_SV_ENABLE))
			return;

		add_filter('media_upload_tabs', array(&$gm_anywhere, 'modifyMediaTab'));
		echo "<div id=\"media-upload-header\">\n";
		media_upload_header();
		echo "</div>\n";
		$gm_anywhere->googlemapsMediaStreetViewBrowse();
	}

	function media_upload_googlemaps_eathview() {
		global $gm_anywhere, $wp_version;
		if (!isset($gm_anywhere))
			return;
		if (!(defined('GM_ANYWHERE_EV_ENABLE') && GM_ANYWHERE_EV_ENABLE))
			return;

		wp_iframe('media_upload_googlemaps_eathview_form');
	}
	function media_upload_googlemaps_eathview_form() {
		global $gm_anywhere;
		if (!isset($gm_anywhere))
			return;
		if (!(defined('GM_ANYWHERE_EV_ENABLE') && GM_ANYWHERE_EV_ENABLE))
			return;

		add_filter('media_upload_tabs', array(&$gm_anywhere, 'modifyMediaTab'));
		echo "<div id=\"media-upload-header\">\n";
		media_upload_header();
		echo "</div>\n";
		$gm_anywhere->googlemapsMediaEarthViewBrowse();
	}

	function media_upload_googlemaps_option() {
		wp_iframe('media_upload_googlemaps_option_form');
	}
	function media_upload_googlemaps_option_form() {
		global $gm_anywhere;
		if (!isset($gm_anywhere))
			return;
		if ( !function_exists('wp_nonce_field') ) {
			$gm_anywhere->nonce = -1;
		} else {
			$gm_anywhere->nonce = 'googlemaps-anywhere-update-config';
		}
		$gm_anywhere->api_key = (
			isset($_POST['googlemaps_api_key'])
			? $_POST['googlemaps_api_key']
			: $gm_anywhere->api_key
			);
		add_filter('media_upload_tabs', array(&$gm_anywhere, 'modifyMediaTab'));
		echo "<div id=\"media-upload-header\">\n";
		media_upload_header();
		echo "</div>\n";
		$gm_anywhere->googlemapsOptionBrowse();
	}
}

global $gm_anywhere;
$gm_anywhere = new GoogleMapsAnywhere();
?>
