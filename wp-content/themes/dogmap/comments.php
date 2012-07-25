<div class="comments-trackbacks">
<?php if ( !empty($post->post_password) && $_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) : ?>
<p><?php _e('Enter your password to view comments.'); ?></p>
<?php endif; ?>
<?php
//************************* Begin Trackbacks
$runonce = false;
if (count($comments) > 0) :
	//$trackbacks = function_exists('wp_list_comments')
	//	? $comments_by_type['pings']
	//	: $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1' AND comment_type in ('pingback', 'trackback') ORDER BY comment_date DESC", $post->ID));
	$trackbacks = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1' AND comment_type in ('pingback', 'trackback') ORDER BY comment_date DESC", $post->ID));
	if (count($trackbacks) > 0) :
		foreach ($trackbacks as $comment) :
			if ($comment->comment_type == "trackback" || $comment->comment_type == "pingback" || ereg("<pingback />", $comment->comment_content) || ereg("<trackback />", $comment->comment_content)) :
				if (!$runonce) :
					 $runonce = true;
?>
<div class="divider"><h2 id="trackbacks">トラックバック &amp; ピンバック <small style="margin-left:1em;"><a href="#trackbacks">&raquo; 表示する</a></small></h2></div>
<ul class="trackback-list" style="display:none;">
<?php
				endif;
?>
<li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
<p class="trackback-time">
<?php if (function_exists('commenters_info')) { commenters_info(); echo '&nbsp;'; } ?>
<?php comment_date(); ?> <?php comment_time(); ?>
</p>
<?php comment_text(); ?><div style="clear:both"></div>
<p class="trackback-post"><?php comment_author_link(); ?> <?php edit_comment_link(__("Edit This"), ' | '); ?> </p>
</li>
<?php
			endif;
		endforeach;
		if ($runonce) echo "</ul>\n";
	endif;
?>
<?php
endif;
//************************* End Trackbacks
?>
<?php
//************************* Begin Comments
$runonce = false;
$comments_only = array();
if ($comments && count($comments) - count($trackbacks) > 0) {
	foreach ($comments as $comment) {
		if ($comment->comment_type != "trackback" && $comment->comment_type != "pingback" && !ereg("<pingback />", $comment->comment_content) && !ereg("<trackback />", $comment->comment_content)) {
			$comments_only[] = $comment;
		}
	}
}
$comment_pages = ( get_option('page_comments')
	? paginate_comments_links(array('total' => get_comment_pages_count($comments_only), 'echo' => 0))
	: FALSE );
?>
<div class="divider"><h2 id="comments">コメント</h2></div>
<?php if ($comment_pages) { ?>
<div class="navigation"><?php echo $comment_pages; ?></div>
<?php } ?>
<ul id="commentlist" class="comment-list" style="margin-bottom:2em;">
<?php
if (count($comments_only) > 0) {
	if (function_exists('wp_list_comments')) {
		wp_list_comments('type=comment&callback=custom_comments');
	} else {
		foreach ($comments_only as $comment) {
			custom_comments($comment, null, null);
			echo "</li>\n";
		}
	}
} else {
	// If there are no comments yet
	echo '<li id="no-comments-yet" style="margin-top:1em;">' . __('No comments yet.') . '</li>'."\n";
}
?>
</ul>
<?php if ($comment_pages) { ?>
<div class="navigation"><?php echo $comment_pages; ?></div>
<?php } ?>
<?php
//************************* End Comments
?>
<?php
//************************* Begin Comment Form
if (comments_open()) : // Comments are opened
//	if ( function_exists('comment_form') ) {
//		comment_form();
//	} else {
?>
<a name="respond"></a>
<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform" onsubmit="">
<div class="divider" style="margin-top:1em;"><h2 id="postcomment"><?php _e('Leave a comment'); ?></h2></div>
<?php
	if ( get_option('comment_registration') && !$user_ID ) {
		echo '<p>';
		printf(__('You must be <a href="%s">logged in</a> to post a comment.'), get_option('siteurl')."/wp-login.php?redirect_to=".urlencode(get_permalink()));
		echo "</p>\n";
	} else {
		if ($user_ID) {
			echo '<p>';
			printf(__('Logged in as %s.'), '<a href="'.get_option('siteurl').'/wp-admin/profile.php">' . $user_identity . '</a>');
			echo '<a href="' . get_option('siteurl') . '/wp-login.php?action=logout" title="' . __('Log out of this account') . '">' . __('Log out &raquo;') . '</a>';
			echo "</p>\n";
		} else {
?>
<p>
<input type="text" name="author" id="author" value="" size="22" tabindex="1" />
<label for="author"><small><?php _e('Name'); ?> <?php if ($req) _e('(required)'); ?></small></label>
</p>
<p>
<input type="text" name="email" id="email" value="" size="22" tabindex="2" />
<label for="email"><small><?php _e('Mail (will not be published)');?> <?php if ($req) _e('(required)'); ?></small></label>
</p>
<p>
<input type="text" name="url" id="url" value="" size="22" tabindex="3" />
<label for="url"><small><?php _e('Website'); ?></small></label>
</p>
<?php global $comment_notify_email; if (isset($comment_notify_email)) : ?>  
<p>
<input type="text" name="twitterID" id="twitterID" value="" size="22" tabindex="4" style="background:#fff;margin-bottom:3px;" />
<label for="twitterID"><small>Twitter ID</small></label><br />
Twitter ID を入力すると新しいコメントがあった際、 <a href="http://twitter.com/dogmap_jp" title="Twitter / dogmap_jp">@dogmap_jp</a> が、あなた宛に＠リプライでお知らせします。
</p>
<?php endif; ?>
<p>入力いただいたメールアドレスから <a href="http://en.gravatar.com/" title="Gravatar - Globally Recognized Avatars">Gravatar</a> に登録されているアイコンを表示します。<br />
(メールアドレスは公開されません)</p>
<?php global $comment_notify_email; if (isset($comment_notify_email)) : ?>  
<p style="margin-top:1em;"><input name="notify_email" id="notify_email" value="on" type="checkbox" tabindex="5" <?php  echo $comment_notify_email ? 'checked="true" ' : ''; ?>/>
<label for="notify_email">この投稿にコメントが追加されたとき、メールで通知してください。</label></p>
<?php endif; ?>
<p style="margin-top:1em;">改行と段落タグは自動で挿入されます。メールアドレスは表示されません。<br />
</p>
<?php } ?>
<?php if (function_exists('cs_print_smilies')) cs_print_smilies(); ?>
<p><textarea name="comment" id="comment" cols="100%" rows="10" tabindex="4"></textarea></p>
<p>
<input name="submit" type="submit" id="submit" tabindex="5" value="<?php echo esc_attr(__('Submit Comment')); ?>" />
<?php global $quick_comments; if (isset($quick_comments)) : ?>
<small>コメント投稿<?php echo $quick_comments->getOption('editMin');?>分後までは「コメント編集」をクリックして内容を修正することができます。</small>
<?php endif; ?>
<?php
if (function_exists('comment_id_fields')) {
	comment_id_fields();
} else {
	$replytoid = isset($_GET['replytocom']) ? (int) $_GET['replytocom'] : 0;
	echo "<input type=\"hidden\" name=\"comment_post_ID\" id=\"comment_post_ID\" value=\"{$id}\" />\n";
	echo "<input type=\"hidden\" name=\"comment_parent\" id=\"comment_parent\" value=\"{$replytoid}\" />\n";
}
?>
<?php do_action('comment_form', $post->ID); ?>
</p>
</form>
<?php } // If registration required and not logged in ?>
<?php
//	}
else : // Comments are closed ?>
<p><?php _e('Sorry, the comment form is closed at this time.'); ?></p>
<?php endif; ?>
</div>
