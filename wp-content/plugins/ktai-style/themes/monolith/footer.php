<?php
	global $ks_settings;
	$count = 1;
	$max   = 10;
	$show_on_front = (get_option('show_on_front') == 'page');
?>
<!--end paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->
<?php ks_switch_inline_images('<hr color="' . $ks_settings['hr_color'] . '" /><div align="center">', '</div>'); ?>
<hr color="<?php echo $ks_settings['hr_color']; ?>" />
<div><?php ks_pict_number($count, true); ?><a name="tail" href="<?php ks_blogurl(); ?>" accesskey="<?php echo intval($count++); ?>"><?php 
	echo ($show_on_front ? __('Front Page', 'ktai_style') : __('New Posts', 'ktai_style')); 
?></a><?php 
ks_pages_menu(' | ', ' | ', '', $ks_settings['list_pages']); ?></div>
<form method="get" action="<?php ks_blogurl(); ?>"><div>
<input type="text" name="ks" value="<?php the_search_query(); ?>" />
<input type="submit" value="<?php _e('Search'); ?>" />
</div></form>
<dl><dt><?php 
if ($show_on_front && ($page_for_posts = get_option('page_for_posts')) ) {
	ks_ordered_link($count++, $max, get_permalink($page_for_posts), __('New Posts', 'ktai_style'));
	_e(' / ', 'ktai_style');
}
ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=comments', 
	(ks_option('ks_separate_comments') ?
	__('Recent Comments/Pings', 'ktai_style') : __('Recent Comments', 'ktai_style'))
	); ?></dt>
<dt><?php ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=months', __('Archives')); ?></dt>
<dt><?php ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=cats', __('Categories'));
_e(' / ', 'ktai_style'); ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=tags', __('Tags')); ?></dt>
<?php ks_admin_link('<dt><img localsrc="112" alt="" />');
ks_login_link(array(
	'before' => '<dt><img localsrc="120" alt="' . __('&dagger;', 'ktai_style') . '" />', 
	'before_logout' => '<img localsrc="120" alt="' . __('&dagger;', 'ktai_style') . '" />', 
	'after' => '</dt>'
));
?></dl>
<div id="footer" style="color:white;background-color:gray;">
<div align="right"><?php 
$admin_user = ks_get_admin_user();
if ($admin_user) {
	?>Copyright <img localsrc="81" /> <?php echo date('Y'), ' ', esc_html($admin_user->display_name);
} else {
	?>Powered by Ktai Style with Monolith theme.<?php 
} 
ks_switch_pc_view('color=white'); ?></div>
<?php ks_wp_footer(KTAI_NONE_PC_HEAD); ?>
</div></body></html>