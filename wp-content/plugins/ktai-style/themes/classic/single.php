<?php ks_header();
global $ks_settings, $post;
?>
<!--start paging-->
<?php if (have_posts()) : the_post();
	if (! ks_is_comments() ) :
		the_date('', '<u>', '</u><br />'); ?>
		[<font color="<?php echo $ks_settings['title_color']; ?>"><?php the_title(); ?></font>]<?php 
		?></div><br />
		<?php ks_content(__('(more...)')); ks_link_pages(); ?>
		<div><?php _e('Author'); the_author(); ?><br />
		<font size="-1"><?php echo __('Categories') . ':'; ks_category(); ?></font><br />
		<?php ks_tags('<font size="-1">' . __('Tags') . ':', '</font><br />');
		the_time(); ?></div>
		<hr />
		<?php if (ks_option('ks_separate_comments')) {
			ks_comments_link('', 
				__('No Comments/Pings', 'ktai_style'), 
				__('One Comment/Ping', 'ktai_style'), 
				__('% Comments and Pings', 'ktai_style'));
		} else {
			ks_comments_link();
		}
		ks_comments_post_link(NULL, ' ', '', '');
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit post') . '</font>', '<br /><img localsrc="112" alt="" />', ''); ?>
		<hr />
		<div><?php 
		ks_previous_post_link(__('*.Prev:%link', 'ktai_style'));
		ks_next_post_link('<br />' . __('#.Next:%link', 'ktai_style'));
		?></div>
	<?php else : // ks_is_comment()
		$title = '<font color="' . $ks_settings['title_color'] . '">' . get_the_title() . '</font>';
		if (! $post->post_password) {
			echo '[<a href="' . apply_filters('the_permalink', get_permalink()) . '">' . $title . '</a>]<br />';
		} else {
			echo '[' . $title . ']<br />';
			ks_back_to_post('');
		}
		comments_template();
	endif;
else : ?>
	<h2><?php _e('Not Found', 'ktai_style'); ?></h2>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif;
ks_footer(); ?>