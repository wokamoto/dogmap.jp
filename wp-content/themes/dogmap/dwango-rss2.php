<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts feed for Dwango.
 *
 * @package WordPress
 */
class remove_feed_tags_for_dwango {
	var $count_tag = 0;
	var $related = '';

	function twice_tag_remove($matches){
		return (
			0 < $this->count_tag++
			? $matches[2]
			: $matches[0]
			);
	}

	function the_related(){
		echo $this->related;
	}

	function for_dwango_feed($content) {
		// 関連投稿取得
		$related = '';
		if (strpos($content, '<h4>Related posts</h4>') !== FALSE) {
			$related = preg_replace(
				'/^.*(<h4>Related posts<\/h4>).*<ul[^>]*>(.*)<\/ul>.*$/ims' ,
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
					if ($count++ < 3) {
						$related .= "\t\t<related><title>{$match[2]}</title><url>{$match[1]}</url></related>\n";
					}
				}
			} else {
				$related = '';
			}
			unset($matches);

			$content = trim(preg_replace(
				'/^(.*)<h4>Related posts<\/h4>.*$/ims' ,
				'$1', 
				$content
				));
		}
		$this->related = $related;

		// div を p に置換
		$content = preg_replace(
			array('/<div([^>]*)>/i', '/<\/div>/') ,
			array('<p$1>', '</p>') ,
			$content
			);

		// 2つ目以降の a を削除
		$this->count_tag = 0;
		$content = preg_replace_callback(
			'/(<a[^>]*>)(.*?)(<\/a>)/i' ,
			array(&$this, 'twice_tag_remove') ,
			$content
			);
		$this->count_tag = 0;

		// 許可されてるタグ以外を削除
		$content = wp_kses(
			$content ,
			array(
				'a' => array('href' => array()) ,
				'img' => array('src' => array(), 'alt' => array(), 'align' => array(), 'border' => array(), 'width' => array(), 'height' => array(), 'hspace' => array(), 'vspase' => array(), 'usemap' => array(), 'style' => array()) ,
				'font' => array('color' => array(), 'size' => array()) ,
				'h2' => array('align' => array(), 'style' => array()) , 
				'h3' => array('align' => array(), 'style' => array()) ,
				'h4' => array('align' => array(), 'style' => array()) ,
				'b' => array('style' => array()) ,
				'p' => array('style' => array()) ,
				'br' => array('style' => array()) ,
				'center' => array('style' => array()) ,
				'strong' => array('style' => array()) ,
				'blockquote' => array('cite' => array(), 'style' => array()) ,
			),
			array('http', 'https')
		);

		return $content;
	}
}
$dwango_feed = new remove_feed_tags_for_dwango();
add_filter('the_content_feed', array(&$dwango_feed, 'for_dwango_feed'), 9999);
add_filter('the_excerpt_rss',  array(&$dwango_feed, 'for_dwango_feed'), 9999);

header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action('rss2_ns'); ?>
>

<channel>
	<title><?php bloginfo_rss('name'); wp_title_rss(); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
	<language><?php echo get_option('rss_language'); ?></language>
	<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
	<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
	<?php do_action('rss2_head'); ?>
	<?php while( have_posts()) : the_post(); ?>
	<item>
		<title><?php the_title_rss() ?></title>
		<link><?php the_permalink_rss() ?></link>
		<comments><?php comments_link_feed(); ?></comments>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
		<dc:creator><?php the_author() ?></dc:creator>
		<?php the_category_rss() ?>

		<guid isPermaLink="false"><?php the_guid(); ?></guid>
<?php if (get_option('rss_use_excerpt')) : ?>
		<description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
<?php else : ?>
		<description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
	<?php if ( strlen( $post->post_content ) > 0 ) : ?>
		<content:encoded><![CDATA[<?php the_content_feed('rss2') ?>]]></content:encoded>
	<?php else : ?>
		<content:encoded><![CDATA[<?php the_excerpt_rss() ?>]]></content:encoded>
	<?php endif; ?>
<?php endif; ?>
<?php  $dwango_feed->the_related(); ?>
		<wfw:commentRss><?php echo esc_url( get_post_comments_feed_link(null, 'rss2') ); ?></wfw:commentRss>
		<slash:comments><?php echo get_comments_number(); ?></slash:comments>
<?php rss_enclosure(); ?>
	<?php do_action('rss2_item'); ?>
	</item>
	<?php endwhile; ?>
</channel>
</rss>
