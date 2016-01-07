<?php
/**
 * Displays the 404 error page of the theme.
 *
 * @package Theme Horse
 * @subpackage Interface
 * @since Interface 1.0
 */
?>
<?php get_header(); ?>
<?php
	/** 
	 * interface_404_content hook
	 *
	 * HOOKED_FUNCTION_NAME PRIORITY
	 *
	 * interface_display_404_page_content 10
	 */
	do_action( 'interface_404_content' );
?>
<?php get_footer(); ?>