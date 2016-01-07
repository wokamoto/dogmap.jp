<?php
/**
 * Contains all the theme option default values
 * 
 *  Default value is userd  for all the settings. If the user-defined values
 * is available then the default values will be used.
 *
 * @package Theme Horse
 * @subpackage Interface
 * @since Interface 1.0
 */

function interface_get_option_defaults() {
	global $array_of_default_settings;
	$array_of_default_settings = array(
	'hide_header_searchform'				=> 0,
 	'disable_slogan' 							=> 0,
 	'home_slogan1'								=> '',
 	'home_slogan2'								=> '',
 	'slogan_position'							=> 'above-slider',
 	'disable_promobox'						=> '',
	'disable_promobox' 						=> '0',
 	'home_promobox1'							=> '',
 	'home_promobox2'							=> '',
 	'promobox_position'						=> 'below-services',
 	'disable_status'							=> '',
 	'disable_slider'							=> 0,
 	'exclude_slider_post'					=> 0,
 	'default_layout'							=> 'right-sidebar',
 	'reset_layout'								=> '0',
 	'custom_css'								=> '',
 	'disable_favicon'							=> 1,
 	'disable_top'								=> 0,
 	'disable_bottom'							=> 0,
 	'favicon'									=> '',
 	'disable_webpageicon'					=> 1,
 	'webpageicon'								=> '',
 	'slider_quantity' 						=> '4',
	'slider_type'								=> 'image-slider',
 	'featured_text_position'				=> 'default-position',
 	'featured_post_slider'					=> array(),
 	'featured_image_slider_image'			=> array(),
 	'featured_image_slider_link'			=> array(),
 	'featured_image_slider_title'			=> array(),
 	'featured_image_slider_description'	=> array(),
 	'header_slider'							=> '',
 	'transition_effect'						=> 'fade',
 	'transition_delay'						=> '4',
 	'transition_duration'					=> '1',
 	'social_phone' 							=> '',
	'social_email' 							=> '',
	'social_location'							=> '',
 	'social_facebook' 						=> '',
 	'social_twitter' 							=> '',
 	'social_googleplus' 						=> '',
 	'social_pinterest' 						=> '',
 	'social_vimeo' 							=> '',
 	'social_linkedin' 						=> '',
 	'social_flickr' 							=> '',
 	'social_tumblr' 							=> '',
 	'social_rss' 								=> '',
 	'social_youtube'							=> '',
 	'front_page_category'					=> array(),
 	'header_logo'								=> '',
 	'header_show'								=> 'header-text',
 	'button_text'								=> '',
 	'redirect_button_link'					=> '',
 	'site_layout'								=> 'wide-layout',
	'site_design'								=> 'on',
	'slider_content'							=> 'on',
 );
return apply_filters( 'interface_get_option_defaults', $array_of_default_settings);
}
?>