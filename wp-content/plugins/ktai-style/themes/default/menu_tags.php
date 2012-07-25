<?php ks_header();
global $ks_settings;
?>
<!--start paging<?php 
if (isset($ks_settings['hr_color'])) {
	echo '[<hr color="' . $ks_settings['hr_color'] . '" />]';
} ?>-->
<div<?php if (isset($ks_settings['h2_style'])) {
	echo $ks_settings['h2_style']; 
} ?>><h2 id="tags"><?php _e('Tags List', 'ktai_style'); ?></h2></div>
<div><?php ks_tag_cloud(); ?></div>
<?php ks_footer(); ?>