<?php
/*
Template Name: Popular Posts
*/
if (!have_posts()) {
	// 404 Error
	include (TEMPLATEPATH . '/404.php');
	return;
}
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
?>
<h3>人気記事</h3>
<?php if (function_exists('akpc_most_popular')) { ?>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>全期間</strong></p>
<ul><?php akpc_most_popular(); ?></ul>
</div>
<?php } ?>
<?php if (function_exists('akpc_most_popular_in_days')) { ?>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>過去30日間</strong></p>
<ul><?php akpc_most_popular_in_days(10,"<li>","</li>",30); ?></ul>
</div>
<?php } ?>
<?php if (function_exists('SBM_popular_entry_list')) { ?>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>被ブックマーク数</strong></p>
<ul><?php SBM_popular_entry_list(10); ?></ul>
</div>
<?php } ?>
<div style="margin-bottom:1em;" class="clear"></div>
<?php// if (function_exists('akpc_most_popular')) { ?>
<?php if (false) { ?>
<h3>カテゴリごとの人気記事</h3>
<p><strong>ジャンル別</strong></p>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>映画</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",22); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>マンガ</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",18); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>ゲーム</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",23); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>小説</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",28); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>TVドラマ</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",107); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>音楽</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",17); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<p><strong style="padding-left:0.5em;">コンピュータ</strong></p>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>オススメサイト</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",27); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>PC</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",15); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>Wordpress</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",102); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<p><strong style="padding-left:0.5em;">gadget</strong></p>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>AV機器</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",82); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>玩具</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",29); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>車</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",26); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<p><strong style="padding-left:0.5em;">嗜好品</strong></p>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>B級グルメ</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",20); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>酒</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",30); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>食</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",88); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<div style="float:left;width:93%;" class="popular-post">
<p><strong>その他</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",8); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>比較的新作</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",7); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>棚から一掴み</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",16); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<p><strong>雑文</strong></p>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>雑想ノート</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",24); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>身辺雑記</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",4); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>旅</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",21); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>サイト運営</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",71); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>プログラミング</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",108); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>blog関連</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",12); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<div style="float:left;width:30.5%;" class="popular-post">
<p><strong>イラスト</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",13); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>ニュース</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",10); ?></ul>
</div>
<div style="float:left;width:30.5%;padding-left:1em;border-left:1px solid #d6d3d3;" class="popular-post">
<p><strong>スポーツ</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",37); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<div style="float:left;width:93%;" class="popular-post">
<p><strong>お知らせ</strong></p>
<ul><?php akpc_most_popular_in_cat(5,"<li>","</li>",87); ?></ul>
</div>
<div style="margin-bottom:1em;" class="clear"></div>
<?php } ?>
</div>
</div>
<div class="postmeta">
<dl class="controls">
<dt>投稿者 / 日付:</dt>
<dd>
<p class="author"><?php the_author_meta('nickname'); ?></p>
<p>/</p>
<p class="post_time"><?php the_time('Y年n月j日 l') ?></p>
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
