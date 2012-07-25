<?php
/* ==================================================
 *   Ktai Admin Create Pages
 *   based on wp-admin/edit.php of WP 2.7
   ================================================== */

require dirname(__FILE__) . '/admin.php';
global $Ktai_Style;
$title = __('Add New Page');
//$parent_file = 'edit-pages.php';
//$submenu_file = 'page-new.php';

if (! current_user_can('edit_pages') ) {
	?><p><?php printf(__('Since you&#8217;re a newcomer, you&#8217;ll have to wait for an admin to raise your level to 1, in order to be authorized to post.<br />
You can also <a href="mailto:%s?subject=Promotion?">e-mail the admin</a> to ask for a promotion.<br />
When you&#8217;re promoted, just reload this page and you&#8217;ll be able to blog. :)'), get_option('admin_email')); ?></p><?php
} else {
//	$action = 'post';
	$post = get_default_page_to_edit();
	include dirname(__FILE__) . '/edit-page-form.php';
}
?>