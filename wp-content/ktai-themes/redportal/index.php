<?php ks_header();
global $ks_settings; ?>
<!--start paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->
<?php if (have_posts()) : ?>
	<div align="center"><?php 
	$per_page = intval(get_option('posts_per_page'));
	$prev_label = sprintf(__ngettext('*.Prev one post', '*.Prev %d posts', $per_page, 'ktai_style'), $per_page);
	$next_num = ks_get_next_num();
	$next_label = sprintf(__ngettext('Next one post.#', 'Next %d posts.#', $next_num, 'ktai_style'), $next_num);
	ks_posts_nav_link('|', '', '', $prev_label, $next_label);
	?></div><br />
	<?php for ($count = 0 ; have_posts() ; $count++) : the_post();
		if ($count > 0) { ?>
			<hr color="<?php echo $ks_settings['hr_color']; ?>" width="95%" />
		<?php } ?>
		<img localsrc="508" alt="" /><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><br />
		<img localsrc="46" alt="@ " /><?php the_time(__('Y.m.d H:i', 'redportal'));
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit') . '</font>', ' <img localsrc="104" alt="" />');
	endfor;
	?><div align="center"><br /><?php 
		ks_posts_nav_link('|', '', '', $prev_label, $next_label);
	?></div>
<?php else: ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>