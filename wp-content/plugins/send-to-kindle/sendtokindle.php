<?php
/*
Plugin Name: Amazon Send to Kindle
Plugin URI: http://wordpress.org/extend/plugins/send-to-kindle/
Description: Allow readers to enjoy your blog anytime, everywhere on their Kindle devices and free reading apps.
Version: 1.0.2
Author: Amazon.com, Inc.
Author URI: https://www.amazon.com/gp/sendtokindle/
License: GPLv2
License URI: COPYING.txt
Text Domain: kindle
Domain Path: /languages/
*/

/*	Copyright (C) 2013 Amazon.com, Inc. or its affiliates. All rights reserved.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
	51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

class STK_Button {
	/**
	 * Singleton instance.
	 *
	 * @var STK_Button
	 */
	private static $instance;

	/**
	 * Constructor to initialize default settings and register events.
	 */
	private function __construct() {
		// Set up sensible defaults.
		add_option( 'stk_button_look', array(
			// placement
			'home' => true,
			'archive' => true,
			'post' => true,
			'page' => false,
			'before' => false,
			'after' => false,
			// button style
			'color' => 'white',
			'size' => 'small',
			'border' => true,
			'theme' => 'light',
			// button text
			'text' => 'send-to-kindle',
			'font' => 'sans-serif',
		) );
		add_option( 'stk_button_advanced', array(
			'selectors' => array(
				'title' => '.entry-title',
				'published' => '.entry-date',
				'content' => '.post',
				'exclude' => '.sharedaddy',
			),
			'enabled' => false,
			'markup' => '<div class="kindleWidget">Kindle</div>',
		) );

		// Register actions and filters.
		add_action( 'plugins_loaded', array( $this, 'load_l18n' ) );
		add_shortcode( 'sendtokindle', array( $this, 'get_button_html' ) );
		add_filter( 'the_content', array( $this, 'attach_to_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		if ( is_admin() ) {
			// Only load settings screens for admins.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ),
					array( $this, 'settings_link' ) );
			if ( ! class_exists( 'STK_Settings' ) ) {
				include_once( dirname( __FILE__ ) . '/admin/settings.php' );
			}
			if ( ! class_exists( 'STK_Settings_Advanced' ) ) {
				include_once( dirname( __FILE__ ) . '/admin/settings-advanced.php' );
			}
			$settings = STK_Settings::get_instance();
			$advanced_settings = STK_Settings_Advanced::get_instance();
		}
	}

	/**
	 * Retrieves the singleton instance or creates one if there is none.
	 *
	 * @return STK_Button instance of the button class
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds a convenience link to the settings page on the plugin index.
	 *
	 * @param array $links the existing action links
	 * @return array new array of action links with a link to the settings page
	 */
	public function settings_link( array $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php' ) ) .
				'?page=kindle-settings">' . __( 'Settings', 'kindle' ) . '</a>';
		return $links;
	}

	/**
	 * Adds the button JavaScript and configuration information.
	 *
	 * @uses wp_register_script
	 * @uses wp_enqueue_script
	 * @uses wp_register_style
	 * @uses wp_enqueue_style
	 */
	public function enqueue_script() {
		// prepare javascript configuration
		$settings = get_option( 'stk_button_advanced' );
		$js = '(function k(){window.$SendToKindle&&window.$SendToKindle.Widget?$SendToKindle.Widget.init(' .
			json_encode( $settings['selectors'] ) . '):setTimeout(k,500);})();';

		// enqueue and attach the button javascript
		wp_enqueue_script( 'kindle_script', ( is_ssl() ? 'https' : 'http' ) .
			'://d1xnn692s7u6t6.cloudfront.net/widget.js', false, null, true );
		global $wp_scripts;
		$wp_scripts->add_data( 'kindle_script', 'data', $js );

		// insert a stylesheet if we are using premade button HTML
		if ( ! $settings['enabled'] ) {
			wp_register_style( 'kindle-style', plugins_url( 'media/kindle.css', __FILE__ ) );
			wp_enqueue_style( 'kindle-style' );
		}
	}

	/**
	 * Inserts the Send to Kindle Button before and/or after posts, depending on
	 * the settings.
	 *
	 * @param string $content the content of the post
	 * @return string revised content with the Send to Kindle Button inserted
	 */
	public function attach_to_content( $content ) {
		$settings = get_option( 'stk_button_look' );
		if ( ! ( $content || $settings ) ) {
			return $content;
		}

		// Ensure the correct page type
		if ( ( is_home() && ! $settings['home'] ) ||
				( is_archive() && ! $settings['archive'] ) ||
				( is_single() && ! $settings['post'] ) ||
				( is_page() && ! $settings['page'] ) ) {
			return $content;
		}

		if ( $settings['before'] ) {
			$content = $this->get_button_html() . $content;
		}
		if ( $settings['after'] ) {
			$content = $content . $this->get_button_html();
		}

		return $content;
	}

	/**
	 * Retrieves the Send to Kindle Button HTML. This may be generated based on
	 * the settings.
	 *
	 * @return string either the custom HTML or a generated <div> block
	 */
	public function get_button_html() {
		// custom markup
		$settings = get_option( 'stk_button_advanced' );
		if ( $settings['enabled'] ) {
			return wp_kses_post( balanceTags( $settings['markup'] ) );
		}

		// build outer class
		$settings = get_option( 'stk_button_look' );
		$html = "<div class='kindleWidget";
		if ( $settings['border'] ) {
			$html .= ('light' === $settings['theme'] ? ' kindleLight' : ' kindleDark');
		}
		$html .= ('dark' === $settings['theme'] ? ' kindleDarkText' : '' ) . "' ";
		if ( 'sans-serif' !== $settings['font']) {
			$html .= "style='font-family: " . esc_attr( $settings['font'] ) . ";'";
		}
		$html .= ">";

		// button icon
		$html .= '<img src="' . plugin_dir_url( __FILE__ ) . 'media/';
		$html .= 'black' === $settings['color'] ? 'black' : 'white';
		$html .= 'large' === $settings['size'] ? '-25' : '-15';
		$html .= '.png" />';

		// text label style
		if ( 'kindle' === $settings['text'] ) {
			$html .= '<span>' . __( 'Kindle', 'kindle' ) . '</span>';
		} elseif ( 'send-to-kindle' === $settings['text'] ) {
			$html .= '<span>' . __( 'Send to Kindle', 'kindle' ) . '</span>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Loads the localized MO for translating strings.
	 *
	 * @uses load_plugin_textdomain
	 */
	public function load_l18n() {
		$textdomain = function_exists( 'wpcom_is_vip' ) ? 'default' : 'kindle';
		load_plugin_textdomain( $textdomain, false,
				basename( dirname( __FILE__ ) ) ) . '/languages/';
	}
}

/**
 * Instantiates the Send to Kindle Button.
 */
function STK_loader() {
	$kindle_loader = STK_Button::get_instance();
	include_once( dirname( __FILE__ ) . '/jetpack.php' );
}

add_action( 'init', 'STK_loader' );

?>
