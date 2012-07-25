<?php 
/* ==================================================
 *   Ktai Admin Output Header
   ================================================== */

if (! defined('ABSPATH')) {
	exit;
}
global $mime_type, $iana_charset, $title, $user_identity;
define ('KTAI_ADMIN_HEAD', true);
ob_start(); ?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="<?php echo esc_html($mime_type); ?>; charset=<?php echo esc_html($iana_charset); ?>" />
<title><?php 
	echo get_bloginfo('name') . '&gt;' . ($title ? esc_html(strip_tags($title)) : __('Admin', 'ktai_style'));
?></title>
<?php ks_wp_head(false); ?>
</head><body>
<div><?php bloginfo('name'); ?></div>
<div align="right"><?php printf(__('Howdy, %s.', 'ktai_style'), $user_identity) ?><a href="#tail"><img localsrc="30" alt="<?php _e('&darr;', 'ktai_style'); ?>" /></a></div>
<hr color="#4f96c8" />
<h1><a name="head"><?php echo esc_html($title); ?></a></h1>
<!--start paging-->
