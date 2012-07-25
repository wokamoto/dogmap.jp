<?php
if ( !defined('ABSPATH') ) {
	exit;
}

/* ==================================================
 *   Ktai Style Install class
   ================================================== */

class KtaiStyle_Install {

/* ==================================================
 * @param	none
 * @return	none
 * @since   0.93
 */
public static function install() {
	global $wpdb;
	if (! current_user_can('activate_plugins')) {
		return;
	}
	$charset_collate = '';
	if ( $wpdb->supports_collation() ) {
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}
	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ktaisession` (
		`sid` varchar(32) NOT NULL,
		`next_id` varchar(32) NULL default NULL,
		`expires` datetime NOT NULL default '0000-00-00 00:00:00',
		`user_id` bigint(20) NOT NULL default 0,
		`user_pass` varchar(64) NOT NULL default '',
		`user_agent` varchar(255) NULL,
		`term_id` varchar(64) NULL,
		`sub_id` varchar(64) NULL,
		`data` text NULL,
		PRIMARY KEY (`sid`)
		) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since   1.90
 */
public static function install_sitewidely() {
	$blogs = get_blog_list(0, 'all', false);
	if (is_array($blogs)) {
		reset($blogs);
		foreach((array) $blogs as $key => $details) {
			switch_to_blog($details['blog_id']);
			self::install();
			restore_current_blog();
		}
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since   0.93
 */
public static function uninstall() {
	global $wpdb;
	if (! current_user_can('activate_plugins')) {
		return;
	}
	$sql = "DROP TABLE IF EXISTS `{$wpdb->prefix}ktaisession`;";
	$wpdb->query($sql);
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since   1.90
 */
public function uninstall_sitewidely() {
	$blogs = get_blog_list(0, 'all', false);
	if (is_array($blogs)) {
		reset($blogs);
		foreach((array) $blogs as $key => $details) {
			switch_to_blog($details['blog_id']);
			self::uninstall();
			restore_current_blog();
		}
	}
}

// ===== End of class ====================
}
?>