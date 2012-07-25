<?php ks_header();
global $ks_settings;
if (have_posts()) :
	if (ks_is_front()) {
		?><div <?php echo $ks_settings['h2_style']; ?>><h2><?php _e('New Posts', 'ktai_style'); ?></h2></div><?php 
	} else {
		?><hr color="<?php echo $ks_settings['hr_color']; ?>" /><?php 
		ks_pagenum('<div style="text-align:right;background-color:teal;color:white;font-size:smaller;"><h2>', '</h2></div>');
		if (is_archive() || is_search()) {
			$post = $posts[0]; // Hack. Set $post so that the_date() works.
		}
	}
	?><dl><?php
	while (have_posts()) : the_post();
		?><dt><br /><?php _e('/-', 'ktai_style'); ?><font color="<?php echo $ks_settings['date_color']; ?>"><?php ks_time(); ?></font>
		<?php 
			$icon = '<img localsrc="86" alt="[' . __('Comments') . '] " />';
			ks_comments_link(array(
			'icon' => $icon, 
			'zero' => '0', 
			'one'  => '1', 
			'more' => '%', 
			'none' => '<img localsrc="61" alt="' . __('Off', 'ktai_style') . '" />', 
			'sec'  => '<img localsrc="120" alt="?" />',
			'icon_none' => $icon,
			'icon_sec' => $icon,
		));
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit') . '</font>', ' <img localsrc="104" alt="" />');
		?><br /><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php 
		if (is_home()) { 
			?><br /><font color="#223333" size="-1"><?php echo ks_excerpt(80, KTAI_NOT_ECHO); ?></font><?php 
		} ?></dt>
	<?php endwhile;
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