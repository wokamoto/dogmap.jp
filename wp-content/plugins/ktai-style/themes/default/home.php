<?php ks_header();
global $ks_settings;
$next_accesskey = 9;
?>
<!--start paging-->
<?php if (have_posts()) : the_post();
	the_date('','<div align="center"><font color="' . $ks_settings['date_color'] . '">','</font></div>'); ?>
	<div align="right"><font size="-1"><a href="#cont" accesskey="<?php echo $next_accesskey; ?>"><img localsrc="30" alt="<?php _e('&darr;', 'ktai_style'); ?>" /><?php _e('Skip the content', 'ktai_style'); ?></a><?php ks_pict_number($next_accesskey, true); ?></font></div>
	<h2><?php if (ks_is_front()) { ?>
		<img localsrc="334" alt="[new] " />
	<?php }
	the_title(); ?></h2>
	<div><img localsrc="68" alt="" /><font color="<?php echo $ks_settings['author_color']; ?>"><?php the_author(); ?></font>
	<img localsrc="46" alt=" @ " /><font color="<?php echo $ks_settings['author_color']; ?>"><?php the_time(); ?></font></div>
	<?php ks_content(__('(more...)'), 0 , '' , 1000); ks_link_pages(); ?>
	<div><img localsrc="354" alt="" /><font size="-1"><?php echo __('Categories') . ':'; ks_category(); ?></font><br />
	<?php ks_tags('<img localsrc="77" alt="" /><font size="-1">' . __('Tags') . ':', '</font><br />');
	if (ks_option('ks_separate_comments')) {
		ks_comments_link(NULL, 
			__('No Comments/Pings', 'ktai_style'), 
			__('One Comment/Ping', 'ktai_style'), 
			__('% Comments and Pings', 'ktai_style'));
	} else {
		ks_comments_link();
	}
	ks_comments_post_link(NULL, '<br />');
	?></div>
	<?php if (have_posts()) : ?>
		<hr />
		<h2><a name="cont"><?php _e('Following posts', 'ktai_style'); ?></a></h2>
		<dl>
		<?php for ($count = $ks_settings['ol_count']; have_posts() ; $count++) :
			the_post(); ?>
			<dt><?php 
			ks_ordered_link($count, $next_accesskey -1, get_permalink(), get_the_title());
			edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit') . '</font>', '<img localsrc="104" alt="" />');
			echo ' ';
			ks_comments_link(
				'<img localsrc="86" alt="[' . __('Comments') . '] " />', 
				'0', '1', '%', 
				'<img localsrc="61" alt="' . __('Comments off', 'ktai_style') . '" />', 
				'<img localsrc="120" alt="?" />');
			?> <img localsrc="46" alt="@ " /><font color="<?php echo $ks_settings['date_color']; ?>"><?php ks_time(); ?></font></dt>
		<?php endfor; ?>
		</dl>
	<?php endif; // inner have_posts() ?>
	<div align="center"><?php 
		ks_posts_nav_link();
		ks_posts_nav_dropdown(array('before' => '<br />', 'min_pages' => 3));
	?></div>
<?php else: ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>