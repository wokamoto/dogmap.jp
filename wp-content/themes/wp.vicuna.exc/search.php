<?php	get_header(); ?>
	<title><?php vicuna_title(wp_specialchars($s, 1)); ?></title>
</head>
<body class="individual <?php vicuna_layout('search'); ?>" id="siteSearch">
<?php vicuna_analysis_code() ?>
<div id="header">
	<p class="siteName"><a href="<?php bloginfo('home'); ?>" title="<?php printf(__('Return to %s index', 'vicuna'), get_bloginfo('name')); ?>"><?php bloginfo('name'); ?></a></p>
	<?php vicuna_description(); ?>
<?php vicuna_global_navigation() ?>
</div>

<div id="content">
	<div id="main">
		<p class="topicPath"><a href="<?php bloginfo('home'); ?>"><?php _e('Home', 'vicuna'); ?></a> &gt; <span class="current"><?php _e('Search Results'); ?></span></p>
<!--start dynamic_sidebar header -->
<div id="header_bar" align="center"><?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('header') ) : ?><?php endif; ?></div>
<!--end dynamic_sidebar header -->
		<h1><?php _e('Search Results'); ?></h1>
		<p><?php $allsearch =& new WP_Query("s=$s&showposts=-1"); printf(__('Result <span class="count">%s</span> for <em>%s</em>', 'vicuna'), $allsearch->post_count, wp_specialchars($s, 1)); ?></p>
<?php
	if (have_posts()) :
		while (have_posts()) : the_post(); ?>
		<div class="section entry">
			<h2><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>
			<ul class="info">
				<li class="date"><?php the_time(__('Y-m-d (D)', 'vicuna')) ?></li>
				<li class="category"><?php the_category(' | ') ?></li>
				<?php /* -- ex cng s -- */
					vicuna_author_displays();
					vicuna_tag_displays(); ?>
				<?php //if (function_exists('the_tags')) : the_tags('<li class="tags">', ' | ', '</li>'); endif; ?>
				<?php /* -- ex cng e -- */ ?>
				<?php edit_post_link(__('Edit'), '<li class="admin">', '</li>'); ?>
			</ul>
			<div class="textBody">
<?php the_content(__('Continue reading', 'vicuna')); ?>
			</div>
			<ul class="reaction">
<?php			$trackpingCount = get_vicuna_pings_count();
			$commentCount = (int) get_vicuna_comment_count() - (int) $trackpingCount;
			if ($commentCount > 0 || 'open' == $post->comment_status) : ?>
				<li class="comment"><a href="<?php the_permalink() ?>#comments" title="<?php printf(__('Comments on %s'), $title); ?>" rel="nofollow"><?php if ('open' == $post->comment_status) : _e('Comments'); else : _e('Comments (Close)', 'vicuna'); endif; ?></a>: <span class="count"><?php echo $commentCount; ?></span></li>
<?php			else : ?>
				<li><?php _e('Comments (Close)', 'vicuna'); ?>: <span class="count"><?php echo $commentCount; ?></span></li>
<?php
			endif;
			if ($trackpingCount > 0 || 'open' == $post->ping_status) :
?>
				<li class="trackback"><a href="<?php the_permalink() ?>#trackback" title="<?php printf(__('Trackbacks to %s', 'vicuna'), $title); ?>" rel="nofollow"><?php if ('open' == $post->ping_status) : _e('Trackbacks', 'vicuna'); else : _e('Trackbacks (Close)', 'vicuna'); endif; ?></a>: <span class="count"><?php echo $trackpingCount; ?></span></li>
<?php			else : ?>
				<li><?php _e('Trackbacks (Close)', 'vicuna'); ?>: <span class="count"><?php echo $trackpingCount; ?></span></li>
<?php			endif ?>
			</ul>
		</div>
<?php
		endwhile;
	elseif (0) : ?>
		<div class="section entry">
				<h2><?php _e('Search Results'); ?></h2>
				<div class="textBody">
					<p><?php _e('You did not enter anything to search for. Please try again', 'vicuna'); ?></p>
				</div>
		</div>
<?php	else : ?>
		<div class="section entry">
			<h2><?php _e('Search Results'); ?></h2>
			<div class="textBody">
				<p><?php printf(__('Your search - <em>%s</em> -- did not match any documents.', 'vicuna'), wp_specialchars($s, 1)); ?></p>
				<p><?php _e('Suggestions:', 'vicuna'); ?></p>
				<ul>
					<li><?php _e('Make sure all words are spelled correctly.', 'vicuna'); ?></li>
					<li><?php _e('Try different keywords.', 'vicuna'); ?></li>
					<li><?php _e('Try more general keywords.', 'vicuna'); ?></li>
					<li><?php _e('Try decreasing the number of keywords.', 'vicuna'); ?></li>
				</ul>
			</div>
		</div>
<?php	endif; ?>
<!--start dynamic_sidebar footer -->
<div id="footer_bar" align="center"><?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('footer') ) : ?><?php endif; ?></div>
<!--end dynamic_sidebar footer -->
<!--start pagenavi -->
<?php if(function_exists('wp_pagenavi')) { wp_pagenavi(); } else { vicuna_paging_link('indent=2'); } ?>
<!--end pagenavi -->
		<p class="topicPath"><a href="<?php bloginfo('home'); ?>"><?php _e('Home', 'vicuna'); ?></a> &gt; <span class="current"><?php _e('Search Results'); ?></span></p>
	</div><!-- end main -->

<?php	get_sidebar(); ?>

<?php	get_footer(); ?>
