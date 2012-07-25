<?php ks_header();
global $ks_settings;
$tree_continued = sprintf('<font color="%s">%s</font>', $ks_settings['list_color'], __('|-', 'ktai_style'));
$tree_last = sprintf('<font color="%s">%s</font>', $ks_settings['list_color'], __('+-', 'ktai_style'));
$max = isset($ks_settings['ol_max']) ? $ks_settings['ol_max'] : 9; ?>
<!--start paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->
<?php if (have_posts()) :
	if (ks_is_front()) {
		?><h2><?php _e('New Posts', 'ktai_style'); ?></h2><?php 
	} else {
		ks_pagenum('<div style="' . $ks_settings['pagenum_style'] . '"><h2>', '</h2></div>');
	} ?>
	<dl>
	<?php for ($count = 1; have_posts() ; $count++) : the_post(); ?>
		<dt><br /><div style="<?php echo $ks_settings['title_style']; ?>"><?php 
		ks_ordered_link(array(
			'count' => $count, 
			'max' => $max, 
			'link' => get_permalink(), 
			'anchor' => '<span style="' . $ks_settings['title_style'] . '">' . get_the_title() . '</span>',
			'hide_over_max' => true,
		)); ?></div>
		<?php echo $tree_continued; ?>
		<font color="<?php echo $ks_settings['time_color']; ?>"><?php ks_time(); ?></font> <?php 
		ks_comments_link('<img localsrc="811" alt="" />');
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit') . '</font>', '<br />' . $tree_continued . '<img localsrc="104" alt="" />');
		?><br /><?php echo $tree_last; ?>
		<font color="#666666" size="-1"><?php echo ks_excerpt(80, KTAI_NOT_ECHO); ?></font></dt>
	<?php endfor; ?>
	</dl>
	<div align="center"><?php 
		ks_posts_nav_link();
		ks_posts_nav_dropdown(array('before' => '<br />', 'min_pages' => 3));
	?></div>
<?php else: ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>