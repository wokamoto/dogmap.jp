<?php
global $page, $numpages;

/*
wp_deregister_script('jquery');
wp_enqueue_script(
  'jquery',
  'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js',
  array(),
  '1.7.1');
*/

$site_url     = trailingslashit(get_bloginfo('wpurl'));
$images_url   = $site_url.'wp-includes/images/';
$template_url = $site_url.'wp-content/themes/dogmap/';
$theme_images = 'http://static.dogmap.jp/images/icons/';
$site_name    = get_bloginfo('name');
$site_description = get_bloginfo('description');

$title = $site_name;
$description = $site_description;

if (is_singular()) {
	$title = trim(wp_title('', false));
} elseif (is_year()) {
	$title = trim(apply_filters('the_time', get_the_time('Y年'), 'Y年')) . 'の記事';
} elseif (is_month()) {
	$title = trim(apply_filters('the_time', get_the_time('Y年F'), 'Y年F')) . 'の記事';
} elseif (is_day()) {
	$title = trim(apply_filters('the_time', get_the_time('Y年Fd日'), 'Y年Fd日')) . 'の記事';
} elseif (is_tag()) {
	$title = '「' . trim(single_tag_title('', false)) . '」一覧';
} elseif (is_category()) {
	$title = trim(single_cat_title('', false));
} elseif ( is_author() ) {
	$author_id = intval( get_query_var('author') );
	$title = trim(get_the_author_meta( 'display_name', $author_id ));
} elseif (is_search()) { 
	$title = '検索結果 - ' . trim(esc_attr(apply_filters('the_search_query', get_search_query(false))));
} elseif (is_404()) {
	$title = '404 Not Found';
}

if (is_home()) {
	$description = $site_description;
	$title = $site_name;
} else {
	$description = $title;
	$title .=  ($title !== $site_name ? ' : ' : '') . $site_name;
}

if ( is_singular() && get_option( 'thread_comments' ) )
	wp_enqueue_script( 'comment-reply' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes('xhtml'); ?> xmlns:fb="https://www.facebook.com/2008/fbml" xmlns:og="http://ogp.me/ns#">
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<title><?php echo $title; ?></title>
<?php if (!empty($description)) { ?>
<meta name="description" content="<?php echo $description; ?>" />
<?php } ?>

<!--- <meta property="fb:admins" content="wokamoto" /> --->
<!--- <meta property="fb:page_id" content="133309100069215" /> --->
<!--- <meta property="fb:app_id" content="169668753079276"> --->

<meta name="verify-v1" content="bbSV5OEjzrRHFUb9VnM4muyviei2JNsLabQwT3KWoAA=" />
<link rel="stylesheet" href="<?php echo $template_url; ?>style.css?ver=20081006" type="text/css" media="all" />
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $site_url; ?>favicon.ico" />
<link rel="icon" type="image/x-icon" href="<?php echo $site_url; ?>favicon.ico" />
<link rel="apple-touch-icon" href="<?php echo $site_url; ?>apple-touch-icon.png" />
<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php echo $site_url; ?>feed/" />
<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php echo $site_url; ?>feed/atom/" />
<link rel="alternate" type="application/rss+xml" title="ROR" href="<?php echo $site_url; ?>sitemap.xml" />
<link rel="index" href="<?php echo $site_url; ?>" title="<?php echo $site_name; ?>" />
<link rel="start" href="<?php echo $site_url; ?>" title="Home" />
<link rel="appendix" href="<?php echo $site_url; ?>about/" title="About" />
<link rel="pingback" href="<?php echo $site_url; ?>xmlrpc.php" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="outer">
<div id="header">
<div id="headercontent" class="left">
<h1><a href="<?php echo $site_url; ?>" title="<?php echo $site_name; ?>"><?php echo $site_name; ?></a></h1>
<p class="description"><?php echo $site_description; ?></p>
</div>
<form id="searchform" name="searchform" method="get" action="<?php echo $site_url; ?>index.php" class="right">
<div id="search">
<input type="text" name="s" id="s" class="text" value="<?php echo esc_html($s, 1); ?>" size="15" />
<input type="submit" class="submit" value=" " />
</div>
</form>
</div>
<div id="menu">
<ul>
<?php
echo '<li class="page_item first'.(is_home() ? ' current_page_item' : '').'"><a href="'.$site_url.'" title="Home">Home</a></li>'."\n";
wp_list_pages('title_li=&depth=1');
?>
</ul>
</div>
