<?php
/* ==================================================
 *   Ktai Admin Create Posts
 *   based on wp-admin/edit.php of WP 2.7
   ================================================== */

require dirname(__FILE__) . '/admin.php';
global $Ktai_Style;
$title = __('Add New Post');
//$parent_file = 'edit.php';
//$submenu_file = 'post-new.php';

if (! current_user_can('edit_posts')) {
	include dirname(__FILE__) . '/admin-header.php';
	?><p><?php printf(__('Since you&#8217;re a newcomer, you&#8217;ll have to wait for an admin to raise your level to 1, in order to be authorized to post.<br />
You can also <a href="mailto:%s?subject=Promotion?">e-mail the admin</a> to ask for a promotion.<br />
When you&#8217;re promoted, just reload this page and you&#8217;ll be able to blog. :)'), get_option('admin_email')); ?></p><?php
	include dirname(__FILE__) . '/admin-footer.php';
} else {
	$post = get_default_post_to_edit();
	include dirname(__FILE__) . '/edit-form.php';
}
?>