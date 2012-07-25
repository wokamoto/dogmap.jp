<?php if (! ks_is_comments()) {
	return;
}
$need_password = ks_check_password(ks_is_comments_list() ? 
	__('Enter your password to view comments.') : 
	__('Enter your password to post a comment.', 'ktai_style'));
if ($need_password && ! is_user_logged_in()) {
	ipotch_box();
	echo $need_password . '</div>';
	return;
}

function ks_comment_content() {
	global $comment, $ks_settings;
	?><img localsrc="<?php comment_type(68, 112, 112); ?>" alt="" /><?php ks_comment_author_link();
	?><img localsrc="46" alt=" @ " /><font color="<?php echo $ks_settings['date_color']; ?>"><?php ks_comment_datetime(); ?></font><br />
	<?php if ($comment->comment_approved == '0') { ?>
		<em><font color="red"><?php _e('Your comment is awaiting moderation.', 'ktai_style'); ?></font></em><br />
	<?php }
	comment_text();
}

function ks_list_comments($comment, $args, $depth) {
	global $ks_settings;
	$GLOBALS['comment'] = $comment;
	extract($args, EXTR_SKIP);
	ipotch_comment();
	?><a name="comment-<?php comment_ID(); ?>"><?php 
	if (! ks_option('ks_separate_comments')) {
		?><font size="-1" color="<?php echo $ks_settings['comment_color']; ?>">[<?php 
		comment_type(__('Comment', 'ktai_style'), __('Trackback'), __('Pingback')); ?>]</font><?php 
	}
	?></a> <?php ks_comment_content();
	comment_reply_link(array_merge(
		array('before' => '<div>', 'after' => '</div>', 'reply_text' => '<img localsrc="149" alt="" />' . __('Reply'), 'login_text' => '<img localsrc="120" alt="" />' . __('Log in to Reply')), 
		$args, 
		array('depth' => $depth, 'max_depth' => $args['max_depth'])
	));
}

function ks_end_comments($comment, $args, $depth) {
	?></div><?php 
}

global $ks_settings, $wp_query;
if (ks_is_comments_list()) :
	$walker = function_exists('wp_list_comments') ? new KS_Walker_Comment : NULL;
	if (ks_option('ks_separate_comments') && ! get_option('page_comments')) {
		$sep_comments = ks_separete_comments($comments);
		$label = array('comment' => __('Comments'), 'pings' => __('Pings', 'ktai_style'));
	} else {
		$sep_comments[] = $comments;
		$label = array();
	}
	foreach ($sep_comments as $type => $_comments) :
		if (isset($label[$type])) {
			echo $ks_settings['h3_style'] . '<h3>' . $label[$type] . '</h3></div>';
		}
		if (count($_comments)) :
			if ($walker) :
				wp_list_comments(array('walker' => $walker, 'callback' => 'ks_list_comments', 'end-callback' => 'ks_end_comments', 'style' => 'div', 'avatar_size' => 0), $_comments);
				if (get_option('page_comments') && $wp_query->max_num_comment_pages >= 2) :
					ipotch_box();
					previous_comments_link(__('<img localsrc="7" alt="&laquo; ">Older Comments', 'ktai_style')); ?><br /><?php 
					next_comments_link(__('Newer Comments<img localsrc="8" alt=" &raquo;">', 'ktai_style'));
					?></div><?php 
				endif; // can't use brace
			else : // $walker
				foreach ($_comments as $comment) : 
					ipotch_box(); ?><a name="comment-<?php comment_ID(); ?>">
					<?php if (! ks_option('ks_separate_comments')) { ?>
						<font size="-1" color="<?php echo $ks_settings['comment_color']; ?>">[<?php 
						comment_type(__('Comment'), __('Trackback'), __('Pingback')); ?>]</font>
					<?php } ?>
					</a> <?php ks_comment_content(); ?></div>
				<?php endforeach;
			endif; // $walker
		else : // If there are no comments yet
			ipotch_box();
			echo ($type != 'pings') ? 
				__('No comments yet.') : 
				__('No pings yet.', 'ktai_style');
			?></div>
		<?php endif;
	endforeach;
	ks_comments_post_link(NULL, ipotch_box(KTAI_NOT_ECHO), '</div>');
elseif (ks_is_comment_post()) :
	if (! comments_open()) {
		ipotch_box(); _e('Sorry, the comment form is closed at this time.'); ?></div>
	<?php } elseif ( get_option('comment_registration') && ! is_user_logged_in() ) {
		if (ks_admin_url(KTAI_NOT_ECHO)) { 
			ipotch_box(); printf(__('You must be <a href="%s">logged in</a> to post a comment.'), ks_plugin_url(KTAI_NOT_ECHO) . 'login.php?redirect_to=' . urlencode(ks_comments_post_url()));?></div>
		<?php } else { 
			ipotch_box(); _e("Can't post a comment from mobile phones. You must logged in from PC to make a comment.", 'ktai_style'); ?></div>
		<?php }
	} else {
		$replytocom = isset($_GET['replytocom']) ? abs(intval($_GET['replytocom'])) : NULL;
		if ($replytocom) {
			$comment = get_comment($replytocom);
			if (! $comment || $comment->comment_approved != 1) {
				return;
			}
			ipotch_box(); ks_comment_content(); ?></div><?php 
			ipotch_box(); ?><h3 style="margin:0;"><?php _e('Reply to the above comment', 'ktai_style'); ?></h3></div><?php 
		}
		global $ks_commentdata;
		if (isset($ks_commentdata['message']) && $ks_commentdata['message']) {
			$comment_author       = $ks_commentdata['author'];
			$comment_author_email = $ks_commentdata['email'];
			$comment_author_url   = $ks_commentdata['url'];
			$comment_content      = $ks_commentdata['content'];
			ipotch_box(); ?><font color="red">
			<?php echo implode("<br />", array_map('wp_specialchars', explode("\n", $ks_commentdata['message']))); ?>
			</font></div> 
		<?php }
		ipotch_nbox();
		ks_require_term_id_form(ks_plugin_url(KTAI_NOT_ECHO) . 'comments-post.php');
		ks_fix_encoding_form();
		if ( is_user_logged_in() ) {
			ks_session_id_form(); ?>
			<p><?php printf(__('Logged in as %s.', 'ktai_style'), wp_specialchars($user_identity));
			?> [<a href="<?php echo attribute_escape(ks_get_logout_url()); ?>"><?php _e('Log out'); ?></a>]<br /><?php
			if ( !ks_cookie_available() ) {
				?><font size="-1"><?php _e('Note: Ater posting a comment, you are automatically logged out.', 'ktai_style'); ?></font><br /><?php
			}
		} else { ?>
			<div align="right"><img localsrc="120" alt="" /><?php printf(__('<a href="%s">Log in</a> and post a comment.', 'ktai_style'), ks_plugin_url(KTAI_NOT_ECHO) . 'login.php?redirect_to=' . urlencode(ks_comments_post_url()));?></div>
			<p><?php _e('Name'); if ($req) _e('(required)'); ?><br />
			<input type="text" name="author" value="<?php echo attribute_escape($comment_author); ?>" size="12" /><br />
			<?php _e('Mail (will not be published)', 'ktai_style'); if ($req) _e('(required)'); ?><br />
			<input type="text" name="email" istyle="3" mode="alphabet" value="<?php echo attribute_escape($comment_author_email); ?>" /><br />
			<?php _e('Website'); ?><br />
			<input type="text" name="url" istyle="3" mode="alphabet" value="<?php echo attribute_escape($comment_author_url); ?>" /><br />
		<?php } // is_user_logged_in()
		_e('Comment', 'ktai_style');
		if (ks_option('ks_allow_pictograms')) {
			_e('(Pictograms Available)', 'ktai_style');
		} ?><br />
		<textarea name="comment" cols="100%" rows="4"><?php echo wp_specialchars($comment_content); ?></textarea><br />
		<?php ks_inline_error_submit(__('Say It!'));
		if (function_exists('comment_id_fields')) {
			comment_id_fields();
		} else { ?>
			<input type="hidden" name="comment_post_ID" value="<?php echo intval($id); ?>" />
		<?php } 
		ks_do_comment_form_action(); ?>
		</p></form>
		<?php if (ks_is_required_term_id()) { ?>
			<div><?php _e('NOTE: If submit comments, your terminal ID will be sent.', 'ktai_style'); ?></div>
		<?php }
		if (! is_user_logged_in() && ks_cookie_available()) { ?>
			<div><?php _e('NOTE: Your name, e-mail and URL will be stored o your phone. (If cookie is ON)', 'ktai_style'); ?></div>
		<?php } ?>
		</div><?php
	} // comments_open, comment_registration
endif; // $need_password, ks_is_comments_list, ks_is_comments_post
ipotch_box(); ks_back_to_post(); ?></div>