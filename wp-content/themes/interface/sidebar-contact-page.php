<?php
/**
 * Displays the sidebar on contact page template.
 *
 * @package Theme Horse
 * @subpackage Interface
 * @since Interface 1.0
 */
?>
<?php
	/**
	 * interface_before_contact_page_sidebar
	 */
	do_action( 'interface_before_contact_page_sidebar' );
?>
<?php
	/** 
	 * interface_contact_page_sidebar hook
	 *
	 * HOOKED_FUNCTION_NAME PRIORITY
	 *
	 * interface_display_contact_page_sidebar 10
	 */
	do_action( 'interface_contact_page_sidebar' );
?>
<?php
	/**
	 * interface_after_contact_page_sidebar
	 */
	do_action( 'interface_after_contact_page_sidebar' );
?>