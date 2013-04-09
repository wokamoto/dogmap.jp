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

class STK_Settings_Advanced {
	/**
	 * Singleton instance.
	 *
	 * @var STK_Settings_Advanced
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

		$this->ui = array(
			'meta' => array(
				'title' => __( 'Meta selectors', 'kindle' ),
				'fields' => array(
					array(
						'name' => 'title',
						'display' => __( 'Title', 'kindle' ),
						'type' => 'text',
						'placeholder' => '.entry-title',
					),
					array(
						'name' => 'author',
						'display' => __( 'Author', 'kindle' ),
						'type' => 'text',
						'placeholder' => 'a[rel="author"]',
					),
					array(
						'name' => 'published',
						'display' => __( 'Published', 'kindle' ),
						'type' => 'text',
						'placeholder' => '.entry-date',
					),
				),
			),
			'content' => array(
				'title' => __( 'Content selectors', 'kindle' ),
				'fields' => array(
					array(
						'name' => 'content',
						'display' => __( 'Content', 'kindle' ),
						'type' => 'text',
						'placeholder' => '.entry-content',
					),
					array(
						'name' => 'exclude',
						'display' => __( 'Exclude', 'kindle' ),
						'type' => 'text',
						'placeholder' => '.advertising,#social-media,aside',
					),
					array(
						'name' => 'pagination',
						'display' => __( 'Pagination', 'kindle' ),
						'type' => 'text',
						'placeholder' => '.next',
					),
				),
			),
			'markup' => array(
				'title' => __( 'Custom HTML', 'kindle' ),
				'fields' => array(
					array(
						'name' => 'enabled',
						'display' => __( 'Enabled', 'kindle' ),
						'type' => 'checkbox',
					),
					array(
						'name' => 'markup',
						'display' => __( 'Markup', 'kindle' ),
						'type' => 'textarea',
						'placeholder' => '<div class="kindleWidget">Kindle</div>',
					),
				),
			),
		);
	}

	/**
	 * Retrieves the singleton instance or creates one if there is none.
	 *
	 * @return STK_Settings_Advanced instance of the advanced settings class
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize and whitelist Send to Kindle Advanced options.
	 *
	 * @uses register_setting
	 * @uses add_settings_section
	 * @uses add_settings_field
	 */
	public function register_options() {
		register_setting(
			'kindle_options_advanced',
			'stk_button_advanced',
			array( $this, 'validate')
		);

		$settings = get_option( "stk_button_advanced" );
		foreach ( $this->ui as $name => $section ) {
			add_settings_section(
				"kindle_advanced_$name",
				$section['title'],
				array( $this, "text_$name" ),
				'kindle-advanced-settings'
			);

			foreach( $section['fields'] as $option ) {
				add_settings_field(
					"kindle_advanced_{$option['name']}",
					$option['display'],
					array( $this, 'settings' ),
					'kindle-advanced-settings',
					"kindle_advanced_$name",
					// This merge is actually probably a bad idea and should be
					// refactored in the future.
					array_merge( $settings, $option )
				);
			}
		}
	}

	/**
	 * Echoes the text description of how metadata selectors work.
	 */
	public function text_meta() {
		echo '<p>';
		_e( 'Use CSS selectors to describe where metadata for your posts appear on the page.', 'kindle' );
		echo '</p>';
	}

	/**
	 * Echoes the text description of how content selectors work.
	 */
	public function text_content() {
		echo '<p>';
		_e( 'Similar to the metadata, CSS selectors to describe where the content is. You can also use a comma-separated list of the area you want to exclude, or how to find the "next" link for paginated articles.', 'kindle' );
		echo '</p>';
	}

	/**
	 * Echoes the text description of how custom HTML markup works.
	 */
	public function text_markup() {
		echo '<p>';
		_e( 'For maximum control over the button look and feel, write your own HTML. Just make sure you give it the class <code>kindleWidget</code> so our JavaScript can find it! For security reasons, your markup is sanitized to only allow the same HTML allowed inside of post content.', 'kindle' );
		echo '</p>';
	}

	/**
	 * Creates the UI elements specified.
	 *
	 * @uses esc_attr
	 * @uses esc_textarea
	 * @param array $args
	 */
	public function settings( array $args ) {
		if ( 'checkbox' === $args['type'] ) {
			// While similar to the checkbox code in the settings, this section
			// is NOT the same!
			echo '<input id="' . esc_attr( $args['name'] );
			echo '" name="stk_button_advanced[' . esc_attr( $args['name'] ) . ']"';
			checked( $args[ $args['name'] ] );
			echo "type='checkbox' value='1' />";
		} elseif ( 'text' === $args['type'] ) {
			echo '<input type="text" id="' . esc_attr( $args['name'] ) . '" ';
			if ( isset( $args['selectors'][ $args['name'] ] ) ) {
				echo "value='" . esc_attr( $args['selectors'][ $args['name'] ] ) . "' ";
			}
			echo 'autocomplete="off" spellcheck="false" class="regular-text code" ';
			echo 'placeholder="' . esc_attr( $args['placeholder'] );
			echo '" name="stk_button_advanced[selectors][' . esc_attr( $args['name'] ) . ']" />';
		} elseif ( 'textarea' === $args['type'] ) {
			echo '<textarea id="' . esc_attr( $args['name'] ) . '" class="large-text code" ';
			echo 'placeholder="' . esc_attr( $args['placeholder'] ) . '" spellcheck="false" ';
			echo 'rows="5" name="stk_button_advanced[' . esc_attr( $args['name'] ) . ']" >';
			echo esc_textarea( $args[ $args['name'] ] ) . '</textarea>';
		}
	}

	/**
	 * Ensures only valid data is copied to the stored settings.
	 *
	 * @param array $input raw settings data from the form
	 * @return array cleaned up settings data
	 */
	public function validate( array $input ) {
		$output = array('selectors' => array());

		foreach( $this->ui as $name => $section ) {
			foreach( $section['fields'] as $option ) {
				$field = $option['name'];
				if ( 'checkbox' === $option['type'] ) {
					$output[ $field ] = isset( $input[ $field ] );
				} elseif ( 'text' === $option['type'] && $input['selectors'][ $field ] ) {
					$output['selectors'][ $field ] = sanitize_text_field(
							$input['selectors'][ $field ] );
				} elseif ( 'textarea' === $option['type'] ) {
					$output[ $field ] = wp_kses_post( balanceTags( $input[ $field ] ) );
				} elseif ( 'select' === $option['type'] ) {
					if ( array_key_exists( $input[ $field ], $option['options'] ) ) {
						$output[ $field ] = $input[ $field ];
					}
				}
			}
		}

		return apply_filters( 'validate', $output, $input );
	}

	/**
	 * Adds the Send to Kindle advanced options menu to the Settings tab of the
	 * admin panel.
	 */
	public function options_menu() {
		$hook = add_submenu_page(
			'kindle-settings',
			__( 'Send to Kindle Advanced Settings', 'kindle' ),
			__( 'Advanced', 'kindle' ),
			'manage_options',
			'kindle-settings-advanced',
			array( $this, 'options_page' )
		);
		add_action( 'load-' . $hook, array( $this, 'help_tabs' ) );
	}

	/**
	 * Generates the help information tabs.
	 *
	 * @uses get_current_screen
	 * @uses add_help_tab
	 * @uses set_help_sidebar
	 */
	public function help_tabs() {
		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id' => 'kindle-help-advanced-overview',
			'title' => __( 'Overview', 'kindle' ),
			'content' => '<p>' . __( 'Some blogs are highly customized using themes and plugins, which can rearrange the content and make it hard to find. There are already some great solutions for automatically marking which content is important, like Microdata, Microformats and Open Graph, but if you cannot or do not want to use them you should provide CSS selectors so the Send to Kindle Button knows where your content is.', 'kindle' ) . '</p>',
		) );
		$screen->add_help_tab( array(
			'id' => 'kindle-help-advanced-meta',
			'title' => __( 'Meta Selectors', 'kindle' ),
			'content' => '<p>' . __( 'Metadata is literally data that describes other data. This refers to things like what your content is called, who wrote it, and when. This is important because it is how your readers will organize content after it arrives on their Kindle. The hardest piece of metadata to parse is typically the publication date, because browsers can sometimes be picky about what they accept. You should prefer to use dates in ISO-8601 format, i.e. 2013-01-15.', 'kindle' ) . '</p>',
		) );
		$screen->add_help_tab( array(
			'id' => 'kindle-help-advanced-content',
			'title' => __( 'Content Selectors', 'kindle' ),
			'content' => '<p>' . __( 'The content refers to the "body" of the article your readers want to read. Find a container on the page that contains all of the content for the full article. Sometimes this will also capture other things you do not want, such as share bars or advertising. In this case, a comma-separated list of selectors can be used to exclude everything that is not content. Finally, if your content is spread over multiple pages, use the Pagination selector to point to the "next page" link.', 'kindle' ) . '</p>',
		) );
		$screen->add_help_tab( array(
			'id' => 'kindle-help-advanced-html',
			'title' => __( 'Custom HTML', 'kindle' ),
			'content' => '<p>' . __( 'For maximum control over how the button looks, you can write your own HTML and give it classes that correspond to your stylesheets. Try to give it Kindle-related text so your readers know what they are clicking, and include the <code>kindleWidget</code> class so our JavaScript can find it.', 'kindle' ) . '</p>',
		) );
		$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'kindle' ) . '</strong></p>' .
				'<p><a target="_blank" href="http://www.w3.org/TR/CSS21/selector.html#pattern-matching">' . __( 'CSS 2.1 Selectors', 'kindle' ) . '</a></p>' .
				'<p><a target="_blank" href="http://www.schema.org/">' . __( 'Microdata', 'kindle' ) . '</a></p>' .
				'<p><a target="_blank" href="http://microformats.org/wiki/hnews">' . __( 'Microformats', 'kindle' ) . '</a></p>' .
				'<p><a target="_blank" href="http://ogp.me/">' . __( 'Open Graph', 'kindle' ) . '</a></p>'
		);
	}

	/**
	 * Generates the advanced configuration screen for the Send to Kindle Button.
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
		_e( 'Advanced', 'kindle' );
		echo '</h2><form action="options.php" method="post">';
		settings_fields( 'kindle_options_advanced' );
		do_settings_sections( 'kindle-advanced-settings' );
		echo '<p class="submit">';
		submit_button( null, 'primary', 'submit', false );
		echo '&nbsp;<input type="reset" class="button button-primary">';
		echo '</p></form></div>';
	}
}
?>