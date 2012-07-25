<?php ks_header();
global $ks_settings;
?>
<!--start paging<?php 
if (isset($ks_settings['hr_color'])) {
	echo '[<hr color="' . $ks_settings['hr_color'] . '" />]';
} ?>-->
<div<?php if (isset($ks_settings['h2_style'])) { 
	echo $ks_h2_style;
} ?>><h2 id="pages"><?php _e('Pages', 'ktai_style'); ?></h2></div>
<ul><?php 
$opt = 'title_li=';
$opt .= isset($ks_settings['list_pages']) ? ('&' . $ks_settings['list_pages']) : '';
wp_list_pages($opt);
?></ul>
<?php ks_footer(); ?>