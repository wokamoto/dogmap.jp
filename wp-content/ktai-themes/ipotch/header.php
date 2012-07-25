<?php /* ks_use_appl_xhtml(); */ ?><?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="<?php ks_mimetype(); ?>; charset=<?php ks_charset(); ?>" />
<?php if (is_ktai('type') == 'TouchPhone') { ?>
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<?php }
if ( ks_is_comment_post() || ks_is_redir() ) { ?>
<meta name="robots" content="noindex,nofollow" />
<?php } ?>
<title><?php if (ks_is_redir()) {
	_e('Confirm connecting to external sites', 'ktai_style');
} else {
	ks_title();
} ?></title>
<?php if (is_ktai() == 'KDDI' && is_ktai('type') == 'WAP2.0') { ?>
	<style type="text/css"><![CDATA[ p {margin:0.75em 0;} h1 {margin-top:16px;} ]]></style>
<?php } elseif (is_ktai() == 'SoftBank' && is_ktai('type') == '3G') { ?>
	<style type="text/css"><![CDATA[ hr {margin:0.5em 0;} ]]></style>
<?php } 
if (ks_ext_css_available()) { ?>
<link rel="stylesheet" type="text/css" media="all" href="<?php ks_theme_url(); ?>style.css" />
<?php }
/* ks_wp_head(); */ ?>
</head>
<body bgcolor="#c6cdd3" background="<?php ks_theme_url(); ?>pinstripes.gif">
<?php global $ks_settings;
$ks_settings = array(); // erase array for security
$ks_settings['list_pages'] = 'sort_column=menu_order,post_title';
$ks_settings['h3_style'] = '<div style="color:white;background-color:#222222;font-size:small;">';
$ks_settings['comments_icon'] = '<img localsrc="86" alt="[' . __('Comments') . '] " />';
$ks_settings['comments_number_style'] = 'color:white;background-color:#ff1133;';
$ks_settings['date_style'] = 'background-color:#dddddd;';
$ks_settings['edit_color'] = 'gray';
if (! ks_is_redir()) { // other than redir.php
	$opt = array(
		'before' => '<div style="color:white;background:#222222 url(' . ks_theme_url(KTAI_NOT_ECHO) . 'head.gif) repeat-x;margin:0;text-align:center;"><br /><h1>', 
		'before_logo' => NULL, 
		'after' => '</h1><br /></div>');
	/* $opt['logo_file'] = 'logo.png'; */
	ks_page_title($opt); ?>
	<div align="right" style="color:white;background-color:#333333;"><a href="#tail"><font size="-1" style="color:white;"><img localsrc="30" alt="<?php _e('&darr;', 'ktai_style'); ?>" /><?php _e('Menu', 'ktai_style'); ?></font></a></div>
<?php } // ks_is_redir
if (ks_ext_css_available()) { ?>
<!--start paging[<div style="background-color:#a0a0a0;">,</div>]-->
<?php } else { ?>
<!--start paging[<hr color="gray" />,<hr color="gray" />]-->
<?php }