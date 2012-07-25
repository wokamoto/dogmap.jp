<?php ks_header();
global $ks_settings;
?>
<!--start paging-->
<?php if (have_posts()) : the_post(); ?>
	<div<?php echo $ks_settings['h2_style']; ?>><h2><?php the_title(); ?></h2></div>
	<?php ks_content(__('(more...)')); ks_link_pages();
	edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit page') . '</font>', '<div>(<img localsrc="112" alt="" />', ')</div>');
	ks_posts_nav_link(' | ', '<hr /><div align="center">', '</div>');
else: ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>