<?php
/**
 * Adds footer structures.
 *
 * @package 		Theme Horse
 * @subpackage 		Interface
 * @since 			Interface 1.0
 * @license 		http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link 			http://themehorse.com/themes/interface
 */

/****************************************************************************************/
global $options, $array_of_default_settings;
$options = wp_parse_args( get_option( 'interface_theme_options', array() ), interface_get_option_defaults());
add_action( 'interface_footer', 'interface_footer_widget_area', 5 );
/** 
 * Displays the footer widgets
 */
function interface_footer_widget_area() {
	get_sidebar( 'footer' );
}

/****************************************************************************************/
if ((1 != $options['disable_bottom']) && (!empty($options['social_phone'] ) || !empty($options['social_email'] ) || !empty($options['social_location']))) {
add_action( 'interface_footer', 'interface_footer_infoblog', 10 );
/**
 * Opens the footer infobox
 */
/****************************************************************************************/

add_action( 'interface_footer', 'interface_footer_div_close', 15 );
/**
 * Opens the site generator div.
 */
function interface_footer_div_close() {
	echo '</div> <!-- .container -->

	</div> <!-- .info-bar -->';
	} 
}
/****************************************************************************************/

add_action( 'interface_footer', 'interface_open_sitegenerator_div', 20 );
/**
 * Opens the site generator div.
 */
function interface_open_sitegenerator_div() {
	echo '

	<div id="site-generator">
				<div class="container clearfix">';
}

	/****************************************************************************************/


add_action( 'interface_footer', 'interface_socialnetworks', 25 );



/****************************************************************************************/

add_action( 'interface_footer', 'interface_footer_info', 30 );
/**
 * function to show the footer info, copyright information
 */
function interface_footer_info() {         
   $output = '<div class="copyright">'.__( 'Copyright &copy;', 'interface' ).' '.interface_the_year().' ' .interface_site_link().' | ' . ' '.__( 'Theme by:', 'interface' ).' '.interface_themehorse_link().' | '.' '.__( 'Powered by:', 'interface' ).' '.interface_wp_link() .'</div><!-- .copyright -->';
   echo $output;
}
/****************************************************************************************/

add_action( 'interface_footer', 'interface_close_sitegenerator_div', 35 );
/**
 * Shows the back to top icon to go to top.
 */
function interface_close_sitegenerator_div() {
echo '</div><!-- .container -->	
			</div><!-- #site-generator -->';
}

/****************************************************************************************/

add_action( 'interface_footer', 'interface_backtotop_html', 40 );
/**
 * Shows the back to top icon to go to top.
 */
function interface_backtotop_html() {
	echo '<div class="back-to-top"><a href="#branding">'.__( ' ', 'interface' ).'</a></div>';
}

?>