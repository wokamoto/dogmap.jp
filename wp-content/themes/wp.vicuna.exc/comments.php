<?php // Do not delete these lines
	if ('comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die (__('Please do not load this page directly. Thanks!', 'vicuna'));

	if (!empty($post->post_password)) { // if there's a password
		if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) {  // and it doesn't match the cookie?>
			<p class="nocomments"><?php _e('This post is password protected. Enter the password to view comments.', 'vicuna'); ?><p>
<?php
			return;
		}
	}

	/* This variable is for alternating comment background */
	$oddcomment = 'alt';

	/* to split comment and pings */
	$trackpingCount = 0;
	$commentCount = 0;
/* -- whisper add s -- */
	global $wpdb;
	$comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d ORDER BY comment_date", $post->ID));
/* -- whisper add e -- */
/* -- wokamoto add s -- */
	$comments = apply_filters('comments_array', $comments, $post->ID);
/* -- wokamoto add e -- */
	if ($comments) :
		foreach ($comments as $comment) {
			$type = get_comment_type();
			switch( $type ) {
				case 'trackback' :
				case 'pingback' :
					if ( $comment->comment_approved != 'spam') {
						$trackpingArray[$trackpingCount++] = $comment;
					}
					break;
				default :
					if ( $comment->comment_approved != 'spam') {
						$commentArray[$commentCount++] = $comment;
					}
			}
		}
	endif;
	if ($commentCount > 0 || 'open' == $post->comment_status) : ?>
			<div class="section" id="comments">
				<h2><?php if ('open' == $post->comment_status) : _e('Comments'); else : _e('Comments (Close)', 'vicuna'); endif; ?>:<span class="count"><?php echo $commentCount ?></span></h2>
<?php		if ($commentCount > 0) :
			vicuna_edit_comments_link(__('Edit this comments.', 'vicuna'), '<p class="admin">', '</p>'); ?>
				<dl class="log">
<?php			foreach ($commentArray as $comment) : ?>
<?php
/* -- whisper add s -- */
 if('1' == $comment->comment_approved){
/* -- whisper add e -- */
?>
<dt id="comment<?php comment_ID() ?>" <?php comment_class(); ?>><span class="name"><?php comment_author_link() ?></span> <span class="date"><?php comment_date(__('y-m-d (D) G:i', 'vicuna')) ?></span> <?php edit_comment_link(__('Edit'), '<span class="admin">', '</span>'); ?></dt>
				<dd>
<?php				comment_text() ?>
<?php
/* -- whisper add s -- */
} else {
	if('0' == $comment->comment_approved){
?>
		<dt id="comment<?php comment_ID() ?>" <?php comment_class(); ?>><span class="name">******</span> <span class="date"><?php comment_date(__('y-m-d (D) G:i', 'vicuna')) ?></span> </dt>
		<dd>
<?php
		_e('This comment is awaiting the approval of the author','vicuna');
	}
?>
				</dd>
<?php
}
/* -- whisper add e -- */
				endforeach; ?>
				</dl>
<?php		endif;
		if ('open' == $post->comment_status) : ?>
				<form class="post" method="post" action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" id="commentsForm" onsubmit="if (this.bakecookie[0].checked) rememberMe(this)">
					<fieldset>
					<legend><?php _e('Comment Form', 'vicuna'); ?></legend>
					<div>
						<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
					</div>
					<dl id="name-email">
<?php			if ( $user_ID ) : ?>
						<dt><?php _e('Logged in', 'vicuna'); ?></dt>
						<dd><?php echo $user_identity; ?> (<a href="<?php echo get_option('siteurl'); ?>/wp-login.php?action=logout" title="<?php _e('Log out of this account', 'vicuna'); ?>"><?php _e('Logout', 'vicuna'); ?></a>)</dd>
<?php			else : ?>
						<dt><label for="comment-author"><?php _e('Name', 'vicuna'); if ($req) echo " (". __('required', 'vicuna') .")"; ?></label></dt>
						<dd><input type="text" class="inputField" id="comment-author" name="author" size="20" value="" /></dd>
						<dt><label for="comment-email"><?php _e('Mail address', 'vicuna'); ?> (<?php _e('will not be published', 'vicuna'); ?>)<?php if ($req) echo " (". __('required', 'vicuna'). ")"; ?></label></dt>
						<dd><input type="text" class="inputField" size="20" id="comment-email" name="email" value="" /></dd>
<?php			endif; ?>
					</dl>
					<dl>
<?php			if ( !$user_ID ) : ?>
						<dt><label for="comment-url"><abbr title="Uniform Resource Identifer">URI</abbr></label></dt>
						<dd><input type="text" class="inputField" id="comment-url" name="url" size="20" value="http://" /></dd>
						<dt><?php _e('Remember personal info', 'vicuna'); ?></dt>
						<dd><input type="radio" class="radio" id="bakecookie" name="bakecookie" /> <label for="bakecookie"><?php _e('Yes'); ?></label><input type="radio" class="radio" id="forget" name="bakecookie" onclick="forgetMe(this.form)" onkeypress="forgetMe(this.form)" value="<?php _e('Forget Info', 'vicuna'); ?>" /> <label for="forget"><?php _e('No'); ?></label></dd>
<?php			endif; ?>
						<dt><label for="comment"><?php _e('Comment'); ?><?php if ( allowed_tags() ) : ?><span><?php _e('You can use some <abbr title="Hyper Text Markup Language">HTML</abbr> tags for decorating.', 'vicuna'); ?></span><?php else : ?><span><?php _e('You cannot use <abbr title="Hyper Text Markup Language">HTML</abbr> tags', 'vicuna'); ?></span><?php endif; ?></label></dt>
						<dd><textarea id="comment" name="comment" rows="8" cols="50" onfocus="if (this.value == '<?php _e('Add Your Comment', 'vicuna'); ?>') this.value = '';" onblur="if (this.value == '') this.value = '<?php _e('Add Your Comment', 'vicuna'); ?>';"><?php _e('Add Your Comment', 'vicuna'); ?></textarea></dd>
					</dl>
<?php
/* -- whisper add s -- */
					do_action('comment_form', $post->ID);
/* -- whisper add e -- */
?>
					<div class="action">
						<input type="submit" class="submit post" id="comment-post" name="post" value="<?php _e('Post'); ?>" />
					</div>
					</fieldset>
<?php			if ( !$user_ID ) : ?>
					<script type="text/javascript">
						applyCookie('comments_form', '<?php echo COOKIEPATH; ?>', '<?php echo $_SERVER['HTTP_HOST']; ?>');
					</script>
<?php			endif; ?>
				</form>
<?php		endif; ?>
			</div><!-- end div#comment -->
<?php	endif;
	do_action('comments_footer');
	if ($trackpingCount > 0 || 'open' == $post->ping_status) : ?>
			<div class="section" id="trackback">
				<h2><?php if ('open' == $post->ping_status) : _e('Trackbacks', 'vicuna'); else : _e('Trackbacks (Close)', 'vicuna'); endif; ?>:<span class="count"><?php echo $trackpingCount; ?></span></h2>
<?php		if ('open' == $post->ping_status) : ?>
				<dl class="info">
				<dt><?php _e('Trackback URL for this entry', 'vicuna'); ?></dt>
				<dd class="URL"><?php trackback_url(); ?></dd>
				<dt><?php _e('Listed below are links to weblogs that reference', 'vicuna'); ?></dt>
				<dd><?php printf(__('%s from %s', 'vicuna'), '<a href="'. get_permalink() .'">'. get_the_title() .'</a>', '<a href="'.get_bloginfo('home'). '">'. get_bloginfo('name') .'</a>'); ?></dd>
				</dl>
<?php		endif;
		if ($trackpingCount > 0) :
			vicuna_edit_comments_link(__('Edit this comments.', 'vicuna'), '<p class="admin">', '</p>'); ?>
				<dl class="log">
<?php			foreach ($trackpingArray as $comment) :
					if('1' == $comment->comment_approved){ ?>
						<dt id="ping<?php comment_ID() ?>"><span class="name"><?php printf(__("%s from %s", 'vicuna'), get_comment_type(), get_comment_author_link()); ?></span> <span class="date"><?php comment_date(__('y-m-d (D) G:i', 'vicuna')); ?></span></dt>
						<dd>
<?php					comment_text(); ?>
						</dd>
<?php				} else if('0' == $comment->comment_approved){ ?>
						<dt id="ping<?php comment_ID() ?>"><span class="name">******</span> <span class="date"><?php comment_date(__('y-m-d (D) G:i', 'vicuna')); ?></span></dt>
						<dd>
<?php					_e('This comment is awaiting the approval of the author','vicuna'); ?>
						</dd>
<?php				} ?>
<?php			endforeach; ?>
				</dl>
<?php		endif; ?>
			</div><!-- end div#trackback -->
<?php	endif; ?>
