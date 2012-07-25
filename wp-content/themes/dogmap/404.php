<?php
//header("HTTP/1.1 404 Not Found", false, 404);
//header("Status: 404 Not Found", false, 404);
get_header();
$theme_images = 'http://static.dogmap.jp/images/icons/';
?>
<div class="divider"></div>
<div id="content">
<div class="contentbar">
<div class="contenttitle">
<div class="contenttitleinfo">
<div class="author1"><h2>Error 404 - File Not Found</h2></div>
</div>
</div>
<div class="contentarea" style="margin-top:1em">  
<div class="entry">
<img src="<?php echo $theme_images; ?>404.jpg" style="margin:1em" align="left" style="width:331px;height:475px;border:none" /><br />
指定された記事は無さそうです。
<div class="search" style="margin:1em 0;float:left;width:50%">
<form id="searchform3" name="searchform" method="get" action="<?php bloginfo('siteurl')?>/index.php">
記事検索：
<input type="text" name="s" id="s3" class="text" value="<?php echo wp_specialchars($s, 1); ?>" size="30" style="background:#FFFFFF none repeat scroll 0 0;" />
<input type="submit" class="submit" value="Go" />
</form>
</div>
<div style="float:left;width:50%">
タグ検索：&nbsp;<a href="<?php bloginfo('siteurl')?>/tags/">Archive of Tags</a>
</div>
<div class="search" style="float:left;width:50%">
<script type="text/javascript">// <![CDATA[
var GOOG_FIXURL_LANG = 'ja';
var GOOG_FIXURL_SITE = 'http://dogmap.jp/';
// ]]></script>
<script type="text/javascript" src="http://linkhelp.clients.google.com/tbproxy/lh/wm/fixurl.js"></script>
</div>
</div>
</div>
</div>
</div>
<?php get_sidebar(); ?>
<div class="divider"></div>
<?php get_footer(); ?>