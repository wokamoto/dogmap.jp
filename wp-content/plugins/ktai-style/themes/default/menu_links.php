<?php ks_header();
global $ks_settings;
?>
<!--start paging<?php 
if (isset($ks_settings['hr_color'])) {
	echo '[<hr color="' . $ks_settings['hr_color'] . '" />]';
} ?>-->
<div<?php if (isset($ks_settings['h2_style'])) {
	echo $ks_settings['h2_style'];
} ?>><h2 id="links"><?php _e('Links', 'ktai_style'); ?></h2></div>
<?php ks_list_bookmarks('title_before=<p>&title_after=</p>&class=&category_before=&category_after=&between=');
ks_footer(); ?>