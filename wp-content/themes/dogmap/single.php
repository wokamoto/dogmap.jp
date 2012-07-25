<?php
if (!have_posts()) {
 // 404 Error
 include (TEMPLATEPATH . '/404.php');
 return;
}
if (!isset($site_url))
	$site_url = get_option('siteurl');
if (!isset($template_url))
 	$template_url = $site_url.'/wp-content/themes/dogmap/';

$theme_images = 'http://static.dogmap.jp/theme_icons/';

global $wph;
if (!isset($wph) && class_exists('WpHatena')) {
	$wph = & new WpHatena();
}

get_header();
?>
<div class="divider">
<div class="left"><?php previous_post_link('&laquo; %link') ?></div>
<div class="right"><?php next_post_link('%link &raquo;') ?></div>
</div>
<div id="content">
<div class="contentbar">
<div id="primarycontent">
<?php
$postcount = 0;
$js_flag = (function_exists('is_javascript') && is_javascript());
while (have_posts()) : the_post();
?>
<?php if (function_exists('jrelated_referer')) jrelated_referer('','<div class="related-post">','</div>'); ?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<div class="contenttitle">
<div class="contenttitleinfo">
<div class="author<?php the_author_meta('ID'); ?>">
<h2><a href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title('<!-- zenback_title_begin -->','<!-- zenback_title_end -->'); ?></a></h2>
</div>
<p><small class="details">Posted by <?php the_author_posts_link(); ?> at <?php the_time('Y年n月j日 l'); ?></small></p>
</div>
</div>
<div class="contentarea">  
<div class="entry">
<?php the_content('...続きを読む'); ?>
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
</div>
</div>
<div class="postmeta">
<dl class="controls">
<dt>投稿者 / 日付:</dt>
<dd>
<p class="author"><?php the_author_posts_link(); ?></p>
<p><a href="<?php echo $site_url; ?>/author/<?php the_author_meta('login'); ?>/feed/" title="feed"><img src="<?php echo $theme_images; ?>icon-feed.png" alt="feed" style="vertical-align:middle;border:0;margin-right:0.25em" /></a></p>
<p>/</p>
<p class="post_time">
<?php // the_time('Y年n月j日 l G:i:s'); ?>
<?php
$u_time = get_the_time('Y年n月j日 l');
echo $u_time;
$u_modified_time = get_the_modified_time('Y年n月j日 l');
if ($u_modified_time != $u_time) echo " ( 最終更新日: $u_modified_time )";
?>
</p>
<?php edit_post_link('編集','<p class="edit">','</p>'); ?>
</dd>
<?php
 if (!is_attachment()) {
  if (class_exists('GeoMashup')) {
   $GeoMashupLink = GeoMashup::post_link('...地図で場所を確認する', false);
   if ($GeoMashupLink != '') {
?>
<dt>場所:</dt>
<dd><p class="location"><?php echo $GeoMashupLink; ?></p></dd>
<?php
   }
  }
?>
<dt>カテゴリー:</dt>
<dd><p class="categories"><?php the_category(', '); ?></p></dd>
<dt>タグ:</dt>
<dd>
<p class="tags"><?php the_tags('', ', ', '<br />'); ?></p>
</dd>
</dl>
<?php if ( function_exists('akpc_content_bargraph')) akpc_content_bargraph(); ?>
<dl class="controls">
<dt>シェア:</dt>
<dd style="height: 21px;">
<p>
<span style="display:inline-block; float:left;">
<?php
if(isset($wph)) {
//	$wph->addhatena();	//はてブ　管理画面から表示タイプを選択可能
//	$wph->addHatenaCountTxt();	//はてブのブックマーク数をテキスト表示
//	$wph->addHatenaCount();	//はてブのブックマーク数を画像で表示
//	$wph->adddelicious();
//	$wph->addLivedoor();
//	$wph->addYahoo();
//	$wph->addFC2();
//	$wph->addNifty();
//	$wph->addPOOKMARK();
//	$wph->addBuzzurl();
//	$wph->addChoix();
//	$wph->addnewsing();
//	$wph->addInstapaper();
//	$wph->addReadItLater();
//	$wph->addEvernoteClip();
//	$wph->addFacebookShare();	//Facebook シェアボタン
	$wph->addFacebook();	//Facebook いいね！ボタン　管理画面から表示タイプ選択可能
//	echo '<div style="width;95px;display:inline;overflow:hidden;">';
	$wph->addTweetBtn();	//Tweet ボタン　管理画面から表示タイプ選択可能
//	echo '</div>';
//	$wph->addMixicheck();	//mixi チェック　※ ディベロッパーセンターに登録が必要、分かる人向け
} else if ( function_exists('tweet_this_link')) {
	tweet_this_link();
 } else {
?>
<a href="http://twitter.com/?status=<?php echo urlencode(trim(wp_title('',false) . ' : ' . get_bloginfo('name') . ' - ' . get_permalink()));?>&amp;inb=tw">
<img src="http://twitter.com/favicon.ico" title="Tweet this" alt="Tweet this" />&nbsp;つぶやく
</a>
<?php
 }
?></span>
<span style="display: inline-block; float: left; width: 70px; margin-left:-45px;"><g:plusone href="<?php the_permalink() ?>"  size="medium" count="true"></g:plusone></span>
<script type="text/javascript" src="http://growbutton.com/javascripts/button.js?apikey=9941c2b0-cc0d-012e-4187-34ef8b111ea0&shape=rectangle"></script>
</p>
</dd>
<?php if ($js_flag && function_exists('SBM_count_get')) { ?>
<dt>SBM:</dt>
<dd>
<p class="sbm">
<?php SBM_count_get(); ?>
<?php if (false) : ?>
 <a href="http://atode.cc/" onclick="javascript:(function(){var s=document.createElement('scr'+'ipt');s.charset='UTF-8';s.language='javascr'+'ipt';s.type='text/javascr'+'ipt';var d=new Date;s.src='http://atode.cc/bjs.php?d='+d.getMilliseconds();document.body.appendChild(s)})();return false;" style="margin-left:2em;"><img src="http://atode.cc/img/iconnja.gif" alt="あとで読む" border="0" align="middle" width="66" height="20" /></a>
<?php endif; ?>
</p>
</dd>
<?php } ?>
<?php } ?>
<dt>コメント&nbsp;/&nbsp;TB:</dt>
<dd>
<?php if (function_exists('comments_only_number')) { ?>
<p class="comments"><a href="#comments"><?php comments_only_number('Comment(0)', 'Comment(1)', 'Comments(%)'); ?></a></p>
<p><?php post_comments_feed_link('<img src="'.$theme_images.'icon-feed.png" alt="feed" style="vertical-align:middle;border:0;margin-right:0.25em" />'); ?></p>
<p>/</p>
<p class="trackbacks"><a href="#trackbacks"><?php if (trackpings('count') > 1) {echo 'Trackbacks('.trackpings('count').')';} else {echo 'Trackback('.trackpings('count').')';} ?></a></p>
<?php } else { ?>
<p class="comments"><a href="#comments"><?php comments_number('Trackback / Comment (0)', 'Trackback / Comment (1)', 'Trackbacks / Comments (%)'); ?></a></p>
<p><?php comments_rss_link('<img src="'.$theme_images.'icon-feed.png" alt="feed" style="vertical-align:middle;border:0;margin-right:0.25em" />'); ?></p>
<?php } ?>
<?php if ($js_flag) { ?>
<!--
<p>/</p>
<p><script type="text/javascript" src="http://parts.technorati.jp/linkcount" charset="UTF-8"></script> <a class="tr-linkcount" href="http://technorati.jp/search/<?php the_permalink(); ?>" title="テクノラティでこの記事にリンクしている記事を見る">この記事へのリンク</a></p>
-->
<?php } ?>
</dd>
<?php if ( pings_open() ) { ?>
<dt>トラックバック <abbr title="Uniform Resource Identifier">URI</abbr>:</dt>
<dd><input type="text" id="trackback-uri" name="trackback-uri" value="<?php trackback_url(); ?>" readonly="readonly" onfocus="this.select()" /></dd>
<?php } ?>
</dl>
</div>

<?php if (!is_attachment()) { ?>
<?php
if (isset($wpjr)) {
	$transient_key = md5( 'wpjr-'.get_the_ID() );
	$related = get_transient( $transient_key );
	if ( is_user_logged_in() || false === $related || empty($related) ) {
		$related = $wpjr->getRelated(get_the_ID());
		set_transient( $transient_key, $related, 60*60*24*7 );
	}
	echo $related;
} elseif (function_exists('related_posts')) {
	related_posts();
} elseif(function_exists('similar_posts')) {
	echo '<div class="divider"><h2>関連記事</h2></div>'."\n";
	echo '<div class="related-post">'."\n";
	similar_posts();
	echo '<br clear="all" />'."\n";
	echo '</div>'."\n";
} elseif (function_exists('st_related_posts')) {
	echo '<div class="divider"><h2>関連記事</h2></div>'."\n";
	echo '<div class="related-post">'."\n";
	st_related_posts('number=5&orderby=post_date&title=&xformat=<a href="%permalink%" title="%title% (%date%)">%title%</a>');
	echo '<br clear="all" />'."\n";
	echo '</div>'."\n";
}
?>
<?php if (function_exists('the_kyodeki')) { ?>
<div class="divider"><h2>本日の人気記事</h2></div>
<div class="related-post">
<?php the_kyodeki(5); ?>
<br clear="all" />
</div>
<?php } ?>
<?php } ?>

<?php comments_template(); ?>
<?php if ($js_flag && class_exists('HateBuAnywhereController') && !is_attachment()) { ?>
<div class="divider" style="margin-top:1.5em;"><h2>このエントリーのはてなブックマーク</h2></div>
<div id="hatena_bookmark_anywhere"></div>
<?php } ?>
</div>
<?php
 $postcount ++;
endwhile;
?>
</div>
</div>
</div>
<?php get_sidebar(); ?>
<div class="divider">
<div class="left"><?php previous_post_link('&laquo; %link') ?></div>
<div class="right"><?php next_post_link('%link &raquo;') ?></div>
</div>
<?php get_footer(); ?>
