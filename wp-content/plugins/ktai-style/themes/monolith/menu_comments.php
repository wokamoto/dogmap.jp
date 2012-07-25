<?php ks_header();
global $post, $comment, $ks_settings;
for ($loop = ks_option('ks_separate_comments') ? 0 : 1 ; 
	 $loop <= 2 ;
	 $loop += 2 ) {
	if ($loop <= 1) { ?>
		<div<?php if (isset($ks_settings['h2_style'])) {
			echo $ks_settings['h2_style'];
		} ?>><h2 id="comments"><?php _e('Recent Comments', 'ktai_style'); ?></h2></div>
	<?php } else { ?>
		<br /><div<?php if (isset($ks_settings['h2_style'])) {
			echo $ks_settings['h2_style'];
		} ?>><h2 id="pings"><?php _e('Recent Pings', 'ktai_style'); ?></h2></div>
	<?php }
	switch ($loop) {
	case 1:
		$comments = ks_get_recent_comments();
		break;
	case 0:
		$comments = ks_get_recent_comments(8, 'comment');
		break;
	case 2:
		$comments = ks_get_recent_comments(8, 'pings');
		break;
	}
	if ($comments) : ?>
		<dl>
		<?php while ($target = array_shift($comments)) :
			$post = array_shift($target);
			?><dt><br /><img localsrc="41" alt="" /><a href="<?php 
			echo esc_url(ks_get_comments_list_link($post->ID)); ?>"><?php 
			the_title(); ?></a></dt><dt><?php 
			if (empty($post->post_password)) {
				while ($comment = array_shift($target)) :
					echo (count($target) >= 1) ? __('|-', 'ktai_style') : __('+-', 'ktai_style');
					?><a href="<?php 
					echo esc_url(ks_get_comment_link($comment)); 
					?>"><font color="<?php echo $ks_settings['date_color']; ?>"><?php ks_comment_datetime(); 
					?></font></a> <img localsrc="<?php 
					comment_type(68, 112, 112); ?>" alt="by " /><?php 
					comment_author(); ?><br /><?php 
				endwhile;
			} else {
				_e("Can't show comments because this post is password protected.", 'ktai_style');
			} // post_password
			?></dt><?php 
		endwhile; ?>
		</dl>
	<?php else: ?>
		<p><?php $loop <= 1 ? 
			_e('No comments yet.') : 
			_e('No pings yet.', 'ktai_style');
		?></p>
	<?php endif; // $comments
} // $loop
ks_footer(); ?>