<?php ks_header();
global $post, $comment, $ks_settings;
$ks_settings['date_color'] = ks_option('ks_date_color');
$ks_settings['comment_color'] = ks_option('ks_comment_type_color');
$ol_max = 9;
for ($loop = ks_option('ks_separate_comments') ? 0 : 1 ; 
	 $loop <= 2 ;
	 $loop += 2 ) {
	if ($loop <= 1) {
		?><h2 id="comments"><?php _e('Recent Comments', 'ktai_style'); ?></h2><?php 
		$box = ipotch_comment(false, 'comment');
	} else {
		?><h2 id="pings"><?php _e('Recent Pings', 'ktai_style'); ?></h2><?php 
		$box = ipotch_comment(false, 'pings');
	}
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
	if ($comments) :
		while ($target = array_shift($comments)) :
			$post = array_shift($target);
			$link = ks_get_comments_list_link($post->ID);
			$password_req = post_password_required($post);
			echo $box; 
			?><dl style="margin:0"><dt><?php 
			$num = apply_filters('get_comments_number', $post->comment_count);
			if ($num > 0 && ! $password_req) {
				?><span style="<?php echo $ks_settings['comments_number_style']; ?>"><?php echo $ks_settings['comments_icon'] . intval($num); ?></span> <?php
			}
			ks_ordered_link('', $ol_max, $link, '<font color="black">' . get_the_title() . '</font>' ); ?></dt><?php 
			if (! $password_req) {
				foreach ($target as $comment) : ?>
					<dd><span style="<?php echo $ks_settings['date_style']; ?>"><a href="<?php 
					echo attribute_escape(ks_get_comment_link($comment)); ?>"><?php ks_comment_datetime(); 
					?></a></span><img localsrc="<?php comment_type(68, 112, 112); ?>" alt=" by " /><?php comment_author(); ?></dd><?php 
					endforeach;
			} else {
				?><dd><?php _e("Can't show comments because this post is password protected.", 'ktai_style'); ?></dd><?php 
			} // post_password
			?></dl></div><?php
		endwhile;
	else:
		ipotch_box();
		$loop <= 1 ? 
			_e('No comments yet.') : 
			_e('No pings yet.', 'ktai_style');
		?></div>
	<?php endif; // $comments
} // $loop
ks_footer(); ?>