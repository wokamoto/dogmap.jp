<?php
/* ==================================================
 *   Ktai Admin Process Comments
 *   based on wp-admin/edit.php,edit-form-comments.php of WP 2.3
   ================================================== */

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$parent_file = 'edit-comments.php';
$submenu_file = 'edit-comments.php';
$View = new KtaiAdmin_Comments($Ktai_Style);

/* ==================================================
 *   KtaiAdmin_Comments class
   ================================================== */

class KtaiAdmin_Comments {
	private $base;
	private $admin;
	private $referer;
	
public function __construct($base) {
	$this->base  = $base;
	$this->admin = $base->admin;
	$this->referer = $this->admin->get_referer();
	if ( '' != wp_get_original_referer() ) {
		$this->referer = wp_get_original_referer();
	}
	$this->referer = remove_query_arg( array('approved', 'unapproved', 'spammed', 'unspammed', 'trashed', 'untrashed', 'deleted', 'ids'), $this->referer );

	global $action;
	wp_reset_vars(array('action'));
	if ( 'cdc' == $action ) {
		$action = 'delete';
	} elseif ( 'mac' == $action ) {
		$action = 'approve';
	}
	if ( isset( $_GET['dt'] ) ) {
		if ( 'spam' == $_GET['dt'] ) {
			$action = 'spam';
		} elseif ( 'trash' == $_GET['dt'] ) {
			$action = 'trash';
		}
	}
	switch($action) {
	case 'spam':
	case 'approve':
	case 'delete':
	case 'trash':
		$this->confirm($action);
		break;
	case 'editcomment':
		$this->edit_form();
		break;
	case 'deletecomment':
	case 'trashcomment':
	case 'untrashcomment':
	case 'spamcomment':
	case 'unspamcomment':
		$this->delete_comment($action);
		exit();
	case 'approvecomment':
	case 'unapprovecomment':
		$this->approve_comment($action);
		exit();
	case 'editedcomment':
		$this->edited_comment();
		exit();
	default:
		break;
	}
}

// ==================================================
private function edit_form() {
	global $user_ID, $title;
	$comment = intval($_GET['c']);
	if (! $comment = get_comment($comment)) {
		$this->base->ks_die(__('Oops, no comment with this ID.'));
	}
	if (! current_user_can('edit_post', $comment->comment_post_ID)) {
		$this->base->ks_die(__('You are not allowed to edit comments on this post.'));
	}
	$comment = get_comment_to_edit($comment);
	$title = sprintf(__('Editing Comment # %s'), $comment->comment_ID);
	include dirname(__FILE__) . '/admin-header.php';
	$submitbutton_text = __('Edit Comment');
	$form_action = 'editedcomment';
	$form_extra = '<input type="hidden" name="comment_ID" value="' . $comment->comment_ID . '" /><input type="hidden" name="comment_post_ID" value="' . $comment->comment_post_ID . '" />';
?>
<form action="comment.php" method="post">
<?php 
	$this->admin->sid_field();
	ks_fix_encoding_form();
	wp_nonce_field('update-comment_' . $comment->comment_ID, "_wpnonce", false);
?>
<input type="hidden" name="user_ID" value="<?php echo intval($user_ID) ?>" />
<input type="hidden" name="action" value="<?php echo $form_action; ?>" /><?php echo $form_extra; ?>
<div><?php _e('Name') ?><br />
<input type="text" name="newcomment_author" size="24" maxlength="99" value="<?php echo $comment->comment_author; ?>" tabindex="1" /><br />
<?php _e('E-mail') ?><br />
<input type="text" name="newcomment_author_email" size="30" maxlength="128" value="<?php echo $comment->comment_author_email; ?>" tabindex="2" /><br />
<?php _e('URL') ?><br />
<input type="text" name="newcomment_author_url" size="36" maxlength="256" value="<?php echo $comment->comment_author_url; ?>" tabindex="3" /><br />
<?php _e('Content', 'ktai_style') ?><br />
<?php $this->admin->the_editor($comment->comment_content, 6, 4);
$id = $this->base->ktai->read_term_id($comment);
if (count($id)) {
	if ($id[0]) {
		echo '<img localsrc="161" alt="&middot;"/>' . sprintf(__('Term ID: %s', 'ktai_style'), esc_attr($id[0])) . '<br />';
	}
	if ($id[1]) {
		echo '<img localsrc="56" alt="&middot;"/>' . sprintf(__('USIM ID: %s', 'ktai_style'), esc_attr($id[1])) . '<br />';
	}
	if ($id[2]) {
		echo '<img localsrc="d170" alt="&middot;"/>' . sprintf(__('Sub ID: %s', 'ktai_style'), esc_attr($id[2])) . '<br />';
	}
} ?>
<?php _e('Approval Status', 'ktai_style') ?><br />
<input name="comment_status" type="radio" value="1" <?php checked($comment->comment_approved, '1'); ?> /> <?php printf(__('<font color="%s">Approved</font>', 'ktai_style'), 'green'); ?>
<input name="comment_status" type="radio" value="0" <?php checked($comment->comment_approved, '0'); ?> /> <?php printf(__('<font color="%s">Pending</font>', 'ktai_style'), '#ff9900'); ?>
<input name="comment_status" type="radio" value="spam" <?php checked($comment->comment_approved, 'spam'); ?> /> <?php printf(__('<font color="%s">Spam</font>', 'ktai_style'), 'red'); ?><br />
<?php
	$referer = $this->base->strip_host($this->referer);
	if ($referer) {
		printf(__('<a href="%s">Cancel Edit</a>', 'ktai_style'), esc_attr($referer) );
		?><input name="referredby" type="hidden" value="<?php echo esc_attr($referer); ?>" /><?php
		$orig_referer = '&_wp_original_http_referer=' . urlencode($referer);
	} else {
		$orig_referer = '';
	}
	?> <input type="submit" name="editcomment" value="<?php echo $submitbutton_text ?>" /><br /><?php
	if (function_exists('wp_trash_comment') && EMPTY_TRASH_DAYS) {
		$button = __('Move to Trash');
		$delete_url = sprintf('comment.php?action=trashcomment&c=%d%s', $comment->comment_ID, $orig_referer );
		$delete_url = wp_nonce_url($delete_url, 'delete-comment_' . $comment->comment_ID);
	} else {
		$button = __('Delete Comment');
		$delete_url = sprintf('comment.php?action=deletecomment&c=%d%s', $comment->comment_ID, $orig_referer );
	}
?><img localsrc="61" /><a href="<?php echo esc_attr($delete_url); ?>"><font color="fuchsia"><?php echo $button; ?></font></a>
<input type="hidden" name="c" value="<?php echo intval($comment->comment_ID) ?>" />
<input type="hidden" name="p" value="<?php echo intval($comment->comment_post_ID) ?>" />
<input type="hidden" name="noredir" value="1" />
</div>
</form>
<?php
	include dirname(__FILE__) . '/admin-footer.php';
}

// ==================================================
private function confirm($action) {
	global $comment;
	$comment_id = intval($_GET['c']);
	$formaction   = $action . 'comment';
	$nonce_action = ('approve' == $action) ? 'approve-comment_' : 'delete-comment_';
	$nonce_action .= $comment_id;
	if (! $comment = get_comment_to_edit($comment_id)) {
		$this->base->ks_die(__('Oops, no comment with this ID.') . sprintf(' <a href="%s">' . __('Go back') . '</a>', 'edit-comments.php' ), '', false);
	}
	if (! current_user_can('edit_post', $comment->comment_post_ID)) {
		$this->base->ks_die('delete' == $action ? __('You are not allowed to delete comments on this post.') : __('You are not allowed to edit comments on this post, so you cannot approve this comment.'));
	}
	include dirname(__FILE__) . '/admin-header.php';
	switch ($action) {
	case 'spam':
		$message = __('You are about to mark the following comment as spam:');
		break;
	case 'trash':
		$message = __('You are about to move the following comment to the Trash:', 'ktai_style');
		break;
	case 'delete':
		$message = __('You are about to delete the following comment:');
		break;
	default:
		$message = __('You are about to approve the following comment:');
		break;
	}
	echo '<p><img localsrc="1" alt="" /><font color="red">' . $message . '</font><br />' . __('Are you sure you want to do this?') . '</p>';
?>
<form action="edit-comments.php" method="get">
<?php $this->admin->sid_field(); ?>
<div><input type="submit" value="<?php _e('No'); ?>" /></div></form>
<form action="comment.php" method="get">
<?php $this->admin->sid_field(); wp_nonce_field($nonce_action); ?>
<input type="hidden" name="action" value="<?php echo esc_attr($formaction); ?>" />
<input type="hidden" name="p" value="<?php echo intval($comment->comment_post_ID); ?>" />
<input type="hidden" name="c" value="<?php echo intval($comment->comment_ID); ?>" />
<input type="hidden" name="noredir" value="1" />
<div><input type="submit" value="<?php _e('Yes'); ?>" /></div>
</form>
<dl><dt><img localsrc="<?php comment_type(68, 112, 112); ?>" alt="[<?php comment_type(__('Comment', 'ktai_style'), __('Trackback'), __('Pingback')); ?>] " /><?php comment_author(); ?><img localsrc="46" alt=" @ " /><font color="<?php echo ks_option('ks_date_color'); ?>"><?php ks_comment_datetime(); ?></font></dt><dd><?php 
	if ($comment->comment_author_email) { 
		?><img localsrc="108" alt="" /><font color="olive"><?php comment_author_email(); ?></font><br /><?php
	}
	if ($comment->comment_author_url && 'http://' != $comment->comment_author_url) {
		?><img localsrc="112" alt="" /><font color="olive"><?php comment_author_url(); ?></font><br /><?php 
	}
	comment_excerpt(); ?></dd></dl><?php
	include dirname(__FILE__) . '/admin-footer.php';
}

// ==================================================
private function delete_comment($action) {
	$comment_id = intval($_REQUEST['c']);
	check_admin_referer('delete-comment_' . $comment_id);
	$noredir = isset($_REQUEST['noredir']);
	if ( !$comment = get_comment($comment_id) ) {
		 $this->base->ks_die(__('Oops, no comment with this ID.') . sprintf(' <a href="%s">'.__('Go back').'</a>!', 'edit-comments.php'), '', false);
		 //exit;
	}
	if (! current_user_can('edit_post', $comment->comment_post_ID) ) {
		$this->base->ks_die(__('You are not allowed to edit comments on this post.'));
	}
	$redir = $this->referer;
	if ( empty($redir) || $noredir || false !== strpos($redir, 'comment.php')) {
		$redir = 'edit-comments.php';
	}

	switch ( $action ) {
	case 'deletecomment':
		wp_delete_comment($comment_id);
		$redir = add_query_arg( array( 'deleted' => 1 ), $redir );
		break;
	case 'trashcomment':
		if (function_exists('wp_trash_comment')) {
			wp_trash_comment($comment_id);
			$redir = add_query_arg( array('trashed' => '1', 'ids' => $comment_id), $redir );
		}
		break;
	case 'untrashcomment':
		if (function_exists('wp_untrash_comment')) {
			wp_untrash_comment($comment_id);
			$redir = add_query_arg( array('untrashed' => '1'), $redir );
		}
		break;
	case 'spamcomment':
		if (function_exists('wp_spam_comment')) {
			wp_spam_comment($comment_id);
		} else {
			wp_set_comment_status($comment_id, 'spam');
		}
		$redir = add_query_arg( array('spammed' => '1', 'ids' => $comment_id), $redir );
		break;
	case 'unspamcomment':
		if (function_exists('wp_spam_comment')) {
			wp_unspam_comment($comment_id);
			$redir = add_query_arg( array('unspammed' => '1'), $redir );
		}
		break;
	}
	$this->admin->redirect($redir);
	exit;
}

// ==================================================
private function approve_comment($action) {
	$comment_id = intval($_GET['c']);
	check_admin_referer('approve-comment_' . $comment_id);
	$noredir = isset($_GET['noredir']);
	if ( !$comment = get_comment($comment_id) ) {
		$this->base->ks_die(__('Oops, no comment with this ID.') . sprintf(' <a href="%s">'.__('Go back').'</a>!', 'edit-comments.php'), '', false);
	}
	if ( !current_user_can('edit_post', $comment->comment_post_ID) ) {
		if ( 'approvecomment' == $action ) {
			$this->base->ks_die(__('You are not allowed to edit comments on this post, so you cannot approve this comment.'));
		} else {
			$this->base->ks_die(__('You are not allowed to edit comments on this post, so you cannot disapprove this comment.'));
		}
		// exit;
	}

	$redir = $this->referer;
	if ( empty($redir) || $noredir ) {
		$redir = 'edit-comments.php?p=' . intval($comment->comment_post_ID);
	}

	if ( 'approvecomment' == $action ) {
		wp_set_comment_status($comment_id, 'approve');
		$redir = add_query_arg( array( 'approved' => 1 ), $redir );
	} else {
		wp_set_comment_status($comment_id, 'hold');
		$redir = add_query_arg( array( 'unapproved' => 1 ), $redir );
	}
	if ( get_option('comments_notify')) {
		wp_notify_postauthor($comment->comment_ID);
	}
	$this->admin->redirect($redir);
	exit;
}

// ==================================================
private function edited_comment() {
	$comment_ID = intval($_POST['comment_ID']);
	$comment_post_ID = intval($_POST['comment_post_ID']);
	check_admin_referer('update-comment_' . $comment_ID);
	$this->edit_comment($comment_ID, $comment_post_ID);
	$location = ( empty($_POST['referredby']) ? "edit-comments.php?p=$comment_post_ID" : $_POST['referredby'] );
	$location = apply_filters('comment_edit_redirect', $location, $comment_ID);
	$this->admin->redirect($location);
}

/* ==================================================
 * @param	none
 * @return	none
 * based on edit_post() at wp-admin/includes/post.php of WP 2.3
 */
private function edit_comment($comment_ID, $comment_post_ID) {
	if (! current_user_can('edit_post', $comment_post_ID)) {
		$this->base->ks_die(__('You are not allowed to edit comments on this post, so you cannot edit this comment.'));
	}
	$charset = ks_detect_encoding();
	if ( !$this->base->get('encoding_converted') ) {
		foreach ( array('newcomment_author','content') as $f) {
			$_POST[$f] = $this->base->decode_from_ktai($_POST[$f], $charset);
		}
	}
	$comment_data = array();
	$comment_data['comment_author']       = trim(strip_tags($_POST['newcomment_author']));
	$comment_data['comment_author_email'] = trim(strip_tags($_POST['newcomment_author_email']));
	$comment_data['comment_author_url']   = trim(strip_tags($_POST['newcomment_author_url']));
	$comment_data['comment_approved']     = trim(strip_tags($_POST['comment_status']));
	$comment_data['comment_content']      = trim(           $_POST['content']);
	$comment_data['comment_ID']           = intval($_POST['comment_ID']);
	wp_update_comment($comment_data);
}

// ===== End of class ====================
}
?>