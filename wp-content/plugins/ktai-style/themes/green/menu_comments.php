<?php ks_header();
global $post, $comment, $ks_settings;
$ol_count = 1;
$ol_max = isset($ks_settings['ol_max']) ? intval($ks_settings['ol_max']) : 9;
for ($loop = ks_option('ks_separate_comments') ? 0 : 1 ; 
	 $loop <= 2 ;
	 $loop += 2 ) {
	if ($loop <= 1) { ?>
		<div<?php echo $ks_settings['h2_style']; ?>><h2 id="comments"><?php _e('Recent Comments', 'ktai_style'); ?></h2></div>
	<?php } else { ?>
		<br /><div<?php echo $ks_settings['h2_style']; ?>><h2 id="pings"><?php _e('Recent Pings', 'ktai_style'); ?></h2></div>
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
		<?php for ( ; $target = array_shift($comments) ; $ol_count++) :
			$post = array_shift($target);
			$link = ks_get_comments_list_link($post->ID);
			?><dt><br /><div style="<?php echo $ks_settings['title_style']; ?>"><?php ks_ordered_link(array(
				'count' => $ol_count, 
				'max' => $ol_max, 
				'link' => $link, 
				'anchor' => '<span style="' . $ks_settings['title_style'] . '">' . get_the_title() . '</span>',
				'hide_over_max' => true,
				)); ?></div>
			<?php if (empty($post->post_password)) {
				while ($comment = array_shift($target)) : ?>
					<font color="<?php echo $ks_settings['list_color']; ?>"><?php 
					echo (count($target) >= 1) ? __('|-', 'ktai_style') : __('+-', 'ktai_style');
					?></font><a href="<?php 
					echo esc_url(ks_get_comment_link($comment)); 
					?>"><?php ks_comment_datetime(); 
					?></a> <img localsrc="<?php 
					comment_type(68, 112, 112); ?>" alt="by " /><?php 
					comment_author(); ?><br /><?php 
				endwhile;
			} else {
				_e("Can't show comments because this post is password protected.", 'ktai_style');
			} // post_password
			?></dt><?php 
		endfor; ?>
		</dl>
	<?php else: ?>
		<p><?php $loop <= 1 ? 
			_e('No comments yet.') : 
			_e('No pings yet.', 'ktai_style');
		?></p>
	<?php endif; // $comments
} // $loop
ks_footer(); ?>