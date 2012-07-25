<?php
/*
Plugin Name: Hello Claudia
Plugin URI: http://wordpress.org/extend/plugins/hello-claudia/
Description: Windows Azure Japanese official character "Claudia Madobe" is plugin to speak a message on a dashboard.
Author: wokamoto
Author URI: http://dogmap.jp/
Version: 0.4.2.1
Text Domain: hello-claudia
Domain Path: /languages/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
  Copyright 2011 wokamoto (email : wokamoto1973@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 Claudia
  Copyright 2011 Microsoft Corp. All Rights Reserved.
  http://msdn.microsoft.com/ja-jp/windowsazure/hh298798

 FlexPlayer v0.1.0
  Copyright (c) 2009 galara
  http://www.wktklabs.com/flexplayer/
  Date: 2009-06-03
  Dual licensed under the MIT licenses.
*/

class HelloClaudia{
	private $plugin_basename, $plugin_dir, $plugin_file, $plugin_url;
	private $textdomain = 'hello-claudia';
	private $image_urls = array();
	private $voice_urls = array();
	private $voice = array();

	const SHORTCODE_HANDLER = 'claudia';

	/**********************************************************
	* Constructor
	***********************************************************/
	function __construct() {
		$this->init();
		if (is_admin()) {
			add_action( 'admin_init', array(&$this,'add_scripts') );
			add_action( 'admin_head-index.php', array(&$this, 'add_styles') );
			add_action( 'wp_dashboard_setup', array(&$this, 'dashboard_widgets') );
		} else {
			add_action( 'wp_print_scripts', array(&$this,'add_scripts') );
			add_action( 'wp_head', array(&$this, 'add_styles') );
			add_shortcode( self::SHORTCODE_HANDLER, array(&$this, 'shortcode_handler') );
		}
	}

	// init
	private function init() {
		$this->set_plugin_dir(__FILE__);
		$this->textdomain  = $this->load_textdomain($this->plugin_dir, 'languages');
		$image = plugin_dir_url( __FILE__ ) . 'images/';
		$this->image_urls = array(
			'fukidashi_top'    => $image . 'back-fukidashi-top.png',
			'fukidashi_center' => $image . 'back-fukidashi-center.png',
			'fukidashi_bottom' => $image . 'back-fukidashi-bottom.png',
			'claudia' => $image . ($this->is_azure() ? 'back-claudia-sd2.png' : 'back-claudia-sd4.png'),
			'all_claudia' => array(
				$image . 'back-claudia-sd1.png',
				$image . 'back-claudia-sd2.png',
				$image . 'back-claudia-sd3.png',
				$image . 'back-claudia-sd4.png',
				$image . 'back-claudia-1.png',
				$image . 'back-claudia-2.png',
				$image . 'back-claudia-3.png',
				$image . 'back-claudia-4.png',
				$image . 'back-claudia-5.png',
				$image . 'back-claudia-6.png',
				$image . 'back-claudia-7.png',
				),
			);
		$media = plugin_dir_url( __FILE__ ) . 'media/';
		$this->voice_urls = array(
			 1 => $media . '01sahajimeru.mp3',
			 3 => $media . '03syuuryou.mp3' ,
			 4 => $media . '04oujougiwa.mp3' ,
			 5 => $media . '05okazure.mp3' ,
			 6 => $media . '06windowsazureha.mp3' ,
			 7 => $media . '07azurenisite.mp3' ,
			 8 => $media . '08windowsazure.mp3' ,
			 9 => $media . '09cloudwoyoro.mp3' ,
			10 => $media . '10microsoft.mp3' ,
			11 => $media . '11mangadewakaru.mp3' ,
			12 => $media . '12sorenara.mp3' ,
			13 => $media . '13blue.mp3' ,
			14 => $media . '14azurenojituryoku.mp3' ,
			16 => $media . '16finiiish.mp3' ,
			17 => $media . '17goo.mp3' ,
			18 => $media . '18par.mp3' ,
			19 => $media . '19choki.mp3' ,
			20 => $media . '20aikogoo.mp3' ,
			21 => $media . '21aikopar.mp3',
			22 => $media . '22aikochoki.mp3' ,
			27 => $media . '27goodmorningazuresky.mp3' ,
			28 => $media . '28denwa.mp3' ,
			29 => $media . '29mail.mp3' ,
			30 => $media . '30start.mp3' ,
			31 => $media . '31deploykaishi.mp3' ,
			32 => $media . '32deploykanryo.mp3' ,
			33 => $media . '33build.mp3' ,
			34 => $media . '34sippai.mp3' ,
			35 => $media . '35daiseikou.mp3' ,
			36 => $media . '36ei.mp3' ,
			37 => $media . '37ah.mp3' ,
			38 => $media . '38otsukare.mp3' ,
			39 => $media . '39kokogapoint.mp3' ,
			40 => $media . '40chui.mp3' ,
			);
		$this->voice = array(
			 1 => __('さぁ、始めるわヨ', $this->textdomain) ,
			 3 => __('終了～', $this->textdomain) ,
			 4 => __('往生際が悪いわヨ', $this->textdomain) ,
			 5 => __('OK, Azure 今日もいい子ネ', $this->textdomain) ,
			 6 => __('Windows Azure はわたしの、最高の友達', $this->textdomain) ,
			 7 => __('Azure にして後悔なんて、あるわけない', $this->textdomain) ,
			 8 => __('Windows Azure', $this->textdomain) ,
			 9 => __('クラウドをよろしくネ', $this->textdomain) ,
			10 => __('マイクロソフト', $this->textdomain) ,
			11 => __('マンガでわかる Windows Azure プラットフォーム', $this->textdomain) ,
			12 => __('それなら Windows Azure にすればいいじゃない', $this->textdomain) ,
			13 => __('ブルースクリーンみたいな顔色ネ', $this->textdomain) ,
			14 => __('Azure の実力はまだまだこんなモノじゃないワ!', $this->textdomain) ,
			16 => __('Finiiiiiish', $this->textdomain) ,
			17 => __('じゃんけん、グー', $this->textdomain) ,
			18 => __('じゃんけん、パー', $this->textdomain) ,
			19 => __('じゃんけん、チョキ', $this->textdomain) ,
			20 => __('あいこで、グー', $this->textdomain) ,
			21 => __('あいこで、パー', $this->textdomain) ,
			22 => __('あいこで、チョキ', $this->textdomain) ,
			27 => __('Good Morning Azure Sky!', $this->textdomain) ,
			28 => __('電話が鳴ってる', $this->textdomain) ,
			29 => __('メールが来たっ', $this->textdomain) ,
			30 => __('スタート!', $this->textdomain) ,
			31 => __('デプロイ開始!', $this->textdomain) ,
			32 => __('デプロイ完了!', $this->textdomain) ,
			33 => __('ビルド完了!', $this->textdomain) ,
			34 => __('失敗しちゃった！', $this->textdomain) ,
			35 => __('大成功!', $this->textdomain) ,
			36 => __('エイッ!', $this->textdomain) ,
			37 => __('きゃっ', $this->textdomain) ,
			38 => __('お疲れ様♪', $this->textdomain) ,
			39 => __('ここがポイント！', $this->textdomain) ,
			40 => __('注意してネ♪', $this->textdomain) ,
			);
	}

	// set plugin dir
	private function set_plugin_dir( $file = '' ) {
		$file_path = ( !empty($file) ? $file : __FILE__);
		$filename = explode("/", $file_path);
		if (count($filename) <= 1)
			$filename = explode("\\", $file_path);
		$this->plugin_basename = plugin_basename($file_path);
		$this->plugin_dir  = $filename[count($filename) - 2];
		$this->plugin_file = $filename[count($filename) - 1];
		$this->plugin_url  = $this->wp_plugin_url($this->plugin_dir);
		unset($filename);
	}

	// WP_CONTENT_DIR
	private function wp_content_dir($path = '') {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_DIR')
			? WP_CONTENT_DIR
			: trailingslashit(ABSPATH) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// WP_CONTENT_URL
	private function wp_content_url($path = '') {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_URL')
			? WP_CONTENT_URL
			: trailingslashit(get_option('home')) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// WP_PLUGIN_DIR
	private function wp_plugin_dir($path = '') {
		return trailingslashit($this->wp_content_dir( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	// WP_PLUGIN_URL
	private function wp_plugin_url($path = '') {
		return trailingslashit($this->wp_content_url( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	// check wp version
	private function wp_version_check($version, $operator = ">=") {
		global $wp_version;
		return version_compare($wp_version, $version, $operator);
	}

	// load textdomain
	private function load_textdomain( $plugin_dir, $sub_dir = 'languages' ) {
		$textdomain_name = $plugin_dir;
		$plugins_dir = trailingslashit( defined('PLUGINDIR') ? PLUGINDIR : 'wp-content/plugins' );
		$abs_plugin_dir = $this->wp_plugin_dir($plugin_dir);
		$sub_dir = (
			!empty($sub_dir)
			? preg_replace('/^\//', '', $sub_dir)
			: (file_exists($abs_plugin_dir.'languages') ? 'languages' : (file_exists($abs_plugin_dir.'language') ? 'language' : (file_exists($abs_plugin_dir.'lang') ? 'lang' : '')))
			);
		$textdomain_dir = trailingslashit(trailingslashit($plugin_dir) . $sub_dir);

		if ( $this->wp_version_check("2.6") && defined('WP_PLUGIN_DIR') )
			load_plugin_textdomain($textdomain_name, false, $textdomain_dir);
		else
			load_plugin_textdomain($textdomain_name, $plugins_dir . $textdomain_dir);

		return $textdomain_name;
	}

	/**********************************************************
	* claudia !
	***********************************************************/
	private function claudia($msg, $voice_url = ''){
		$copyright  = sprintf(
			'<p style="padding:0;"><small>%s</small></p>' ,
			'&copy; 2011 Microsoft Corp.' . (is_admin() ? '<br />' : ' ') . 'All Rights Reserved.'
			);
		$retval = '';
		if ( !empty($voice_url) ) {
			$retval .= $this->flexplayer();
			$voice = ' onclick="playStart(' . "'" . esc_url($voice_url) . "'" . ')"';
		}
		$retval .= '<div class="claudia2">';
		$retval .= '<div class="claudia3"> </div>';
		$retval .= sprintf( '<div class="claudia4">%s</div>', $msg );
		$retval .= '</div>';
		$retval .= sprintf( '<div class="claudia"%s>&nbsp;</div>', $voice );
		$retval .= sprintf( '<div class="claudia5">%s</div>', $copyright );
		if ( is_admin() ) {
			$retval .= '<div style="clear:both;"> </div>';
		}
		return $retval;
	}

	// init flaxplayer
	private function flexplayer(){
		static $init_flexplayer = TRUE;
		$retval = '';
		if ( $init_flexplayer ) {
			$plugin_dir_url = plugin_dir_url( __FILE__ );
			$retval  = '<script src="' . $plugin_dir_url . 'flexplayer/jquery.flexplayer.min.js" type="text/javascript"></script>' . "\n";
			$retval .= '<script type="text/javascript">' . "\n";
			$retval .= '// <![CDATA[' . "\n";
			$retval .= 'var player;' . "\n";
			$retval .= 'jQuery(function() {';
			$retval .= 'player = new jQuery(".claudia").flexplayer({ swfPath : "' . $plugin_dir_url . 'flexplayer/flexplayer.swf" });}';
			$retval .= ');' . "\n";
			$retval .= 'function playStart(media_url){';
			$retval .= 'player.play({type: "mp3", path: media_url, volume: 100});';
			$retval .= '}' . "\n";
			$retval .= '// ]]>' . "\n";
			$retval .= '</script>' . "\n";
			$init_flexplayer = FALSE;
		}
		return $retval;
	}

	// a last contribution day.
	private function get_last_update(){
		global $wpdb;
		$current_user = wp_get_current_user();
		$date = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT MAX(post_date) as last FROM $wpdb->posts WHERE post_status = 'publish' AND post_author = %d" ,
				(int)$current_user->ID
				)
			);
		return $date[0]->last;
	}
	
	// is azure ?
	private function is_azure(){
		return function_exists( 'azure_getconfig' );
	}

	/**********************************************************
	* dashboard widgets
	***********************************************************/
	public function dashboard_widgets() {
		wp_add_dashboard_widget( 'claudia_dashboard', __('クラウディア窓辺', $this->textdomain), array(&$this,'claudia_dashboard') );
	}

	/**********************************************************
	* add scripts
	***********************************************************/
	public function add_scripts() {
		wp_enqueue_script( 'jquery' );
	}

	/**********************************************************
	* add styles
	***********************************************************/
	public function add_styles(){
		$claudia_img = $this->image_urls['claudia'];
		if ( is_admin() ) {
			$claudia_img = $this->image_urls['all_claudia'][array_rand($this->image_urls['all_claudia'])];
		}
		$this->write_styles( $claudia_img );
	}
	private function write_styles($claudia_img = ''){
		if (empty($claudia_img)) {
			$claudia_img = $this->image_urls['claudia'];
		}
?>
<style type="text/css" charset="utf-8">
.claudia, .claudia2, .claudia3, .claudia4, .claudia5 {
	padding: 0;
}
.claudia2, .claudia3, .claudia4 {
	max-width: 440px;
}
.claudia {
	margin: -5px 0 0 0;
	background: url( <?php echo esc_url( $claudia_img ); ?> ) no-repeat right top;
	background-size: contain;
	height: 246px;
	float: left;
	width: 30%;
	max-width: 220px;
}
.claudia2 {
	width: 70%;
	margin: 20px 0 0;
	background: url( <?php echo esc_url( $this->image_urls['fukidashi_center'] ); ?> ) repeat-y left top;
	background-size: contain;
	float: left;
}
.claudia3 {
	margin: -20px 0 0;
	height: 100px;
	background: url( <?php echo esc_url( $this->image_urls['fukidashi_top'] ); ?> ) no-repeat left top;
	background-size: contain;
}
.claudia4 {
	margin: -100px 0 -20px;
	padding: 5% 15% 5% 10%;
	line-height: 160%;
	background: url( <?php echo esc_url( $this->image_urls['fukidashi_bottom'] ); ?> ) no-repeat left bottom;
	background-size: contain;
	min-height: 90px;
}
.claudia5 {
	margin: 0;
	text-align:right;
	<?php echo (is_admin() ? 'float: left;' : 'clear: both;') . "\n"; ?>
}
<?php
		if (is_admin()) {
?>
#claudia_dashboard .inside {min-height:330px}
.claudia6, .claudia7, .claudia8 {padding: 0;}
.claudia6 {margin: 3px 0 12px;}
.claudia7 {margin: 8px 0 12px;}
.claudia8 {margin: 10px 0 3px;}
<?php
		}
?>

</style>
<?php
	}

	/**********************************************************
	* "claudia" widget of the dashboard.
	***********************************************************/
	public function claudia_dashboard(){
		global $current_user;

		$msg = '';
		$date_format = __('Y年n月j日', $this->textdomain);
		$time_format = __('G時i分', $this->textdomain);

		// claudia6
		$hour = (int)date_i18n('H');
		if ($hour > 17) {
			$msg_1 = 38;
		} elseif ($hour > 10) {
			$msg_1 = 1;
		} elseif ($hour > 5) {
			$msg_1 = 27;
		} elseif ($hour < 3) {
			$msg_1 = 4;
		} else {
			$msg_1 = 13;
		}
		if ($this->is_azure()) {
			$rand = (int)rand(1,100);
			if ($rand > 80) {
				$msg_2 = 6;
			} elseif ($rand > 60) {
				$msg_2 = 7;
			} elseif ($rand > 40) {
				$msg_2 = 9;
			} elseif ($rand > 20) {
				$msg_2 = 5;
			} else {
				$msg_2 = 14;
			}
		} else {
			$msg_2 = 12;
		}
		$voice = array( $this->voice_urls[$msg_1], $this->voice_urls[$msg_2] );
		$msg .= '<p class="claudia6">';
		$msg .= sprintf(
			__('<big><strong>%1$s</strong></big><br style="margin-bottom:.5em;"/><big>%2$s</big><br />%3$s', $this->textdomain),
			$current_user->display_name,
			$this->voice[$msg_1],
			$this->voice[$msg_2]
			);
		$msg .= '</p>';

		// claudia7
		$week = array(
			__('日', $this->textdomain),
			__('月', $this->textdomain),
			__('火', $this->textdomain),
			__('水', $this->textdomain),
			__('木', $this->textdomain),
			__('金', $this->textdomain),
			__('土', $this->textdomain),
			);
		$nowtime = sprintf(
			__('%1$s(%2$s)', $this->textdomain),
			date_i18n($date_format),
			$week[(int)date_i18n('w')]
			);
		$nowtime .= '&nbsp;' . date_i18n($time_format);
		$msg .= '<p class="claudia7">';
		$msg .= sprintf(
			__('<big>%s</big>&nbsp;だヨ。', $this->textdomain),
			$nowtime
			);
		$msg .= '</p>';

		// claudia6
		$last_update = $this->get_last_update();
		$msg .= '<p class="claudia6">';
		$msg .= sprintf(
			__('最後に記事を投稿したのは、<br /><big>%s</big>', $this->textdomain),
			mysql2date( $date_format, $last_update )
			);
		$msg .= '</p>';

		$lastdate = mysql2date( 'YmdHi', $last_update );
		$now = date_i18n('YmdHi');
		$lastdatenow = (strtotime($now)-strtotime($lastdate))/(3600*24);
		$lasttimenow = (strtotime($now)-strtotime($lastdate))/(3600);	
		if ($lastdatenow < 1) {
			$msg_3 = sprintf(
				__('%1$d 時間', $this->textdomain),
				floor($lasttimenow)
				);
		} elseif ($lastdatenow < 365) {
			$msg_3 = sprintf(
				__('%1$d 日と %2$d 時間', $this->textdomain),
				floor($lastdatenow),
				floor($lasttimenow - (intval($lastdatenow) * 24))
				);
		} else {
			$msg_3 = __('1年以上', $this->textdomain);
		}
		$msg .= '<p class="claudia6">';
		$msg .= sprintf(
			__('最後に記事を投稿してから、<br /><big>%s</big>&nbsp;たったワ。', $this->textdomain). '<br />',
			$msg_3
			);
		$msg .= '</p>';

		// claudia8
		if ($lastdatenow > 365) {
			$msg_6 = __('心機一転！<br />1年以上ぶりでもいいじゃない！<br />思い切って記事を書いてみよう！', $this->textdomain);
		} elseif ($lastdatenow > 30) {
			$msg_6 = __('久しぶりに記事を書いてみよう！<br />思い切って書き始めれば、<br />記事を書くペースもつかめるヨ！', $this->textdomain);
		} elseif ($lastdatenow > 7) {
			$msg_6 = __('1週間以上、空いちゃったネ。<br />みんな待ってるヨ！<br />また記事を書いてみよう！', $this->textdomain);
		} else {
			$msg_6 = __('いいペースだネ！<br />今日も記事を書いてみよう！<br />&nbsp;', $this->textdomain);
		}
		$msg .= '<p class="claudia8">';
		$msg .= sprintf(
			__('<big>%s</big>', $this->textdomain),
			$msg_6
			);
		$msg .= '</p>';

		echo $this->claudia( $msg, $voice[0] );
	}

	/**********************************************************
	* shortcode handler
	***********************************************************/
	public function shortcode_handler($atts, $content = '') {
		$atts = shortcode_atts(
			array(
				'voice'       => '' ,
				),
			$atts
			);
		extract( $atts );

		$voice = !empty($voice) ? intval($voice) : '';
		$voice_url = ''; 
 		if ( !empty($voice) && isset($this->voice[$voice]) ) {
 			$content .= $this->voice[$voice];
 			$voice_url = $this->voice_urls[$voice];
 		}

		return $this->claudia($content, $voice_url);
	}
}
new HelloClaudia();
