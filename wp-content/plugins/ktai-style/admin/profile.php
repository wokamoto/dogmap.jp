<?php
/* ==================================================
 *   Ktai Admin Profile
 *   based on wp-admin/profile.php of WP 2.3
   ================================================== */

require dirname(__FILE__) . '/admin.php';
global $Ktai_Style;
$is_profile_page = true;
$title = __('Profile');
$parent_file = 'profile.php';

wp_reset_vars(array('action', 'user_id'));
$user_id = (int) $user_id;
if ( !$user_id ) {
	if ( $is_profile_page ) {
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
	} else {
		$Ktai_Style->ks_die(__('Invalid user ID.'));
	}
} elseif ( !get_userdata($user_id) ) {
	$Ktai_Style->ks_die( __('Invalid user ID.') );
}

switch ($action) {
case 'update':
	check_admin_referer('update-profile_' . $user_id);

	require_once ABSPATH . WPINC . '/registration.php';
	$_POST['nickname'] = $Ktai_Style->decode_from_ktai($_POST['nickname']);
	do_action('personal_options_update');
	$errors = edit_user($user_id);
	if ( is_wp_error( $errors ) ) {
		$Ktai_Style->ks_die(implode('<br />', $errors->get_error_messages()));
	}
	$Ktai_Style->admin->redirect('profile.php?updated=true');
	exit;
default:
	$profileuser = get_user_to_edit($user_id);
	if ( !current_user_can('edit_user', $user_id) ) {
		$Ktai_Style->ks_die(__('You do not have permission to edit this user.'));
	}
	include dirname(__FILE__) . '/admin-header.php';
	if ( isset($_GET['updated']) ) { ?>
<p><font color="teal"><?php _e('User updated.'); ?></font></p>
<?php } ?>
<form action="" method="post"><div>
<?php 
	$Ktai_Style->admin->sid_field();
	wp_nonce_field('update-profile_' . $user_id, "_wpnonce", false);
	ks_fix_encoding_form(); ?>
<label><?php _e('Nickname') ?><br />
<input type="text" name="nickname" size="100%" tabidex="1" value="<?php echo esc_attr($profileuser->nickname); ?>" /></label><br />
<label><?php _e('E-mail') ?><br />
<input type="text" name="email" size="100%" istyle="3" mode="alphabet" tabidex="2" value="<?php echo esc_attr($profileuser->user_email); ?>" /></label></div>
<p><input type="hidden" name="action" value="update" />
<input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
<input type="submit" name="submit" tabindex="3" value="<?php _e('Update') ?>" /></p>
</form>
<?php include dirname(__FILE__) . '/admin-footer.php';
} ?>