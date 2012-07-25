<?php ks_use_appl_xhtml(); ?>
<head>
<meta http-equiv="Content-Type" content="<?php ks_mimetype(); ?>; charset=<?php ks_charset(); ?>" />
<title><?php if (ks_is_redir()) {
	_e('Confirm connecting to external sites', 'ktai_style');
} else {
	ks_title();
} ?></title>
<?php ks_wp_head(KTAI_NONE_PC_HEAD); ?>
</head>
<body link="green" vlink="olive">
<?php global $ks_settings,  $show_on_front, $page_for_posts;
$ks_settings = array(); // erase array for security
$ks_settings['h2_style'] = '';
$ks_settings['hr_color'] = 'green';
$ks_settings['list_pages'] = 'sort_column=menu_order,post_title';
$ks_settings['title_style'] = 'color:white;background-color:#22bb11;';
$ks_settings['list_color'] = 'maroon';
$ks_settings['date_color'] = 'maroon';
$ks_settings['time_color'] = 'green';
$ks_settings['author_color'] = ks_option('ks_author_color');
$ks_settings['comment_color'] = 'gray';
$ks_settings['edit_color'] = 'teal';
$ks_settings['pagenum_style'] = 'color:olive;text-align:center;';
$show_on_front = (get_option('show_on_front') == 'page');
$page_for_posts = get_option('page_for_posts');
$ks_settings['ol_max'] = ($show_on_front && $page_for_posts) ? 6 : 7;
if (! ks_is_redir()) { // other than redir.php
	$before = '<div style="color:white;background-color:#009933;margin:0;">' 
	. (ks_is_flat_rate() ? 
	'<h1 style="margin:0"><img src="' . ks_theme_url(KTAI_NOT_ECHO) . 'icon.gif" alt="" />'
/*	'<h1 style="margin:0"><img src="' . ks_theme_url(KTAI_NOT_ECHO) . 'icon-feather.gif" alt="" />' */
/*	'<h1 style="margin:0"><img src="' . ks_theme_url(KTAI_NOT_ECHO) . 'icon-sunflower.gif" alt="" />' */
	: '<h1>');
	ks_page_title(array('before' => $before, 'after' => '</h1></div>'));
	if (ks_is_front()) { ?>
		<p><font size="-1"><?php bloginfo('description'); ?></font></p>
	<?php } ?>
	<div align="right"><font size="-1"><a href="#tail"><img localsrc="30" alt="<?php _e('&darr;', 'ktai_style'); ?>" /><?php _e('Menu', 'ktai_style'); ?></a></font></div>
<hr color="<?php echo $ks_settings['hr_color']; ?>" />
<?php } // ks_is_redir ?>