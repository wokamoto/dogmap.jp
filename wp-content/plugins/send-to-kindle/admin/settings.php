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

class STK_Settings {
	/**
	 * Singleton instance.
	 *
	 * @var STK_Settings
	 */
	private static $instance;

	/**
	 * Associative array representation of the configuration screen.
	 *
	 * @var array
	 */
	private $ui;

	/**
	 * Constructor to initialize interface and register events.
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'register_options' ) );
		add_action( 'admin_menu', array( $this, 'options_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_script' ) );

		$this->ui = array(
			'placement' => array(
				'title' => __( 'Placement', 'kindle' ),
				'fields' => array(
					array(
						'name' => 'pages',
						'display' => __( 'Pages', 'kindle' ),
						'type' => 'checkbox',
						'options' => array(
							'home' => __( 'Home', 'kindle' ),
							'archive' => __( 'Archive', 'kindle' ),
							'post' => __( 'Post', 'kindle' ),
							'page' => __( 'Page', 'kindle' ),
						),
					),
					array(
						'name' => 'placement',
						'display' => __( 'Placement', 'kindle' ),
						'type' => 'checkbox',
						'options' => array(
							'before' => __( 'Before', 'kindle' ),
							'after' => __( 'After', 'kindle' ),
						),
					),
				),
			),
			'style' => array (
				'title' => __( 'Style', 'kindle' ),
				'fields' => array(
					array(
						'name' => 'text',
						'display' => __( 'Text', 'kindle' ),
						'type' => 'select',
						'options' => array(
							'none' => __( 'None', 'kindle' ),
							'kindle' => __( 'Kindle', 'kindle' ),
							'send-to-kindle' => __( 'Send to Kindle', 'kindle' ),
						),
					),
					array(
						'name' => 'font',
						'display' => __( 'Font', 'kindle' ),
						'type' => 'select',
						'options' => array (
							'sans-serif' => __( 'Sans Serif', 'kindle' ),
							'arial' => __( 'Arial', 'kindle' ),
							'tahoma' => __( 'Tahoma', 'kindle' ),
							'verdana' => __( 'Verdana', 'kindle' ),
						),
					),
					array(
						'name' => 'color',
						'display' => __( 'Icon Color', 'kindle' ),
						'type' => 'radio',
						'options' => array(
							'black' => __( 'Dark', 'kindle' ),
							'white' => __( 'Light', 'kindle' ),
						),
					),
					array(
						'name' => 'size',
						'display' => __( 'Icon Size', 'kindle' ),
						'type' => 'radio',
						'options' => array(
							'small' => __( 'Small', 'kindle' ),
							'large' => __( 'Large', 'kindle' ),
						),
					),
					array(
						'name' => 'theme',
						'display' => __( 'Theme', 'kindle' ),
						'type' => 'radio',
						'options' => array(
							'dark' => __( 'Dark', 'kindle' ),
							'light' => __( 'Light', 'kindle' ),
						),
					),
					array(
						'name' => 'border',
						'display' => __( 'Border', 'kindle' ),
						'type' => 'checkbox',
						'options' => array(
							'border' => '',
						),
					),
				),
			),
		);
	}

	/**
	 * Retrieves the singleton instance or creates one if there is none.
	 *
	 * @return STK_Settings instance of the settings class
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds JavaScript and CSS for generating the live preview.
	 *
	 * @uses wp_enqueue_script
	 * @uses wp_register_style
	 * @uses wp_enqueue_style
	 * @param string $hook
	 */
	public function enqueue_script( $hook ) {
		// CSS to help out with the live preview and Jetpack
		wp_register_style( 'kindle_style', plugins_url( 'media/kindle.css', dirname( __FILE__ ) ) );
		wp_enqueue_style( 'kindle_style' );

		if ( 'toplevel_page_kindle-settings' !== $hook ) {
			return;
		}
		wp_enqueue_script( 'kindle_preview', plugins_url( 'preview.js',
				__FILE__ ), array( 'jquery' ) );
	}

	/**
	 * Initialize and whitelist Send to Kindle options.
	 *
	 * @uses register_setting
	 * @uses add_settings_section
	 * @uses add_settings_field
	 */
	public function register_options() {
		register_setting(
			'kindle_options_look',
			'stk_button_look',
			array( $this, 'validate')
		);

		$settings = get_option( 'stk_button_look' );
		foreach ( $this->ui as $name => $section ) {
			add_settings_section(
				"kindle_look_$name",
				$section['title'],
				array( $this, "text_$name" ),
				'kindle-look-settings'
			);

			foreach( $section['fields'] as $option ) {
				add_settings_field(
					"kindle_look_{$option['name']}",
					$option['display'],
					array( $this, 'settings' ),
					'kindle-look-settings',
					"kindle_look_$name",
					array_merge( $settings, $option )
				);
			}
		}
	}

	/**
	 * Echoes the text description for where the button will be placed.
	 */
	public function text_placement() {
		echo '<p>';
		_e( 'Choose which page types the Send to Kindle Button will appear on and whether to place it before or after the content.', 'kindle');
		echo '</p>';
	}

	/**
	 * Echoes the text description for how the button text should look.
	 */
	public function text_style() {
		echo '<p>';
		_e( 'Configure how the Send to Kindle Button will look.', 'kindle' );
		echo '</p>';
	}

	/**
	 * Creates the UI elements specified.
	 *
	 * @param array $args
	 */
	public function settings( array $args ) {
		if ( 'checkbox' === $args['type'] ) {
			// While similar to the checkbox code in the advanced settings, this
			// section is NOT the same!
			foreach( $args['options'] as $choice => $text ) {
				$id = esc_attr( $args['name'] . '_' . $choice );
				echo "<input id='$id' name='stk_button_look[" . esc_attr( $choice ) . "]'";
				checked( $args[ $choice ] );
				echo " type='checkbox' value='1' /><label for='$id'>&nbsp;";
				echo sanitize_text_field( $text ) . ' </label>';
			}
		} elseif ( 'radio' === $args['type'] ) {
			foreach( $args['options'] as $choice => $text ) {
				$id = esc_attr( $args['name'] . '_' . $choice );
				echo "<input id='$id' name='stk_button_look[" . esc_attr( $args['name'] ) . "]'";
				checked( $args[ $args['name'] ], $choice );
				echo ' type="radio" value="' . esc_attr( $choice ) . '" />';
				echo "<label for='$id'>&nbsp;" . sanitize_text_field( $text ) . ' </label>';
			}
		} elseif ( 'select' === $args['type'] ) {
			echo "<select id='{$args['name']}' name='stk_button_look[{$args['name']}]'>";
			foreach( $args['options'] as $choice => $text ) {
				echo '<option value="' . esc_attr( $choice ) . '"';
				selected( $args[ $args['name'] ], $choice );
				echo '>' . sanitize_text_field( $text )  . '</option>';
			}
			echo '</select>';
		}
	}

	/**
	 * Ensures only valid data is copied to the stored settings.
	 *
	 * @param array $input raw settings data from the form
	 * @return array cleaned up settings data
	 */
	public function validate( array $input ) {
		$output = array();

		foreach( $this->ui as $name => $section ) {
			foreach( $section['fields'] as $option ) {
				$field = $option['name'];
				if ( 'checkbox' === $option['type'] ) {
					foreach ( $option['options'] as $choice => $text ) {
						$output[ $choice ] = isset( $input[ $choice ] );
					}
				} elseif ( 'radio' === $option['type'] ) {
					if ( array_key_exists( $input[ $field ], $option['options'] ) ) {
						$output[ $field ] = $input[ $field ];
					}
				} elseif ( 'select' === $option['type'] ) {
					if ( array_key_exists( $input[ $field ], $option['options'] ) ) {
						$output[ $field ] = $input[ $field ];
					}
				}
			}
		}

		return apply_filters( 'validate_look', $output, $input );
	}

	/**
	 * Adds the Send to Kindle options menu to the "utility" section of the
	 * admin menu.
	 */
	public function options_menu() {
		add_utility_page(
			NULL,
			__( 'Send to Kindle', 'kindle' ),
			'manage_options',
			'kindle-settings',
			NULL,
			plugins_url( 'media/black-15.png', dirname( __FILE__ ) )
		);
		add_submenu_page(
			'kindle-settings',
			__( 'Send to Kindle Settings', 'kindle' ),
			__( 'Look and Feel', 'kindle' ),
			'manage_options',
			'kindle-settings',
			array( $this, 'options_page' )
		);
	}

	/**
	 * Generates the configuration screen for the Send to Kindle Button.
	 *
	 * @uses settings_fields
	 * @uses do_settings_sections
	 * @uses submit_button
	 */
	public function options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		echo '<div class="wrap"><h2>';
		_e( 'Look and Feel', 'kindle' );
		echo '</h2><form action="options.php" method="post">';
		settings_fields( 'kindle_options_look' );
		do_settings_sections( 'kindle-look-settings' );
		echo '<h3>';
		_e( 'Live Preview', 'kindle' );
		echo '</h3><div id="preview" style="padding:.5em;">';
		echo STK_Button::get_instance()->get_button_html();
		echo '</div>';
		submit_button();
		echo '</form></div>';
	}
}
?>