<?php
if (!have_posts()) {
	// 404 Error
	include (TEMPLATEPATH . '/404.php');
	return;
}
global $page, $numpages;
get_header();
?>
<div class="divider"></div>
<div id="content">
<div class="contentbar">
<div id="primarycontent">
<?php
$postcount = 0;
while (have_posts()) : the_post();
?>
<?php if (function_exists('jrelated_referer')) jrelated_referer('','<div class="related-post">','</div>'); ?>
<div id="post-<?php the_author_meta('ID'); ?>" <?php post_class(); ?>>
<div class="contenttitle">
<div class="contenttitleinfo">
<div class="author<?php the_author_meta('ID'); ?>">
<h2><a href="<?php
the_permalink();
if ($page > 1) echo "$page/";
?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php
the_title('<!-- zenback_title_begin -->','<!-- zenback_title_end -->');
if ($numpages > 1) echo "&nbsp; ( $page / $numpages )";
?></a></h2>
</div>
<p><small class="details">Posted by <?php the_author_meta('nickname'); ?> at <?php the_time('Y年n月j日 l') ?></small></p>
</div>
</div>
<div class="contentarea">  
<div class="entry">
<?php echo apply_filters('the_content', get_the_content('... 続きを読む')); ?>
</div>
</div>
<div class="postmeta">
<dl class="controls">
<dt>投稿者 / 日付:</dt>
<dd>
<p class="author"><?php the_author_meta('nickname'); ?></p>
<p>/</p>
<p class="post_time">
<?php //the_time('Y年n月j日 l G:i:s'); ?>
<?php
$u_time = get_the_time('Y年n月j日 l');
echo $u_time;
$u_modified_time = get_the_modified_time('Y年n月j日 l');
if ($u_modified_time != $u_time) echo " ( 最終更新日: $u_modified_time )";
?>
</p>
<?php edit_post_link('編集','<p class="edit">','</p>'); ?>
</dd>
</dl>
<?php if ( function_exists('SBM_count_get')) { ?>
<dl class="controls">
<dt>SBM:</dt>
<dd>
<p class="sbm">
<?php SBM_count_get(); ?>
<a href="http://atode.cc/" onclick='javascript:(function(){var s=document.createElement("scr"+"ipt");s.charset="UTF-8";s.language="javascr"+"ipt";s.type="text/javascr"+"ipt";var d=new Date;s.src="http://atode.cc/bjs.php?d="+d.getMilliseconds();document.body.appendChild(s)})();return false;' style="margin-left:2em;"><img src="http://atode.cc/img/iconnja.gif" alt="あとで読む" border="0" align="middle" width="66" height="20" /></a>
</p>
</dd>
</dl>
<?php } ?>
</div>
</div>
<?php if (class_exists('HateBuAnywhereController') && (function_exists('is_javascript') && is_javascript())) { ?>
<div class="divider"><h2>このエントリーのはてなブックマーク</h2></div>
<div id="hatena_bookmark_anywhere"></div>
<br clear="all" />
<?php } ?>
<?php
$postcount ++; endwhile;
?>
</div>
</div>
</div>
<?php get_sidebar(); ?>
<?php
$link_pages = wp_link_pages(array('before' => '', 'after' => '', 'next_or_number' => 'number', 'echo' => '0'));
if ($link_pages != '') {
	echo '<div class="navigation"><div class="divider">';
	if (preg_match_all("/(<a [^>]*>[\d]+<\/a>|[\d]+)/i", $link_pages, $matched, PREG_SET_ORDER)) {
		foreach ($matched as $link) {
			if (preg_match("/<a ([^>]*)>([\d]+)<\/a>/i", $link[0], $link_matched))
				echo "<a class=\"page-numbers\" {$link_matched[1]}>{$link_matched[2]}</a>\n";
			else
				echo "<span class=\"page-numbers current\">{$link[0]}</span>\n";
			unset($link_matched);
		}
		unset($link);
	}
	unset($matched);
	echo '</div></div>';
}
?>
<?php get_footer(); ?>
