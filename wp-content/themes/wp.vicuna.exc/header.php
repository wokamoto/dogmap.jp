<?php echo '<?xml version="1.0" encoding="'. get_bloginfo('charset') . '" ?>'; ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
	if (function_exists('wpj_cut_hindrance')) {
		$lang = wpj_cut_hindrance(get_bloginfo('language'));
	} else {
		$lang = "ja";
	}
?>
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo $lang; ?>" xml:lang="<?php echo $lang; ?>">
<head profile="http://purl.org/net/ns/metaprof">
<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta name="author" content="<?php bloginfo('name'); ?>" />
<meta http-equiv="X-UA-Compatible" content="IE=8" />
<?php vicuna_description_displays(); ?>
<?php	if ( !is_home() ) : ?>
<link rel="start" href="<?php bloginfo('home'); ?>" title="<?php bloginfo('name'); ?> Home" />
<?php	endif; ?>
<?php $css = get_bloginfo('template_url') .'/style.php'; ?>
<link rel="stylesheet" type="text/css" href="<?php echo $css; ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php	if ( is_single() || is_page() ) : ?>
<script type="text/javascript" charset="utf-8" src="<?php echo get_vicuna_javascript_uri() ?>"></script>
<?php	endif; ?>
<!-- WordPress general-template  start -->
<?php wp_head(); ?>
<!-- WordPress general-template  end -->
