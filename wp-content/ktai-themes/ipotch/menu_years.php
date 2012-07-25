<?php ks_header();
global $ks_settings, $Ktai_Style;

function ks_yearly_menu($link, $year) {
	$link = ks_blogurl(KTAI_NOT_ECHO) . '?menu=months&amp;y=' . intval($year);
	return $link;
}
add_filter('year_link', 'ks_yearly_menu', 10, 2); 
?>
<h2 id="years"><?php _e('Archives by Years', 'ktai_style'); ?></h2>
<?php 
	$output = ks_get_archives(array(
		'type' => 'yearly', 
		'show_post_count' => true, 
		'before' => ipotch_box(KTAI_NOT_ECHO), 
		'after' => '</div>', 
		'format' => 'custom', 
		'echo' => false,
	));
	$output = $Ktai_Style->filter_tags($output);
	$output = preg_replace('/ ?(\d+) ?/', '\\1' , $output);
	$output = preg_replace('!href=([\'"])' . preg_quote(get_bloginfo('url'), '!') . '/?!', 'href=$1' . $Ktai_Style->shrinkage->get('url'), $output); //"syntax highlighting fix
	$output = preg_replace('#(<a href=([\'"])[^>]+\\2>[^<]*</a>)(&nbsp;|\s*)\((\d+)\)\s*</div>#', '<span class="num">$4</span> $1</div>', $output);
	echo $output;
ks_footer(); ?>