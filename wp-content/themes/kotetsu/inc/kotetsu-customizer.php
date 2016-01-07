<?php
/**
 * Kotetsu_Customizer
 * Version: 1.0.1
 * Author: inc2734
 * Author URI: http://2inc.org
 */
class Kotetsu_Customizer {

	/**
	 * Defaults theme mods
	 */
	protected $defaults = array();

	/**
	 * __construct
	 */
	public function __construct() {
		$this->defaults = apply_filters( 'kotetsu_theme_mods_defaults', array(
			'logo'                => get_template_directory_uri() . '/images/common/logo.png',
			'gnav_color'          => '#377ab1',
			'gnav_rollover_color' => '#3370a1',
			'font_color'          => '#000',
			'link_color'          => '#377ab1',
		) );
	}

	/**
	 * get_theme_mod
	 * @param string $key
	 * @return string
	 */
	protected function get_theme_mod( $key ) {
		if ( isset( $this->defaults[$key] ) ) {
			$theme_mod = get_theme_mod( $key );
			if ( !$theme_mod ) {
				$theme_mod = $this->defaults[$key];
			}
			return $theme_mod;
		}
	}

	/**
	 * customize_register
	 * @param WP_Customizer $wp_customize
	 */
	public function customize_register( $wp_customize ) {
		$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
		$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';

		$wp_customize->add_section( 'kotetsu_design', array(
			'title'    => __( 'settings', 'kotetsu' ),
			'priority' => 100,
		) );

		require_once get_template_directory() . '/inc/kotetsu-logo-control.php';
		$wp_customize->add_setting( 'logo', array(
			'default'   => $this->defaults['logo'],
			'sanitize_callback' => array( $this, 'sanitize_image_url' ),
		) );
		$wp_customize->add_control( new Kotetsu_Logo_Control( $wp_customize, 'logo', array(
			'label'    => __( 'Logo', 'kotetsu' ),
			'section'  => 'kotetsu_design',
			'settings' => 'logo',
		) ) );

		$wp_customize->add_setting( 'gnav_color' , array(
			'default'   => $this->defaults['gnav_color'],
			'transport' => 'postMessage',
			'sanitize_callback' => array( $this, 'sanitize_colorcode' ),
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'gnav_color', array(
			'label'    => __( 'Global navigation color', 'kotetsu' ),
			'section'  => 'colors',
			'settings' => 'gnav_color',
		) ) );

		$wp_customize->add_setting( 'gnav_rollover_color' , array(
			'default'   => $this->defaults['gnav_rollover_color'],
			'sanitize_callback' => array( $this, 'sanitize_colorcode' ),
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'gnav_rollover_color', array(
			'label'    => __( 'Global navigation rollover color', 'kotetsu' ),
			'section'  => 'colors',
			'settings' => 'gnav_rollover_color',
		) ) );

		$wp_customize->add_setting( 'font_color' , array(
			'default'   => $this->defaults['font_color'],
			'transport' => 'postMessage',
			'sanitize_callback' => array( $this, 'sanitize_colorcode' ),
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'font_color', array(
			'label'    => __( 'Font color', 'kotetsu' ),
			'section'  => 'colors',
			'settings' => 'font_color',
		) ) );

		$wp_customize->add_setting( 'link_color' , array(
			'default'   => $this->defaults['link_color'],
			'transport' => 'postMessage',
			'sanitize_callback' => array( $this, 'sanitize_colorcode' ),
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'link_color', array(
			'label'    => __( 'Link color', 'kotetsu' ),
			'section'  => 'colors',
			'settings' => 'link_color',
		) ) );
	}

	/**
	 * customize_css
	 */
	public function customize_css() {
		?>
		<style>
		body,
		.hentry .entry-title a {
			color: <?php echo $this->get_theme_mod( 'font_color' ); ?>;
		}
		a,
		.hentry .entry-title a:hover,
		.hentry .entry-title a:active {
			color: <?php echo $this->get_theme_mod( 'link_color' ); ?>;
		}
		.entries .hentry .entry-thumbnail a {
			background-color: <?php echo $this->get_theme_mod( 'link_color' ); ?>;
		}
		.global-nav,
		.global-nav ul li a {
			background-color: <?php echo $this->get_theme_mod( 'gnav_color' ); ?>;
		}
		.global-nav ul li.current-menu-ancestor > a,
		.global-nav ul li.current-menu-item > a,
		.global-nav ul li.current_page_item > a,
		.global-nav ul li a:hover {
			background-color: <?php echo $this->get_theme_mod( 'gnav_rollover_color' ); ?>;
		}
		</style>
		<?php
	}

	/**
	 * customize_preview_js
	 */
	public function customize_preview_js() {
		wp_enqueue_script( 'customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '18', true );
	}

	/**
	 * sanitize_image_url
	 * @param string $value
	 * @return string $value
	 */
	public function sanitize_image_url( $value ) {
		if ( preg_match( '/^' . preg_quote( home_url(), '/' ) . '\/.+?\.(gif|jpg|jpeg|bmp|png)$/', $value ) ) {
			return $value;
		}
		return false;
	}

	/**
	 * sanitize_colorcode
	 * @param string $value
	 * @return string $value
	 */
	public function sanitize_colorcode( $value ) {
		if ( preg_match( '/^#([\da-fA-F]{6}|[\da-fA-F]{3})$/', $value ) ) {
			return $value;
		}
		return false;
	}
}