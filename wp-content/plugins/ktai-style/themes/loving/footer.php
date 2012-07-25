<?php
	global $ks_settings;
	$count = 3;
	$max   = 10;
?>
<!--end paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->
<?php ks_switch_inline_images('<hr color="' . $ks_settings['hr_color'] . '" /><div align="center">', '</div>'); ?>
<hr color="<?php echo $ks_settings['hr_color']; ?>" />
<div><?php 
if ($ks_settings['need_front']) {
	?><a name="tail" href="<?php ks_blogurl(); ?>"><?php _e('Front Page', 'ktai_style'); ?></a> | <?php
} else {
	?><a name="tail"></a><?php 
}
ks_pages_menu($ks_settings['list_pages']); ?>
</div>
<form method="get" action="<?php ks_blogurl(); ?>"><div>
<input type="text" name="ks" value="<?php the_search_query(); ?>" />
<input type="submit" value="<?php _e('Search'); ?>" />
</div></form>
<dl><dt><?php ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=months', __('Archives')); ?></dt>
<dt><?php ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=cats', __('Categories'));
_e(' / ', 'ktai_style'); ks_ordered_link($count++, $max, ks_blogurl(KTAI_NOT_ECHO) . '?menu=tags', __('Tags')); ?></dt>
<?php if (is_user_logged_in()) {
	ks_admin_link(array(
		'before' => '<dt>' . ks_pict_number($count) . '<img localsrc="112" alt="' . __('&dagger;', 'ktai_style') . '" />', 
		'accesskey' => $count++));
	$accesskey = NULL;
} else {
	$accesskey = $count++;
}
ks_login_link(array(
	'before' => '<dt>' . ks_pict_number($accesskey) . '<img localsrc="120" alt="' . __('&dagger;', 'ktai_style') . '" />', 
	'before_logout' => '<img localsrc="120" alt="' . __('&dagger;', 'ktai_style') . '" />', 
	'after' => '</dt>',
	'accesskey' => $accesskey)
); ?></dl>
<div id="footer" style="color:white;background-color:#dd143c;">
<div align="right">We<font color="white"><img localsrc="416" alt=" love " /></font>WordPress.<?php ks_switch_pc_view('color=#eeeeee'); ?></div>
<?php ks_wp_footer(KTAI_NONE_PC_HEAD); ?>
</div></body></html>