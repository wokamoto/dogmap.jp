<?php ks_force_text_html(); ?><html>
<?php /* ks_use_appl_xhtml(); */ ?>
<head>
<meta http-equiv="Content-Type" content="<?php ks_mimetype(); ?>; charset=<?php ks_charset(); ?>" />
<title><?php ks_title(); ?></title>
<?php ks_wp_head(KTAI_NONE_PC_HEAD); ?>
</head>
<body>
<?php /* <body bgcolor="" text="" link="" alink="" vlink=""> */ ?>
<?php global $ks_settings;
$ks_settings = array(); // erase array for security
$ks_settings['h2_style'] = '';
$ks_settings['list_pages'] = 'sort_column=menu_order,post_title';
$ks_settings['ol_max'] = 9;
$ks_settings['ol_count'] = 1;
$ks_settings['date_color'] = ks_option('ks_date_color');
$ks_settings['time_color'] = ks_option('ks_time_color');
$ks_settings['author_color'] = ks_option('ks_author_color');
$ks_settings['comment_color'] = ks_option('ks_comment_color');
$ks_settings['edit_color'] = ks_option('ks_edit_color');
ks_page_title();
/* ks_page_title('logo.png'); */
?><div align="right"><font size="-1"><a href="#tail" accesskey="0"><img localsrc="30" alt="<?php _e('&darr;', 'ktai_style'); ?>" /><?php _e('Menu', 'ktai_style'); ?></a><?php ks_pict_number(0, true); ?></font></div>
<hr />