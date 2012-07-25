<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts feed for goo.
 *
 * clicccar.com 用
 *
 * @package WordPress
 */
define('OUTPUT_CHARSET', 'EUC-JP');
//define(RELATED_POST_TITLE, '<h2 class="related_photos_title"><span>あわせて読みたい</span></h2>');
define(RELATED_POST_TITLE, '<h3>関連記事</h3>');


$blog_charset = get_option('blog_charset');

function convert_charset($buf) {
	return mb_convert_encoding($buf, OUTPUT_CHARSET, get_option('blog_charset'));
}
function convert_datetime($date_time){
	return str_replace(' ', 'T', mysql2date('Y-m-d H:i:s+0900', $date_time, false));
}
function remove_related($content) {
	return trim(preg_replace(
		'/^(.*)'.preg_quote(RELATED_POST_TITLE,'/').'.*$/ims' ,
		'$1', 
		$content
		));
}

header('Content-Type: '.feed_content_type('rss-http').'; charset='.OUTPUT_CHARSET, true);

ob_start('convert_charset');

echo '<?xml version="1.0" encoding="'.OUTPUT_CHARSET.'"?'.'>'; ?>

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
	<lastBuildDate><?php echo convert_datetime(get_lastpostmodified()); ?></lastBuildDate>
	<language><?php echo get_option('rss_language'); ?></language>
	<sy:updatePeriod><?php echo apply_filters('rss_update_period', 'hourly'); ?></sy:updatePeriod>
	<sy:updateFrequency><?php echo apply_filters('rss_update_frequency', '1'); ?></sy:updateFrequency>
	<?php do_action('rss2_head'); ?>
	<?php while( have_posts()) : the_post(); ?>
	<item>
		<guid><?php the_permalink_rss() ?></guid>
		<title><?php the_title_rss() ?></title>
		<link rel="alternate" type="text/html" href="<?php the_permalink_rss() ?>" />
		<modified><?php echo convert_datetime(get_the_modified_time('Y-m-d H:i:s', false)); ?></modified>
		<issued>2011-02-23</issued>
		<id><?php echo $post->ID; ?></id>
		<summary><![CDATA[<?php echo remove_related(apply_filters('the_excerpt_rss', get_the_excerpt())); ?>]]></summary>
		<author>
			<name><?php the_author(); ?></name>
		</author>
		<created><?php echo convert_datetime(get_post_time('Y-m-d H:i:s', false)); ?></created>
<?php
		// get attachments count
		$attachments = get_children(array(
			'post_parent' => $post->ID ,
			'post_type' => 'attachment' ,
			'post_mime_type' => 'image' ,
			'orderby' => 'menu_order' ,
			));
		echo "\t\t<attachments>".count($attachments)."</attachments>\n";
		unset($attachments);

		// categories
		foreach((get_the_category()) as $category) { 
			echo "\t\t<category_id>{$category->cat_ID}</category_id>\n"; 
			echo "\t\t<category>{$category->cat_name}</category>\n"; 
		} 
?>
		<content type="text/xhtml+xml" mode="escaped" xml:lang="ja"><![CDATA[<?php the_content_feed('rss2') ?>]]></content>
	</item>
	<?php endwhile; ?>
</channel>
</rss>
<?php ob_end_flush(); ?>