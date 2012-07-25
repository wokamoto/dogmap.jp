<?php ks_header();
global $ks_settings;
if (have_posts()) :
	if (is_archive() || is_search()) {
		$post = $posts[0]; // Hack. Set $post so that the_date() works.
	}
	if (! ks_is_front()) {
		ks_pagenum('<div ' . $ks_settings['h2_style'] . '><h2>', '</h2></div>');
	}
	?><dl><?php 
	while (have_posts()) : the_post();
		?><dt><br /><img localsrc="144" alt="" /><a href="<?php the_permalink(); ?>"><span style="<?php echo $ks_settings['title_style']; ?>"><?php the_title(); ?></span></a><br />
		<img localsrc="46" alt="@ " /><font color="<?php echo $ks_settings['date_color']; ?>"><?php ks_time(); ?></font><br />
		<?php ks_comments_link(array(
			'zero' => __('No comments', 'ktai_style'), 
			'one'  => __('One comment', 'ktai_style'), 
			'more' => __('% comments', 'ktai_style'), 
		) + $ks_settings['comments_icon']);
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit') . '</font>', '<img localsrc="104" alt="" />');
		if (is_home()) {
			?><br /><font color="#ff8c00" size="-1"><?php echo ks_excerpt(80, KTAI_NOT_ECHO); ?></font><?php
		}
		?></dt><?php 
	endwhile;
	?></dl>
	<div align="center"><?php 
		ks_posts_nav_link();
		ks_posts_nav_dropdown(array('before' => '<br />', 'min_pages' => 3));
 	?></div>
<?php else: ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>