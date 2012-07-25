<?php ks_header();
global $ks_settings;
$count = isset($ks_settings['ol_count']) ? intval($ks_settings['ol_count']) : 1;
$max = isset($ks_settings['ol_max']) ? intval($ks_settings['ol_max']) : 9;
?>
<!--start paging-->
<?php if (have_posts()) :
	if (is_archive()) {
		$post = $posts[0]; // Hack. Set $post so that the_date() works.
		?><h2><?php ks_pagenum(); ?></h2><?php
	}
	for (; have_posts() ; $count++) :
		the_post();
		the_date('','<u>','</u><br/>');
		ks_ordered_link($count, $max, get_permalink(), get_the_title());
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit') . '</font>', '(', ')');
		echo ' ';
		ks_comments_link('', '[0]', '[1]', '[%]', '[X]', '[?]');
		?> <font color="<?php echo $ks_settings['date_color']; ?>"><?php the_time('H:i'); ?></font><br />
	<?php endfor; ?>
	<div align="center"><?php ks_posts_nav_link(); ?></div>
<?php else: ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>