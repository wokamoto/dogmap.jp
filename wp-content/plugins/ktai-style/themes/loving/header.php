<?php ks_use_appl_xhtml(); ?>
<head>
<meta http-equiv="Content-Type" content="<?php ks_mimetype(); ?>; charset=<?php ks_charset(); ?>" />
<title><?php ks_title(); ?></title>
<?php 
$h1_style = ' style="width:186px;background:url(' . ks_theme_url(KTAI_NOT_ECHO) . 'frame.gif) repeat-y;margin:0 auto;padding:0 23px;text-align:center;"';
// $h1_style = ' style="margin:0;text-align:center;"'; // style for bugle, lip title
// $logo_ext = '-bugle';
// $logo_ext = '-lip';
if ( is_ktai() == 'DoCoMo' && ks_cookie_available() ) {
}
ks_wp_head(KTAI_NONE_PC_HEAD); ?>
</head>
<body bgcolor="#fff0e0" link="purple" vlink="fuchsia">
<?php 
global $ks_settings;
$ks_settings = array(); // erase array for security
$ks_settings['h2_style'] = ' style="color:white;background-color:#ff1493;font-size:smaller;text-align:center;"';
$ks_settings['hr_color'] = '#ff69b4';
$ks_settings['list_pages'] = 'sort_column=menu_order,post_title';
$ks_settings['title_style'] = 'color:white;background-color:#ff69b4;';
$ks_settings['date_color'] = '#3366cc';
$ks_settings['comment_color'] = ks_option('ks_comment_type_color');
$ks_settings['comments_icon'] = array(
	'none' => __('Comments off', 'ktai_style'), 
	'sec'  => __('View comments (Need password)', 'ktai_style'),
	'icon' => '', 
	'icon_zero' => '<img localsrc="265" alt="" />',
	'icon_one'  => '<img localsrc="51" alt="" />',
	'icon_more' => '<img localsrc="266" alt="" />',
	'icon_none' => '<img localsrc="61" alt="" />',
	'icon_sec'  => '<img localsrc="120" alt="?" />',
);
$ks_settings['edit_color'] = 'green';
if (ks_is_flat_rate()) {
	$before = sprintf('<div align="center"><img src="%stitle1%s.gif" alt="" /><h1%s>', ks_theme_url(KTAI_NOT_ECHO), $logo_ext, $h1_style);
	$after = sprintf('</h1><img src="%stitle2%s.gif" alt="" /></div>',  ks_theme_url(KTAI_NOT_ECHO), $logo_ext);
} else {
	$before = '<h1 align="center">';
	$after = '</h1>';
}
ks_page_title(array('before' => $before, 'after' => $after));
?>
<div align="right"><a href="#tail"><font size="-1"><img localsrc="30" alt="<?php _e('&darr;', 'ktai_style'); ?>" /><?php _e('MenU', 'loving'); ?></font></a></div>
<hr color="<?php echo $ks_settings['hr_color']; ?>" />
<!--start paging[<hr color="<?php echo $ks_settings['hr_color']; ?>" />]-->
<div align="center"><?php 
	$title = __('New Posts', 'ktai_style');
	$url = ks_blogurl(KTAI_NOT_ECHO);
	$ks_settings['need_front'] = false;
	if (get_option('show_on_front') == 'page') {
		if ($post_id = get_option('page_for_posts')) {
			$url = get_permalink($post_id);
			$ks_settings['need_front'] = true;
		} else {
			$title = __('Front Page', 'ktai_style');
		}
	}
	if (ks_is_front()) {
		echo $title;
	} else {
		ks_ordered_link(1, 10, $url, $title);
	}
?> | <?php 
	$title = ks_option('ks_separate_comments') ?
	__('Recent Comments/Pings', 'ktai_style') : __('Recent Comments', 'ktai_style');
	if (ks_is_menu('comments')) {
		echo $title;
	} else {
		ks_ordered_link(2, 10, ks_blogurl(KTAI_NOT_ECHO) . '?menu=comments', $title);
	} ?></div>
<hr color="<?php echo $ks_settings['hr_color']; ?>" />