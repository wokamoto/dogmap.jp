<?php ks_header();
global $ks_settings;
?>
<!--start paging<?php 
if (isset($ks_settings['hr_color'])) {
	echo '[<hr color="' . $ks_settings['hr_color'] . '" />]';
} ?>-->
<div<?php if (isset($ks_settings['h2_style'])) {
	echo $ks_settings['h2_style'];
} ?>><h2 id="cats"><?php _e('Category List', 'ktai_style'); ?></h2></div>
<ul>
<?php wp_list_categories('orderby=name&show_count=1&use_desc_for_title=0&title_li='); ?>
</ul>
<?php ks_footer(); ?>