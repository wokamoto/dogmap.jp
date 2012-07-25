<?php ks_header();
global $ks_settings;
?>
<!--start paging<?php 
if (isset($ks_settings['hr_color'])) {
	echo '[<hr color="' . $ks_settings['hr_color'] . '" />]';
} ?>-->
<div<?php if (isset($ks_settings['h2_style'])) {
	echo $ks_settings['h2_style'];
} ?>><h2 id="months"><?php 
	if (isset($_GET['y']) && ($year =$_GET['y']) > 0) {
		printf(__('Archives for year %d', 'ktai_style'), $year);
		$arg = '&year=' . $year;
	} else {
		_e('Archives by Months', 'ktai_style');
		$arg = '';
	} ?></h2></div>
<ul>
<?php ks_get_archives('type=monthly&show_post_count=1' . $arg); ?>
</ul>
<?php ks_footer(); ?>