<?php if (! ks_is_comments()) {
	return;
}
$need_password = ks_check_password(ks_is_comments_list() ?
	__('Enter your password to view comments.') :
	__('Enter your password to post a comment.', 'ktai_style'));
if ($need_password && ! is_user_logged_in()) {
	echo $need_password;
	return;
}

function ks_comment_content() {
	global $comment;
	 ks_comment_author_link(); ?>@ <?php ks_comment_datetime(); ?><br />
	<?php if ($comment->comment_approved == '0') { ?>
		<em><font color="red"><?php _e('Your comment is awaiting moderation.', 'ktai_style'); ?></font></em><br />
	<?php }
	comment_text();
	edit_comment_link('<font color="' . $args['edit_color'] . '">' . __('Edit') . '</font>', '[ ', ' ]');
}

function ks_list_comments($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment;
	?><li><a name="comment-<?php comment_ID(); ?>"><?php 
	if (! ks_option('ks_separate_comments')) {
		?>[<?php comment_type(_x('Comment', 'noun'), __('Trackback'), __('Pingback')); ?>]<?php 
	}
	?></a> <?php 
	ks_comment_content(); 
	comment_reply_link(array_merge(
		array(
			'before' => '[ ', 
			'after' => ' ]', 
			'reply_before' => '', 
			'reply_text' => __('Reply'), 
			'login_text' => __('Log in to Reply'), 
		), 
		$args, 
		array('depth' => $depth, 'max_depth' => $args['max_depth'])
	));
	?></li><?php
}

global $wp_query, $comment;
if (ks_is_comments_list()) :
	ks_comments_post_link(NULL, '', '<hr />', '');
	$walker = new KS_Walker_Comment;
	if (ks_option('ks_separate_comments') && ! get_option('page_comments')) {
		$sep_comments = ks_separete_comments($comments);
		$label = array('comment' => __('Comments'), 'pings' => __('Pings', 'ktai_style'));
	} else {
		$sep_comments[] = $comments;
		$label = array();
	}
	foreach ($sep_comments as $type => $_comments) :
		if (isset($label[$type])) {
			echo '<h3>' . $label[$type] . '</h3>';
		}
		if (count($_comments)) : ?>
			<ul>
			<?php wp_list_comments(array('walker' => $walker, 'avatar_size' => 0, 'callback' => 'ks_list_comments'), $_comments);
			if (get_option('page_comments') && $wp_query->max_num_comment_pages >= 2) {
				?><div align="center"><p><?php 
				previous_comments_link(__('<img localsrc="7" alt="&laquo; ">Older Comments', 'ktai_style') . '<br />');
				next_comments_link(__('Newer Comments<img localsrc="8" alt=" &raquo;">', 'ktai_style'));
				?></p></div><?php 
			} ?>
			</ul>
		<?php else : // If there are no comments yet ?>
			<p><?php echo ($type != 'pings') ? 
				__('No comments yet.') : 
				__('No pings yet.', 'ktai_style');
			?></p>
		<?php endif;
	endforeach; 
elseif (ks_is_comment_post()) :
	ks_comments_link('', __('No Comments/Pings', 'ktai_style'), __('One Comment/Ping', 'ktai_style'), __('% Comments and Pings', 'ktai_style'));
	$login_url = ks_get_login_url(KTAI_NOT_ECHO, ks_comments_post_url());
?>
	<hr />
	<?php if (! comments_open()) { ?>
		<p><?php _e('Sorry, the comment form is closed at this time.'); ?></p>
	<?php } elseif ( get_option('comment_registration') && ! is_user_logged_in() ) {
		if ( $login_url ) { ?>
			<p><?php printf(__('You must be <a href="%s">logged in</a> to post a comment.'), esc_url($login_url) ); ?></p>
		<?php } else { ?>
			<p><?php _e("Can't post a comment from mobile phones. You must logged in from PC to make a comment.", 'ktai_style'); ?></p>
		<?php }
	} else {
		$replytocom = isset($_GET['replytocom']) ? abs(intval($_GET['replytocom'])) : NULL;
		if ($replytocom) {
			$comment = get_comment($replytocom);
			if (! $comment || $comment->comment_approved != 1) {
				return;
			}
			?><div><?php ks_comment_content(); ?></div>
			<hr />
			<h3><?php _e('Reply to the above comment', 'ktai_style'); ?></h3><?php 
		}
		global $ks_commentdata;
		if (isset($ks_commentdata['message']) && $ks_commentdata['message']) {
			$comment_author       = $ks_commentdata['author'];
			$comment_author_email = $ks_commentdata['email'];
			$comment_author_url   = $ks_commentdata['url'];
			$comment_content      = $ks_commentdata['content'];
			?><p><font color="red">
			<?php echo implode("<br />", array_map('esc_html', explode("\n", $ks_commentdata['message']))); ?>
			</font></p>
		<?php }
		ks_require_term_id_form(ks_plugin_url(KTAI_NOT_ECHO) . 'comments-post.php');
		ks_fix_encoding_form();
		if ( is_user_logged_in() ) {
			ks_session_id_form(); ?>
			<p><?php printf(__('Logged in as %s.', 'ktai_style'), esc_html($user_identity));
			?> [<a href="<?php echo esc_url(ks_get_logout_url(KTAI_NOT_ECHO, ks_comments_post_url())); ?>"><?php _e('Log out'); ?></a>]<br /><?php
			if ( !ks_cookie_available() ) {
				?><small><?php _e('Note: Ater posting a comment, you are automatically logged out.', 'ktai_style'); ?></small><br /><?php 
			}
		} else {
			if ( $login_url ) {
				?><div align="right"><?php printf(__('<a href="%s">Log in</a> and post a comment.', 'ktai_style'), esc_url($login_url) ); ?></div><?php 
			} 
			?><p><?php _e('Name'); if ($req) _e('(required)'); ?><br />
			<input type="text" name="author" value="<?php echo esc_attr($comment_author); ?>" size="12" /><br />
			<?php _e('Mail (will not be published)', 'ktai_style'); if ($req) _e('(required)'); ?><br />
			<input type="text" name="email" istyle="3" mode="alphabet" value="<?php echo esc_attr($comment_author_email); ?>" /><br />
			<?php _e('Website'); ?><br />
			<input type="text" name="url" istyle="3" mode="alphabet" value="<?php echo esc_attr($comment_author_url); ?>" /><br />
		<?php } // is_user_logged_in()
		_x('Comment', 'noun');
		if (ks_option('ks_allow_pictograms')) {
			_e('(Pictograms Available)', 'ktai_style');
		} ?><br />
		<textarea name="comment" cols="100%" rows="4"><?php echo esc_html($comment_content); ?></textarea><br />
		<input type="submit" name="submit" value="<?php _e('Say It!'); ?>" />
		<?php if (function_exists('comment_id_fields')) {
			comment_id_fields();
		} else { ?>
			<input type="hidden" name="comment_post_ID" value="<?php echo intval($id); ?>" />
		<?php } 
		ks_do_comment_form_action(); ?>
		</form>
		<?php if (ks_is_required_term_id()) { ?>
			<div><?php _e('NOTE: If submit comments, your terminal ID will be sent.', 'ktai_style'); ?></div>
		<?php }
		if (! is_user_logged_in() && ks_cookie_available()) { ?>
			<div><?php _e('NOTE: Your name, e-mail and URL will be stored o your phone. (If cookie is ON)', 'ktai_style'); ?></div>
		<?php }
	} // comments_open, comment_registration
endif; // $need_password, ks_is_comments_list, ks_is_comments_post ?>