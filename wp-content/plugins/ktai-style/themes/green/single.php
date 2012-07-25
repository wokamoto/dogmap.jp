<?php ks_header();
global $ks_settings;
?>
<!--start paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->
<?php if (have_posts()) : the_post();
	if (! ks_is_comments()) : ?>
		<h2><?php the_title(); ?></h2>
		<?php the_date('','<div align="center"><font color="'. $ks_settings['date_color'] . '">','</font></div>'); ?>
		<div align="right"><img localsrc="68" alt="" /><font color="<?php echo $ks_settings['author_color']; ?>"><?php the_author(); ?></font>
		<img localsrc="46" alt=" @ " /><font color="<?php echo $ks_settings['author_color']; ?>"><?php the_time(); ?></font></div>
		<?php ks_content(__('(more...)')); ks_link_pages(); ?>
		<div><img localsrc="44" alt="" /><font size="-1"><?php echo __('Categories') . ':'; ks_category(); ?></font><br /><?php 
		ks_tags('<img localsrc="119" alt="" /><font size="-1">' . __('Tags') . ':', '</font><br />');
		if (ks_option('ks_separate_comments')) {
			ks_comments_link(
				'<img localsrc="811" alt="" />', 
				__('No Comments/Pings', 'ktai_style'), 
				__('One Comment/Ping', 'ktai_style'), 
				__('% Comments and Pings', 'ktai_style'));
		} else {
			ks_comments_link('<img localsrc="811" alt="" />');
		}
		ks_comments_post_link(NULL, '<br />');
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit post') . '</font>', '<br /><img localsrc="104" alt="" />');
		?></div>
		<hr color="<?php echo $ks_settings['hr_color']; ?>" />
		<?php
		ks_previous_post_link('<div align="left"><img localsrc="7" alt="&laquo; ">*.%link</div>');
		ks_next_post_link('<div align="right">#.%link<img localsrc="8" alt=" &raquo;"></div>');
	else : // ks_is_comment()
		echo '<div style="' . $ks_settings['title_style'] . '"><h2>' . sprintf(__('Comments for <a href="%1$s"><span style="%2$s">%3$s</span></a>', 'ktai_style'), get_permalink(),'color:#eeeeee', get_the_title()) . '</h2></div>';
		comments_template();
	endif;
else : ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2></div>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>