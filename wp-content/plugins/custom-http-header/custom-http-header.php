<?php
/*
Plugin Name: Custom HTTP header
Plugin URI: 
Description: Custom HTTP header
Version: 0.2.0
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: custom-http-header
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2010

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
class custom_http_header {
	const TEXTDOMAIN_NAME = 'custom-http-header';
	const META_KEY        = 'custom-http-header';
	const DEFAULT_HEADER  = 'X-Odyssey: Tears of the eternity in my heart.';

	private $file_path, $plugins_dir, $plugin_dir, $plugin_file, $plugin_url;
	private $admin_action;

	public function __construct() {
		$this->set_plugin_dir(__FILE__);
		$this->load_text_domain();
		$this->admin_action =
			  trailingslashit(get_bloginfo('wpurl')) . 'wp-admin/'
			. ($this->wp_version_check('2.6') ? 'options-general.php' : 'admin.php')
			. '?page=' . $this->plugin_file;
		if (is_admin()) {
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_filter('plugin_action_links', array(&$this, 'plugin_setting_links'), 10, 2 );
		} else {
			add_action('template_redirect', array(&$this,'output_header'));
		}
	}

	//**************************************************************************************
	// http header
	//**************************************************************************************
	private function merge_headers($headers_1, $headers_2) {
		$values  = (array)$headers_1;
		foreach ($headers_2 as $header) {
			$header = trim($header);
			if (!empty($header) && array_search($header, $values) === FALSE) {
				$values[] = $header;
			}
		}
		return $values;
	}

	private function get_headers($single = false) {
		$values  = $this->merge_headers(array(), (array)get_option(self::META_KEY));

		if (count($values) <= 0) {
			$values[] = self::DEFAULT_HEADER;
		}
		if ($single) {
			$values  = $this->merge_headers($values, (array)get_post_meta(get_the_ID(), self::META_KEY));
		}
		return $values;
	}

	public function output_header() {
		$headers = $this->get_headers(is_singular());
		foreach ($headers as $header) {
			if (!empty($header)) {
				header(esc_html($header));
			}
		}
	}

	//**************************************************************************************
	// Utility
	//**************************************************************************************
	private function wp_version_check($ver) {
		global $wp_version;
		return version_compare($wp_version, $ver, ">=");
	}

	private function wp_content_dir($path = '') {
		return trailingslashit(trailingslashit(
			defined('WP_CONTENT_DIR')
			? WP_CONTENT_DIR
			: trailingslashit(ABSPATH) . 'wp-content'
			)
			. preg_replace('/^\//', '', $path));
	}

	private function wp_content_url($path = '') {
		return trailingslashit(trailingslashit(
			defined('WP_CONTENT_URL')
			? WP_CONTENT_URL
			: trailingslashit(get_option('siteurl')) . 'wp-content'
			)
			. preg_replace('/^\//', '', $path));
	}

	private function wp_plugin_dir($path = '') {
		return trailingslashit($this->wp_content_dir('plugins/' . preg_replace('/^\//', '', $path)));
	}

	private function wp_plugin_url($path = '') {
		return trailingslashit($this->wp_content_url('plugins/' . preg_replace('/^\//', '', $path)));
	}

	private function set_plugin_dir($file) {
		$this->file_path = $file;
		$this->plugins_dir = trailingslashit(defined('PLUGINDIR') ? PLUGINDIR : 'wp-content/plugins');
		$filename = explode("/", $this->file_path);
		if(count($filename) <= 1) {
			$filename = explode("\\", $this->file_path);
		}
		$this->plugin_dir  = $filename[count($filename) - 2];
		$this->plugin_file = $filename[count($filename) - 1];
		$this->plugin_url  = $this->wp_plugin_url($this->plugin_dir);
		unset($filename);
	}

	private function load_text_domain($sub_dir = '') {
		$abs_plugin_dir = $this->wp_plugin_dir($this->plugin_dir);
		$sub_dir = (!empty($sub_dir)
			? preg_replace('/^\//', '', $sub_dir)
			: (file_exists($abs_plugin_dir.'languages') ? 'languages' : (file_exists($abs_plugin_dir.'language') ? 'language' : (file_exists($abs_plugin_dir.'lang') ? 'lang' : '')))
			);
		$textdomain_dir = trailingslashit(trailingslashit($this->plugin_dir) . $sub_dir);

		if ($this->wp_version_check('2.6') && defined('WP_PLUGIN_DIR')) {
			load_plugin_textdomain(self::TEXTDOMAIN_NAME, false, $textdomain_dir);
		} else {
			load_plugin_textdomain(self::TEXTDOMAIN_NAME, $this->plugins_dir . $textdomain_dir);
		}
	}

	//**************************************************************************************
	// Add Admin Menu
	//**************************************************************************************
	public function add_admin_scripts() {
		wp_enqueue_script('jquery');
	}

	public function add_admin_head() {
?>
<script type="text/javascript">//<![CDATA[
function add_row() {
	jQuery("p.header:last input").unbind("keydown");

	var count = Number(jQuery("input#number").attr("value")) + 1;
	jQuery("p.header:last").after(jQuery(
		'<p class="header"><input type="text" name="header[' + count + ']" value="" style="width:80%;" /></p>'
		).hide().fadeIn()
	);
	jQuery("input#number").attr("value", count);

	jQuery("p.header:last input").unbind("keydown").keydown(add_row);
}

jQuery(function(){
	jQuery("p.header:last input").unbind("keydown").keydown(add_row);
});
//]]></script>
<?php
	}

	public function admin_menu() {
		$hook = add_options_page(
			__('Custom HTTP header', self::TEXTDOMAIN_NAME),
			__('Custom HTTP header', self::TEXTDOMAIN_NAME),
			'manage_options',
			$this->plugin_file,
			array($this, 'options_page')
			);
		add_action('admin_print_scripts-'.$hook, array($this,'add_admin_scripts'));
		add_action('admin_head-'.$hook, array($this,'add_admin_head'));
	}

	public function plugin_setting_links($links, $file) {
		global $wp_version;

		$this_plugin = plugin_basename(__FILE__);
		if ($file == $this_plugin) {
			$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links
		}

		return $links;
	}

	public function options_page() {
		$nonce_action = 'update_options';
		$nonce_name   = '_wpnonce_update_options';

		$out   = '';
		$note  = '';
		$error = 0;

		// Update options
		if (isset($_POST['options_update'])) {
			if ($this->wp_version_check('2.5') && function_exists('check_admin_referer')) {
				check_admin_referer($nonce_action, $nonce_name);
			}

			// get options
			$values  = $this->merge_headers(array(), (array)$_POST['header']);

			// update or delete options
			if (count($values) > 0) {
				update_option(self::META_KEY, $values);
			} else {
				delete_option(self::META_KEY);
			}
			$note .= "<strong>".__('Done!', self::TEXTDOMAIN_NAME)."</strong>";
			unset($options);
			unset($values);
		}

		// Add Options
		$out .= '<div class="wrap">'."\n";
		$out .= '<form method="post" id="update_options" action="'.$this->admin_action.'">'."\n";
		$out .= '<h2>'.__('Custom HTTP header Options', self::TEXTDOMAIN_NAME).'</h2>'."\n";

		if ($this->wp_version_check('2.5') && function_exists('wp_nonce_field') ) {
			$out .= wp_nonce_field($nonce_action, $nonce_name, true, false);
		}

		$headers = $this->get_headers(false);
		$i = 0;
		foreach ($headers as $header) {
			if (!empty($header)) {
				$out .= '<p class="header"><input type="text" name="header['.$i.']" value="'.esc_html($header).'" style="width:80%;"/></p>';
				$i++;
			}
		}
		$out .= '<p class="header"><input type="text" name="header['.$i.']" value="" style="width:80%;" /></p>';

		// Add Update Button
		$out .= '<p style="margin-top:1em">';
		$out .= '<input type="hidden" name="number" id="number" value="'.$i.'" />';
		$out .= '<input type="submit" name="options_update" class="button-primary" value="'.__('Save Changes').' &raquo;" class="button" />';
		$out .= '</p>';
		$out .= '</form></div>'."\n";

		// Output
		echo ( !empty($note)
			? '<div id="message" class="updated fade"><p>'.$note.'</p></div>'
			: '' );
		echo "\n";

		echo ( $error <= 0 ? $out : '' );
		echo "\n";
	}
}

new custom_http_header();
?>