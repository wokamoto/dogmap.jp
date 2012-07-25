<?php ks_header();
global $ks_settings;
if (have_posts()) :
	if (is_archive() || is_search()) {
		$post = $posts[0]; // Hack. Set $post so that the_date() works.
		ks_pagenum('<h2 align="right">', '</h2>');
	}
	while (have_posts()) : the_post();
		ipotch_box(); ?><span style="<?php echo $ks_settings['comments_number_style']; ?>"><?php 
		ks_comments_link(
			$ks_settings['comments_icon'], 
			'', // zero
			ipotch_link_desc('1'), 
			ipotch_link_desc('%'), 
			'', // none
			''  // sec
		); ?></span> <span style="<?php echo $ks_settings['date_style']; ?>"><?php the_time('Y-m-d H:i'); ?></span><?php 
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit') . '</font>', '<font size="-1"> <img localsrc="104" alt="" />', '</font>'); ?>
		<h4 style="margin:0;"><a href="<?php the_permalink(); ?>" style="text-decoration:none;"><font color="black"><?php the_title(); ?></font></a></h4>
		<span style="color:gray;font-size:small;"><?php _e('Author:'); ?><strong><?php the_author(); ?></strong><br />
		<?php _e('Categories:'); ks_category();
		ks_tags('<br />' . __('Tags:'), ''); ?></span></div>
	<?php endwhile;
	ks_posts_nav_link(' &nbsp; ', ipotch_cbox(KTAI_NOT_ECHO), '</div>');
else:
	ipotch_box(); ?><h3><?php _e('Not Found', 'ktai_style'); ?></h3></div><?php
	ipotch_box(); ?><p><?php _e('Sorry, no posts matched your criteria.'); ?></p></div>
<?php endif;
ks_footer(); ?>