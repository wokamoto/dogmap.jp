<?php
if (!isset($site_url))
	$site_url = get_option('siteurl');
if (!isset($template_url))
	$template_url = $site_url.'/wp-content/themes/dogmap/';
if (!isset($theme_images))
	$theme_images = 'http://static.dogmap.jp/images/icons/';
$js_flag = (!function_exists('is_javascript') || (function_exists('is_javascript') && is_javascript()));
$show_comments = true;
$show_feeds = false;
?>
<div id="r_sidebar">
<div class="sidebar">
<ul id="r_sidebarwidgeted">
<?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar()) { ?>
<li>
<h3>Information</h3>
<ul>
<li>
<p>複数の投稿者が、映画・マンガ・小説・音楽・ゲーム等の感想を新旧問わずに書きなぐっています。詳細は<a href="/about/">こちら</a></p>
<p style="margin-top:1em;">コメント・ＴＢは大歓迎です。お気軽にどうぞ。ただし、アダルト・商業系・投稿記事と関係のないものは断り無く削除させていただくことがあります。</p>
</li>
<!--
<li>
<p>寄付は、いつでも受け付けております。
「プラグイン or エントリが役に立ったぜひ寄付をしたい！」という奇特な方はぜひ、<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=9S8AJCY7XB8F4&amp;lc=JP&amp;item_name=WordPress%20Plugins&amp;item_number=wp%2dplugins&amp;currency_code=JPY&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" title="PayPalでスピーディに寄付をしてください - PayPal">PayPal 経由で寄付をしてください</a>。</p>
</li>
-->
<li>
<p><a href="http://www.amazon.co.jp/WordPress-%E9%AB%98%E9%80%9F%E5%8C%96%EF%BC%86%E3%82%B9%E3%83%9E%E3%83%BC%E3%83%88%E9%81%8B%E7%94%A8%E5%BF%85%E6%90%BA%E3%82%AC%E3%82%A4%E3%83%89-%E3%81%93%E3%82%82%E3%82%8A%E3%81%BE%E3%81%95%E3%81%82%E3%81%8D/dp/4844362526%3FSubscriptionId%3DAKIAIU4DT5KBIGKR6XJQ%26tag%3Ddogmatismandp-22%26linkCode%3Dxm2%26camp%3D2025%26creative%3D165953%26creativeASIN%3D4844362526" title="Amazon で商品の詳細を確認する"><img src="http://ecx.images-amazon.com/images/I/51GZ0I1jMnL._SL75_.jpg" alt="WordPress 高速化＆スマート運用必携ガイド" align="left" class="alignleft size-thumbnail" style="margin-right:.2em;"/></a>
<a href="http://blog.gaspanik.com/" title="gaspanik weblog">こもりさん</a>と一緒に本書きました。 2012.3.16 発売予定。<br>
<a href="http://www.amazon.co.jp/WordPress-%E9%AB%98%E9%80%9F%E5%8C%96%EF%BC%86%E3%82%B9%E3%83%9E%E3%83%BC%E3%83%88%E9%81%8B%E7%94%A8%E5%BF%85%E6%90%BA%E3%82%AC%E3%82%A4%E3%83%89-%E3%81%93%E3%82%82%E3%82%8A%E3%81%BE%E3%81%95%E3%81%82%E3%81%8D/dp/4844362526%3FSubscriptionId%3DAKIAIU4DT5KBIGKR6XJQ%26tag%3Ddogmatismandp-22%26linkCode%3Dxm2%26camp%3D2025%26creative%3D165953%26creativeASIN%3D4844362526" title="Amazon で商品の詳細を確認する">WordPress 高速化＆スマート運用必携ガイド</a><br clear="all" /></p>
</li>
<li>
<p>
サイト管理者への連絡は以下のアドレスにお願いします。<br />
<img src="<?php echo $theme_images; ?>gmail_banner_dogmap.png" alt="Gmail Banner" width="149" height="21" />
</p>
</li>
<?php if (false) { ?>
<li>
<p><a href="http://technorati.jp/claim/3r4whq7iw7" rel="me">テクノラティプロフィール</a></p>
<p><a href="http://www.technorati.jp/faves?sub=addfavbtn&amp;add=http://dogmap.jp"><img src="http://static.technorati.jp/pix/fave/tech-fav-1.png" alt="テクノラティお気に入りに追加する" style="border:none;" width="145px" height="25px" /></a></p>
</li>
<li>
<a href="http://sites.google.com/site/quake20110311jp/bokin"><img src="http://static.dogmap.jp/2011/03/fund_raising_210_105.png" width="210px" height="105px" alt="東北地方太平洋沖地震 義援金" /></a>
</li>
<li>
<p><a href="<?php echo $site_url; ?>/feed/" rel="alternate" type="application/rss+xml"><img src="<?php echo $theme_images; ?>icon-feed.png" alt="rss icon" style="vertical-align:middle;border:0;margin-right:0.25em" width="16px" height="16px" />RSSリーダーで購読する</a></p>
</li>
<?php } ?>
<li>
<a href="http://wordvolcano.info"><img src="http://wordvolcano.info/wp/wp-content/themes/wv/images/volanobanner/banner125x125.jpg" width="113" height="113" alt="" /></a>
<a href="http://niigm.com/about" title="新潟グラム" rel="lightbox"><img src="http://dogmap.jp/wp-content/uploads/2011/07/bnr09.gif" alt="新潟グラム" title="新潟グラム" width="113" height="113" class="alignnone size-full wp-image-2529" border="0" /></a>
<?php if (false) { ?>
<a href="http://ja.wordpress.org/2011/02/19/wordpress-japan-character/" title="WordPress | 日本語 &laquo; WordPress 日本公式キャラクターが登場"><img src="http://static.dogmap.jp/2011/02/chara_wp_mark-158x160.png" class="attachment-80x60" alt="WordPress 日本公式キャラクター" title="WordPress 日本公式キャラクター" height="60" width="59"></a>
<?php } ?>
</li>
<?php if (false) { ?>
<li>
<a href="http://2011.wordcampfukuoka.com/"><img src="http://2011.wordcampfukuoka.com/wp-content/themes/wc-fukuoka11/images/bn/bn234x60.gif" alt="WordCamp Fukuoka 2011" title="WordCamp Fukuoka 2011" width="234" height="60" /></a>
</li>
<li>
<a href="http://2011.tokyo.wordcamp.org/" title="WordCamp Tokyo 2011 | 2011年11月27日(日) 品川シーサイド 楽天タワー2号館"><img src="http://dogmap.jp/wp-content/uploads/2011/11/113x113.png" alt="WordCamp Tokyo 2011 | 2011年11月27日(日) 品川シーサイド 楽天タワー2号館" title="WordCamp Tokyo 2011 | 2011年11月27日(日) 品川シーサイド 楽天タワー2号館" width="113" height="113" class="alignnone size-full wp-image-2633" border="0" /></a>
<a href="http://yokohama2010.wordcamp.jp/" title="WordCamp Yokohama"><img src="http://dogmap.jp/wp-content/uploads/2010/04/180_150_banner.jpg" alt="WordCamp Yokohama" title="WordCamp Yokohama" width="180" height="150" class="size-full" border="0" /></a>
<a href="http://kyoto.wordcamp.org/" title="WordCamp Kyoto"><img width="180" height="150" class="size-full" title="WordCamp Kyoto 2009" alt="WordCamp Kyoto 2009" src="http://static.dogmap.jp/imgs/wordcamp_logo.png"/></a>
<div style="margin:0 auto;width:200px;" >
<iframe src="http://interFORest.org/banners/foxkeh/0d2255411b8fbef9281819a3db6d121e447b01e7" width="200" height="200" style="margin:0;border:none;overflow:hidden;"></iframe>
</div>
</li>
<li>
<p><a href="http://getfirefox.jp/"><img src="http://getfirefox.jp/banners/88x31_2_orange.png" alt="Mozilla Firefox ブラウザ無料ダウンロード" title="Mozilla Firefox ブラウザ無料ダウンロード" style="border:none;" width="88px" height="31px" /></a></p>
</li>
<?php } ?>
</ul>
</li>
<?php if ($show_comments && is_home() && (function_exists('blc_latest_comments') || function_exists('c2c_get_recently_commented'))) { ?>
<li>
<h3>Comments</h3>
<ul>
<?php
if (function_exists('blc_latest_comments')) blc_latest_comments();
elseif (function_exists('blc_latest_comments')) c2c_get_recently_commented(5, "<li>%comments_URL%<br />%last_comment_date%<br />%comments_fancy%</li>", '', 'DESC', 0, 'n/j', '', 'post', 'publish', false);
?>
</ul>
</li>
<?php } ?>
<?php if (function_exists('akpc_most_popular') && (is_home() || is_category() || (is_archive() && is_month()))) { ?>
<li>
<h3>Popular Posts</h3>
<ul>
<?php
if (is_home()) akpc_most_popular();
elseif (is_category()) akpc_most_popular_in_cat();
elseif (is_archive() && is_month()) akpc_most_popular_in_month();
?>
</ul>
</li>
<?php } elseif (function_exists('c2c_get_recent_posts') && !is_home()) { ?>
<li>
<h3>Recentries</h3>
<ul><?php c2c_get_recent_posts(10,"<li>%post_URL%　%post_date%</li>",'','date','DESC',0,'n/j'); ?></ul>
</li>
<?php } ?>
<li>
<h3>Categories</h3>
<ul id="categories-list">
<?php wp_list_categories('orderby=order&optioncount=1&hierarchical=1&title_li=&show_count=1'); ?>
</ul>
</li>
<?php if (is_home()) { ?>
<li>
<h3>Authors</h3>
<ul>
<li><a href="http://dogmap.jp/author/ash/feed/"><img src="<?php echo $theme_images ?>icon-feed.png" alt="rss icon" style="vertical-align:middle;border:0;margin-right:0.25em" width="16px" height="16px" /></a> <a href="http://dogmap.jp/author/ash/" title="Ash による投稿">Ash</a></li>
<li><a href="http://dogmap.jp/author/wokamoto/feed/"><img src="<?php echo $theme_images; ?>icon-feed.png" alt="rss icon" style="vertical-align:middle;border:0;margin-right:0.25em" width="16px" height="16px" /></a> <a href="http://dogmap.jp/author/wokamoto/" title="をかもと による投稿">をかもと</a></li>
<li><a href="http://dogmap.jp/author/mti/feed/"><img src="<?php echo $theme_images; ?>icon-feed.png" alt="rss icon" style="vertical-align:middle;border:0;margin-right:0.25em" width="16px" height="16px" /></a> <a href="http://dogmap.jp/author/mti/" title="もとい による投稿">もとい</a></li>
<li><a href="http://dogmap.jp/author/tomo_ring/feed/"><img src="<?php echo $theme_images; ?>icon-feed.png" alt="rss icon" style="vertical-align:middle;border:0;margin-right:0.25em" width="16px" height="16px" /></a> <a href="http://dogmap.jp/author/tomo_ring/" title="智凛 による投稿">智凛</a></li>
<li><a href="http://dogmap.jp/author/hhase/feed/"><img src="<?php echo $theme_images; ?>icon-feed.png" alt="rss icon" style="vertical-align:middle;border:0;margin-right:0.25em" width="16px" height="16px" /></a> <a href="http://dogmap.jp/author/hhase/" title="ハセ による投稿">ハセ</a></li>
</ul>
</li>
<?php } ?>
<?php if ($js_flag) { ?>
<li>
<h3>Facebook</h3>
<ul><li><script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script><fb:like-box href="http://www.facebook.com/dogmap.jp" width="230" show_faces="true" stream="false" header="false"></fb:like-box></li></ul>
</li>
<?php } ?>
<?php if (is_home()) { ?>
<?php if ($js_flag) { ?>
<li>
<h3>BlogLinks</h3>
<?php wp_list_bookmarks(array(
	'title_before' => '<h4>', 
	'title_after' => '</h4>',
	'category_before' => '<div id="%id" class="%class">',
	'category_after' => '</div>',
)); ?>
<?php if (false) { ?>
<ul>
<li style="width:95%">
<div id="blogpeople"><p style="clear:both"><img src="<?php echo $theme_images; ?>ajax-loader.gif" width="16" height="16" alt="Now Loading..." align="left" /><br />Now Loading...</p></div>
</li>
<li><p><a href="http://awasete.com/show.phtml?u=http%3A%2F%2Fdogmap.jp%2F"><img src="http://img.awasete.com/image.phtml?u=http%3A%2F%2Fdogmap.jp%2F" width="160" height="140" alt="あわせて読みたい" style="border:none" /></a></p></li>
</ul>
<?php } ?>
</li>
<?php if (false) { ?>
<li>
<h3>Clips</h3>
<?php
if (function_exists('ld_clips'))
	ld_clips('wokamoto_1973',5);
else
	echo "<div id=\"livedoor-clips\"><p><img src=\"{$theme_images}ajax-loader.gif\" width=\"16\" height=\"16\" alt=\"Now Loading...\" align=\"left\" /><br />Now Loading...</p></div>\n";
?>
</li>
<?php } ?>
<?php } ?>
<?php } ?>
<?php if (!(is_404() || is_search()) && $js_flag) { ?>
<li>
<h3>Trackwords</h3>
<ul>
<li>
<ul class="trackwords" style="list-style:none;width:95%;">
<li style="padding:0px;vertical-align:middle;border:none;"><a href="http://www.trackword.net/"><img style="border:0" src="http://www.trackword.net/img/minilogov.gif" alt="ブログSEO対策:track word" width="120" height="14" /></a></li>
<li style="border:none;display:inline;margin-right:.2em;"><a href="http://my.trackword.net/u/dogmap/1"></a></li>
<li style="border:none;display:inline;margin-right:.2em;"><a href="http://my.trackword.net/u/dogmap/2"></a></li>
<li style="border:none;display:inline;margin-right:.2em;"><a href="http://my.trackword.net/u/dogmap/3"></a></li>
<li style="border:none;display:inline;margin-right:.2em;"><a href="http://my.trackword.net/u/dogmap/4"></a></li>
<li style="border:none;display:inline;margin-right:.2em;"><a href="http://my.trackword.net/u/dogmap/5"></a></li>
</ul>
</li>
</ul>
</li>
<?php } ?>
<?php if (is_singular()) { ?>
<li>
<h3>ZenBack</h3>
<ul>
<li>
<!-- X:S ZenBackWidget --><script type="text/javascript">document.write(unescape("%3Cscript")+" src='http://widget.zenback.jp/?base_uri=http%3A//dogmap.jp/&nsid=93727819586114917%3A%3A93727833007913207&rand="+Math.ceil((new Date()*1)*Math.random())+"' type='text/javascript'"+unescape("%3E%3C/script%3E"));</script><!-- X:E ZenBackWidget -->
</li>
</ul>
</li>
<?php } ?>
<?php if ($show_feeds && is_home()) { ?>
<li>
<h3>Feeds</h3>
<ul>
<li>
<p><a href="http://feeds.feedburner.jp/dogmap"><img src="http://feeds.feedburner.jp/~fc/dogmap?bg=99FFFF&amp;fg=444444&amp;anim=0" height="26" width="88" style="border:0" alt="Feed 購読者数" /></a></p>
<p><a href="http://reader.livedoor.com/subscribe/http://feeds.feedburner.jp/dogmap"><img src="http://www.feedburner.jp/fb/i/livedoor.gif" align="middle" style="border:0" alt="Livedoorへ追加" /></a></p>
<p><a href="http://r.hatena.ne.jp/append/http://feeds.feedburner.jp/dogmap" title="独断と偏見の何でもレビュー"><img src="http://r.hatena.ne.jp/images/addto_w.gif" alt="はてなRSSへ追加" style="border:0" /></a></p>
<p><a href="http://www.bloglines.com/sub/http://feeds.feedburner.jp/dogmap" title="独断と偏見の何でもレビュー" type="application/rss+xml"><img src="http://www.bloglines.com/images/sub_modern11.gif" alt="購読する Bloglines" style="border:0" /></a></p>
<p><a href="http://www.feedburner.com"><img src="http://www.feedburner.com/fb/images/pub/powered_by_fb.gif" alt="Powered by FeedBurner" style="border:0" /></a></p>
</li>
<li><p><a href="http://feed-media.com/doumei/feed/http%3A%252F%252Fdogmap.jp%252Findex.rdf"><img src="http://feed-media.com/doumei/img/full-parts-90x22.gif" alt="全文配信同盟" border="0" width="90" height="22" /></a></p></li>
</ul>
</li>
<?php } ?>
<?php if (is_singular() && false) { ?>
<li>
<h3>Twitterでの反応<span id="topsy_counter"></span></h3>
<ul>
<li><div id="topsy_trackbacks"></div></li>
</ul>
</li>
<?php } ?>
<?php if (!is_404()) { ?>
<li>
<h3>Sponsored</h3>
<ul>
<li><a href="http://www.megumi-server.net/" title="め組サーバ｜WordPress サーバ｜ホスティング"><img src="<?php echo $theme_images; ?>megumi-vps.png" style="border:none;width:210px;height:73px;" /></a></li>
<li><p><a href="http://www.amazon.co.jp/?tag=dogmatismandp-22&amp;camp=295&amp;creative=1395&amp;linkCode=ur1&amp;adid=0XBT7372P1TYPQT0NRWN&amp;"><img src="<?php echo $theme_images; ?>remote-buy-jp8.gif" alt="Amazon.co.jp アソシエイト" style="border:none;width:109px;height:28px" /></a><br /></p></li>
</ul>
</li>
<?php } ?>
<?php if (is_home()) { ?>
<li>
<h3>Meta</h3>
<ul>
<?php wp_register(); ?>
<li><?php wp_loginout(); ?></li>
<?php if (false) { ?>
<li><a href="http://validator.w3.org/check/referer" title="This page validates as XHTML 1.0 Transitional">Valid <abbr title="eXtensible HyperText Markup Language">XHTML</abbr></a></li>
<li><a href="http://gmpg.org/xfn/"><abbr title="XHTML Friends Network">XFN</abbr></a></li>
<li><a href="http://wordpress.org/" title="Powered by WordPress, state-of-the-art semantic personal publishing platform.">WordPress</a></li>
<li><a href="http://ja.wordpress.org/" title="WordPress | 日本語">WordPress | 日本語</a></li>
<li><a href="http://wppluginsj.sourceforge.jp/" title="WordPress Plugins/JSeries">WordPress Plugins/JSeries</a></li>
<?php } ?>
<?php wp_meta(); ?>
</ul>
</li>
<?php } ?>
<?php if ($js_flag && false){ ?>
<li><p style="margin:0.5em;"><a href="http://www.seo-stats.com"><img src="http://www.seo-stats.com/services/seostats/seostats.php?s=cd49195baeff3a9e12ce&amp;bg=FFFFFF&amp;textcolor=000000&amp;bordercolor=999999&amp;indicatorcolor=5EAA5E&amp;ugo=0&amp;uho=0&amp;umo=0&amp;amo=0&amp;upr=1&amp;tuv=0&amp;tpv=1&amp;yuv=0&amp;ypv=1&amp;ttuv=0&amp;ttpv=0&amp;uonline=1&amp;f=46690" title="SEO Stats powered by SEO-Stats.com" alt="SEO Stats powered by SEO-Stats.com" border="0" /></a></p></li>
<?php } ?>
<?php } ?>
</ul>
</div>
</div>
