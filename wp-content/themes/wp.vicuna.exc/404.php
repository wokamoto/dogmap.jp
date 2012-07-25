<?php	get_header(); ?>
	<title><?php vicuna_title(__('Error 404', 'vicuna')); ?></title>
</head>
<body class="individual <?php vicuna_layout('404'); ?>">
<div id="header">
	<p class="siteName"><a href="<?php bloginfo('home'); ?>" title="<?php printf(__('Return to %s index', 'vicuna'), get_bloginfo('name')); ?>"><?php bloginfo('name'); ?></a></p>
	<?php vicuna_description(); ?>
<?php vicuna_global_navigation() ?>
</div>

<div id="content">
	<div id="main">
		<p class="topicPath"><a href="<?php bloginfo('home'); ?>"><?php _e('Home', 'vicuna'); ?></a> &gt; <span class="current"><?php _e('Error 404', 'vicuna'); ?></span></p>
<!--start dynamic_sidebar header -->
<div id="header_bar" align="center"><?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('header') ) : ?><?php endif; ?></div>
<!--end dynamic_sidebar header -->
		<h1><?php _e('Error 404', 'vicuna'); ?> - <?php _e('Not Found', 'vicuna'); ?></h1>
		<div class="entry">
<div class="textBody">
<p><?php _e("Sorry, but you are looking for something that isn't here.", 'vicuna'); ?></p>
</div>

		</div><!--end entry-->
<!--start dynamic_sidebar footer -->
<div id="footer_bar" align="center"><?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('footer') ) : ?><?php endif; ?></div>
<!--end dynamic_sidebar footer -->
		<p class="topicPath"><a href="<?php bloginfo('home'); ?>"><?php _e('Home', 'vicuna'); ?></a> &gt; <span class="current"><?php _e('Error 404', 'vicuna'); ?></span></p>
	</div><!-- end main-->

<?php	get_sidebar(); ?>

<?php	get_footer(); ?>
