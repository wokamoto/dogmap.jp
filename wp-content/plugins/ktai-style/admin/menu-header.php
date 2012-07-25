<?php
/* ==================================================
 *   Ktai Admin Display the Menu
 *   based on wp-admin/menu-header.php of WP 2.3
   ================================================== */

if (! defined('ABSPATH')) {
	exit;
}
global $Ktai_Style, $menu, $submenu, $parent_file, $submenu_file;
$self = preg_replace('!^.*' . preg_quote($Ktai_Style->get('plugin_dir'), '!') . '/admin/!i', '', $_SERVER['PHP_SELF']);
if (strcmp($self, 'index.php') == 0) {
	$self = './';
}
get_admin_page_parent();

// Sub Menu
if ( isset($submenu[$parent_file]) ) :
	$menu_items = array();
	foreach ($submenu[$parent_file] as $item) :
		if (! current_user_can($item[1]) ) {
			continue;
		}
		if ((isset($submenu_file) && $submenu_file == $item[2]) 
		|| (! isset($submenu_file) && ((isset($plugin_page) && $plugin_page == $item[2]) || ( !isset($plugin_page) && $self == $item[2])))) {
			$menu_items[] = $item[0];
		} else {
			$menu_items[] = '<a href="' . esc_attr($item[2]) . '">' . $item[0] . '</a>';
		}
	endforeach;
	if (count($menu_items)) {
		echo  implode(' | ', $menu_items) . '<hr color="#4f96c8"/>';
	}
endif;

// Main Menu
$menu_items = array();
foreach ($menu as $item) {
	// 0 = name, 1 = capability, 2 = file
	$is_current = ((strcmp($self, $item[2]) == 0 && empty($parent_file)) || ($parent_file && ($item[2] == $parent_file)));
	if (! empty($submenu[$item[2]])) {
		$submenu[$item[2]] = array_values($submenu[$item[2]]);  // Re-index.
		$m = array('link' => $submenu[$item[2]][0][2], 'desc' => $item[0]);
	} else if ( current_user_can($item[1]) ) {
		$m = array('link' => $item[2], 'desc' => $item[0]);
	}
	if ($is_current) {
		$menu_items[] = $m['desc'];
	} else {
		$menu_items[] = '<a href="' . esc_attr($m['link']) . '">' . $m['desc'] . '</a>';
	}
}
echo implode(' | ', $menu_items);
printf(' [<a href="%s">' . __('Log out') . '</a>]', ks_get_logout_url() );
echo '<br />';
if ($Ktai_Style->get('cookie_available')) {
	ks_ordered_link(0, 0, ks_blogurl(KTAI_NOT_ECHO), __('Visit Site with logged in', 'ktai_style'));
} else {
	?><img localsrc="1" alt="!" /><font color="red"><?php 
	_e('DO NOT bookmark this page. DO NOT tell others the URL of this page.', 'ktai_style');
	?></font><br /><?php
}
?>