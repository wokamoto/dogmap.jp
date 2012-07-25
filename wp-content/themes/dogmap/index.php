<?php
// 404 Error Check
if (!have_posts()) {
	// 404 Error
	include (TEMPLATEPATH . '/404.php');
	return;
}
get_header();
?>
<div class="divider">
<?php if (!function_exists('paginate_links')) { ?>
<div class="left" style="margin:0; text-align:left; width:8%"><?php next_posts_link('&laquo; 前へ') ?></div>
<?php } ?>
<div class="left" style="margin:0; text-align:center; width:80%">
<?php
if (is_category()) {
	/* If this is a category archive */
	echo 'Archive for the &#8216;';
	single_cat_title();
	echo '&#8217; Category';
} elseif (is_day()) {
	/* If this is a daily archive */
	echo 'Archive for ';
	the_time('F jS, Y');
} elseif (is_month()) { 
	/* If this is a monthly archive */
	echo 'Archive for ';
	the_time('F, Y');
} elseif (is_year()) {
	/* If this is a yearly archive */
	echo 'Archive for ';
	the_time('Y');
} elseif (is_author()) {
	/* If this is an author archive */
	echo 'Author Archive';
	$author_id = intval( get_query_var('author') );
	$google_profile = get_the_author_meta( 'google_profile', $author_id );
	if ( $google_profile ) {
		echo ' ( <a href="' . $google_profile . '" rel="me">Google Profile</a> )';
	}
} elseif (is_page()) {
	/* If this is a paged archive */
	echo 'Blog Archives';
}
?>
</div>
<?php if (!function_exists('paginate_links')) { ?>
<div class="right" style="margin:0; text-align:right; width:8%"><?php previous_posts_link('次へ &raquo;') ?></div>
<?php } ?>
</div>
<div id="content">
<div class="contentbar">
<?php if (function_exists('jrelated_referer')) jrelated_referer('','<div class="related-post">','</div>'); ?>
<?php
$postcount = 0;
while (have_posts()): the_post();
?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<div class="contenttitle">
<div class="contenttitleinfo">
<div class="author<?php the_author_meta('ID'); ?>"><h2><a href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a></h2></div>
<p><small class="details">Posted by <?php the_author_posts_link(); ?> at <?php the_time('Y年n月j日 l'); ?></small></p>
</div>
</div>
<div class="contentarea">
<div class="entry">
<?php the_content('...続きを読む'); ?>
<?php if (function_exists('akpc_content_bargraph')) akpc_content_bargraph(); ?>
</div>
<div class="controls">
<ul>
<li class="permalink"><a href="<?php the_permalink(); ?>">Permalink</a></li>
<?php if ( function_exists('comments_only_popup_link')) { ?>
<li class="comments"><?php comments_only_popup_link('Comment(0)', 'Comment(1)', 'Comments(%)'); ?></li>
<li class="trackbacks"><a href="<?php the_permalink(); ?>#trackbacks" title="trackback/pingback" class="trackacklink"><?php echo (trackpings('count') > 1 ? 'Trackbacks('.trackpings('count').')' : 'Trackback('.trackpings('count').')'); ?></a></li>
<?php } else { ?>
<li class="comments"><?php comments_popup_link('Trackback / Comment (0)', 'Trackback / Comment (1)', 'Trackbacks / Comments (%)'); ?></li>
<?php } ?>
<li class="categories"><?php the_category(', '); ?></li>
<?php //edit_post_link('編集','<li class="edit">','</li>'."\n"); ?>
</ul>
</div>
</div>
</div>
<?php
$postcount++; endwhile;
?>
<div class="navigation">
<div class="divider">
<?php
if (function_exists('paginate_links')) { 
	global $wp_rewrite;
	$paginate_base = get_pagenum_link(1);
	if (strpos($paginate_base, '?') || ! $wp_rewrite->using_permalinks()) {
		$paginate_format = '';
		$paginate_base = add_query_arg('paged', '%#%');
	} else {
		$paginate_format = (substr($paginate_base, -1 ,1) == '/' ? '' : '/') .
		user_trailingslashit('page/%#%/', 'paged');;
		$paginate_base .= '%_%';
	}
	echo paginate_links( array(
		'base' => $paginate_base ,
		'format' => $paginate_format ,
		'total' => $wp_query->max_num_pages ,
		'mid_size' => 5 ,
		'current' => ($paged ? $paged : 1)
		));
} else {
?>
<div class="left"><?php next_posts_link('&laquo; 前へ'); ?></div>
<div class="right"><?php previous_posts_link('次へ &raquo;'); ?></div>
<?php } ?>
</div>
</div>
</div>
</div>
<?php
get_sidebar();
get_footer();
?>
