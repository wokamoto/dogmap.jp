<?php
/**
 * Interface defining constants, adding files and WordPress core functionality.
 *
 * Defining some constants, loading all the required files and Adding some core functionality.
 * @uses add_theme_support() To add support for post thumbnails and automatic feed links.
 * @uses register_nav_menu() To add support for navigation menu.
 * @uses set_post_thumbnail_size() To set a custom post thumbnail size.
 *
 * @package Theme Horse
 * @subpackage Interface
 * @since Interface 1.0
 */
add_action( 'after_setup_theme', 'interface_setup' );
/**
 * This content width is based on the theme structure and style.
 */
 function interface_setup() {
	 	global $content_width;
		if ( ! isset( $content_width ) ){
			$content_width = 700;
		}
 }


add_action( 'interface_init', 'interface_constants', 10 );
/**
 * This function defines the Interface theme constants
 *
 * @since 1.0
 */
function interface_constants() {

	/** Define Directory Location Constants */
	define( 'INTERFACE_PARENT_DIR', get_template_directory() );
	define( 'INTERFACE_CHILD_DIR', get_stylesheet_directory() );
	define( 'INTERFACE_IMAGES_DIR', INTERFACE_PARENT_DIR . '/images' );
	define( 'INTERFACE_INC_DIR', INTERFACE_PARENT_DIR. '/inc' );
	define( 'INTERFACE_PARENT_CSS_DIR', INTERFACE_PARENT_DIR. '/css' );
	define( 'INTERFACE_ADMIN_DIR', INTERFACE_INC_DIR . '/admin' );
	define( 'INTERFACE_ADMIN_IMAGES_DIR', INTERFACE_ADMIN_DIR . '/images' );
	define( 'INTERFACE_ADMIN_JS_DIR', INTERFACE_ADMIN_DIR . '/js' );
	define( 'INTERFACE_JS_DIR', INTERFACE_PARENT_DIR . '/js' );
	define( 'INTERFACE_CSS_DIR', INTERFACE_PARENT_DIR . '/css' );	
	define( 'INTERFACE_FUNCTIONS_DIR', INTERFACE_INC_DIR . '/functions' );
	define( 'INTERFACE_SHORTCODES_DIR', INTERFACE_INC_DIR . '/footer_info' );
	define( 'INTERFACE_STRUCTURE_DIR', INTERFACE_INC_DIR . '/structure' );
	if ( ! defined( 'INTERFACE_LANGUAGES_DIR' ) ) /** So we can define with a child theme */
		define( 'INTERFACE_LANGUAGES_DIR', INTERFACE_PARENT_DIR . '/languages' );
	define( 'INTERFACE_WIDGETS_DIR', INTERFACE_INC_DIR . '/widgets' );

	/** Define URL Location Constants */
	define( 'INTERFACE_PARENT_URL', get_template_directory_uri() );
	define( 'INTERFACE_CHILD_URL', get_stylesheet_directory_uri() );
	define( 'INTERFACE_IMAGES_URL', INTERFACE_PARENT_URL . '/images' );
	define( 'INTERFACE_INC_URL', INTERFACE_PARENT_URL . '/inc' );
	define( 'INTERFACE_ADMIN_URL', INTERFACE_INC_URL . '/admin' );
	define( 'INTERFACE_ADMIN_JS_URL', INTERFACE_ADMIN_URL . '/js' );
	define( 'INTERFACE_ADMIN_CSS_URL', INTERFACE_ADMIN_URL . '/css' );
	define( 'INTERFACE_JS_URL', INTERFACE_PARENT_URL . '/js' );
	define( 'INTERFACE_CSS_URL', INTERFACE_PARENT_URL . '/css' );
	define( 'INTERFACE_FUNCTIONS_URL', INTERFACE_INC_URL . '/functions' );
	define( 'INTERFACE_SHORTCODES_URL', INTERFACE_INC_URL . '/footer_info' );
	define( 'INTERFACE_STRUCTURE_URL', INTERFACE_INC_URL . '/structure' );
	if ( ! defined( 'INTERFACE_LANGUAGES_URL' ) ) /** So we can predefine to child theme */
		define( 'INTERFACE_LANGUAGES_URL', INTERFACE_PARENT_URL . '/languages' );
	define( 'INTERFACE_WIDGETS_URL', INTERFACE_INC_URL . '/widgets' );

}

add_action( 'interface_init', 'interface_load_files', 15 );
/**
 * Loading the included files.
 *
 * @since 1.0
 */
function interface_load_files() {
	/** 
	 * interface_add_files hook
	 *
	 * Adding other addtional files if needed.
	 */
	do_action( 'interface_add_files' );

	/** Load functions */
	require_once( INTERFACE_FUNCTIONS_DIR . '/i18n.php' );
	require_once( INTERFACE_FUNCTIONS_DIR . '/custom-header.php' );
	require_once( INTERFACE_FUNCTIONS_DIR . '/functions.php' );
	require_once( INTERFACE_FUNCTIONS_DIR . '/custom-style.php' );
	require_once( INTERFACE_ADMIN_DIR . '/interface-themedefaults-value.php' );
	require_once( INTERFACE_FUNCTIONS_DIR . '/customizer.php' );
	require_once( INTERFACE_ADMIN_DIR . '/interface-metaboxes.php' );
	

	/** Load Shortcodes */
	require_once( INTERFACE_SHORTCODES_DIR . '/interface-footer_info.php' );

	/** Load Structure */
	require_once( INTERFACE_STRUCTURE_DIR . '/header-extensions.php' );
	require_once( INTERFACE_STRUCTURE_DIR . '/searchform-extensions.php' );
	require_once( INTERFACE_STRUCTURE_DIR . '/sidebar-extensions.php' );
	require_once( INTERFACE_STRUCTURE_DIR . '/footer-extensions.php' );
	require_once( INTERFACE_STRUCTURE_DIR . '/content-extensions.php' );

	/** Load Widgets and Widgetized Area */
	require_once( INTERFACE_WIDGETS_DIR . '/interface_widgets.php' );
}

add_action( 'interface_init', 'interface_core_functionality', 20 );
/**
 * Adding the core functionality of WordPess.
 *
 * @since 1.0
 */
function interface_core_functionality() {
	/** 
	 * interface_add_functionality hook
	 *
	 * Adding other addtional functionality if needed.
	 */
	do_action( 'interface_add_functionality' );

	// Add default posts and comments RSS feed links to head
	add_theme_support( 'automatic-feed-links' );

	/*
	* Let WordPress manage the document title.
	* By adding theme support, we declare that this theme does not use a
	* hard-coded <title> tag in the document head, and expect WordPress to
	* provide it for us.
	*/
	add_theme_support( 'title-tag' );

	// This theme uses Featured Images (also known as post thumbnails) for per-post/per-page.
	add_theme_support( 'post-thumbnails' ); 
 
	// This theme uses wp_nav_menu() in header menu location.
	register_nav_menu( 'primary', __( 'Primary Menu', 'interface' ) );

	// Add Interface custom image sizes
	add_image_size( 'featured', 670, 300, true );
	add_image_size( 'featured-medium', 230, 160, true );
	add_image_size( 'slider-narrow', 1038, 500, true ); 		// used on Featured Slider on Homepage Header for narrow layout
	add_image_size( 'slider-wide', 1440, 500, true ); 			// used on Featured Slider on Homepage Header for wide layout
	add_image_size( 'gallery', 474, 342, true ); 				// used to show gallery all images
	add_image_size( 'icon', 100, 100, true );						//used for icon on business layout
	

	/**
	 * This theme supports custom background color and image
	 */
	add_theme_support( 'custom-background' );

	// Adding excerpt option box for pages as well
	add_post_type_support( 'page', 'excerpt' );
}

/** 
 * interface_init hook
 *
 * Hooking some functions of functions.php file to this action hook.
 */
do_action( 'interface_init' );
?>