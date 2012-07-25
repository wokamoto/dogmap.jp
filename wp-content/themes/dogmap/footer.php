<?php
global $current_user;

$is_login = !((function_exists('is_user_logged_in') && !is_user_logged_in()) || empty($current_user->ID));
$site_url = trailingslashit(get_bloginfo('wpurl'));
$theme_images = 'http://static.dogmap.jp/images/icons/';
?>
<div id="footer" class="clear">
<div class="left">
<p>Copyright &copy; 2005 - <?php print (date('Y')); ?> <?php bloginfo('name'); ?>. All Rights Reserved.</p>
<p>
Powered by&nbsp;<a href="http://www.wordpress.org/" title="WordPress <?php bloginfo('version'); ?>">WordPress&nbsp;<?php bloginfo('version'); ?></a>&nbsp;on&nbsp;<a href="http://www.megumi-server.net/" title="め組サーバ｜WordPress サーバ｜ホスティング">め組 VPS</a>. &nbsp;
CODE IS POETRY &nbsp;
<?php
if ( !$is_login && !(is_404() || is_search()) ) {
	echo '<script type="text/javascript" src="http://script.trackfeed.com/usr/f053e1a8bd.js"></script>';
}
?>
</p>
</div>
<div class="right">
<p><a href="http://validator.w3.org/check?uri=referer"><img src="<?php echo $theme_images; ?>valid-xhtml10.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" style="border:none;" /></a></p>
</div>
<div class="right">
<p><a href="<?php echo $site_url; ?>feed/">Entries (RSS)</a> and <a href="<?php bloginfo('comments_rss2_url'); ?>">Comments (RSS)</a>.</p>
<p><?php echo get_num_queries(); ?> queries. <?php timer_stop(1); ?> seconds.
<strong>Object Cache</strong><?php
global $wp_object_cache;

if (!empty($wp_object_cache->cache_hits)) {
	echo '   Hits:  ' . $wp_object_cache->cache_hits;
} elseif (!empty($wp_object_cache->cold_cache_hits) && !empty($wp_object_cache->warm_cache_hits)) {
	echo '   Cold:  ' . $wp_object_cache->cold_cache_hits;
	echo ' / Warm: ' . $wp_object_cache->warm_cache_hits;
}
if (!empty($wp_object_cache->cache_misses)) {
	echo ' /';
	echo ' Misses: ' . $wp_object_cache->cache_misses;
}
?></p>
</div>
<div class="clear"></div>
</div>
</div>
<!--[if lt IE 7]>
<script type="text/javascript" src="http://dogmap.jp/wp-content/themes/dogmap/js/jquery.pngfix.min.js"></script>
<script type="text/javascript"> //<![CDATA[
jQuery(function(){jQuery(".pngfix,#search,.divider").pngfix();});
//]]> </script>
<![endif]-->
<?php
wp_footer();

$out = '';

// trackword
if ( !(is_404() || is_search()) ) {
	$url = get_bloginfo('url');
	if (! preg_match('|^(https?://[^/]*)|', $url, $host))
		$host[1] = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://' . $_SERVER['SERVER_NAME'];
	$url = preg_replace( '/\?[^s].*$/i', '', $host[1] . $_SERVER['REQUEST_URI']);
	unset($host);
	$trackword_js = 'http://az.trackword.net/show.phtml?r=dogmap&url=' . urlencode($url);
	$out .= '<script type="text/javascript" src="' . $trackword_js . '"></script>' . "\n";
}

// google+
if ( is_singular() ) {
	$out .= '<script type="text/javascript" src="http://apis.google.com/js/plusone.js">{lang: \'ja\'}</script>' . "\n";
}

echo trim(preg_replace(array('/[\r\n]/', '/[ \t]+/'), array('', ' '), $out));
?>
</body></html>
<!-- Memory used - <?php echo round(memory_get_peak_usage() / 1024 / 1024, 2); ?> MB <?php echo date('Y-m-d H:i:sP'); ?> -->