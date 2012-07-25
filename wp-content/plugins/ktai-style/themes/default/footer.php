<!--end paging-->
<?php ks_switch_inline_images(); ?>
<hr />
<div><a name="tail" href="<?php ks_blogurl(); ?>"><?php 
global $ks_settings;
$max = 10;
$show_on_front = (get_option('show_on_front') == 'page');
echo ($show_on_front ? __('Front Page', 'ktai_style') : __('New Posts', 'ktai_style')); ?></a><?php 
if ($show_on_front && ($page_for_posts = get_option('page_for_posts')) ) {
	?> | <?php ks_ordered_link('', $max, get_permalink($page_for_posts), __('New Posts', 'ktai_style'));
}
?> | <?php ks_ordered_link('', $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=comments', (ks_option('ks_separate_comments') ? __('Recent Comments/Pings', 'ktai_style') : __('Recent Comments', 'ktai_style')) );
?> | <?php ks_ordered_link('', $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=months', __('Archives'));
?> | <?php ks_ordered_link('', $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=cats', __('Categories')); 
?> | <?php ks_ordered_link('', $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=tags', __('Tags'));
ks_admin_link(' | '); ks_login_link(' | '); ?></div>
<form method="get" action="<?php ks_blogurl(); ?>"><div>
<input type="text" name="ks" value="<?php the_search_query(); ?>" />
<input type="submit" value="<?php _e('Search'); ?>" />
</div></form>
<?php ks_pages_menu(' | ', '<div>', '</div>', $ks_settings['list_pages']); ?>
<div align="right">Converted by Ktai Style plugin.<?php ks_switch_pc_view(); ?></div>
<?php ks_wp_footer(KTAI_NONE_PC_HEAD); ?>
</body></html>