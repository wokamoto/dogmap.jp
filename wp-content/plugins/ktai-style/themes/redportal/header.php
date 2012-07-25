<?php ks_force_text_html(); ?><html>
<?php /* ks_use_appl_xhtml(); */ ?>
<head>
<meta http-equiv="Content-Type" content="<?php ks_mimetype(); ?>; charset=<?php ks_charset(); ?>" />
<title><?php ks_title(); ?></title>
<?php ks_wp_head(KTAI_NONE_PC_HEAD); ?>
</head>
<body link="#0066cc" vlink="#0066cc">
<?php global $ks_settings;
$ks_settings = array(); // erase array for security
$ks_settings['h2_style'] = '';
$ks_settings['hr_color'] = 'red';
$ks_settings['list_pages'] = 'sort_column=menu_order,post_title';
$ks_settings['ol_max'] = 4;
$ks_settings['date_color'] = 'red';
$ks_settings['comment_color'] = ks_option('ks_comment_type_color');
$ks_settings['edit_color'] = 'maroon';
ks_page_title();
/* ks_page_title('logo.png'); */
?>
<div align="right"><img localsrc="30" alt="<?php _e('&darr;', 'ktai_style'); ?>" /><a href="#tail"><?php _e('Menu', 'ktai_style'); ?></a></div>
<?php if (! is_single()) {
	?><hr color="<?php echo $ks_settings['hr_color']; ?>" /><?php
} ?>