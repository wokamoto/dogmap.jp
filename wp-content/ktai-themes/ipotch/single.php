<?php ks_header();
global $ks_settings;
if (have_posts()) : the_post();
	if (! ks_is_comments()) : 
		ipotch_box();
		?><h3 style="margin:0;"><font color="black"><?php the_title(); ?></font></h3>
		<div align="right"><span style="color:gray;"><?php ks_time(); ?><img localsrc="68" alt=" by "/><?php the_author(); ?></span></div>
		</div><?php ipotch_box(); ks_content(__('(more...)')); ks_link_pages(); ?>
		<div style="background-color:#eeeeee;"><span style="color:gray;font-size:small;"><?php _e('Categories:'); ks_category();
		ks_tags('<br />' . __('Tags:'), ''); ?></span></div>
		</div><?php ipotch_box();
		ks_previous_post_link('<div align="left"><img localsrc="7" alt="&laquo; ">*.%link</div>');
		ks_next_post_link('<div align="right">#.%link<img localsrc="8" alt=" &raquo;"></div>');
		?></div><?php ipotch_box();
		if (ks_option('ks_separate_comments')) {
			ks_comments_link(NULL, 
				__('No Comments/Pings', 'ktai_style'), 
				__('One Comment/Ping', 'ktai_style'), 
				__('% Comments and Pings', 'ktai_style'));
		} else {
			ks_comments_link();
		}
		ks_comments_post_link(NULL, '<br />');
		edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit post') . '</font>', '<br /><img localsrc="104" alt="" />');
		?></div><?php
	else : // ks_is_comment()
		ipotch_box();
		echo '<h3 style="margin:0;">' . sprintf(__('Comments for <a href="%1$s"><span style="%2$s">%3$s</span></a>', 'ktai_style'), get_permalink(), 'color:black;' ,get_the_title()) . '</h3></div>';
		comments_template();
	endif;
else : 
	ipotch_box(); ?><h3><?php _e('Not Found', 'ktai_style'); ?></h3></div><?php 
	ipotch_box(); ?><p><?php _e('Sorry, no posts matched your criteria.'); ?></p></div>
<?php endif;
ks_footer(); ?>