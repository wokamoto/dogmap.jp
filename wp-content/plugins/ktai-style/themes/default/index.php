<?php ks_header();
global $ks_settings;
if (have_posts()) :
	if (is_archive() || is_search()) {
		$post = $posts[0]; // Hack. Set $post so that the_date() works.
		ks_pagenum('<h2>', '</h2>');
	} ?>
<!--start paging-->
	<dl>
	<?php for ($count = $ks_settings['ol_count']; have_posts() ; $count++) :
		the_post(); ?>
		<dt><?php 
		ks_ordered_link($count, $ks_settings['ol_max'], get_permalink(), get_the_title());
		echo ' ';
		ks_comments_link(
			'<img localsrc="86" alt="[' . __('Comments') . '] " />', 
			'0', '1', '%', 
			'<img localsrc="61" alt="' . __('Comments off', 'ktai_style') . '" />', 
			'<img localsrc="120" alt="?" />'
		); ?> <img localsrc="46" alt="@ " /><font color="<?php echo $ks_settings['date_color']; ?>"><?php ks_time(); ?></font>
		</dt>
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