<?php
/* ==================================================
 *   Ktai Admin Options
 *   based on wp-admin/options-writing.php of WP 2.3
   ================================================== */

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$title = __('Options');
$this_file = 'options.php';
$parent_file = 'options-writing.php';
wp_reset_vars(array('action'));
if (! current_user_can('manage_options')) {
	$Ktai_Style->ks_die(__('Cheatin&#8217; uh?'));
}
switch($action) {
case 'update':
	$any_changed = 0;
	check_admin_referer('update-options');
	if ( !$_POST['page_options'] ) {
		foreach ( (array) $_POST as $key => $value) {
			if ( !in_array($key, array('_wpnonce', '_wp_http_referer')) )
				$options[] = $key;
		}
	} else {
		$options = explode(',', stripslashes($_POST['page_options']));
	}

	if ($options) {
		foreach ($options as $option) {
			$option = trim($option);
			$value = $_POST[$option];
			if (!is_array($value)) {
				$value = trim($value);
			}
			$value = stripslashes_deep($value);
			update_option($option, $value);
		}
	}

	$goback = add_query_arg('updated', 'true', wp_get_referer());
	$Ktai_Style->admin->redirect($goback);
    break;
default:
	$Ktai_Style->admin->redirect('options-writing.php');
	break;
} // end switch
include dirname(__FILE__) . '/admin-footer.php'; ?>