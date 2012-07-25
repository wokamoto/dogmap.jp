<?php
/* ==================================================
 *   Ktai Admin Options of Default Category
 *   based on wp-admin/options-writing.php of WP 2.3
   ================================================== */

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$title = __('Options');
$parent_file = 'options-cat.php';
include dirname(__FILE__) . '/admin-header.php';
if (isset($_GET['updated'])) { ?>
<p><font color="teal"><?php _e('Options saved.') ?></font></p>
<?php } ?>
<h2><?php _e('Default Post Category:', 'ktai_style'); ?></h2>
<form method="post" action="options.php">
<?php $Ktai_Style->admin->sid_field(); wp_nonce_field('update-options', "_wpnonce", false); ?>
<div><?php echo $Ktai_Style->admin->dropdown_categories('default_category', get_option('default_category')); ?>
</div>
<div><input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="default_category" />
<input type="submit" name="Submit" value="<?php _e('Set Category', 'ktai_style'); ?>" />
</div></form>
<?php include dirname(__FILE__) . '/admin-footer.php'; ?>