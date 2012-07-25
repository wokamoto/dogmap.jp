<?php 
global $paged, $wp_query;
if (intval($paged) >= 2) { // restrict home.php for the front page
	include dirname(__FILE__) . '/index.php';
	return;
}

ks_header();
global $ks_settings; ?>
<!--start paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->
<?php if (have_posts()) : ?>
	<div align="center"><img localsrc="334" alt="[new] " /><?php _e('New Articles', 'redportal'); ?></div><br />
	<?php for ($count = 0 ; have_posts() ; $count++) : the_post(); ?>
		<img localsrc="508" alt="" /><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><br />
		<img localsrc="46" alt="@ " /><font color="<?php echo $ks_settings['date_color']; ?>"><?php the_time(__('Y.m.d H:i', 'redportal')); ?></font><?php 
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit') . '</font>', ' <img localsrc="104" alt="" />');
		?><br /> 
	<?php endfor;
	if ($wp_query->max_num_pages >= 2) : ?>
		<div align="right"><br /><?php _e('&rarr; ', 'redportal'); ks_next_posts_link(__('Read More', 'redportal')); ?></div>
	<?php endif;
else: ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>