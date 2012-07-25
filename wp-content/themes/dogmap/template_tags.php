<?php
/*
Template Name: Tag Cloud
*/
get_header();
?>
<div class="divider"></div>
<div id="content">
<div class="contentbar">
<div id="primarycontent">
<?php
$postcount = 0;
while (have_posts()) :
the_post();
?>
<div id="post-<?php the_author_meta('ID'); ?>" <?php post_class(); ?>>
<div class="contenttitle">
<div class="contenttitleinfo">
<div class="author<?php the_author_meta('ID'); ?>">
<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a></h2>
</div>
<p><small class="details">Posted by <?php the_author_meta('nickname'); ?> at <?php the_time('Y年n月j日 l') ?></small></p>
</div>
</div>
<div class="contentarea">  
<div class="entry">
<?php
the_content('Continue Reading &raquo;');
if(function_exists('st_tag_cloud')){
st_tag_cloud('smallest=0.7&largest=3.5&number=0&title=&unit=em&orderby=name&format=list&min_usage=3');
} elseif(function_exists('wp_tag_cloud')){
wp_tag_cloud('smallest=0.7&largest=3.5&unit=em&number=500');
//  wp_tag_cloud('smallest=0.7&largest=3.5&unit=em&number=200&orderby=COUNT&order=RAND');
} elseif(function_exists('UTW_ShowWeightedTagSetAlphabetical')){
UTW_ShowWeightedTagSetAlphabetical("coloredsizedtagcloud","",0) ;
}
?>
</div>
</div>
<div class="postmeta">
<dl class="controls">
<dt>投稿者 / 日付:</dt>
<dd>
<p class="author"><?php the_author_meta('nickname'); ?></p>
<p class="post_time">
<?php //the_time('Y年n月j日 l G:i:s') ?>
<?php
$u_time = get_the_time('Y年n月j日 l');
echo $u_time;
$u_modified_time = get_the_modified_time('Y年n月j日 l');
if ($u_modified_time != $u_time) echo " ( 最終更新日: $u_modified_time )";
?>
</p>
</p>
<?php edit_post_link('編集','<p class="edit">','</p>'); ?>
</dd>
</dl>
<?php //if ( function_exists('akpc_content_bargraph')) {akpc_content_bargraph();} ?>
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
$postcount ++;
endwhile;
?>
</div>
</div>
</div>
<?php get_sidebar(); ?>
<div class="divider"></div>
<?php get_footer(); ?>
