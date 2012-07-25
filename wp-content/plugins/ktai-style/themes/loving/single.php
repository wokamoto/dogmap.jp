<?php ks_header();
global $ks_settings;
if (have_posts()) : the_post();
	if (! ks_is_comments()) : ?>
		<h2><img localsrc="144" alt="" /><span style="<?php echo $ks_settings['title_style']; ?>"><?php the_title(); ?></span></h2>
		<div><img localsrc="251" alt="" /><font size="-1"><?php echo __('Categories') . ':'; ks_category(); ?></font></div>
		<?php ks_content(__('(more...)')); ks_link_pages(); ?>
		<p align="right"><img localsrc="68" alt="by " /><font color="#ff8c00"><?php the_author(); ?></font> <img localsrc="46" alt=" @ " /><font color="<?php echo $ks_settings['date_color']; ?>"><?php ks_time('Y-m-d H:i'); ?></font></p>
		<div><?php ks_tags('<img localsrc="106" alt="" /><font size="-1">' . __('Tags') . ':', '</font><br />');
		if (ks_option('ks_separate_comments')) {
			ks_comments_link($ks_settings['comments_icon'] + array(
			'zero' => __('No Comments/Pings', 'ktai_style'), 
			'one'  => __('One Comment/Ping', 'ktai_style'), 
			'more' => __('% Comments and Pings', 'ktai_style'),
			));
		} else {
			ks_comments_link($ks_settings['comments_icon'] + array(
			'zero' => __('No comments', 'ktai_style'), 
			));
		}
		ks_comments_post_link(NULL, '<br />');
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit post') . '</font>', '<br /><img localsrc="104" alt="" />');
		?></div><hr color="<?php echo $ks_settings['hr_color']; ?>" /><?php
		ks_previous_post_link('<div align="left"><img localsrc="7" alt="&laquo; ">*.%link</div>');
		ks_next_post_link('<div align="right">#.%link<img localsrc="8" alt=" &raquo;"></div>');
	else : // ks_is_comment()
		echo '<h2>' . sprintf(__('Comments for <a href="%1$s"><span style="%2$s">%3$s</span></a>', 'ktai_style'), get_permalink(), $ks_settings['title_style'], get_the_title()) . '</h2>';
		comments_template();
	endif;
else : ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>