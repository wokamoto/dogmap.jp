<?php	get_header(); ?>
	<title><?php vicuna_title(the_title('','',false)); ?></title>
</head>
<?php	if (have_posts()) : the_post(); ?>
<body class="individual <?php vicuna_layout('page'); ?>" id="entry<?php the_ID(); ?>">
<?php vicuna_analysis_code() ?>
<div id="header">
	<p class="siteName"><a href="<?php bloginfo('home'); ?>" title="<?php printf(__('Return to %s index', 'vicuna'), get_bloginfo('name')); ?>"><?php bloginfo('name'); ?></a></p>
	<?php vicuna_description(); ?>
<?php vicuna_global_navigation() ?>
</div>

<div id="content">
	<div id="main">
		<p class="topicPath"><a href="<?php bloginfo('home'); ?>"><?php _e('Home', 'vicuna'); ?></a><?php
		$parent_pages = get_vicuna_page_navigation('sort_column=menu_order');
		if ($parent_pages) : ?> &gt; <?php echo $parent_pages; ?><?php endif; ?> &gt; <span class="current"><?php the_title(); ?></span></p>
<!--start dynamic_sidebar header -->
<div id="header_bar" align="center"><?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('header') ) : ?><?php endif; ?></div>
<!--end dynamic_sidebar header -->
		<h1><?php the_title(); ?></h1>
		<div class="entry">
			<ul class="info">
				<li class="date"><?php echo get_the_modified_time(__('Y-m-d (D) G:i', 'vicuna')); ?></li>
				<?php edit_post_link(__('Edit'), '<li class="admin">', '</li>'); ?>
			</ul>
			<div class="textBody">
<?php the_content(__('Continue reading', 'vicuna')); ?>
			</div>
<?php comments_template(); ?>
		</div><!--end entry-->
<!--start dynamic_sidebar footer -->
<div id="footer_bar" align="center"><?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('footer') ) : ?><?php endif; ?></div>
<!--end dynamic_sidebar footer -->
		<p class="topicPath"><a href="<?php bloginfo('home'); ?>"><?php _e('Home', 'vicuna'); ?></a><?php if ($parent_pages) : ?> &gt; <?php echo $parent_pages; ?><?php endif; ?> &gt; <span class="current"><?php the_title(); ?></span></p>
	</div><!-- end main-->

<?php	get_sidebar(); ?>

<?php	get_footer(); ?>
<?php endif; ?>
