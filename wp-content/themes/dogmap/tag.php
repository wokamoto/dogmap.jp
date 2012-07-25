<?php
get_header();
?>
<div class="divider">
<?php if (!function_exists('paginate_links')) { ?>
<div class="left" style="margin:0; text-align:left; width:8%"><?php next_posts_link('&laquo; 前へ') ?></div>
<?php } ?>
<div class="left" style="margin:0; text-align:center; width:80%"></div>
<?php if (!function_exists('paginate_links')) { ?>
<div class="right" style="margin:0; text-align:right; width:8%"><?php previous_posts_link('次へ &raquo;') ?></div>
<?php } ?>
</div>
<div id="content">
<div class="contentbar">
<?php
$postcount = 0;
while (have_posts()): the_post();
?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<div class="contenttitle">
<div class="contenttitleinfo">
<div class="author<?php the_author_ID(); ?>"><h2><a href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a></h2></div>
<p><small class="details">Posted by <?php the_author_posts_link(); ?> at <?php the_time('Y年n月j日 l'); ?></small></p>
</div>
</div>
<div class="contentarea">
<div class="entry">
<?php the_content('...続きを読む'); ?>
</div>
<div class="controls">
<ul>
<li class="permalink"><a href="<?php the_permalink(); ?>">Permalink</a></li>
<?php if ( function_exists('comments_only_popup_link')) { ?>
<li class="comments"><?php comments_only_popup_link('Comment(0)', 'Comment(1)', 'Comments(%)'); ?></li>
<li class="trackbacks"><a href="<?php the_permalink(); ?>#trackback" title="trackback/pingback" class="trackacklink">Trackbacks(<?php echo trackpings('count'); ?>)</a></li>
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
		echo paginate_links( array(
		'base' => trailingslashit(get_pagenum_link(1)) . '%_%'
		,'format' => user_trailingslashit('page/%#%/', 'paged')
		,'total' => $wp_query->max_num_pages
		,'mid_size' => 3
		,'current' => ($paged ? $paged : 1)
		));
} else { ?>
<div class="left"><?php next_posts_link('&laquo; 前へ') ?></div>
<div class="right"><?php previous_posts_link('次へ &raquo;') ?></div>
<?php } ?>
</div>
</div>
</div>
</div>
<?php
	get_sidebar();
	get_footer();
?>
