<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts feed for livedoor homme.
 *
 * clicccar.com 用
 *
 * @package WordPress
 */
define(RELATED_POST_TITLE, '<h2 class="related_photos_title"><span>あわせて読みたい</span></h2>');

// 関連投稿取得
class related_post_tool {
	var $related = '';

	// <related> タグを出力する
	function the_related(){
		echo $this->related;
	}

	function for_livedoor_homme_feed($content) {
		$related = '';
		if (strpos($content, RELATED_POST_TITLE) !== FALSE) {
			$related = preg_replace(
				'/^.*('.preg_quote(RELATED_POST_TITLE,'/').'.*<ul[^>]*>(.*)<\/ul>.*$/ims' ,
				'$2', 
				$content
				);
			if (preg_match_all(
				'/<li[^>]*>.*?<a href=[\'"](http:\/\/[^\'"]*)[\'"][^>]*>([^<]*).*?<\/li>/i' ,
				$related ,
				$matches ,
				PREG_SET_ORDER
				)) {
				$related = '';
				$count = 0;
				foreach ($matches as $match) {
					$related .= "
		<ldnfeed:rel>
			<ldnfeed:rel_subject>{$match[2]}</ldnfeed:rel_subject>
			<ldnfeed:rel_link>{$match[1]}</ldnfeed:rel_link>
		</ldnfeed:rel>
";
				}
			} else {
				$related = '';
			}
			unset($matches);

			// フィードの contents 内から関連記事を消去
			$content = trim(preg_replace(
				'/^(.*)'.preg_quote(RELATED_POST_TITLE,'/').'.*$/ims' ,
				'$1', 
				$content
				));
		}
		$this->related = $related;

		return $content;
	}
}
$related_post = new related_post_tool();
add_filter('the_excerpt_rss',  array(&$related_post, 'for_livedoor_homme_feed'), 9999);
add_filter('the_content_feed', array(&$related_post, 'for_livedoor_homme_feed'), 9999);

// 画像用タグを取得
function ldnfeed_image($id) {
	$attachments = get_children(array(
		'post_parent' => $id ,
		'post_type' => 'attachment' ,
		'post_mime_type' => 'image' ,
		'orderby' => 'menu_order' ,
		));

	$images = '';
	foreach ($attachments as $attachment) {
		$image_src = wp_get_attachment_image($attachment->ID);
		if (preg_match('/src=[\'"]([^\'"]*)[\'"].*title=[\'"]([^\'"]*)[\'"]/i', $image_src, $matches)) {
			$images .= "
		<ldnfeed:image>
			<ldnfeed:image_link>{$matches[1]}</ldnfeed:image_link>
			<ldnfeed:image_subject>{$matches[2]}</ldnfeed:image_subject>
		</ldnfeed:image>
";
		}
		unset($matches);
	}
	unset($attachments);

	return $images;
}


header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0" xmlns:ldnfeed="http://news.livedoor.com/ldnfeed/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
	<language><?php echo get_option('rss_language'); ?></language>
	<title><?php bloginfo_rss('name'); wp_title_rss(); ?></title>
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0900', get_lastpostmodified(), false); ?></lastBuildDate>

	<?php while( have_posts()) : the_post(); ?>
	<item>
		<guid><?php the_permalink_rss() ?></guid>
		<title><?php the_title_rss() ?></title>
		<link><?php the_permalink_rss() ?></link>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0900', get_post_time('Y-m-d H:i:s', false), false); ?></pubDate>
		<description><![CDATA[<?php the_content_feed('rss2') ?>]]></description>
		<?php the_category_rss() ?>
		<ldnfeed:status>add</ldnfeed:status>
<?php echo ldnfeed_image($post->ID);	// <ldnfeed:image> タグを出力 ?>
<?php $related_post->the_related();		// <ldnfeed:rel> タグを出力 ?>
	</item>
	<?php endwhile; ?>
</channel>
</rss>
