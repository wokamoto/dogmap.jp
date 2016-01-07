<?php
/**
 * Displays the right sidebar of the theme.
 *
 * @package Theme Horse
 * @subpackage Interface
 * @since Interface 1.0
 */
?>
<?php
	/**
	 * interface_before_right_sidebar
	 */
	do_action( 'interface_before_right_sidebar' );
?>
<?php
	/** 
	 * interface_right_sidebar hook
	 *
	 * HOOKED_FUNCTION_NAME PRIORITY
	 *
	 * interface_display_right_sidebar 10
	 */
	do_action( 'interface_right_sidebar' );
?>
<?php
	/**
	 * interface_after_right_sidebar
	 */
	do_action( 'interface_after_right_sidebar' );
?>