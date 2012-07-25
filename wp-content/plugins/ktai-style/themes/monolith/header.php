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
<body bgcolor="#e0e0e8" link="navy" vlink="teal">
<?php global $ks_settings;
$ks_settings = array(); // erase array for security
$ks_settings['h2_style'] = ' style="background-color:#223355;color:white;font-size:smaller;"';
$ks_settings['h2_a_color'] = 'color:#eeeeee';
$ks_settings['hr_color'] = 'gray';
$ks_settings['list_pages'] = 'sort_column=menu_order,post_title';
$ks_settings['date_color'] = 'maroon';
$ks_settings['author_color'] = '#223300';
$ks_settings['comment_color'] = '#223300';
$ks_settings['edit_color'] = 'green';
if (! ks_is_redir()) { // other than redir.php
	$opt = array('before' => '<div style="color:white;background-color:gray;margin:0;text-align:center;"><br /><h1>', 'before_logo' => NULL, 'after' => '</h1><br /></div>');
	/* $opt['logo_file'] = 'logo.png'; */
	ks_page_title($opt);
	if (ks_is_front()) { ?>
		<p><font size="-1"><?php bloginfo('description'); ?></font></p>
	<?php } ?>
	<div align="right"><a href="#tail"><font size="-1"><img localsrc="30" alt="<?php _e('&darr;', 'ktai_style'); ?>" /><?php _e('Menu', 'ktai_style'); ?></font></a></div>
<?php } // ks_is_redir ?>
<!--start paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->