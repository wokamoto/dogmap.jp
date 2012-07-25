<?php
/*
Plugin Name: FeedLogger Edited by kota
Version: 1.0
Plugin URI: http://kota-kota.net/
Description: Feedの購読者数を記録するプラグインです。FeedLogger0.14b(http://techblog.ecstudio.jp/tech-tips/wordpress/feedlogger.html)をもとに作成しました。
Author: kota
Author URI: http://kota-kota.net/
License: GNU General Public License
*/

/**
 * ダッシュボードに出力
 */
function feedlogger_add_dashboard_widgets() {
	wp_add_dashboard_widget( 'feedlogger', 'FeedLogger Edited by kota', 'feedlogger_display' );
}
add_action('wp_dashboard_setup', 'feedlogger_add_dashboard_widgets' );
add_action('admin_head', 'feedlogger_css');

add_action('do_feed_rss','feedlogger_write_rss');
add_action('do_feed_rss2','feedlogger_write_rss2');
add_action('do_feed_atom','feedlogger_write_atom');
add_action('do_feed_rdf','feedlogger_write_rdf');
//add_action('admin_menu', 'feedlogger_add_page');

require(dirname(__FILE__)."/lib/FeedLogger.php");

function feedlogger_write_rss(){
	$fl =& FeedLogger::singleton();
	
	$fl->write("rss");
}

function feedlogger_write_rss2(){
	$fl =& FeedLogger::singleton();
	
	$fl->write("rss2");
}

function feedlogger_write_atom(){
	$fl =& FeedLogger::singleton();
	
	$fl->write("atom");
}

function feedlogger_write_rdf(){
	$fl =& FeedLogger::singleton();
	
	$fl->write("rdf");
}

//function feedlogger_add_page(){
//	add_management_page('FeedLogger','FeedLogger',8, __FILE__, 'feedlogger_options_page');
//}

/**
 * 管理画面の<head>内に外部CSSを埋め込み
 */

function feedlogger_css() {
	    $url =  dirname(WP_PLUGIN_URL . '/' . plugin_basename(__FILE__)) . '/style.css';
	    echo "\n" . '<link rel="stylesheet" type="text/css" href="' . $url . '" />' . "\n";
	}

/**
 * ダッシュボードに表示
 */

function feedlogger_display(){
	$fl =& FeedLogger::singleton();
	?>
	<p>購読者合計：<?php echo $fl->getTotalSubscribers(); ?> ユーザー</p>
	<table class="flTable" cellspacing="1" cellpadding="2" border="0">
	 <tr class="flTableHeader">
	  <td>リーダー</td>
	  <td>フィード</td>
	  <td>購読者数</td>
	  <td>最終更新日</td>
	 </tr>
	 <?php if ($sb_dat = $fl->getFeedSummary()) { ?>
	  <?php foreach ($sb_dat as $reader => $feed_dat) { ?>
	  	<?php if ($reader == "_v") { continue; } ?>
	 <tr class="flTableRow">
	 	<td rowspan="<?php echo count($feed_dat); ?>"><a href="<?php echo $fl->fetcher_type[$reader]['url']; ?>" target="_blank"><?php echo $fl->fetcher_type[$reader]['name']; ?></a></td>
	 	<?php
	 	$i = 0;
	 	foreach ($feed_dat as $feed_type => $data){
			if ($i){ ?>
				</tr>
				<tr class="flTableRow">
			<?php } ?>
			<td align="center"><?php echo $feed_type; ?></td>
			<td align="center"><?php echo $data['num']; ?></td>
			<td><?php echo date("m/d H:i:s",$data['time']); ?></td>
			<?php $i++;
		} ?>
	 </tr>
	  <?php } ?>
	 <?php }else{ ?>
	 <tr class="flTableRow">
	  <td align="center" colspan="4"> - 購読者数はまだ取得できていません - </td>
	 </tr>
	 <?php } ?>
	</table>
<?php
}