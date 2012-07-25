<?php
/* ==================================================
 *   Ktai Admin Reply Comments
   ================================================== */

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$parent_file = 'edit-comments.php';
$submenu_file = 'edit-comments.php';
$View = new KtaiAdmin_CommentReply($Ktai_Style);

/* ==================================================
 *   KtaiAdmin_CommentReply class
   ================================================== */

class KtaiAdmin_CommentReply {
	private $base;
	private $admin;
	
public function __construct($base) {
	$this->base  = $base;
	$this->admin = $base->admin;
	global $action;
	wp_reset_vars(array('action'));
	switch($action) {
	case 'post':
		$this->post_reply();
		break;
	default:
		if (isset($_GET['replytocom'])) {
			$this->show_form();
		}
		break;
	}
}

// ==================================================
private function show_form($errors = array()) {
	global $user_ID, $title, $comment, $id;
	$comment = intval($_GET['replytocom']);
	if (! $comment = get_comment($comment)) {
		$this->base->ks_die(__('Oops, no comment with this ID.'));
	}
	$title = __('Comment Reply', 'ktai_style');
	include dirname(__FILE__) . '/admin-header.php';
	$id = $comment->comment_post_ID;
	echo '<h2>' . sprintf(__('Comments for %s', 'ktai_style'), get_the_title($id)) . '</h2>';
	?><img localsrc="<?php comment_type(68, 112, 112); ?>" alt="" /><?php ks_comment_author_link();
	?><img localsrc="46" alt=" @ " /><font color="<?php echo ks_option('ks_date_color'); ?>"><?php ks_comment_datetime(); ?></font><br />
	<?php comment_text();?>
	<hr />
	<h3><?php _e('Reply to the above comment', 'ktai_style'); ?></h3>
	<?php if ($errors) {
		?><p><font color="red"><?php
			echo implode('<br />', array_map('wp_specialchars', $errors));
			?></font></p>
	<?php } ?>
<form action="" method="post">
<input type="hidden" name="action" value="post" />
<input type="hidden" name="redirect_to" value="<?php echo esc_attr($this->admin->get_referer()); ?>" />
<?php 
	$this->admin->sid_field();
	ks_fix_encoding_form(); ?>
	<p><?php _e('Comment', 'ktai_style');
	if (ks_option('ks_allow_pictograms')) {
		_e('(Pictograms Available)', 'ktai_style');
	} ?><br />
	<textarea name="content" cols="100%" rows="4"></textarea><br />
	<a href="<?php echo esc_attr($this->admin->get_referer()); ?>"><?php _e('Cancel reply', 'ktai_style'); ?></a><br />
	<input type="submit" name="submit" value="<?php _e('Say It!'); ?>" />
	<?php if (function_exists('comment_id_fields')) {
		comment_id_fields($id);
	} else { ?>
		<input type="hidden" name="comment_post_ID" value="<?php echo intval($id); ?>" />
	<?php } 
	ks_do_comment_form_action(); ?>
	</p></form>
<?php
	include dirname(__FILE__) . '/admin-footer.php';
}

// ==================================================
// Based on wp-admin/admin-ajax.php of WP 2.7
private function post_reply() {
	$comment_post_ID = (int) $_POST['comment_post_ID'];
	if (! current_user_can('edit_post', $comment_post_ID) ) {
		$this->base->ks_die(__('You are not allowed to edit comments on this post.'));
	}
	global $wpdb;
	$status = $wpdb->get_var( $wpdb->prepare("SELECT post_status FROM $wpdb->posts WHERE ID = %d", $comment_post_ID) );

	if ( empty($status) ) {
		$this->base->ks_die(__('No target for your post.', 'ktai_style'));
		exit;
	} elseif ( in_array($status, array('draft', 'pending')) ) {
		$this->base->ks_die( __('Error: you are replying to a comment on a draft post.', 'ktai_style') );
		exit;
	}
	$user = wp_get_current_user();
	if ( $user->ID ) {
		$comment_author       = $wpdb->escape($user->display_name);
		$comment_author_email = $wpdb->escape($user->user_email);
		$comment_author_url   = $wpdb->escape($user->user_url);
		$comment_content      = trim($_POST['content']);
		if ( current_user_can('unfiltered_html') ) {
			if ( wp_create_nonce('unfiltered-html-comment_' . $comment_post_ID) != $_POST['_wp_unfiltered_html_comment'] ) {
				kses_remove_filters(); // start with a clean slate
				kses_init_filters(); // set up the filters
			}
		}
	} else {
		$this->base->ks_die( __('Sorry, you must be logged in to reply to a comment.') );
	}

	$charset = $this->base->detect_encoding(isset($_POST['charset_detect']) ? $_POST['charset_detect'] : '');

	if ($this->base->similar_encoding($charset, $this->base->get('charset'))) {
		$comment_content = $this->base->ktai->pickup_pics(stripslashes($comment_content));
		if (! $this->base->get_option('ks_allow_pictograms')) {
			$comment_content = preg_replace('!<img localsrc="[^"]*" />!', '', $comment_content);
		}
		$comment_content = $wpdb->escape($comment_content);
	}
	if (function_exists('mb_convert_encoding')) {
		$comment_content = mb_convert_encoding($comment_content, get_bloginfo('charset'), $charset);
	}

	$comment_content = trim($comment_content);
	$errors = array();
	if ( '' == $comment_content ) {
		$errors[] = __('Error: please type a comment.');
	}
	if ($errors) {
		$this->show_form($errors);
		exit;
	}

	$comment_type = '';
	$comment_parent = abs(intval($_POST['comment_parent']));
	$user_ID = $user->ID;
	$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

	$comment_id = wp_new_comment( $commentdata );
	$comment = get_comment($comment_id);
	if (empty($_POST['redirect_to'])) {
		$location = 'edit-comments.php';
	} else {
		$location = stripslashes($_POST['redirect_to']);
	}
	$location = apply_filters('comment_post_redirect', $location, $comment);
	$this->admin->redirect($location);
	exit;
}

// ===== End of class ====================
}
?>