<?php
	global $ks_settings;
	$count = 1;
	$max   = 10;
	$show_on_front = (get_option('show_on_front') == 'page');
	if (ks_ext_css_available()) { ?>
<!--end paging[<div style="background-color:#a0a0a0;">,</div>]-->
<?php } else { ?>
<!--end paging[<hr color="gray" />]-->
<?php }
ks_switch_inline_images(ipotch_box(false), '</div>');
if (! ks_applied_appl_xhtml()) { ?>
<hr color="gray" />
<?php } ?>
<div style="color:white;background-color:#333333;"><small><a name="tail" href="<?php ks_blogurl(); ?>"><?php echo ipotch_link_desc($show_on_front ? __('Front Page', 'ktai_style') : __('New Posts', 'ktai_style')); ?></a> &nbsp; <?php 
if ($show_on_front && ($page_for_posts = get_option('page_for_posts')) ) {
	ks_ordered_link('', $max, get_permalink($page_for_posts), ipotch_link_desc(__('New Posts', 'ktai_style')) );
	?> &nbsp; <?php 
}
ks_ordered_link('', $max, ks_blogurl(false) . '?menu=pages', ipotch_link_desc(__('Pages', 'ktai_style')) ); ?></small>
<form method="get" action="<?php ks_blogurl(); ?>"><div>
<img localsrc="119" alt="" /><input type="text" name="ks" size="16" value="<?php the_search_query(); ?>" />
<input type="submit" value="<?php _e('Search'); ?>" />
</div></form>
<small><?php 
ks_ordered_link('', $max, ks_blogurl(false) . '?menu=comments', 
	ipotch_link_desc( ks_option('ks_separate_comments') ? __('Recent Comments/Pings', 'ktai_style') : __('Recent Comments', 'ktai_style')) ); ?> &nbsp; <?php 
ks_ordered_link('', $max, ks_blogurl(false) . '?menu=months', ipotch_link_desc(__('Archives')) ); ?> &nbsp; <?php 
ks_ordered_link('', $max, ks_blogurl(false) . '?menu=cats', ipotch_link_desc(__('Categories')) ); ?> &nbsp; <?php 
ks_ordered_link('', $max, ks_blogurl(false) . '?menu=tags', ipotch_link_desc(__('Tags')) );
ks_admin_link(array('before' => '<br /><img localsrc="112" alt="" />', 'label' => ipotch_link_desc(__('Site Admin')) ));
ks_login_link(array(
	'before' => '<br /><img localsrc="120" alt="' . __('&dagger;', 'ktai_style') . '" />', 
	'before_logout' => '<img localsrc="120" alt="' . __('&dagger;', 'ktai_style') . '" />', 
	'label' => ipotch_link_desc(__('Log in')), 
	'label_logout' => ipotch_link_desc(__('Log out'))
)); ?></small>
<div align="right"><?php 
$admin_user = ks_get_admin_user();
if ($admin_user) {
	?>Copyright <img localsrc="81" /> <?php echo date('Y'), ' ', wp_specialchars($admin_user->display_name);
} else {
	?>Powered by Ktai Style with iPotch theme.<?php 
} 
ks_switch_pc_view('color=silver');
$UH2url  = 'http://b14.ugo2.jp/?u=5019649&amp;h=fa8c0d&amp;ut=1&amp;guid=ON&amp;qM=';
$UH2url .= urlencode(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'').'|Az|'.(int)($_SERVER['SERVER_PORT']);
$UH2url .= '|'.urlencode($_SERVER['HTTP_HOST']).'|'.urlencode($_SERVER['REQUEST_URI']);
$UH2url .= '|H|&amp;ch=UTF-8&amp;sb='.urlencode('[page title]');
echo '&nbsp;<a href="http://ugo2.jp/m/">';
echo '<img src="'.$UH2url.'" alt="携帯アクセス解析" width="72" height="16" border="0" />';
echo '</a>';
?></div>
<?php /* ks_wp_footer(); */ ?>
</div></body></html>