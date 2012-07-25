<?php if (! ks_is_comments()) {
	return;
}

function ks_comment_content() {
	global $comment, $ks_settings;
	?><img localsrc="<?php comment_type(68, 112, 112); ?>" alt="" /><?php ks_comment_author_link();
	?><img localsrc="46" alt=" @ " /><font color="<?php echo $ks_settings['date_color']; ?>"><?php ks_comment_datetime(); ?></font>
	<?php edit_comment_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit') . '</font>', '<img localsrc="104" alt="" />'); ?><br />
	<?php if ($comment->comment_approved == '0') { ?>
		<em><font color="blue"><?php _e('Your comment is awaiting moderation.') ?></font></em><br />
	<?php }
	comment_text();
}

function ks_list_comments($comment, $args, $depth) {
	global $ks_settings;
	$GLOBALS['comment'] = $comment;
	extract($args, EXTR_SKIP);

	if ( 'div' == $args['style'] ) {
		$tag = 'div';
	} else {
		$tag = 'li';
	}
?>
	<<?php echo $tag ?>><a name="comment-<?php comment_ID(); ?>"><?php 
	if (! ks_option('ks_separate_comments')) {
		?><font size="-1" color="<?php echo $ks_settings['comment_color']; ?>">[<?php 
		comment_type(_x('Comment', 'noun'), __('Trackback'), __('Pingback')); ?>]</font><?php 
	}
	?></a> <?php ks_comment_content();
	comment_reply_link(array_merge(
		array('before' => '<div>', 'after' => '</div><br />', 'reply_text' => '<img localsrc="149" alt="" />' . __('Reply'), 'login_text' => '<img localsrc="120" alt="" />' . __('Log in to Reply')), 
		$args, 
		array('depth' => $depth, 'max_depth' => $args['max_depth'])
	));
}

global $ks_settings, $wp_query, $comment;
if (ks_is_comments_list()) : 
	?><h2><?php printf(__('Comments list for <a href="%1$s">%2$s</a>', 'redportal'), get_permalink(), get_the_title()); ?></h2>
	<hr color="<?php echo $ks_settings['hr_color']; ?>" />
	<?php $need_password = ks_check_password(__('Enter your password to view comments.'));
	if ($need_password && ! is_user_logged_in()) {
		echo $need_password;
		return;
	}
	$walker = new KS_Walker_Comment;
	if (isset($_GET['co_order']) && $_GET['co_order'] == 'asc') {
		$co_order = 'asc';
		$desc_format = '<a href="' . esc_url(add_query_arg('co_order', 'desc')) . '">' . __('From Newer', 'redportal') . '</a>';
		$asc_format  = __('From Older', 'redportal');
		$prev_navi = __('<img localsrc="7" alt="&laquo; ">Older Comments', 'ktai_style');
		$next_navi = __('Newer Comments<img localsrc="8" alt=" &raquo;">', 'ktai_style');
	} else {
		$co_order = 'desc';
		$desc_format = __('From Newer', 'redportal');
		$asc_format  = '<a href="' . esc_url(add_query_arg('co_order', 'asc')) . '">' .__('From Older', 'redportal') . '</a>';
		$prev_navi = __('<img localsrc="7" alt="&laquo; ">Newer Comments', 'ktai_style');
		$next_navi = __('Older Comments<img localsrc="8" alt=" &raquo;">', 'ktai_style');
	}
	?><div align="center"><?php echo $desc_format; ?> | <?php echo $asc_format; ?></div>
	<?php ks_comments_post_link(__('Write a comment', 'redportal'), '', '', ks_pict_number(1), '1');
	if (ks_option('ks_separate_comments') && ! get_option('page_comments')) {
		$sep_comments = ks_separete_comments($comments, $co_order);
		$label = array('comment' => __('Comments'), 'pings' => __('Pings', 'ktai_style'));
	} else {
		if ($co_order == 'desc') {
			$sep_comments[] = array_reverse($comments);
		} else {
			$sep_comments[] = $comments;
		}
		$label = array();
	}
	foreach ($sep_comments as $type => $_comments) :
		if (isset($label[$type])) {
			echo '<h3>' . $label[$type] . '</h3>';
		}
		if (count($_comments)) : ?>
			<ol>
			<?php wp_list_comments(array('walker' => $walker, 'callback' => 'ks_list_comments', 'avatar_size' => 0), $_comments);
			if (get_option('page_comments') && $wp_query->max_num_comment_pages >= 2) {
				?><div align="center"><p><?php 
				previous_comments_link($prev_navi . '<br />');
				next_comments_link($next_navi);
				?></p></div><?php 
			} ?>
			</ol>
		<?php else : // If there are no comments yet ?>
			<p><?php echo ($type != 'pings') ? 
				__('No comments yet.') : 
				__('No pings yet.', 'ktai_style');
			?></p>
		<?php endif;
	endforeach;
	ks_comments_post_link(__('Write a comment', 'redportal'), '<hr color="' . $ks_settings['hr_color'] . '" />', '', ks_pict_number(1), '1');
elseif (ks_is_comment_post()) : ?>
	<h2 align="center"><?php _e('Comment', 'redportal'); ?></h2>
	<hr color="<?php echo $ks_settings['hr_color']; ?>" />
	<?php $need_password = ks_check_password(__('Enter your password to post a comment.', 'ktai_style'));
	if ($need_password && ! is_user_logged_in()) {
		echo $need_password;
		return;
	}
	$login_url = ks_get_login_url(KTAI_NOT_ECHO, ks_comments_post_url());
	if (! comments_open()) { ?>
		<p><?php _e('Sorry, the comment form is closed at this time.'); ?></p>
	<?php } elseif (get_option('comment_registration') && ! is_user_logged_in()) {
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
			ks_comment_content();
			echo '<hr color="' . $ks_settings['hr_color'] . '" /><p>' . 
			sprintf(__('Reply to comment #%1$d of <a href="%2$s">%3$s</a> of &quot;%4$s&quot;', 'redportal'), $replytocom, get_permalink(), get_the_title(), get_bloginfo('name')) . '</p>';
		} else {
			echo '<p>' . sprintf(__('Post a comment for <a href="%1$s">%2$s</a> of &quot;%3$s&quot;', 'redportal'), get_permalink(), get_the_title(), get_bloginfo('name')) . '</p>';
		}
		global $ks_commentdata;
		if (isset($ks_commentdata['message']) && $ks_commentdata['message']) {
			$comment_author       = $ks_commentdata['author'];
			$comment_author_email = $ks_commentdata['email'];
			$comment_author_url   = $ks_commentdata['url'];
			$comment_content      = $ks_commentdata['content'];
			?><p><?php _e('Please confirm below items', 'redportal'); ?></p>
			<ul><li><font color="blue">
			<?php echo implode('</font></li><li><font color="blue">', array_map('esc_html', explode("\n", $ks_commentdata['message']))); ?>
			</font></li></ul>
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
			?><font color="orange"><?php _e('&diams;', 'redportal'); ?></font><?php _e('Comment', 'ktai_style');
			if (ks_option('ks_allow_pictograms')) {
				_e('(Pictograms Available)', 'ktai_style');
			} ?><br />
			<textarea name="comment" cols="100%" rows="4"><?php echo esc_html($comment_content); ?></textarea><br /><?php 
		} else {
			if ( $login_url ) {
				?><div align="right"><img localsrc="120" alt="" /><?php printf(__('<a href="%s">Log in</a> and post a comment.', 'ktai_style'), esc_url($login_url) ); ?></div><?php 
			} 
			?><p><font color="orange"><?php _e('&diams;', 'redportal'); ?></font><?php _e('Nickname', 'redportal'); ?><?php if ($req) _e('(required)'); ?><br />
			<input type="text" name="author" value="<?php echo esc_attr($comment_author); ?>" size="12" /><br />
			<font color="orange"><?php _e('&diams;', 'redportal'); ?></font><?php _e('Comment', 'ktai_style');
			if (ks_option('ks_allow_pictograms')) {
				_e('(Pictograms Available)', 'ktai_style');
			} ?><br />
			<textarea name="comment" cols="100%" rows="4"><?php echo esc_html($comment_content); ?></textarea><br />
			<font color="orange"><?php _e('&diams;', 'redportal'); ?></font><?php _e('Mail (will not be published)', 'ktai_style'); if ($req) _e('(required)'); ?><br />
			<input type="text" name="email" istyle="3" mode="alphabet" value="<?php echo esc_attr($comment_author_email); ?>" /><br />
			<font color="orange"><?php _e('&diams;', 'redportal'); ?></font><?php _e('URL', 'redportal'); ?><br />
			<input type="text" name="url" istyle="3" mode="alphabet" value="<?php echo esc_attr($comment_author_url); ?>" /><br />
		<?php } // is_user_logged_in()
		ks_inline_error_submit(__('Submit', 'redportal')); ?>
		<input type="hidden" name="comment_post_ID" value="<?php echo intval($id); ?>" /></p>
		<?php ks_do_comment_form_action(); ?>
		</form>
		<?php if (ks_is_required_term_id()) { ?>
			<div><font color="orange"><?php _e('&diams;', 'redportal'); ?></font><?php _e('NOTE: If submit comments, your terminal ID will be sent.', 'ktai_style'); ?></div>
		<?php }
		if (! is_user_logged_in() && ks_cookie_available()) { ?>
			<div><font color="orange"><?php _e('&diams;', 'redportal'); ?></font><?php _e('NOTE: Your name, e-mail and URL will be stored o your phone. (If cookie is ON)', 'ktai_style'); ?></div>
		<?php }
	} // comments_open, comment_registration
endif; // $need_password, ks_is_comments_list, ks_is_comments_post
?>