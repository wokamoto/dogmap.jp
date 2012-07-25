<?php ks_header();
global $ks_settings;
if (have_posts()) : the_post();
	ipotch_box(); ?><h4 style="margin:0;"><font color="black"><?php the_title(); ?></font></h4></div><?php
	ipotch_box();
	ks_content(__('(more...)'));
	ks_link_pages();
	?></div><?php
	edit_post_link('<font color="' . $ks_settings['edit_color'] . '">' . __('Edit page') . '</font>', ipotch_box(KTAI_NOT_ECHO) . '<img localsrc="104" alt="" />', '</div>');
	ks_posts_nav_link(' &nbsp; ', ipotch_box(KTAI_NOT_ECHO), '</div>');
else: ?>
	ipotch_box(); ?><h3><?php _e('Not Found', 'ktai_style'); ?></h3></div><?php 
	ipotch_box(); ?><p><?php _e('Sorry, no posts matched your criteria.'); ?></p></div>
<?php endif;
ks_footer(); ?>