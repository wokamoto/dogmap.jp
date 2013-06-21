<?

/**
 * Edit Author Slug Uninstall Functions
 *
 * @package Edit_Author_Slug
 * @subpackage Uninstall
 *
 * @author Brandon Allen
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Make sure we're uninstalling
if ( !defined( 'WP_UNINSTALL_PLUGIN') )
	return false;

// Delete all the options
delete_option( '_ba_eas_author_base'           );
delete_option( '_ba_eas_db_version'            );
delete_option( '_ba_eas_old_options'           );
delete_option( '_ba_eas_do_auto_update'        );
delete_option( '_ba_eas_default_user_nicename' );
delete_option( 'ba_edit_author_slug'           );

// Final flush for good measure
flush_rewrite_rules( false );

?>