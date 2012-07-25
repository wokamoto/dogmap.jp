<?php ks_header();
global $ks_settings, $Ktai_Style;
?>
<h2 id="months"><?php 
	if (isset($_GET['y']) && ($year =$_GET['y']) > 0) {
		printf(__('Archives for year %d', 'ktai_style'), $year);
		$arg = array('year' => $year);
	} else {
		_e('Archives by Months', 'ktai_style');
		$arg = array();
	} ?></h2>
<?php 
	$output = ks_get_archives(array(
		'type' => 'monthly', 
		'show_post_count' => true, 
		'before' => ipotch_box(KTAI_NOT_ECHO), 
		'after' => '</div>', 
		'format' => 'custom', 
		'echo' => false,
	) + $arg);
	$output = $Ktai_Style->filter_tags($output);
	$output = preg_replace('/ ?(\d+) ?/', '\\1' , $output);
	$output = preg_replace('!href=([\'"])' . preg_quote(get_bloginfo('url'), '!') . '/?!', 'href=$1' . $Ktai_Style->shrinkage->get('url'), $output); //"syntax highlighting fix
	$output = preg_replace('#(<a href=([\'"])[^>]+\\2>[^<]*</a>)(&nbsp;|\s*)\((\d+)\)\s*</div>#', '<span class="num">$4</span> $1</div>', $output);
	echo $output;
ks_footer(); ?>