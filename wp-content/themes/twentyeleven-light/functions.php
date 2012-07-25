<?php
/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) )
	$content_width = 584;

function twentyeleven_light_init() {
	// Register jQuery ( Google Ajax Libraries )
	if ( !is_admin() ) {
		wp_deregister_script('jquery');
		wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js', array(), '1.7.1');
	}
}
add_action( 'init', 'twentyeleven_light_init' );

/**
 * Tell WordPress to run twentyeleven_setup() when the 'after_setup_theme' hook is run.
 */
add_action( 'after_setup_theme', 'twentyeleven_setup' );

if ( ! function_exists( 'twentyeleven_setup' ) ):
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which runs
 * before the init hook. The init hook is too late for some features, such as indicating
 * support post thumbnails.
 *
 * To override twentyeleven_setup() in a child theme, add your own twentyeleven_setup to your child theme's
 * functions.php file.
 *
 * @uses load_theme_textdomain() For translation/localization support.
 * @uses add_editor_style() To style the visual editor.
 * @uses add_theme_support() To add support for post thumbnails, automatic feed links, and Post Formats.
 * @uses register_nav_menus() To add support for navigation menus.
 * @uses add_custom_background() To add support for a custom background.
 * @uses add_custom_image_header() To add support for a custom header.
 * @uses register_default_headers() To register the default custom header images provided with the theme.
 * @uses set_post_thumbnail_size() To set a custom post thumbnail size.
 *
 * @since Twenty Eleven 1.0
 */
function twentyeleven_setup() {

	/* Make Twenty Eleven available for translation.
	 * Translations can be added to the /languages/ directory.
	 * If you're building a theme based on Twenty Eleven, use a find and replace
	 * to change 'twentyeleven' to the name of your theme in all the template files.
	 */
	load_theme_textdomain( 'twentyeleven', get_template_directory() . '/languages' );

	$locale = get_locale();
	$locale_file = get_template_directory() . "/languages/$locale.php";
	if ( is_readable( $locale_file ) )
		require_once( $locale_file );

	// This theme styles the visual editor with editor-style.css to match the theme style.
	add_editor_style();

	// Load up our theme options page and related code.
	require( get_template_directory() . '/inc/theme-options.php' );

	// Grab Twenty Eleven's Ephemera widget.
	require( get_template_directory() . '/inc/widgets.php' );

	// Add default posts and comments RSS feed links to <head>.
	add_theme_support( 'automatic-feed-links' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menu( 'primary', __( 'Primary Menu', 'twentyeleven' ) );

	// Add support for a variety of post formats
	add_theme_support( 'post-formats', array( 'aside', 'link', 'gallery', 'status', 'quote', 'image' ) );

	// Add support for custom backgrounds
	add_custom_background();

	// This theme uses Featured Images (also known as post thumbnails) for per-post/per-page Custom Header images
	add_theme_support( 'post-thumbnails' );

	// The next four constants set how Twenty Eleven supports custom headers.

	// The default header text color
	define( 'HEADER_TEXTCOLOR', '000' );

	// By leaving empty, we allow for random image rotation.
	define( 'HEADER_IMAGE', '' );

	// The height and width of your custom header.
	// Add a filter to twentyeleven_header_image_width and twentyeleven_header_image_height to change these values.
	define( 'HEADER_IMAGE_WIDTH', apply_filters( 'twentyeleven_header_image_width', 1000 ) );
	define( 'HEADER_IMAGE_HEIGHT', apply_filters( 'twentyeleven_header_image_height', 288 ) );

	// We'll be using post thumbnails for custom header images on posts and pages.
	// We want them to be the size of the header image that we just defined
	// Larger images will be auto-cropped to fit, smaller ones will be ignored. See header.php.
	set_post_thumbnail_size( HEADER_IMAGE_WIDTH, HEADER_IMAGE_HEIGHT, true );

	// Add Twenty Eleven's custom image sizes
	add_image_size( 'large-feature', HEADER_IMAGE_WIDTH, HEADER_IMAGE_HEIGHT, true ); // Used for large feature (header) images
	add_image_size( 'small-feature', 500, 300 ); // Used for featured posts if a large-feature doesn't exist

	// Turn on random header image rotation by default.
	add_theme_support( 'custom-header', array( 'random-default' => true ) );

	// Add a way for the custom header to be styled in the admin panel that controls
	// custom headers. See twentyeleven_admin_header_style(), below.
	add_custom_image_header( 'twentyeleven_header_style', 'twentyeleven_admin_header_style', 'twentyeleven_admin_header_image' );

	// ... and thus ends the changeable header business.

	// Default custom headers packaged with the theme. %s is a placeholder for the theme template directory URI.
	$stylesheet_uri = get_stylesheet_directory_uri();
	register_default_headers( array(
		'wheel' => array(
			'url' => $stylesheet_uri.'/images/headers/wheel.jpg',
			'thumbnail_url' => '%s/images/headers/wheel-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Wheel', 'twentyeleven' )
		),
		'shore' => array(
			'url' => $stylesheet_uri.'/images/headers/shore.jpg',
			'thumbnail_url' => '%s/images/headers/shore-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Shore', 'twentyeleven' )
		),
		'trolley' => array(
			'url' => $stylesheet_uri.'/images/headers/trolley.jpg',
			'thumbnail_url' => '%s/images/headers/trolley-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Trolley', 'twentyeleven' )
		),
		'pine-cone' => array(
			'url' => $stylesheet_uri.'/images/headers/pine-cone.jpg',
			'thumbnail_url' => '%s/images/headers/pine-cone-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Pine Cone', 'twentyeleven' )
		),
		'chessboard' => array(
			'url' => $stylesheet_uri.'/images/headers/chessboard.jpg',
			'thumbnail_url' => '%s/images/headers/chessboard-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Chessboard', 'twentyeleven' )
		),
		'lanterns' => array(
			'url' => $stylesheet_uri.'/images/headers/lanterns.jpg',
			'thumbnail_url' => '%s/images/headers/lanterns-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Lanterns', 'twentyeleven' )
		),
		'willow' => array(
			'url' => $stylesheet_uri.'/images/headers/willow.jpg',
			'thumbnail_url' => '%s/images/headers/willow-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Willow', 'twentyeleven' )
		),
		'hanoi' => array(
			'url' => $stylesheet_uri.'/images/headers/hanoi.jpg',
			'thumbnail_url' => '%s/images/headers/hanoi-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Hanoi Plant', 'twentyeleven' )
		)
	) );

	remove_action( 'wp_enqueue_scripts', 'twentyeleven_enqueue_color_scheme' );
}
endif; // twentyeleven_setup

if ( ! function_exists( 'twentyeleven_header_style' ) ) :
/**
 * Styles the header image and text displayed on the blog
 *
 * @since Twenty Eleven 1.0
 */
function twentyeleven_header_style() {
	// If no custom options for text are set, let's bail
	// get_header_textcolor() options: HEADER_TEXTCOLOR is default, hide text (returns 'blank') or any hex value
	if ( HEADER_TEXTCOLOR == get_header_textcolor() )
		return;
	// If we get this far, we have custom styles. Let's do this.
	echo "\n<style type=\"text/css\">\n";
	if ( 'blank' == get_header_textcolor() )
		// Has the text been hidden?
		echo '#site-title,#site-description{position:absolute !important;clip:rect(1px 1px 1px 1px);clip:rect(1px, 1px, 1px, 1px);}';
	else
		// If the user has set a custom color for the text use that
		echo '#site-title a,#site-description {color:#' . get_header_textcolor() .' !important;}';
	echo "\n</style>\n";
}
endif; // twentyeleven_header_style

function twentyeleven_light_enqueue_color_scheme($color_scheme) {
	$options = twentyeleven_get_theme_options();
	$color_scheme = $options['color_scheme'];

	if ( 'dark' == $color_scheme ) {
		wp_dequeue_style( 'dark' );
		wp_enqueue_style( 'dark', get_stylesheet_directory_uri() . '/colors/dark.css', array(), null );
	}
}
add_action( 'wp_enqueue_scripts', 'twentyeleven_light_enqueue_color_scheme' );


/**
 * HTML Cleaner
 *
 */
function twentyeleven_light_html_cleaner_helper($content) {
	$home_url = trailingslashit(get_home_url('/'));
	$ret_val = preg_replace(
		array('/<(img [^>]*src=|a [^>]*href=|)[\'"]('.preg_quote($home_url,'/').')[\'"]/i', '/<br[ \t]*\/>/i') ,
		array('$1"$2"', '<br>'),
		$content
		);

	$pattern = '/(' .
		'<(meta [^>]*property="og:(url|image)"' .
		'|link [^>]*rel="(canonical|shortlink)")[^>]*>' .
		'|<pre[^>]*>.*?<\/pre>' .
		'|<script[^>]*>.*?<\/script>' .
		'|<style[^>]*>.*?<\/style>' .
		'|<[^>]*style=["\'][^"\']*["\'][^>]*>' .
		')/ims';
	$replace = array();
	if (preg_match_all($pattern, $content, $matches)) {
		foreach ($matches[0] as $match) {
			if (preg_match('/^(<script[^>]*>)(.*?)(<\/script>)/ims', $match, $wk)) {
				$replace[] =
					trim(str_replace($home_url, '/', $wk[1])) .
					trim(preg_replace(array('/^[ \t]+/ims','/[ \t]*([\'"=;])[ \t]+/ims'),array('','$1'),$wk[2])) .
					trim($wk[3]);
			} elseif (preg_match('/^(<style[^>]*>)(.*?)(<\/style>)/ims', $match, $wk)) {
				$replace[] =
					trim(str_replace($home_url, '/', $wk[1])) .
					trim(preg_replace(array('/^[ \t]+/ims','/[ \t]*([:;])[ \t]+/ims'),array('','$1'),$wk[2])) .
					trim($wk[3]);
			} elseif (preg_match('/^(<[^>]*style=["\'])([^"\']*)(["\'][^>]*>)/ims', $match, $wk)) {
				$replace[] =
					trim(str_replace($home_url, '/', $wk[1])) .
					trim(preg_replace(array('/^[ \t]+/ims','/[ \t]*([:;])[ \t]+/ims'),array('','$1'),$wk[2])) .
					trim(str_replace($home_url, '/', $wk[3]));
			} else {
				$replace[] = trim($match);
			}
			unset($wk);
		}
	}
	$ret_val = trim(preg_replace(
		array('/[\r\n]/', '/[\t ]+/', '/>[\t ]+</', '/[\t ]+>/'),
		array('', ' ','><', '>'),
		$ret_val
		));
	if (count($replace) > 0 && preg_match_all($pattern, $ret_val, $matches)) {
		$ret_val = str_replace($matches[0], $replace, $ret_val);
	}
	unset($replace);
	unset($matches);

	// remove comments
	$pattern = '/<\![\-]+.*?[\-]+>/ims';
	$IE_tag_pattern = '/\[if[ \t]*[\(]?[ \t]*(IE|[gl]te?[ \t]+IE)[^\]]*[\)]?[^\]]*\]/i';
	$replace = array();
	if (preg_match_all($pattern, $ret_val, $matches)) {
		foreach ($matches[0] as $match) {
			if (preg_match($IE_tag_pattern, $match)) {
				$replace[] = trim($match);
			} else {
				$replace[] = '';
			}
		}
	}
	if (count($replace) > 0 && preg_match_all($pattern, $ret_val, $matches)) {
		$ret_val = str_replace($matches[0], $replace, $ret_val);
	}
	unset($replace);
	unset($matches);

	if ( is_user_logged_in() && function_exists('dbgx_trace_var') )
		dbgx_trace_var($ret_val);

	return $ret_val;
}
add_filter( 'the_content', 'twentyeleven_light_html_cleaner_helper', 99 );
