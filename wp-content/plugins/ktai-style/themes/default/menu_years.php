<?php ks_header();
global $ks_settings;

function ks_yearly_menu($link, $year) {
	$link = ks_blogurl(KTAI_NOT_ECHO) . '?menu=months&amp;y=' . intval($year);
	return $link;
}
add_filter('year_link', 'ks_yearly_menu', 10, 2); 
?>
<!--start paging<?php 
if (isset($ks_settings['hr_color'])) {
	echo '[<hr color="' . $ks_settings['hr_color'] . '" />]';
} ?>-->
<div<?php if (isset($ks_settings['h2_style'])) {
	echo $ks_settings['h2_style'];
} ?>><h2 id="years"><?php _e('Archives by Years', 'ktai_style'); ?></h2></div>
<ul> 
	<?php ks_get_archives('type=yearly&show_post_count=1'); ?>
</ul>
<?php ks_footer(); ?>