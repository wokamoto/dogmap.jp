<?php global $ks_settings; ?>
<!--end paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->
<?php ks_switch_inline_images('<hr color="' . $ks_settings['hr_color'] . '" /><div align="center">', '</div>'); ?>
<hr color="<?php echo $ks_settings['hr_color']; ?>" />
<a name="tail"></a>
<form method="get" action="<?php ks_blogurl(); ?>"><div>
<input type="text" name="ks" value="<?php the_search_query(); ?>" />
<input type="submit" value="<?php _e('Search'); ?>" />
</div></form>
<dl><dt><?php 
$count = $ks_settings['ol_max'] + 1;
$max = 10;
$show_on_front = (get_option('show_on_front') == 'page');
ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO), ($show_on_front ? __('Front Page', 'ktai_style') : __('New Posts', 'ktai_style')) );
if ($show_on_front && ($page_for_posts = get_option('page_for_posts')) ) {
	_e(', ', 'redportal');
	ks_ordered_link('', $max, get_permalink($page_for_posts), __('New Posts', 'ktai_style'));
}
?></dt><dt><?php 
ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=comments', ks_option('ks_separate_comments') ? __('Recent Comments/Pings', 'ktai_style') : __('Recent Comments', 'ktai_style')); ?></dt><dt><?php 
ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=months', __('Archives')); ?></dt><dt><?php 
ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=cats', __('Categories'));
_e(', ', 'redportal'); ?><a href="<?php ks_blogurl(); ?>?menu=tags"><?php _e('Tags'); ?></a></dt><dt><?php 
ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=pages', __('Pages')); ?></dt><?php 
ks_admin_link('<dt><img localsrc="112" alt="" />', '</dt>');
ks_login_link('<dt><img localsrc="120" alt="'. __('&dagger;', 'ktai_style') . '" />', '</dt>'); ?></dl>
<hr color="<?php echo esc_attr($ks_settings['hr_color']); ?>" />
<div align="center">Redportal theme for Ktai Style.<?php ks_switch_pc_view(); ?></div>
<?php ks_wp_footer(KTAI_NONE_PC_HEAD); ?>
</body></html>