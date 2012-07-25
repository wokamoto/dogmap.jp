<?php ks_header();
global $ks_settings, $id;
?>
<!--start paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->
<?php if (have_posts()) : the_post();
	if (! ks_is_comments() ) : ?>
		<img localsrc="508" alt="" /><?php the_title(); ?><br />
		<font color="<?php echo $ks_settings['date_color']; ?>"><?php the_time('Y.m.d H:i'); ?></font><br />
		<font size="-1"><?php echo __('Categories') . ':'; ks_category(); ?></font>
		<div align="center"><?php 
		ks_previous_post_link('*.%link', __('Prev', 'redportal')); 
		echo '|';
		ks_next_post_link('%link.#', __('Next', 'redportal'));
		?></div>
		<hr color="<?php echo $ks_settings['hr_color']; ?>" />
		<?php ks_content(__('(more...)')); ks_link_pages(); ?>
		<hr color="<?php echo $ks_settings['hr_color']; ?>" />
		<?php ks_comments_link(ks_pict_number(1), __('View Comments(0)', 'redportal'), __('View Comment(1)', 'redportal'), sprintf(__('View Comments(%d)', 'redportal'), get_comments_number($id)), '' . __('<img localsrc="61" alt="X " />Comments Stopped', 'redportal'), NULL, '1');
		ks_comments_post_link(__('Write a comment', 'redportal'), '<br />', '', ks_pict_number(2), '2');
		edit_post_link(__('Edit post'), '<br /><img localsrc="104" alt="" />');
		?><hr color="<?php echo $ks_settings['hr_color']; ?>" /><?php 
		ks_previous_post_link('<div align="left"><img localsrc="7" alt="&laquo;">*.%link</div>');
		ks_next_post_link('<div align="right">#.%link<img localsrc="8" alt="&raquo;"></div>');
		ks_tags('<hr color="' . $ks_settings['hr_color'] . '" />' . __('<img localsrc="77" alt="" />Related Words', 'redportal') . '<br />');
	else : // ks_is_comment()
		comments_template();
	endif;
else : ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>