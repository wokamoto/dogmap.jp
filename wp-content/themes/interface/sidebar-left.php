<?php
/**
 * Displays the left sidebar of the theme.
 *
 * @package Theme Horse
 * @subpackage Interface
 * @since Interface 1.0
 */
?>
<?php
	/**
	 * interface_before_left_sidebar
	 */
	do_action( 'interface_before_left_sidebar' );
?>
<?php
	/** 
	 * interface_left_sidebar hook
	 *
	 * HOOKED_FUNCTION_NAME PRIORITY
	 *
	 * interface_display_left_sidebar 10
	 */
	do_action( 'interface_left_sidebar' );
?>
<?php
	/**
	 * interface_after_left_sidebar
	 */
	do_action( 'interface_after_left_sidebar' );
?>