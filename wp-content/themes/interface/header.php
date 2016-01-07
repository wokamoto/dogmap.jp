<?php
/**
 * Displays the header section of the theme.
 *
 * @package Theme Horse
 * @subpackage Interface
 * @since Interface 1.0
 */
?>
<!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) | !(IE 8) ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
<?php		
		/** 
		 * interface_title hook
		 *
		 * HOOKED_FUNCTION_NAME PRIORITY
		 *
		 * interface_add_meta_name 5
		 *
		 */
			//global $interface_theme_setting_value;
		 //echo $interface_theme_setting_value['home_slogan1' ]; 
		do_action( 'interface_title' );

		/** 
		 * interface_meta hook
		 */
		do_action( 'interface_meta' );

		/** 
		 * interface_links hook
		 *
		 * HOOKED_FUNCTION_NAME PRIORITY
		 *
		 * interface_add_links 10
		 * interface_favicon 15
		 * interface_webpage_icon 20
		 *
		 */
		do_action( 'interface_links' ); ?>
<?php 
		/** 
		 * This hook is important for WordPress plugins and other many things
		 */
		wp_head();
	?>
</head>

<body <?php body_class(); ?>>
<?php
		/** 
		 * interface_before hook
		 */
		do_action( 'interface_before' );
	?>
<div class="wrapper">
<?php
			/** 
			 * interface_before_header hook
			 */
			do_action( 'interface_before_header' );
		?>
<header id="branding" >
  <?php
				/** 
				 * interface_header hook
				 *
				 * HOOKED_FUNCTION_NAME PRIORITY
				 *
				 * interface_headercontent_details 10
				 */
				do_action( 'interface_header' );
			?>
</header>
<?php
			/** 
			 * interface_after_header hook
			 */
			do_action( 'interface_after_header' );
		?>
<?php
			/** 
			 * interface_before_main hook
			 */
			do_action( 'interface_before_main' );
		?>
<div id="main">
<div class="container clearfix">
