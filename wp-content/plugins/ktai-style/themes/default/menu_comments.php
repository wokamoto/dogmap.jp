<?php ks_header();
global $post, $comment, $ks_settings;
$ks_settings['date_color'] = isset($ks_settings['date_color']) ? $ks_settings['date_color'] : ks_option('ks_date_color');
$ks_settings['comment_color'] = isset($ks_settings['comment_color']) ? $ks_settings['comment_color'] : ks_option('ks_comment_type_color');
$ol_count = isset($ks_settings['ol_count']) ? intval($ks_settings['ol_count']) : 1;
$ol_max = isset($ks_settings['ol_max']) ? intval($ks_settings['ol_max']) : 9;
?>
<!--start paging<?php 
if (isset($ks_settings['hr_color'])) {
	echo '[<hr color="' . $ks_settings['hr_color'] . '" />]';
} ?>-->
<?php for ($loop = ks_option('ks_separate_comments') ? 0 : 1 ; 
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
		<?php for (; $target = array_shift($comments) ; $ol_count++) :
			$post = array_shift($target);
			$link = ks_get_comments_list_link($post->ID); ?>
			<dt><?php ks_ordered_link($ol_count, $ol_max, $link, get_the_title()); ?></dt><?php 
			if (empty($post->post_password)) {
				foreach ($target as $comment) : ?>
					<dd><?php /*
					if (! ks_option('ks_separate_comments')) {
						?><font size="-1" color="<?php echo $ks_settings['comment_color']; ?>">[<?php 
						comment_type(__('Comment', 'ktai_style'), __('Trackback'), __('Pingback')); ?>]</font> <?php 
					} */
					?><img localsrc="46" alt=" @ " /><a href="<?php 
						echo esc_url(ks_get_comment_link($comment)); ?>"><font color="<?php echo $ks_settings['date_color']; ?>"><?php ks_comment_datetime(); 
					?></font></a> <img localsrc="<?php comment_type(68, 112, 112); ?>" alt="by " /><?php comment_author(); ?>
					</dd><?php 
				endforeach;
			} else {
				?><dd><?php _e("Can't show comments because this post is password protected.", 'ktai_style'); ?></dd><?php 
			} // post_password
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