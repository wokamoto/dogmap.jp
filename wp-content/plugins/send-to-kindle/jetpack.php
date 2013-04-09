<?php
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

// Let's see if there is a plugin to integrate with.
$share_plugin = preg_grep( '/\/jetpack\.php$/i', wp_get_active_and_valid_plugins() );
if ( 1 === count( $share_plugin ) ) {
	// There is absolutely no reason why more than one result should appear, but
	// let's assume a correct search will only have one match.
	if ( ! class_exists( 'Sharing_Source' ) ) {
		include_once( preg_replace( '/jetpack\.php$/i', 'modules/sharedaddy/sharing-sources.php',
				reset( $share_plugin ) ) );
	}
	add_filter( 'sharing_services', array( 'Share_Kindle', 'inject_service' ) );
	add_action( 'admin_notices', array( 'Share_Kindle', 'jetpack_message') );
} else {
	// Nothing to see here, move along.
	return;
}

class Share_Kindle extends Sharing_Source {
    /**
     * @var string
     */
	var $shortname = 'kindle';

	/**
	 * Populates the local copy of the settings.
	 *
	 * @param mixed $id
	 * @param array $settings
	 */
	public function __construct( $id, array $settings ) {
		parent::__construct( $id, $settings );
		$this->smart = 'official' == $this->button_style;
		$this->button_style = 'icon-text';
	}

	/**
	 * The human-readable, localized name of the module.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Kindle', 'kindle' );
	}

	/**
	 * Returns the HTML markup used in the settings screen for the button in
	 * "Available Services" and "Enabled Services".
	 *
	 * @return string
	 */
	public function display_preview() {
		$settings = get_option( 'stk_button_look' );

		$html = '<div class="kindleWidget option option-smart-off">';
		$html .= '<a rel="nofollow" class="share-kindle share-icon sd-button">';
		$html .= '<span style="background-image: url(\'';
		$html .= plugin_dir_url( __FILE__ ) . 'media/';
		$html .= 'black' == $settings['color'] ? 'black' : 'white';
		$html .= '-15.png\')">';
		if ( 'kindle' == $settings['text'] ) {
			$html .= __( 'Kindle', 'kindle' );
		} elseif ( 'send-to-kindle' == $settings['text'] ) {
			$html .= __( 'Send to Kindle', 'kindle' );
		}
		$html .= '</span></a></div>';

		return $html;
	}

	/**
	 * Returns the HTML markup used in the settings screen for the Live Preview.
	 *
	 * @param mixed $post
	 * @return string
	 */
	public function get_display( $post ) {
		return $this->display_preview();
	}

	/**
	 * Adds the Send to Kindle Button to the list of services in the Sharedaddy
	 * module for Jetpack.
	 *
	 * @param array $services
	 * @return array
	 */
	public function inject_service ( array $services ) {
		if ( ! array_key_exists( 'kindle', $services ) ) {
			$services['kindle'] = 'Share_Kindle';
		}
		return $services;
	}

	/**
	 * Lets the user know Jetpack integration is available.
	 */
	public function jetpack_message() {
		global $current_screen;
		if ( 'toplevel_page_kindle-settings' != $current_screen->id ) {
			return;
		}
		echo '<div class="updated"><p>';
		global $current_screen;
		_e( 'It looks like you have Jetpack installed! Go to the settings screen for the sharebar and you will find an option for adding the Send to Kindle Button next to your other share buttons. Come back to this page to customize the text and icon.', 'kindle' );
		echo '</p></div>';
	}
}
?>