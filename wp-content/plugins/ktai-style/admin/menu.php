<?php
/* ==================================================
 *   Ktai Admin Build Menu
 *   based on wp-admin/menu.php of WP 2.3
   ================================================== */

if (! defined('ABSPATH')) {
	exit;
}
global $wpdb, $Ktai_Style;
$menu = array();
$submenu = array();

// This array constructs the admin menu bar.
//
// Menu item name
// The minimum level the user needs to access the item: between 0 and 10
// The URL of the item's file
$menu[0] = array(__('Dashboard'), 'read', './');
$menu[35] = array(__('Profile'), 'read', 'profile.php');
if ( $Ktai_Style->admin_available_wp_upper() ) { 
	$menu[5] = array(__('Edit Posts', 'ktai_style'), 'edit_posts', 'edit.php');
	$menu[10] = array(__('Edit Pages', 'ktai_style'), 'edit_pages', 'edit-pages.php');
	$menu[20] = array(__('Comments'), 'edit_posts', 'edit-comments.php');
	$menu[40] = array(__('Options'), 'manage_options', 'options-cat.php');

	$submenu['options-cat.php'][15] = array(__('Post Cat', 'ktai_style'), 'manage_options', 'options-cat.php');
	$submenu['options-cat.php'][16] = array(__('Email Post', 'ktai_style'), 'manage_options', 'options-cat_mail.php');
}

// Create list of page plugin hook names.
foreach ($menu as $menu_page) {
	$admin_page_hooks[$menu_page[2]] = sanitize_title($menu_page[0]);
}

$_wp_submenu_nopriv = array();
$_wp_menu_nopriv = array();
// Loop over submenus and remove pages for which the user does not have privs.
foreach ($submenu as $parent => $sub) {
	foreach ($sub as $index => $data) {
		if ( ! current_user_can($data[1]) ) {
			unset($submenu[$parent][$index]);
			$_wp_submenu_nopriv[$parent][$data[2]] = true;
		}
	}

	if ( empty($submenu[$parent]) )
		unset($submenu[$parent]);
}

// Loop over the top-level menu.
// Menus for which the original parent is not acessible due to lack of privs will have the next
// submenu in line be assigned as the new menu parent.
foreach ( $menu as $id => $data ) {
	if ( empty($submenu[$data[2]]) ) 
		continue;
	$subs = $submenu[$data[2]];
	$first_sub = array_shift($subs);
	$old_parent = $data[2];
	$new_parent = $first_sub[2];
	// If the first submenu is not the same as the assigned parent,
	// make the first submenu the new parent.
	if ( $new_parent != $old_parent ) {
		$_wp_real_parent_file[$old_parent] = $new_parent;
		$menu[$id][2] = $new_parent;

		foreach ($submenu[$old_parent] as $index => $data) {
			$submenu[$new_parent][$index] = $submenu[$old_parent][$index];
			unset($submenu[$old_parent][$index]);
		}
		unset($submenu[$old_parent]);
		$_wp_submenu_nopriv[$new_parent] = $_wp_submenu_nopriv[$old_parent];
	}
}

// Remove menus that have no accessible submenus and require privs that the user does not have.
// Run re-parent loop again.
foreach ( $menu as $id => $data ) {
	if ( ! current_user_can($data[1]) ) {
		$_wp_menu_nopriv[$data[2]] = true;
	}
	// If submenu is empty...
	if ( empty($submenu[$data[2]]) ) {
		// And user doesn't have privs, remove menu.
		if ( ! current_user_can($data[1]) ) {
			$_wp_menu_nopriv[$data[2]] = true;
			unset($menu[$id]);
		}
	}
}
unset($id);
uksort($menu, "strnatcasecmp"); // make it all pretty

if (! user_can_access_admin_page()) {
	$Ktai_Style->ks_die(__('You do not have sufficient permissions to access this page.'), '', false);
}
?>