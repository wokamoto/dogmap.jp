<?php
/* これは文字化け防止のための日本語文字列です。
   このソースファイルは UTF-8 で保存されています。
   Above is a Japanese strings to avoid charset mis-understanding.
   This source file is saved with UTF-8.
 */

/* ==================================================
 *   KtaiServices class
   ================================================== */

class KtaiServices {
	private $base;
	protected $theme;
	protected $user_agent;
	protected $search_engine;
	protected $operator = 'Unknown';
	protected $type = 'N/A';
	protected $image_pictograms = true;
	protected $flat_rate = true;
	protected $use_redir = false;
	protected $show_plugin_icon = false;
	protected $pcview_enabled = true;
	protected $term_name = '';
	protected $term_ID = '';
	protected $usim_ID = '';
	protected $sub_ID = '';
	protected $sub_ID_available = false;
	protected $cookie_available = true;
	protected $ext_css_available = true;
	protected $available_js_version = '3.0';
	protected $textarea_size = 50000;
	protected $page_size = 50000;
	protected $cache_size = 524288;
	protected $screen_width = 240;
	protected $screen_height = 320;
	protected $charset = 'UTF-8';
	protected $mime_type = 'text/html';
	protected $preamble = '<?xml version="1.0" encoding="__CHARSET__"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'; // <?php /* syntax highiting fix */
	protected $xhtml_head = '<html xmlns="http://www.w3.org/1999/xhtml">';
	protected $allowedtags;
	protected static $pictograms;
	protected static $pict_url;
	protected static $translated;
	protected static $search_ip;
	const DEFAULT_CHARSET = 'SJIS';
	const PICT_DIR = 'pics/';
	const PICT_CLASS = 'pictogram';
	const PICT_STYLE = 'border:0 none;';

/* ==================================================
 * @param	none
 * @return	object  $ktai
 */
public static function factory($ua = NULL) {
	$ktai = NULL;
	$ua = $ua ? $ua : $_SERVER['HTTP_USER_AGENT'];
	if ( isset($_GET['preview']) && isset($_GET['mobile']) ) {
		$ua = 'Ktai_Theme_Preview';
		$ktai = new KtaiService_Preview($ua);
	} elseif (preg_match('!^DoCoMo/1!', $ua)) {
		require_once dirname(__FILE__) . '/i-mode.php';
		$ktai = new KtaiService_imode_mova($ua);
	} elseif (preg_match('!^DoCoMo/2!', $ua)) {
		require_once dirname(__FILE__) . '/i-mode.php';
		if (preg_match('/\(c(\d+);/', $ua, $cache) && $cache[1] >= 500) {
			$ktai = new KtaiService_imode_Browser2($ua);
		} else {
			$ktai = new KtaiService_imode_FOMA($ua);
		}
	} elseif (preg_match('!^J-(PHONE|EMULATOR)/!', $ua)) {
		require_once dirname(__FILE__) . '/softbank.php';
		$ktai = new KtaiService_Softbank_PDC($ua);
	} elseif (preg_match('!^(Vodafone/|MOT(EMULATOR)?-[CV]|SoftBank/|[VS]emulator/)!', $ua)) {
		require_once dirname(__FILE__) . '/softbank.php';
		$ktai = new KtaiService_Softbank_3G($ua);
	} elseif (preg_match('/(DDIPOCKET|WILLCOM);/', $ua)) {
		require_once dirname(__FILE__) . '/willcom.php';
		$ktai = new KtaiService_WILLCOM($ua);
	} elseif (preg_match('!^(emobile|Huawei|IAC)/!', $ua)) {
		require_once dirname(__FILE__) . '/emobile.php';
		$ktai = new KtaiService_EMOBILE($ua);
	} elseif (preg_match('/^KDDI-/',$ua)) {
		require_once dirname(__FILE__) . '/ezweb.php';
		$ktai = new KtaiService_EZweb_WAP2($ua);
	} elseif (preg_match('/^UP\.Browser/',$ua)) {
		require_once dirname(__FILE__) . '/ezweb.php';
		$ktai = new KtaiService_EZweb_HDML($ua);
	} elseif (preg_match('/\b(iP(hone|od);|Android )/', $ua, $name) && ks_option('ks_theme_touch')) {
		$ktai = new KtaiService_Touch($ua);
		$ktai->term_name = $name[1];
	} elseif (preg_match('!PDA; SL-\w+!', $ua, $name)) {
		$ktai = new KtaiService_Other_Japan($ua);
		$ktai->term_name = $ktai->term_name ? $ktai->term_name : $name[1];
	} elseif (preg_match('!(^Nokia\w+|^BlackBerry[0-9a-z]+/|^SAMSUNG\b|Opera Mini|Opera Mobi|PalmOS\b|Windows CE\b)!', $ua, $name)) {
		$ktai = new KtaiService_Other($ua);
		$ktai->term_name = $ktai->term_name ? $ktai->term_name : $name[1];
	} elseif (preg_match('/\(PSP \(PlayStation Portable\);/', $ua)) {
		$ktai = new KtaiService_Other($ua);
		$ktai->term_name = 'PlayStation Portable';
	} elseif (preg_match('!SONY/COM!', $ua)) {
		$ktai = new KtaiService_Other($ua);
		$ktai->term_name = 'Somy mylo';
	} elseif (preg_match('/(\bNitro\) Opera|Nintendo (\w+);)/', $ua, $type)) {
		$ktai = new KtaiService_Other($ua);
		$ktai->term_name = isset($type[2]) ? "Nintendo $type[2]" : 'Nintendo DS';
	} elseif (preg_match('!^mixi-mobile-converter/!', $ua)) {
		$ktai = new KtaiService_Other_Japan($ua);
		$ktai->term_name = 'mixi Mobile';
		$ktai->xhtml_head = ''; // force text/html
	}
	$ktai = apply_filters('ktai_detect_agent', $ktai, $ua);
	$ktai = apply_filters('detect_agent/ktai_style.php', $ktai, $ua);
	if ($ktai) {
		if (preg_match('#\b(Googlebot-Mobile)/#', $ua, $match)) {
			$ktai->search_engine = $match[1];
		} elseif (preg_match('#\b(Y!J-(SRD|MBS))/#', $ua, $match)) {
			$ktai->search_engine = $match[1];
		} elseif (preg_match('/\b(LD_mobile_bot);/', $ua, $match)) {
			$ktai->search_engine = $match[1];
		} elseif (preg_match('#(ichiro/mobile goo);#', $ua, $match)) {
			$ktai->search_engine = $match[1];
		} elseif (preg_match('/\((symphonybot\d\.froute\.jp);/', $ua, $match)) {
			$ktai->search_engine = $match[1];
		} elseif (preg_match('#\b(moba-crawler);#', $ua, $match)) {
			$ktai->search_engine = $match[1];
		} elseif (preg_match('#\b(BaiduMobaider)/#', $ua, $match)) {
			$ktai->search_engine = $match[1];
		} elseif (preg_match('#\b(Hatena-Mobile-Gateway)/#', $ua, $match)) {
			$ktai->search_engine = $match[1];
		}
	}
	return $ktai;
}

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 */
public function __construct($user_agent) {
	require_once dirname(dirname(__FILE__)) . '/' . KtaiStyle::INCLUDES_DIR . '/kses.php';
	$this->allowedtags = apply_filters('ktai_allowedtags', Ktai_HTML_Filter::$allowedtags);
	$this->allowedtags = apply_filters('allowedtags/ktai_style.php', $this->allowedtags);
	$this->user_agent = $user_agent;
	if (empty($this->theme)) {
		$this->theme = ks_option('ks_theme');
	}
	$this->set_variables();
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function set_variables() {
	global $Ktai_Style;
	self::$pict_url = $Ktai_Style->get('plugin_url') . self::PICT_DIR; // keep FQDN for PC
	self::$search_ip = array(
		// http://googlejapan.blogspot.com/2008/05/google.html
		'72.14.199.0/25',
		'209.85.238.0/25',
		// http://help.yahoo.co.jp/help/jp/search/indexing/indexing-27.html
		'124.83.159.146-124.83.159.185',
		'124.83.159.224-124.83.159.247',
		// http://helpguide.livedoor.com/help/search/qa/grp627
		'203.104.254.0/24',
		// http://help.goo.ne.jp/help/article/1142/
		'210.150.10.32/27',
		'203.131.250.0/24',
		// http://search.froute.jp/howto/crawler.html
		'60.43.36.253/32',
		// http://crawler.dena.jp/
		'202.238.103.126/32',
		'202.213.221.9/32',
		// http://www.baidu.jp/spider/
		'119.63.195.0/24',
	);

	if ( isset($this) && !$this->image_pictograms ) {
		return;
	}
 	// http://www.au.kddi.com/ezfactory/tec/spec/4_4.html
	// http://creation.mb.softbank.jp/download.php?docid=103 (members only)
	// http://www.nttdocomo.co.jp/service/imode/make/content/pictograph/
	// http://www.willcom-inc.com/ja/service/contents_service/club_air_edge/for_phone/homepage/pdf/contents_reference.pdf
	// http://developer.emnet.ne.jp/emoji_list.pdf
	self::$pictograms = array(
		'1'     => array('SA/danger.gif', '[!]'), 
		'2'     => array('SA/sign01.gif', '!'), 
		'3'     => '?', 
		'4'     => array('SA/mobaq.gif', 'Q'), 
		'5'     => '＜', 
		'6'     => '＞', 
		'7'     => '≪', 
		'8'     => '≫', 
		'9'     => '□', 
		'10'    => '■', 
		'11'    => '[i]', 
		'12'    => array('SA/wine.gif', '[ワイングラス]'), 
		'13'    => '[スピーカー]', 
		'14'    => '$', 
		'15'    => array('SA/moon3.gif', '[月]'), 
		'16'    => array('SA/thunder.gif', '[稲妻]'), 
		'17'    => '□', 
		'18'    => '■', 
		'19'    => '◇', 
		'20'    => '◆', 
		'21'    => '□', 
		'22'    => '■', 
		'23'    => '◇', 
		'24'    => '◆', 
		'25'    => array('SA/watch.gif', '[腕時計]'), 
		'26'    => '+', 
		'27'    => '-', 
		'28'    => '☆', 
		'29'    => '↑', 
		'30'    => '↓', 
		'31'    =>  array('SA/ban.gif', '[禁止]'), 
		'32'    => '▽', 
		'33'    => '△', 
		'34'    => '▼', 
		'35'    => '▲', 
		'36'    => '◇', 
		'37'    => '◆', 
		'38'    => '□', 
		'39'    => '■', 
		'40'    => '○', 
		'41'    => '●', 
		'42'    => array('SA/upwardleft.gif', '[左上]'), 
		'43'    => array('SA/downwardright.gif', '[右下]'), 
		'44'    => array('SA/sun.gif', '[晴れ]'), 
		'45'    => array('SA/baseball.gif', '[野球]'), 
		'46'    => array('SA/clock.gif', '[時計]'), 
		'47'    => array('SA/moon03.gif', '[月]'), 
		'48'    => array('SA/bell.gif', '[ベル]'), 
		'49'    => '[画鋲]', 
		'50'    => "('')", // 小顔
		'51'    => array('SA/heart01.gif', '&hearts;'), // ハート
		'52'    => array('SA/bar.gif', '[マティーニ]'), 
		'53'    => array('SA/clover.gif', '[四つ葉]'), 
		'54'    => array('SA/tm.gif', '&trade;'), 
		'55'    => '×', // かける
		'56'    => array('SA/memo.gif', '[文書]'), 
		'57'    => array('SA/sandclock.gif', '[砂時計]'), 
		'58'    => array('SA/sandclock.gif', '[砂時計]'), 
		'59'    => '[フロッピー]', 
		'60'    => '*', // 雪の結晶
		'61'    => '×', // バツ1
		'62'    => '×', // バツ2 
		'63'    => '→', // 右矢印 1
		'64'    => '←', // 左矢印 1
		'65'    =>  array('SA/beer.gif', '[マグカップ]'), 
		'66'    => '÷', 
		'67'    => '[カレンダー]', 
		'68'    => array('68.png', "(^_^)"),  // スマイルフェイス
		'69'    => '★', // 星2
		'70'    => array('SA/upwardright.gif', '[右上]'), 
		'71'    => array('SA/downwardleft.gif', '[左下]'), 
		'72'    => array('SA/ring.gif', '[指輪]'), 
		'73'    => 'レ', // チェックマーク1
		'74'    => array('SA/dog.gif', '[プードル]'), 
		'75'    => '☆', //星3
		'76'    => '‡', // スパーク
		'77'    => array('SA/flair.gif', '[電球]'), 
		'78'    => array('SA/chick.gif', '[鳥]'), 
		'79'    => '[閉フォルダ]', 
		'80'    => "('_')", // 顔
		'81'    => array('SA/copyright.gif', '&copy;'), 
		'82'    => array('SA/r-mark.gif', '&reg;'), 
		'83'    => '[ブリーフケース]', 
		'84'    => '[開フォルダ]', 
		'85'    => array('SA/telephone.gif', '[電話]'), 
		'86'    => array('86.png', '[吹き出し]'), 
		'87'    => '[クレジットカード]', 
		'88'    => '△', 
		'89'    => '▽', 
		'90'    => array('90.png', '[アメリカ]'), 
		'91'    => array('SA/memo.gif', '[ノート]'), 
		'92'    => array('SA/memo.gif', '[クリップボード]'), 
		'93'    => array('SA/cafe.gif', '[カップ]'), 
		'94'    => array('SA/camera.gif', '[カメラ]'),  
		'95'    => array('SA/rain.gif', '[雨]'), 
		'96'    => '[フットボール]', 
		'97'    => array('SA/book.gif', '[本]'), 
		'98'    => array('SA/ban.gif', '[立ち入り禁止]'), 
		'99'    => array('SA/signaler.gif', '[信号]'), 
		'100'   => array('SA/book.gif', '[本]'), 
		'101'   => array('SA/book.gif', '[本]'), 
		'102'   => array('SA/book.gif', '[本]'), 
		'103'   => array('SA/memo.gif', '[文書]'), 
		'104'   => array('SA/hairsalon.gif', '[はさみ]'), 
		'105'   => '[カレンダー]', 
		'106'   => array('SA/ticket.gif', '[チケット]'), 
		'107'   => array('SA/cloud.gif', '[曇り]'), 
		'108'   => array('SA/mail.gif', '[封筒]'), 
		'109'   => array('SA/yen.gif', '[￥]'), 
		'110'   => array('SA/movie.gif', '[ビデオカメラ]'), 
		'111'   => array('SA/movie.gif', '[ハンディカム]'), 
		'112'   => array('SA/house.gif', '[家]'), 
		'113'   => array('SA/tulip.gif', '[チューリップ]'), 
		'114'   => '[ナイフ]', 
		'115'   => '[ビデオテープ]', 
		'116'   => array('SA/eyeglass.gif', '[眼鏡]'), 
		'117'   => '└→', // 回り矢印1
		'118'   => array('SA/enter.gif', '←┘'), // 回り矢印2 
		'119'   => array('SA/search.gif', '[虫眼鏡]'), 
		'120'   => array('SA/key.gif', '[鍵]'), 
		'121'   => array('SA/memo.gif', '[ノート]'), 
		'122'   => array('SA/memo.gif', '[開ノート]'), 
		'123'   => '[ボルトとナット]', 
		'124'   => array('SA/boutique.gif','[ハイヒール]'), 
		'125'   => array('SA/car.gif', '[車]'), 
		'126'   => '[フロッピー]', 
		'127'   => '[棒グラフ]', 
		'128'   => '[折れ線グラフ]', 
		'129'   => array('SA/mail.gif', '[メールボックス]'), 
		'130'   => '[懐中電灯]', 
		'131'   => '[Roldex]', 
		'132'   => 'レ', // チェックマーク2
		'133'   => array('SA/maple.gif', '[紅葉]'), 
		'134'   => array('SA/dog.gif', '[猟犬]'), 
		'135'   => '[電池]', 
		'136'   => '[スクロール]', 
		'137'   => '[画鋲]', 
		'138'   => array('SA/key.gif', '[錠前]'), 
		'139'   => array('SA/ticket.gif', '[ドル札]'), 
		'140'   => '←', // 指差し確認左
		'141'   => '→', // 指差し確認右 
		'142'   => array('SA/book.gif', '[台帳]'), 
		'143'   => array('SA/clip.gif', '[クリップ]'), 
		'144'   => array('SA/present.gif', '[プレゼント]'), 
		'145'   => '[名札]', 
		'146'   => array('SA/restaurant.gif', '[レストラン]'), 
		'147'   => array('SA/book.gif', '[本]'), 
		'148'   => '[トラック]', 
		'149'   => array('SA/pencil.gif', '[鉛筆]'), 
		'150'   => '[IDO社]', 
		'151'   => array('SA/mailto.gif', '[封筒]'), 
		'152'   => array('SA/wrench.gif', '[レンチ]'), 
		'153'   => array('SA/mail.gif', '[送信 BOX]'), 
		'154'   => array('SA/mailto.gif', '[受信 BOX]'), 
		'155'   => array('SA/telephone.gif', '[受話器]'), 
		'156'   => array('SA/building.gif', '[建物]'), 
		'157'   => '□', // 定規 1 (直線)
		'158'   => '△', // 定規 2 (三角)
		'159'   => '[折れ線グラフ]', 
		'160'   => '[マンガ肉]', 
		'161'   => array('SA/mobilephone.gif', '[携帯電話]'), 
		'162'   => '[コンセント]', 
		'163'   => array('SA/shadow.gif', '[家族]'), 
		'164'   => array('SA/ribbon.gif', '[リンク]'), 
		'165'   => array('SA/present.gif', '[パッケージ]'), 
		'166'   => array('SA/faxto.gif', '[FAX]'), 
		'167'   => array('SA/cloud.gif', '[ときどき曇り]'), 
		'168'   => array('SA/airplane.gif', '[飛行機]'), 
		'169'   => array('SA/yacht.gif', '[ボート]'), 
		'170'   => '[サイコロ]', 
		'171'   => array('SA/memo.gif', '[新聞]'), 
		'172'   => array('SA/train.gif', '[電車]'), 
		'173'   => '　', // 全部ブランク
		'174'   => ' ',  // 半分ブランク
		'175'   => ' ',  // 1/4ブランク
		'176'   => array('SA/smoking.gif', '[喫煙]'), 
		'177'   => array('SA/nosmoking.gif', '[禁煙]'), 
		'178'   => array('SA/wheelchair.gif', '[車椅子]'), 
		'179'   => '[初心者]', 
		'180'   => array('SA/one.gif', '1'), 
		'181'   => array('SA/two.gif', '2'), 
		'182'   => array('SA/three.gif', '3'), 
		'183'   => array('SA/four.gif', '4'), 
		'184'   => array('SA/five.gif', '5'), 
		'185'   => array('SA/six.gif', '6'), 
		'186'   => array('SA/seven.gif', '7'), 
		'187'   => array('SA/eight.gif', '8'), 
		'188'   => array('SA/nine.gif', '9'), 
		'189'   => array('SA/zero.gif', '10'), 
		'190'   => array('SA/typhoon.gif', '[台風]'), 
		'191'   => array('SA/snow.gif', '[雪]'), 
		'192'   => array('SA/aries.gif', '[牡羊座]'),
		'193'   => array('SA/taurus.gif', '[牡牛座]'), 
		'194'   => array('SA/gemini.gif', '[双子座]'), 
		'195'   => array('SA/cancer.gif', '[蟹座]'),
		'196'   => array('SA/leo.gif', '[獅子座]'), 
		'197'   => array('SA/virgo.gif', '[乙女座]'), 
		'198'   => array('SA/libra.gif', '[天秤座]'), 
		'199'   => array('SA/scorpius.gif', '[蠍座]'), 
		'200'   => array('SA/sagittarius.gif', '[射手座]'), 
		'201'   => array('SA/capricornus.gif', '[山羊座]'),
		'202'   => array('SA/aquarius.gif', '[水瓶座]'), 
		'203'   => array('SA/pisces.gif', '[魚座]'), 
		'204'   => '[蛇遣座]', 
		'205'   => array('SA/atm.gif', '[ATM]'),
		'206'   => array('SA/24hours.gif', '[コンビニ]'), 
		'207'   => array('SA/toilet.gif', '[トイレ]'), 
		'208'   => array('SA/parking.gif', '[駐車場]'), 
		'209'   => array('SA/bus.gif', '[バス停]'), 
		'210'   => array('SA/flag.gif', '[位置情報]'), 
		'211'   => array('SA/ship.gif', '[錨]'), 
		'212'   => array('SA/bank.gif', '[銀行]'), 
		'213'   => array('SA/gasstation.gif', '[ガススタンド]'), 
		'214'   => '[地図]', 
		'215'   => array('SA/bicycle.gif', '[自転車]'), 
		'216'   => array('SA/bus.gif', '[バス]'), 
		'217'   => array('SA/bullettrain.gif', '[新幹線]'), 
		'218'   => array('SA/run.gif', '[マラソン]'), 
		'219'   => array('SA/soccer.gif', '[サッカー]'), 
		'220'   => array('SA/tennis.gif', '[テニス]'), 
		'221'   => array('SA/snowboard.gif', '[スノーボード]'), 
		'222'   => array('SA/motorsports.gif', '[チェッカーフラッグ]'), 
		'223'   => array('SA/carouselpony.gif', '[遊園地]'), 
		'224'   => array('SA/spa.gif', '[温泉]'), 
		'225'   => array('SA/bottle.gif', '[赤ちょうちん]'), 
		'226'   => array('SA/movie.gif', '[映画]'), 
		'227'   => array('SA/night.gif', '[夜の橋]'), 
		'228'   => '[東京タワー]', 
		'229'   => '[777]', 
		'230'   => '[くす玉]', 
		'231'   => '◎←', // 当選弓矢
		'232'   => array('SA/game.gif', '[ゲーム]'), 
		'233'   => array('SA/dollar.gif', '($)'), // 賞金 
		'234'   => array('SA/xmas.gif', '[クリスマスツリー]'), 
		'235'   => array('SA/cherryblossom.gif', '[花見]'), 
		'236'   => '[お化け]', 
		'237'   => array('237.png', '[日本]'), 
		'238'   => '[西瓜]', 
		'239'   => array('SA/cake.gif', '[ケーキ]'), 
		'240'   => '[フライパン]', 
		'241'   => array('SA/cherry.gif', '[さくらんぼ]'), 
		'242'   => '[河豚]', 
		'243'   => '[苺]', 
		'244'   => array('SA/riceball.gif', '[おにぎり]'), 
		'245'   => array('SA/fastfood.gif', '[ハンバーガー]'), 
		'246'   => '[鯨]', 
		'247'   => '[兎]', 
		'248'   => array('SA/horse.gif', '[馬]'), 
		'249'   => '[猿]', 
		'250'   => '[蛙]', 
		'251'   => array('SA/cat.gif', '[猫]'), 
		'252'   => array('SA/penguin.gif', '[ペンギン]'), 
		'253'   => '[蟻]', 
		'254'   => array('SA/pig.gif', '[豚]'), 
		'255'   => '[椰子の木]', 
		'256'   => '[向日葵]', 
		'257'   => array('SA/happy01.gif', "(^_^)"), // うれしい顔
		'258'   => array('SA/angry.gif', "(`_`)"), // おこった顔
		'259'   => array('SA/sad.gif', "(;_;)"), // かなしい顔
		'260'   => array('SA/wobbly.gif', "(x_x)"), // つかれた顔
		'261'   => array('SA/sleepy.gif', 'Zzz'), // 眠い (ZZZ...)
		'262'   => array('SA/annoy.gif', '[ムカッ]'), 
		'263'   => array('SA/weep.gif', '[雫]'), 
		'264'   => array('SA/bleah.gif', "(^j^)"), // からかう (あっかんべえ) 
		'265'   => array('SA/heart03.gif', '[失恋]'), 
		'266'   => array('SA/heart04.gif', '&hearts;&hearts;'), // ハート3 (大きい&小さい)
		'267'   => array('SA/shine.gif', '‡'), // スパーク2 (キラリマーク)
		'268'   => array('SA/bomb.gif', '●〜'), // 爆弾
		'269'   => '[炎]', 
		'270'   => '[SOS]', 
		'271'   => '[力こぶ]', 
		'272'   => array('SA/heart01.gif', '→&hearts;'), // ハート5 (矢がささっている)
		'273'   => array('SA/kissmark.gif', '[キスマーク]'), 
		'274'   => array('SA/shadow.gif', '[宇宙人]'), 
		'275'   => array('SA/typhoon.gif', '[なると]'), 
		'276'   => array('SA/foot.gif', '[足跡]'), 
		'277'   => '[悪魔]', 
		'278'   => '[花丸]', 
		'279'   => array('SA/secret.gif', '[秘]'), 
		'280'   => '[100点満点]', 
		'281'   => array('SA/punch.gif', '[パンチ]'), 
		'282'   => array('SA/dash.gif', '=3'), // ダッシュ
		'283'   => '[ウンチ]', 
		'284'   => '↑', 
		'285'   => '(得)', 
		'286'   => '[ドクロ]', 
		'287'   => array('SA/good.gif', '[親指サイン]'), 
		'288'   => array('SA/tv.gif', '[テレビ]'), 
		'289'   => array('SA/karaoke.gif', '[マイク]'), 
		'290'   => array('SA/moneybag.gif', '[財布]'), 
		'291'   => array('SA/notes.gif', '&#9835;'), // メロディ
		'292'   => '[ギター]', 
		'293'   => '[バイオリン]', 
		'294'   => array('SA/music.gif', '[ヘッドフォン]'), 
		'295'   => array('SA/rouge.gif', '[口紅]'), 
		'296'   => '[ピストル]', 
		'297'   => '[エステ]', 
		'298'   => '[EZ]', 
		'299'   => array('SA/free.gif', '[FREE]'), 
		'300'   => array('SA/cd.gif', '[CD/DVD]'), 
		'301'   => '[婦人服]', 
		'302'   => '[UFO]', 
		'303'   => '[更新!]', 
		'304'   => '[注射]', 
		'305'   => array('SA/mist.gif', '[霧]'), 
		'306'   => array('SA/golf.gif', '[ゴルフ]'), 
		'307'   => array('SA/basketball.gif', '[バスケットボール]'), 
		'308'   => array('SA/pocketbell.gif', '[ページャー]'), 
		'309'   => array('SA/art.gif', '[アート]'), 
		'310'   => array('SA/drama.gif', '[演劇]'), 
		'311'   => array('SA/event.gif', '[イベント]'), 
		'312'   => array('SA/ribbon.gif', '[リボン]'), 
		'313'   => array('SA/birthday.gif', '[バースデー]'), 
		'314'   => array('SA/spade.gif', '&spades;'), 
		'315'   => array('SA/diamond.gif', '&diams;'), 
		'316'   => array('SA/club.gif', '&clubs;'), 
		'317'   => array('SA/eye.gif', '[目]'), 
		'318'   => array('SA/ear.gif', '[耳]'), 
		'319'   => array('SA/scissors.gif', '[チョキ]'), 
		'320'   => array('SA/paper.gif', '[パー]'), 
		'321'   => array('SA/newmoon.gif', '●'), // 新月 
		'322'   => array('SA/moon1.gif', '[やや欠け月]'), 
		'323'   => array('SA/moon2.gif', '[半月]'), 
		'324'   => array('SA/clear.gif', '[クリア]'), 
		'325'   => array('SA/zero.gif', '0'), 
		'326'   => array('SA/ok.gif', '[OK]'), 
		'327'   => array('SA/wobbly.gif', "(x_x)"), // ふらふら
		'328'   => array('SA/cute.gif', '&hearts;'), // かわいい
		'329'   => array('SA/impact.gif', 'Σ3'), // どんっ, 
		'330'   => array('SA/sweat01.gif', ';;'), // あせあせ
		'331'   => '[ezplus]', 
		'332'   => '[地球]', 
		'333'   => array('SA/noodle.gif', '[ラーメン]'), 
		'334'   => array('SA/new.gif', '[NEW]'), 
		'335'   => array('SA/t-shirt.gif', '[シャツ]'), 
		'336'   => array('SA/shoe.gif', '[靴]'), 
		'337'   => array('SA/pc.gif', '[パソコン]'), 
		'338'   => '[ラジオ]', 
		'339'   => '[薔薇]', 
		'340'   => array('SA/bell.gif', '[チャペル]'), 
		'341'   => array('SA/subway.gif', '[地下鉄]'), 
		'342'   => array('SA/fuji.gif', '[富士山]'), 
		'343'   => array('SA/note.gif', '♪'), // 八分音符
		'344'   => '[天使]', 
		'345'   => '[虎]', 
		'346'   => '[熊]', 
		'347'   => '[鼠]', 
		'348'   => array('SA/wink.gif', "('_-)"), // ウィンク
		'349'   => array('SA/lovely.gif', "(*_*)"), // 目がハート
		'350'   => array('SA/shock.gif', ")@_@("), // ショッキング
		'351'   => array('SA/think.gif', ";-.-)"), // 困り
		'352'   => '[蛸]', 
		'353'   => '[ロケット]', 
		'354'   => array('SA/crown.gif', '[王冠]'), 
		'355'   => array('SA/kissmark.gif', '[チュー]'), 
		'356'   => '[ハンマー]', 
		'357'   => '[花火]', 
		'358'   => array('SA/maple.gif', '[枯れ葉]'), 
		'359'   => array('SA/bag.gif', '[仕事かばん]'), 
		'360'   => '[噴水]', 
		'361'   => '[キャンプ]', 
		'362'   => '[麻雀]', 
		'363'   => '[VS]', 
		'364'   => '[トロフィー]', 
		'365'   => '[亀]', 
		'366'   => '[スペイン]', 
		'367'   => '[ロシア]', 
		'368'   => '[工事中]', 
		'369'   => array('SA/spa.gif', '[風呂]'), 
		'370'   => '[祝]', 
		'371'   => '[夕方]', 
		'372'   => '[卵]', 
		'373'   => '[株価]', 
		'374'   => '[警察官]', 
		'375'   => array('SA/postoffice.gif', '〒'), 
		'376'   => array('SA/hospital.gif', '[病院]'), 
		'377'   => array('SA/school.gif', '[学校]'), 
		'378'   => array('SA/hotel.gif', '[ホテル]'), 
		'379'   => array('SA/ship.gif', '[船]'), 
		'380'   => array('SA/ban.gif', '[18禁]'), 
		'381'   => '[.i|]', // 圏内
		'382'   => '[COOL!]', 
		'383'   => '[割]', 
		'384'   => '[サ]', 
		'385'   => array('SA/id.gif', '[ID]'), 
		'386'   => array('SA/full.gif', '[満席]'), 
		'387'   => array('SA/empty.gif', '[空席]'), 
		'388'   => '[指]', 
		'389'   => '[営]', 
		'390'   => '↑', // 上指差し
		'391'   => '↓', // 下指差し
		'392'   => '[占い]', 
		'393'   => array('SA/mobilephone.gif', '[マナーモード]'), 
		'394'   => array('SA/mobilephone.gif', '[電源OFF]'), 
		'395'   => array('SA/memo.gif', '[メモ]'), 
		'396'   => '[ネクタイ]', 
		'397'   => array('SA/cute.gif', '[ハイビスカス]'), 
		'398'   => array('SA/cute.gif', '[花束]'), 
		'399'   => '[サボテン]', 
		'400'   => array('SA/bottle.gif', '[お銚子]'), 
		'401'   => array('SA/beer.gif', '[ビール]'), 
		'402'   => '[祝]', 
		'403'   => '[薬]', 
		'404'   => '[風船]', 
		'405'   => '[クラッカー]', 
		'406'   => '[eznavi]', 
		'407'   => '[帽子]', 
		'408'   => array('SA/shoe.gif', '[ロングブーツ]'), 
		'409'   => '[ネイル]', 
		'410'   => array('SA/hairsalon.gif', '[美容院]'), 
		'411'   => '[床屋]', 
		'412'   => '[着物]', 
		'413'   => array('SA/sports.gif', '[ビキニ]'), 
		'414'   => array('SA/heart.gif', '&hearts;'), // ハート (トランプ)
		'415'   => array('SA/heart02.gif', '&hearts;'), // 光るハート
		'416'   => array('SA/heart02.gif', '&hearts;'), // 青ハート
		'417'   => array('SA/heart02.gif', '&hearts;'), // 緑ハート
		'418'   => array('SA/heart02.gif', '&hearts;'), // 黄ハート
		'419'   => array('SA/heart02.gif', '&hearts;'), // 紫ハート
		'420'   => array('SA/shine.gif', '†'), // きらきら
		'421'   => array('SA/ski.gif', '[スキー]'), 
		'422'   => '○', // ○
		'423'   => array('SA/japanesetea.gif', '[日本茶]'), 
		'424'   => array('SA/bread.gif', '[食パン]'), 
		'425'   => '[ソフトクリーム]', 
		'426'   => '[ポテト]', 
		'427'   => '[団子]', 
		'428'   => '[煎餅]', 
		'429'   => '[御飯]', 
		'430'   => '[スパゲティ]', 
		'431'   => '[カレー]', 
		'432'   => '[おでん]', 
		'433'   => '[寿司]', 
		'434'   => array('SA/apple.gif', '[林檎]'),  
		'435'   => '[蜜柑]', 
		'436'   => '[トマト]', 
		'437'   => '[茄子]', 
		'438'   => '[弁当]', 
		'439'   => '[鍋]', 
		'440'   => array('SA/confident.gif', "(-」-)"), // ふっ
		'441'   => array('SA/despair.gif', "(v_v)"), // しょんぼり
		'442'   => array('SA/happy02.gif', "^^.^^"), // 勝ち誇り
		'443'   => array('SA/sad.gif', "(x~x)"), // 失敗 
		'444'   => array('SA/think.gif', "(?_?)"), // わからん
		'445'   => array('SA/sleepy.gif', "(~_~)"), // 眠い
		'446'   => array('SA/lovely.gif', "(*_*)"), // てれてれ
		'447'   => array('SA/coldsweats02.gif', "(ToT)"), // 青ざめ
		'448'   => array('SA/bearing.gif', "(#□#)"), // 風邪ひき
		'449'   => array('SA/pout.gif', "(#_#)"), // 熱 
		'450'   => array('SA/gawk.gif', "(→_→)"), // 横目
		'451'   => array('SA/shock.gif', "(@_@)"), // びっくり
		'452'   =>  array('SA/coldsweats02.gif', "(:_;;"), //あせり
		'453'   => array('SA/notes.gif', '♪♯'), // 楽譜
		'454'   => array('SA/happy02.gif', "(^o^)"), // にこにこ
		'455'   => array('SA/lovely.gif', "(~3~)"), // チュー 2
		'456'   => array('SA/lovely.gif', "(^3^)"), // チュッ
		'457'   => '[鼻]', 
		'458'   => array('SA/kissmark.gif', '[口]'), 
		'459'   => '[ゴメン]', 
		'460'   => '[拍手]', 
		'461'   => array('SA/ok.gif', '[OK!]'), // 指で丸サイン
		'462'   => '[ブーイング]', // 親指を下
		'463'   => '[バイバイ]', 
		'464'   => array('SA/ng.gif', '[NG]'), 
		'465'   => array('SA/ok.gif', '[OK!]'), 
		'466'   => "m(__)m", // 平謝り 
		'467'   => '[カップル]', 
		'468'   => '[バニー]', 
		'469'   => '[トランペット]', 
		'470'   => '[ビリヤード]', 
		'471'   => '[水泳]', 
		'472'   => '[消防車]', 
		'473'   => '[救急車]', 
		'474'   => '[パトカー]', 
		'475'   => '[ローラーコースター]', 
		'476'   => '[門松]', 
		'477'   => '[雛祭り]', 
		'478'   => '[卒業式]', 
		'479'   => '[入学式]', 
		'480'   => '[鯉のぼり]', 
		'481'   => array('SA/sprinkle.gif', '[傘]'), 
		'482'   => '[花嫁]', 
		'483'   => '[かき氷]', 
		'484'   => '[線香花火]', 
		'485'   => '[巻き貝]', 
		'486'   => '[風鈴]', 
		'487'   => '[ハロウィン]', 
		'488'   => '[お月見]', 
		'489'   => '[サンタクロース]', 
		'490'   => array('SA/night.gif', '[夜]'), 
		'491'   => '[虹]', 
		'492'   => array('SA/hotel.gif', '[ラブホテル]'), 
		'493'   => array('SA/sun.gif', '[日の出]'), 
		'494'   => array('SA/drama.gif', '[シルクハット]'), 
		'495'   => array('SA/building.gif', '[デパート]'), 
		'496'   => '[天守閣]', 
		'497'   => '[城]', 
		'498'   => '[工場]', 
		'499'   => '[フランス]', 
		'500'   => '[オープンウェーブ]', 
		'501'   => array('SA/key.gif', '[ロック確認]'), 
		'502'   => '[ABC]', // 大文字
		'503'   => '[abc]', // 小文字
		'504'   => '[012]', // 数字
		'505'   => '[,@#]', // 記号
		'506'   => '[可]', 
		'507'   => 'レ', // チェックボックス
		'508'   => array('SA/pen.gif', '[エディット]'), 
		'509'   => '◎', // ラジオボタン
		'510'   => array('SA/search.gif', '[虫眼鏡]'), 
		'511'   => array('SA/enter.gif', '[←┘]'), 
		'512'   => array('SA/memo.gif', '[お気に入り]'), 
		'513'   => array('SA/end.gif', '[終了]'), 
		'514'   => array('SA/house.gif', '[ホーム]'), 
		'515'   => array('SA/mailto.gif', '[受信箱]'), 
		'516'   => '□', // マーク
		'517'   => array('SA/key.gif', '[個人用]'), 
		'518'   => array('SA/recycle.gif', '[再読み込み]'), 
		'700'   => '[ドイツ]', 
		'701'   => '[イタリア]', 
		'702'   => '[イギリス]', 
		'703'   => '[中国]', 
		'704'   => '[韓国]', 
		'705'   => '[白人]', 
		'706'   => '[中国人]', 
		'707'   => '[インド人]', 
		'708'   => '[おじいさん]', 
		'709'   => '[おばあさん]', 
		'710'   => '[赤ちゃん]', 
		'711'   => '[工事現場の人]', 
		'712'   => '[お姫様]', 
		'713'   => '[イルカ]', 
		'714'   => '[ダンス]', 
		'715'   => array('SA/fish.gif', '[熱帯魚]'), 
		'716'   => '[毛虫]', 
		'717'   => '[象]', 
		'718'   => '[コアラ]', 
		'719'   => '[ホルスタイン]', 
		'720'   => '[蛇]', 
		'721'   => array('SA/chick.gif', '[鶏]'), 
		'722'   => '[猪]', 
		'723'   => '[ラクダ]', 
		'724'   => '[A型]', 
		'725'   => '[B型]', 
		'726'   => '[O型]', 
		'727'   => '[AB型]', 
		'728'   => array('SA/foot.gif', '[足跡]'), 
		'729'   => array('SA/shoe.gif', '[スニーカー]'), 
		'730'   => array('SA/flag.gif', '[旗]'), 
		'731'   => array('SA/up.gif', '↑'), // 上向きカーブ矢印
		'732'   => array('SA/down.gif', '↓'), // 下向きカーブ矢印
		'733'   => array('SA/sign02.gif', '!?'), 
		'734'   => array('SA/sign03.gif', '!!'), 
		'735'   => array('SA/sign05.gif', '-o-'), // くるり
		'736'   => '[メロン]', 
		'737'   => '[パイナップル]', 
		'738'   => '[葡萄]', 
		'739'   => array('SA/banana.gif', '[バナナ]'),
		'740'   => '[とうもろこし]', 
		'741'   => '[キノコ]', 
		'742'   => '[栗]', 
		'743'   => '[桃]', 
		'744'   => '[焼き芋]', 
		'745'   => '[ピザ]', 
		'746'   => '[ターキー]', 
		'747'   => '[七夕笹飾り]', 
		'748'   => array('SA/wine.gif', '[トロピカルドリンク]'), 
		'749'   => '[辰]', 
		'750'   => '[ピアノ]', 
		'751'   => array('SA/wave.gif', '[サーフィン]'), 
		'752'   => '[釣り]', 
		'753'   => '[ボウリング]', 
		'754'   => '[なまはげ]', 
		'755'   => '[天狗]', 
		'756'   => '[パンダ]', 
		'757'   => array('SA/bleah.gif', ':-P'), // あかんべー (舌)
		'758'   => array('SA/pig.gif','[豚の鼻]'), 
		'759'   => array('SA/cute.gif', '[花]'), 
		'760'   => '[アイスクリーム]', 
		'761'   => '◎', // ドーナツ
		'762'   => '[クッキー]', 
		'763'   => '[チョコ]', 
		'764'   => '[包みキャンディ]', 
		'765'   => '[ぺろぺろキャンディ]', 
		'766'   => '(/_＼)', // 見ざる (猿)
		'767'   => '(・x・)', // 言わざる (猿)
		'768'   => '|(・_・)|', // 聞かざる (猿)
		'769'   => '[火山]', 
		'770'   => '[リボンがけのハート]', 
		'771'   => '[ABC]', 
		'772'   => '[プリン]', 
		'773'   => '[蜜蜂]', 
		'774'   => '[テントウムシ]', 
		'775'   => '[蜂蜜ポット]', 
		'776'   => array('SA/apple.gif', '[青林檎]'),
		'777'   => '[羽のはえたお札]', 
		'778'   => "(@_@)", // 目がまわる時の記号 
		'779'   => array('SA/pout.gif', "(`_`)"), // ぷー
		'780'   => array('SA/pout.gif', "(`_`)"), // ぷー (ネコ)
		'781'   => '[天の川]', 
		'782'   => array('SA/lovely.gif', "(^3^)"), // チュー (ネコ)
		'783'   => array('SA/happy02.gif', "(^-^)"), // にこ (ネコ)
		'784'   => array('SA/mail.gif', '[メールする]'), 
		'785'   => array('SA/happy02.gif', "(T^T)"), // 泣き笑い (ネコ)
		'786'   => array('SA/happy02.gif', "(T^T)"), // 泣き笑い
		'787'   => array('SA/lovely.gif', "(*_*)"), // 目がハート (ネコ)
		'788'   => array('SA/crying.gif', "('o')"), // ほえー (ネコ)
		'789'   => array('SA/crying.gif', "('o')"), // ほえー
		'790'   => array('SA/coldsweats01.gif', "(-_;)"), // 涙ぽろり 
		'791'   => array('SA/coldsweats01.gif', "(-_;)"), // 涙ぽろり (ネコ)
		'792'   => array('SA/gawk.gif', "(-v-)"), // きりり (ネコ) 
		'793'   => '[ドレス]', 
		'794'   => '[モヤイ像]', 
		'795'   => '[駅]', 
		'796'   => '[花札]', 
		'797'   => '[ジョーカー]', 
		'798'   => '[海老フライ]', 
		'799'   => array('SA/mail.gif', '[eメール]'), 
		'800'   => '[歩く人]', 
		'801'   => '[パトカーのランプ]', 
		'802'   => '[ezmovie]', 
		'803'   => array('SA/heart02.gif', '[ドキドキハート]'), 
		'804'   => array('SA/chick.gif', '[正面向きのひよこ]'), 
		'805'   => array('SA/denim.gif', '[ジーンズ]'), 
		'806'   => array('SA/loveletter.gif', '[ラブレター]'), 
		'807'   => array('SA/recycle.gif', '[循環矢印]'), 
		'808'   => array('SA/leftright.gif', '⇔'), 
		'809'   => array('SA/updown.gif', '↑↓'), 
		'810'   => array('SA/wave.gif', '[荒波]'), 
		'811'   => array('SA/bud.gif', '[双葉]'), 
		'812'   => array('SA/snail.gif', '[かたつむり]'), 
		'813'   => array('SA/smile.gif', "(^◇^)"), //うっしっし (ネコ)
		'814'   => array('SA/smile.gif', "(^◇^)"), //うっしっし 
		'815'   => array('SA/mail.gif', '[Cメール]'), 
		'816'   => array('SA/bud.gif', '[ハーブ]'), 
		'817'   => array('SA/rock.gif', '[グー]'), 
		'818'   => array('SA/sharp.gif', '#'), 
		'819'   => "!('')", // 挙手 (キャラ)
		'820'   => "!(^^)!", // 万歳 (キャラ)
		'821'   => "(v_v)", // しょんぼり (キャラ)
		'822'   => "(`^`)", // かわいく怒る (キャラ) 
		'se001' => "(^_^)", // 男の子
		'se002' => "(^^)", // 女の子
		'se003' => array('SA/kissmark.gif', '[キス]'), 
		'se004' => "('_')", // お父さん
		'se005' => "('')", // お母さん 
		'se006' => array('SA/t-shirt.gif', '[洋服]'), 
		'se007' => array('SA/shoe.gif', '[ブーツ]'), 
		'se008' => array('SA/camera.gif', '[カメラ]'),
		'se009' => array('SA/telephone.gif', '[電話]'), 
		'se00a' => array('SA/mobilephone.gif', '[携帯電話]'), 
		'se00b' => array('SA/faxto.gif', '[FAX]'), 
		'se00c' => array('SA/pc.gif', '[パソコン]'), 
		'se00d' => array('SA/punch.gif', '[パンチ]'), 
		'se00e' => array('SA/good.gif', '[GOOD!]'), 
		'se00f' => '[No.1!]', 
		'se010' => array('SA/rock.gif', '[グー]'), 
		'se011' => array('SA/scissors.gif', '[チョキ]'), 
		'se012' => array('SA/paper.gif', '[パー]'), 
		'se013' => array('SA/ski.gif', '[スキー]'), 
		'se014' => array('SA/golf.gif', '[ゴルフ]'), 
		'se015' => array('SA/tennis.gif', '[テニス]'), 
		'se016' => array('SA/baseball.gif', '[野球]'), 
		'se017' => array('SA/snowboard.gif', '[サーフィン]'), 
		'se018' => array('SA/soccer.gif', '[サッカー]'), 
		'se019' => array('SA/fish.gif', '[魚]'), 
		'se01a' => array('SA/horse.gif', '[馬]'), 
		'se01b' => array('SA/car.gif', '[車]'), 
		'se01c' => array('SA/yacht.gif', '[ヨット]'), 
		'se01d' => array('SA/airplane.gif', '[飛行機]'),
		'se01e' => array('SA/train.gif', '[電車]'), 
		'se01f' => array('SA/bullettrain.gif', '[新幹線]'), 
		'se020' => '?', 
		'se021' => array('SA/sign01.gif', '!'), 
		'se022' => array('SA/heart01.gif', '&hearts;'), // ハート
		'se023' => array('SA/heart03.gif', '[失恋]'), 
		'se024' => array('se024.png', '[1時]'), 
		'se025' => array('se025.png', '[2時]'), 
		'se026' => array('se026.png', '[時計]'), 
		'se027' => array('se027.png', '[4時]'), 
		'se028' => array('se028.png', '[5時]'), 
		'se029' => array('se029.png', '[6時]'), 
		'se02a' => array('se02a.png', '[7時]'), 
		'se02b' => array('se02b.png', '[8時]'), 
		'se02c' => array('se02c.png', '[9時]'), 
		'se02d' => array('se02d.png', '[10時]'), 
		'se02e' => array('se02e.png', '[11時]'), 
		'se02f' => array('se02f.png', '[12時]'), 
		'se030' => array('SA/cherryblossom.gif', '[お花見]'), 
		'se031' => array('SA/crown.gif', '[エンブレム]'), 
		'se032' => array('SA/cherryblossom.gif', '[バラ]'), 
		'se033' => array('SA/xmas.gif', '[クリスマス]'), 
		'se034' => array('SA/ring.gif', '[指輪]'), 
		'se035' => array('SA/ring.gif', '[宝石]'), 
		'se036' => array('SA/house.gif', '[家]'), 
		'se037' => array('SA/bell.gif', '[教会]'), 
		'se038' =>  array('SA/building.gif', '[ビル]'), 
		'se039' => '[駅]', 
		'se03a' => array('SA/gasstation.gif', '[ガソリンスタンド]'), 
		'se03b' => array('SA/fuji.gif', '[山]'), 
		'se03c' => array('SA/karaoke.gif', '[マイク]'), 
		'se03d' => array('SA/movie.gif', '[映画]'), 
		'se03e' => array('SA/note.gif', '[音楽]'), 
		'se03f' => array('SA/key.gif', '[カギ]'), 
		'se040' => '[サックス]', 
		'se041' => '[ギター]', 
		'se042' => '[トランペット]', 
		'se043' => array('SA/restaurant.gif', '[レストラン]'), 
		'se044' => array('SA/bar.gif', '[カクテル]'), 
		'se045' => array('SA/cafe.gif', '[コーヒー]'), 
		'se046' => array('SA/cake.gif', '[ケーキ]'), 
		'se047' => array('SA/beer.gif', '[ビールで乾杯]'), 
		'se048' => array('SA/snow.gif', '[雪]'), 
		'se049' => array('SA/cloud.gif', '[曇り]'), 
		'se04a' => array('SA/sun.gif',  '[晴れ]'), 
		'se04b' => array('SA/rain.gif',  '[雨]'), 
		'se04c' => array('SA/moon3.gif', '[月]'), 
		'se04d' => array('SA/sun.gif', '[朝]'), 
		'se04e' => '[天使]', 
		'se04f' => array('SA/cat.gif', '[猫]'), 
		'se050' => '[虎]', 
		'se051' => '[熊]', 
		'se052' => array('SA/dog.gif', '[犬]'), 
		'se053' => '[鼠]', 
		'se054' => '[鯨]', 
		'se055' => array('SA/penguin.gif', '[ペンギン]'), 
		'se056' => array('SA/happy01.gif', "(^_^)"), // 楽 (顔) 
		'se057' => array('SA/happy02.gif', "(^o^)"), // 喜 (顔)
		'se058' => array('SA/despair.gif', "(v_v)"), // 哀 (顔) 
		'se059' => array('SA/angry.gif', "(`_`)"), // 怒 (顔) 
		'se05a' => '[ウンチ]', 
		'se101' => array('SA/mailto.gif', '[メール受信]'), 
		'se102' => array('SA/mailto.gif', '[メール送信]'), 
		'se103' => array('SA/mail.gif', '[メール宛先]'), 
		'se104' => array('SA/phoneto.gif', '[電話連絡先]'), 
		'se105' => array('SA/bleah.gif', "(^i^)"), // アカンベー
		'se106' => array('SA/lovely.gif', "(*_*)"), // ラブラブ 
		'se107' => array('SA/shock.gif', "(@_@)"), // ガビーン
		'se108' => array('SA/coldsweats02.gif', "(-_-;"), // タラー
		'se109' => '[猿]', 
		'se10a' => '[蛸]', 
		'se10b' => array('SA/pig.gif', '[豚]'), 
		'se10c' => array('SA/shadow.gif', '[宇宙人]'), 
		'se10d' => '[ロケット]', 
		'se10e' => array('SA/crown.gif', '[王冠]'), 
		'se10f' => array('SA/flair.gif', '[電球]'), 
		'se110' => array('SA/clover.gif', '[四つ葉]'), 
		'se111' => array('SA/kissmark.gif', '[キス]'), 
		'se112' => array('SA/present.gif', '[プレゼント]'), 
		'se113' => '[ピストル]', 
		'se114' => array('SA/search.gif', '[虫眼鏡]'), 
		'se115' => array('SA/run.gif', '[陸上]'), 
		'se116' => '[オークション]', 
		'se117' => array('SA/shine.gif', '[花火]'), 
		'se118' => array('SA/maple.gif', '[もみじ]'), 
		'se119' => array('SA/maple.gif', '[落ち葉]'), 
		'se11a' => '[悪魔]', 
		'se11b' => '[お化け]', 
		'se11c' => '[ドクロ]', 
		'se11d' => '[ファイヤー]', 
		'se11e' => array('SA/bag.gif', '[かばん]'), 
		'se11f' => array('SA/chair.gif', '[座席]'), 
		'se120' => array('SA/fastfood.gif', '[ハンバーガー]'), 
		'se121' => '[公園]', 
		'se122' => '[キャンプ場]', 
		'se123' => array('SA/spa.gif', '[温泉]'), 
		'se124' => array('SA/carouselpony.gif', '[遊園地]'), 
		'se125' => array('SA/ticket.gif', '[チケット]'), 
		'se126' => array('SA/cd.gif', '[CD]'), 
		'se127' => array('SA/cd.gif', '[DVD]'), 
		'se128' => array('SA/music.gif', '[ラジオ]'), 
		'se129' => '[ビデオ]', 
		'se12a' => array('SA/tv.gif', '[テレビ]'), 
		'se12b' => array('SA/game.gif', '[ゲーム]'), 
		'se12c' => '&#12349;', // 歌い出し
		'se12d' => '[麻雀]', 
		'se12e' => '[VS]', 
		'se12f' => array('SA/dollar.gif', '($)'), // ドル袋 
		'se130' => '[当たり]', 
		'se131' => '[トロフィー]', 
		'se132' => array('SA/motorsports.gif', '[チェッカーフラッグ]'), 
		'se133' => '[スロット]', 
		'se134' => array('SA/horse.gif', '[競馬]'), 
		'se135' => array('SA/yacht.gif', '[競艇]'), 
		'se136' => array('SA/bicycle.gif', '[競輪]'), 
		'se137' => '[工事中]', 
		'se138' => '♂', // 男性
		'se139' => '♀', // 女性
		'se13a' => '[乳幼児]', 
		'se13b' => '[注射器]', 
		'se13c' => array('SA/sleepy.gif', 'Zzz'), 
		'se13d' => array('SA/thunder.gif', '[雷]'), 
		'se13e' => array('SA/boutique.gif','[ハイヒール]'), 
		'se13f' => array('SA/spa.gif', '[入浴]'), 
		'se140' => array('SA/toilet.gif', '[トイレ]'), 
		'se141' => '[音声]', 
		'se142' => '[お知らせ]', 
		'se143' => '[祝日]', 
		'se144' => array('SA/key.gif','[ロックON]'), 
		'se145' => array('SA/key.gif','[ロックOFF]'), 
		'se146' => '[街]', 
		'se147' => '[卵]', 
		'se148' => array('SA/book.gif', '[本]'), 
		'se149' => '[為替相場]', 
		'se14a' => '[株価]', 
		'se14b' => '[パラボラ]', 
		'se14c' => '[力こぶ]', 
		'se14d' => array('SA/bank.gif', '[銀行]'), 
		'se14e' => array('SA/signaler.gif', '[信号]'), 
		'se14f' => array('SA/parking.gif', '[駐車場]'), 
		'se150' => array('SA/bus.gif', '[バス停]'), 
		'se151' => array('SA/toilet.gif', '[公衆トイレ]'), 
		'se152' => '[交番]', 
		'se153' => array('SA/postoffice.gif', '〒'), 
		'se154' => array('SA/atm.gif', '[ATM]'), 
		'se155' => array('SA/hospital.gif', '[病院]'), 
		'se156' => array('SA/24hours.gif', '[コンビニ]'), 
		'se157' => array('SA/school.gif', '[学校]'), 
		'se158' => array('SA/hotel.gif', '[ホテル]'), 
		'se159' => array('SA/bus.gif', '[バス]'), 
		'se15a' => array('SA/car.gif', '[タクシー]'), 
		'se201' => array('SA/run.gif', '[徒歩]'), 
		'se202' => array('SA/ship.gif', '[船]'), 
		'se203' => '[ココ]', 
		'se204' => array('SA/cute.gif', '&#10070;'), // 飾罫1
		'se205' => array('SA/cute.gif', '&#10070;'), // 飾罫2 
		'se206' => array('SA/cute.gif', '&#10070;'), // 飾罫3
		'se207' => array('SA/ban.gif', '[18禁]'), 
		'se208' => array('SA/nosmoking.gif', '[禁煙]'), 
		'se209' => '[初心者]', 
		'se20a' => array('SA/wheelchair.gif', '[車椅子]'), 
		'se20b' => '[.i|]', // アンテナ
		'se20c' => array('SA/heart.gif', '&hearts;'), 
		'se20d' => array('SA/diamond.gif', '&diams;'), 
		'se20e' => array('SA/spade.gif', '&spades;'), 
		'se20f' => array('SA/club.gif', '&clubs;'), 
		'se210' => array('SA/sharp.gif', '#'), // シャープダイヤル
		'se211' => array('SA/free.gif', '[フリーダイヤル]'), 
		'se212' => array('SA/new.gif', '[新着]'), 
		'se213' => array('SA/shine.gif', '[更新]'), 
		'se214' => array('SA/eyeglass.gif', '[COOL]'), 
		'se215' => array('SA/yen.gif', '[有料]'), 
		'se216' => array('SA/free.gif', '[無料]'), 
		'se217' => '[月]', // 月額
		'se218' => '[申]', // 申し込み
		'se219' => '●', // 見出しボタン1
		'se21a' => '◎', // 見出しボタン2
		'se21b' => '◎', // 見出しボタン3
		'se21c' => array('SA/one.gif', '1'), 
		'se21d' => array('SA/two.gif', '2'), 
		'se21e' => array('SA/three.gif', '3'), 
		'se21f' => array('SA/four.gif', '4'), 
		'se220' => array('SA/five.gif', '5'), 
		'se221' => array('SA/six.gif', '6'), 
		'se222' => array('SA/seven.gif', '7'), 
		'se223' => array('SA/eight.gif', '8'), 
		'se224' => array('SA/nine.gif', '9'), 
		'se225' => array('SA/zero.gif', '0'), 
		'se226' => '[得]',  // お得
		'se227' => '[割]',  // 割引
		'se228' => '[サ]',  // サービス料
		'se229' => array('SA/id.gif', '[ID]'), 
		'se22a' => array('SA/full.gif', '[満席]'), 
		'se22b' => array('SA/empty.gif', '[空席]'), 
		'se22c' => '[指]', // 指定席
		'se22d' => '[営]', // 営業時間
		'se22e' => '↑', 
		'se22f' => '↓', 
		'se230' => '←', 
		'se231' => '→', 
		'se232' => '↑', 
		'se233' => '↓', 
		'se234' => '→', 
		'se235' => '←', 
		'se236' => array('SA/upwardright.gif', '[右上]'), 
		'se237' => array('SA/upwardleft.gif', '[左上]'), 
		'se238' => array('SA/downwardright.gif', '[右下]'), 
		'se239' => array('SA/downwardleft.gif', '[左下]'), 
		'se23a' => '＜', 
		'se23b' => '＞', 
		'se23c' => '≪', 
		'se23d' => '≫', 
		'se23e' => '[☆]', // 星座
		'se23f' => array('SA/aries.gif', '[牡羊座]'), 
		'se240' => array('SA/taurus.gif', '[牡牛座]'), 
		'se241' => array('SA/gemini.gif', '[双子座]'), 
		'se242' => array('SA/cancer.gif', '[蟹座]'),
		'se243' => array('SA/leo.gif', '[獅子座]'), 
		'se244' => array('SA/virgo.gif', '[乙女座]'),
		'se245' => array('SA/libra.gif', '[天秤座]'), 
		'se246' => array('SA/scorpius.gif', '[蠍座]'), 
		'se247' => array('SA/sagittarius.gif', '[射手座]'), 
		'se248' => array('SA/capricornus.gif', '[山羊座]'), 
		'se249' => array('SA/aquarius.gif', '[水瓶座]'), 
		'se24a' => array('SA/pisces.gif', '[魚座]'), 
		'se24b' => '[蛇遣座]', 
		'se24c' => '[TOP]', 
		'se24d' => array('SA/ok.gif', '[OK]'), 
		'se24e' => array('SA/copyright.gif', '&copy;'), 
		'se24f' => array('SA/r-mark.gif', '&reg;'), 
		'se250' => array('SA/mobilephone.gif', '[マナーモード]'), 
		'se251' => array('SA/mobilephone.gif', '[電源切]'), 
		'se252' => array('SA/danger.gif', '[!]'), 
		'se253' => array('SA/shadow.gif', '[ご案内]'), 
		'se254' => array('SA/building.gif', '[J-PHONEショップ]'), 
		'se255' => '[スカイウェブ]', 
		'se256' => array('SA/mail.gif', '[スカイウォーカー]'), 
		'se257' => '[スカイメロディ]', 
		'se258' => 'J-', 
		'se259' => 'Pho', 
		'se25a' => 'ne', 
		'se301' => array('SA/memo.gif', '[メモ]'), 
		'se302' => '[ネクタイ]', 
		'se303' => array('SA/cute.gif', '[ハイビスカス]'), 
		'se304' => array('SA/tulip.gif', '[チューリップ]'), 
		'se305' => '[向日葵]', 
		'se306' => '[花束]', 
		'se307' => '[椰子の木]', 
		'se308' => '[サボテン]', 
		'se309' => array('SA/toilet.gif', '[トイレ]'), 
		'se30a' => array('SA/music.gif', '[ヘッドホン]'), 
		'se30b' => array('SA/bottle.gif', '[徳利]'), 
		'se30c' => array('SA/beer.gif', '[ビール]'), 
		'se30d' => '[祝]', 
		'se30e' => array('SA/smoking.gif', '[喫煙]'), 
		'se30f' => '[カプセル薬]', 
		'se310' => '[風船]', 
		'se311' => array('SA/bomb.gif', '●〜'), // 爆弾
		'se312' => '[クラッカー]', 
		'se313' => array('SA/hairsalon.gif', '[鋏]'), 
		'se314' => array('SA/ribbon.gif', '[リボン]'), 
		'se315' => array('SA/secret.gif', '[秘]'), 
		'se316' => '[MD]', 
		'se317' => '[メガフォン]', 
		'se318' => '[帽子]', 
		'se319' => '[ワンピース]', 
		'se31a' => array('SA/boutique.gif', '[ミュール]'), 
		'se31b' => '[ロングブーツ]', 
		'se31c' => array('SA/rouge.gif', '[口紅]'), 
		'se31d' => '[マニキュア]', 
		'se31e' => '[エステ]', 
		'se31f' => '[美容院]', 
		'se320' => '[理髪店]', 
		'se321' => '[着物]', 
		'se322' => array('SA/sports.gif', '[ビキニ]'), 
		'se323' => array('SA/bag.gif', '[ブランドバッグ]'), 
		'se324' => array('SA/slate.gif', '[カチンコ]'), 
		'se325' => array('SA/bell.gif', '[鈴]'), 
		'se326' => array('SA/notes.gif', '&#9835;'), // 踊る音符 
		'se327' => array('SA/heart02.gif', '&hearts;'), // ぴかぴかハート
		'se328' => array('SA/heart02.gif', '&hearts;'), // ドキドキハート
		'se329' => array('SA/heart.gif', '→&hearts;'), // ハートを射とめて
		'se32a' => array('SA/heart.gif', '&hearts;'), // ハート (青)
		'se32b' => array('SA/heart.gif', '&hearts;'), // ハート (緑)
		'se32c' => array('SA/heart.gif', '&hearts;'), // ハート (黄)
		'se32d' => array('SA/heart.gif', '&hearts;'), // ハート (紫)
		'se32e' => array('SA/shine.gif', '◇'), // ぴかぴか
		'se32f' => '☆', 
		'se330' => array('SA/dash.gif', '=3'), // ダッシュ, 
		'se331' => array('SA/sweat01.gif', ';;'), // 飛び散る汗  
		'se332' => '○', 
		'se333' => '×', 
		'se334' => array('SA/annoy.gif', '[ムカッ]'), 
		'se335' => '☆', 
		'se336' => '?', 
		'se337' => array('SA/sign01.gif', '!'), 
		'se338' => array('SA/japanesetea.gif', '[お茶]'), 
		'se339' => array('SA/bread.gif', '[パン]'), 
		'se33a' => '[ソフトクリーム]', 
		'se33b' => '[フライドポテト]', 
		'se33c' => '[串団子]', 
		'se33d' => '[煎餅]', 
		'se33e' => '[ご飯]', 
		'se33f' => '[スパゲティ]', 
		'se340' => array('SA/noodle.gif', '[ラーメン]'), 
		'se341' => '[カレーライス]', 
		'se342' => array('SA/riceball.gif', '[おにぎり]'), 
		'se343' => '[おでん]', 
		'se344' => '[寿司]', 
		'se345' => array('SA/apple.gif', '[林檎]'), 
		'se346' => '[蜜柑]', 
		'se347' => '[苺]', 
		'se348' => '[西瓜]', 
		'se349' => '[トマト]', 
		'se34a' => '[茄子]', 
		'se34b' => array('SA/birthday.gif', '[バースデーケーキ]'), 
		'se34c' => '[駅弁]', 
		'se34d' => '[鍋]', 
		'se401' => array('SA/coldsweats02.gif', "(:_;;"), // あせり
		'se402' => array('SA/catface.gif', "(- 」-)"), // ほそくんでいる
		'se403' => array('SA/think.gif', "m(__)m"), // ひたすらごめんなさい
		'se404' => array('SA/think.gif', "^^.^^"), // えっへん
		'se405' => array('SA/wink.gif', "('_-)"), // ウィンク
		'se406' => array('SA/bearing.gif', "(x_x)"), // わさびがきいた
		'se407' => array('SA/sad.gif', "(@_@)"), // 目がぐるぐる
		'se408' => array('SA/sleepy.gif', "(zzz)"), // 鼻ちょうちん
		'se409' => array('SA/bleah.gif', "(^j^)"), // あっかんべー
		'se40a' => array('SA/confident.gif', "(*_*)"), // 酔っ払い
		'se40b' => array('SA/shock.gif', "(ToT)"), // ゲロゲロ
		'se40c' => "(#□#)", // マスクをつけた顔
		'se40d' => array('SA/happy02.gif',"(#_#)"), // 顔があかくなる
		'se40e' => array('SA/gawk.gif', "(→_→)"), // しらけ顔
		'se40f' => array('SA/coldsweats02.gif', "('_';"), // 冷や汗
		'se410' => array('SA/wobbly.gif', "(@_@)"), // びっくり
		'se411' => array('SA/crying.gif', "(T_T)"), // 泣き
		'se412' => array('SA/crying.gif', "(T^T)"), // 泣いたり笑ったり
		'se413' => array('SA/weep.gif', "(;_;)"), // 可愛く泣いてる人
		'se414' => array('SA/smile.gif', "(^.^)"), // にこにこ
		'se415' => array('SA/happy01.gif', "(^o^)"), // あははと笑う
		'se416' => array('SA/pout.gif', "(`^`)"), // めちゃめちゃ怒っている
		'se417' => array('SA/lovely.gif', "(~3~)"), // KISS
		'se418' => array('SA/lovely.gif', "(^3-)"), // 投げKISS
		'se419' => array('SA/eye.gif', '[目]'), 
		'se41a' => '[鼻]', 
		'se41b' => array('SA/ear.gif', '[耳]'), 
		'se41c' => array('SA/kissmark.gif', '[口]'), 
		'se41d' => '[ゴメン]', // ごめんなさい (合わせた手)
		'se41e' => array('SA/paper.gif', '[バイバイ]'), 
		'se41f' => array('SA/paper.gif', '[拍手]'), 
		'se420' => array('SA/ok.gif', '[OK]'), // 指で丸サイン
		'se421' => array('SA/down.gif', '[ブーイング]'), // 親指を下
		'se422' => array('SA/paper.gif', '[おっは〜]'), 
		'se423' => array('SA/ng.gif', '×'), 
		'se424' => array('SA/ok.gif', '○'), 
		'se425' => '[手を繋ぐ]', 
		'se426' => "m(__)m", // 土下座
		'se427' => '＼(^o^)／', // バンザイ
		'se428' => '[人と人]', 
		'se429' => '[バニー]', 
		'se42a' => array('SA/basketball.gif', '[バスケットボール]'),  
		'se42b' => '[アメフト]', 
		'se42c' => '[ビリヤード]', 
		'se42d' => array('SA/wave.gif', '[水泳]'), 
		'se42e' => array('SA/rvcar.gif', '[RV車]'), 
		'se42f' => '[トラック]', 
		'se430' => '[消防車]', 
		'se431' => '[救急車]', 
		'se432' => '[パトカー]', 
		'se433' => '[ローラーコースター]', 
		'se434' => array('SA/subway.gif', '[地下鉄]'), 
		'se435' => array('SA/bullettrain.gif', '[新幹線]'), 
		'se436' => '[門松]', 
		'se437' => '[チョコ]', 
		'se438' => '[お雛様]', 
		'se439' => '[卒業式]', 
		'se43a' => '[ランドセル]', 
		'se43b' => '[鯉のぼり]', 
		'se43c' => array('SA/sprinkle.gif', '[閉じ傘]'), 
		'se43d' => '[結婚式]', 
		'se43e' => array('SA/wave.gif', '[波がザーン]'), 
		'se43f' => '[かき氷]', 
		'se440' => '[線香花火]', 
		'se441' => '[貝]', 
		'se442' => '[風鈴]', 
		'se443' => array('SA/typhoon.gif', '[台風]'), 
		'se444' => '[稲穂]', 
		'se445' => '[ハロウィン]', 
		'se446' => array('SA/fullmoon.gif', '[お月見]'), 
		'se447' => array('SA/maple.gif', '[風がビュー]'), 
		'se448' => '[サンタクロース]', 
		'se449' => array('SA/sun.gif', '[朝日]'), 
		'se44a' => array('SA/sun.gif', '[夕日]'), 
		'se44b' => array('SA/night.gif',  '[流れ星]'), 
		'se44c' => '[虹]', 
		'se501' => array('SA/hotel.gif', '[ラブホテル]'), 
		'se502' => array('SA/art.gif', '[アート]'), 
		'se503' => array('SA/drama.gif', '[演劇]'), 
		'se504' => array('SA/building.gif', '[デパート]'), 
		'se505' => '[天守閣]', 
		'se506' => '[城]', 
		'se507' => array('SA/movie.gif', '[映画館]'), 
		'se508' => array('SA/building.gif', '[工場]'), 
		'se509' => '[東京タワー]', 
		'se50a' => array('SA/building.gif', '[109]'), 
		'se50b' => array('237.png', '[日本]'), 
		'se50c' => array('90.png', '[アメリカ]'), 
		'se50d' => '[フランス]', 
		'se50e' => '[ドイツ]', 
		'se50f' => '[イタリア]', 
		'se510' => '[イギリス]', 
		'se511' => '[スペイン]', 
		'se512' => '[ロシア]', 
		'se513' => '[中国]', 
		'se514' => '[韓国]', 
		'se515' => '[白人]', 
		'se516' => '[中国人]', 
		'se517' => '[インド人]', 
		'se518' => '[おじいちゃん]', 
		'se519' => '[おばあちゃん]', 
		'se51a' => '[赤ちゃん]', 
		'se51b' => '[安全第一]', 
		'se51c' => '[お姫さま]', 
		'se51d' => '[自由の女神]', 
		'se51e' => '[衛兵]', 
		'se51f' => '[フラメンコ]', 
		'se520' => array('SA/fish.gif', '[イルカ]'), 
		'se521' => array('SA/chick.gif', '[鳥]'), 
		'se522' => array('SA/fish.gif', '[熱帯魚]'), 
		'se523' => array('SA/chick.gif', '[ひよこ]'), 
		'se524' => '[ハムスター]', 
		'se525' => '[毛虫]', 
		'se526' => '[象]', 
		'se527' => '[コアラ]', 
		'se528' => '[チンパンジー]', 
		'se529' => '[羊]', 
		'se52a' => array('SA/dog.gif', '[狼]'), 
		'se52b' => '[牛]', 
		'se52c' => '[兎]', 
		'se52d' => '[蛇]', 
		'se52e' => array('SA/chick.gif', '[鶏]'), 
		'se52f' => '[猪]', 
		'se530' => '[ラクダ]', 
		'se531' => '[蛙]', 
		'se532' => '[A型]', 
		'se533' => '[B型]', 
		'se534' => '[AB型]', 
		'se535' => '[O型]', 
		'se536' => array('SA/foot.gif', '[足跡]'), 
		'se537' => array('SA/tm.gif', '&trade;'), 
		'se538' => 'J-', 
		'se539' => 'Sky', 
		'se53a' => '“', 
		'se53b' => '”', 
		'se53c' => '◎V', 
		'se53d' => 'odaf', 
		'se53e' => 'one', 
		'd001'  => array('SA/sun.gif', '[晴れ]'), 
		'd002'  => array('SA/cloud.gif', '[曇り]'), 
		'd003'  => array('SA/rain.gif', '[雨]'), 
		'd004'  => array('SA/snow.gif', '[雪]'), 
		'd005'  => array('SA/thunder.gif', '[雷]'), 
		'd006'  => array('SA/typhoon.gif', '[台風]'), 
		'd007'  => array('SA/mist.gif', '[霧]'), 
		'd008'  => array('SA/sprinkle.gif', '[小雨]'), 
		'd009'  => array('SA/aries.gif', '[牡羊座]'),
		'd010'  => array('SA/taurus.gif', '[牡牛座]'), 
		'd011'  => array('SA/gemini.gif', '[双子座]'), 
		'd012'  => array('SA/cancer.gif', '[蟹座]'),
		'd013'  => array('SA/leo.gif', '[獅子座]'), 
		'd014'  => array('SA/virgo.gif', '[乙女座]'), 
		'd015'  => array('SA/libra.gif', '[天秤座]'), 
		'd016'  => array('SA/scorpius.gif', '[蠍座]'), 
		'd017'  => array('SA/sagittarius.gif', '[射手座]'), 
		'd018'  => array('SA/capricornus.gif', '[山羊座]'),
		'd019'  => array('SA/aquarius.gif', '[水瓶座]'), 
		'd020'  => array('SA/pisces.gif', '[魚座]'), 
		'd021'  => array('SA/sports.gif', '[スポーツウェア]'), 
		'd022'  => array('SA/baseball.gif', '[野球]'), 
		'd023'  => array('SA/golf.gif', '[ゴルフ]'), 
		'd024'  => array('SA/tennis.gif', '[テニス]'), 
		'd025'  => array('SA/soccer.gif', '[サッカー]'), 
		'd026'  => array('SA/ski.gif', '[スキー]'), 
		'd027'  => array('SA/basketball.gif', '[バスケットボール]'), 
		'd028'  => array('SA/motorsports.gif', '[チェッカーフラッグ]'), 
		'd029'  => array('SA/pocketbell.gif', '[ページャー]'), 
		'd030'  => array('SA/train.gif', '[電車]'), 
		'd031'  => array('SA/subway.gif', '[地下鉄]'), 
		'd032'  => array('SA/bullettrain.gif', '[新幹線]'), 
		'd033'  => array('SA/car.gif', '[車]'), 
		'd034'  => array('SA/rvcar.gif', '[RV車]'), 
		'd035'  => array('SA/bus.gif', '[バス]'), 
		'd036'  => array('SA/ship.gif', '[船]'), 
		'd037'  => array('SA/airplane.gif', '[飛行機]'), 
		'd038'  => array('SA/house.gif', '[家]'), 
		'd039'  => array('SA/building.gif', '[ビル]'), 
		'd040'  => array('SA/postoffice.gif', '〒'), 
		'd041'  => array('SA/hospital.gif', '[病院]'), 
		'd042'  => array('SA/bank.gif', '[銀行]'), 
		'd043'  => array('SA/atm.gif', '[ATM]'),
		'd044'  => array('SA/hotel.gif', '[ホテル]'), 
		'd045'  => array('SA/24hours.gif', '[コンビニ]'), 
		'd046'  => array('SA/gasstation.gif', '[ガソリンスタンド]'), 
		'd047'  => array('SA/parking.gif', '[駐車場]'), 
		'd048'  => array('SA/signaler.gif', '[信号]'), 
		'd049'  => array('SA/toilet.gif', '[トイレ]'), 
		'd050'  => array('SA/restaurant.gif', '[レストラン]'), 
		'd051'  => array('SA/cafe.gif', '[喫茶店]'), 
		'd052'  => array('SA/bar.gif', '[マティーニ]'), 
		'd053'  => array('SA/beer.gif', '[ビールで乾杯]'),
		'd054'  => array('SA/fastfood.gif', '[ハンバーガー]'), 
		'd055'  => array('SA/boutique.gif', '[ブティック]'), 
		'd056'  => array('SA/hairsalon.gif', '[鋏]'), // 美容院
		'd057'  => array('SA/karaoke.gif', '[マイク]'), // カラオケ
		'd058'  => array('SA/movie.gif', '[映画]'), 
		'd059'  => array('SA/upwardright.gif', '[右斜め上]'), 
		'd060'  => array('SA/carouselpony.gif', '[木馬]'), 
		'd061'  => array('SA/music.gif', '[ヘッドホン]'), 
		'd062'  => array('SA/art.gif', '[アート]'), 
		'd063'  => array('SA/drama.gif', '[演劇]'), 
		'd064'  => array('SA/event.gif', '[イベント]'), 
		'd065'  => array('SA/ticket.gif', '[チケット]'), 
		'd066'  => array('SA/smoking.gif', '[喫煙]'), 
		'd067'  => array('SA/nosmoking.gif', '[禁煙]'), 
		'd068'  => array('SA/camera.gif', '[カメラ]'), 
		'd069'  => array('SA/bag.gif', '[カバン]'), 
		'd070'  => array('SA/book.gif', '[本]'), 
		'd071'  => array('SA/ribbon.gif', '[リボン]'), 
		'd072'  => array('SA/present.gif', '[プレゼント]'), 
		'd073'  => array('SA/birthday.gif', '[バースデー]'), 
		'd074'  => array('SA/telephone.gif', '[電話]'), 
		'd075'  => array('SA/mobilephone.gif', '[携帯電話]'), 
		'd076'  => array('SA/memo.gif', '[メモ]'), 
		'd077'  => array('SA/tv.gif', '[TV]'), 
		'd078'  => array('SA/game.gif', '[ゲーム]'), 
		'd079'  => array('SA/cd.gif', '[CD]'), 
		'd080'  => array('SA/heart.gif', '&hearts;'), 
		'd081'  => array('SA/spade.gif', '&spades;'), 
		'd082'  => array('SA/diamond.gif', '&diams;'), 
		'd083'  => array('SA/club.gif', '&clubs;'), 
		'd084'  => array('SA/eye.gif', '[目]'), 
		'd085'  => array('SA/ear.gif', '[耳]'), 
		'd086'  => array('SA/rock.gif', '[グー]'), 
		'd087'  => array('SA/scissors.gif', '[チョキ]'), 
		'd088'  => array('SA/paper.gif', '[パー]'), 
		'd089'  => array('SA/downwardright.gif', '[右斜め下]'), 
		'd090'  => array('SA/upwardleft.gif', '[左斜め上]'), 
		'd091'  => array('SA/foot.gif', '[足跡]'), 
		'd092'  => array('SA/shoe.gif', '[くつ]'), 
		'd093'  => array('SA/eyeglass.gif', '[眼鏡]'), 
		'd094'  => array('SA/wheelchair.gif', '[車椅子]'), 
		'd095'  => array('SA/newmoon.gif', '●'), // 新月
		'd096'  => array('SA/moon1.gif', '[やや欠け月]'), 
		'd097'  => array('SA/moon2.gif', '[半月]'), 
		'd098'  => array('SA/moon3.gif', '[三日月]'), 
		'd099'  => array('SA/fullmoon.gif', '○'), // 満月
		'd100'  => array('SA/dog.gif', '[犬]'), 
		'd101'  => array('SA/cat.gif', '[猫]'),
		'd102'  => array('SA/yacht.gif', '[ヨット]'), 
		'd103'  => array('SA/xmas.gif', '[クリスマス]'), 
		'd104'  => array('SA/downwardleft.gif', '[左斜め下]'), 
		'd105'  => array('SA/phoneto.gif', '[phone]'), 
		'd106'  => array('SA/mailto.gif', '[mail]'), 
		'd107'  => array('SA/faxto.gif', '[fax]'), 
		'd108'  => array('SA/info01.gif', '[i]'), 
		'd109'  => array('SA/info02.gif', '[i]'), 
		'd110'  => array('SA/mail.gif', '[メール]'), 
		'd111'  => array('SA/by-d.gif', '[ドコモ提供]'), 
		'd112'  => array('SA/d-point.gif', '[ドコモポイント]'), 
		'd113'  => array('SA/yen.gif', '[有料]'), 
		'd114'  => array('SA/free.gif', '[無料]'), 
		'd115'  => array('SA/id.gif', '[ID]'), 
		'd116'  => array('SA/key.gif', '[パスワード]'), 
		'd117'  => array('SA/enter.gif', '←┘'), // 次項有
		'd118'  => array('SA/clear.gif', '[CL]'), 
		'd119'  => array('SA/search.gif', '[虫眼鏡]'), 
		'd120'  => array('SA/new.gif', '[NEW]'), 
		'd121'  => array('SA/flag.gif', '[位置情報]'), 
		'd122'  => array('SA/free.gif', '[FREE]'), 
		'd123'  => array('SA/sharp.gif', '#'), 
		'd124'  => array('SA/mobaq.gif', 'Q'), 
		'd125'  => array('SA/one.gif', '1'), 
		'd126'  => array('SA/two.gif', '2'), 
		'd127'  => array('SA/three.gif', '3'), 
		'd128'  => array('SA/four.gif', '4'), 
		'd129'  => array('SA/five.gif', '5'), 
		'd130'  => array('SA/six.gif', '6'), 
		'd131'  => array('SA/seven.gif', '7'), 
		'd132'  => array('SA/eight.gif', '8'), 
		'd133'  => array('SA/nine.gif', '9'), 
		'd134'  => array('SA/zero.gif', '0'), 
		'd135'  => array('SA/ok.gif', '[OK]'), 
		'd136'  => array('SA/heart01.gif', '&hearts;'), // 黒ハート
		'd137'  => array('SA/heart02.gif', '&hearts;'), // 揺れるハート
		'd138'  => array('SA/heart03.gif', '[失恋]'), 
		'd139'  => array('SA/heart04.gif', '&hearts;&hearts;'), // ハートたち (複数ハート)
		'd140'  => array('SA/happy01.gif', "(^_^)"), // わーい (嬉しい顔)
		'd141'  => array('SA/angry.gif', "(`_`)"), // ちっ (怒った顔)
		'd142'  => array('SA/despair.gif', "(v_v)"), // がく〜 (落胆した顔)
		'd143'  => array('SA/sad.gif', "(;_;)"), // もうやだ〜 (悲しい顔)
		'd144'  => array('SA/wobbly.gif', "(x_x)"), // ふらふら
		'd145'  => array('SA/up.gif', '↑'),    // グッド (上向き矢印)
		'd146'  => array('SA/note.gif', '♪'),    // るんるん (音符)
		'd147'  => array('SA/spa.gif', '[温泉]'), 
		'd148'  => array('SA/cute.gif', '[花]'), // かわいい 
		'd149'  => array('SA/kissmark.gif', '[キスマーク]'), 
		'd150'  => array('SA/shine.gif', '◇'), // ぴかぴか
		'd151'  => array('SA/flair.gif', '[ひらめき]'), 
		'd152'  => array('SA/annoy.gif', '[むかっ]'), 
		'd153'  => array('SA/punch.gif', '[パンチ]'), 
		'd154'  => array('SA/bomb.gif', '●〜'), // 爆弾
		'd155'  => array('SA/notes.gif', '♪♪'), // ムード
		'd156'  => array('SA/down.gif', '↓'),   // バッド (下向き矢印)
		'd157'  => array('SA/sleepy.gif', 'Zzz'),  // 眠い (睡眠)
		'd158'  => array('SA/sign01.gif', '!'), 
		'd159'  => array('SA/sign02.gif', '!?'), 
		'd160'  => array('SA/sign03.gif', '!!'), 
		'd161'  => array('SA/impact.gif', 'Σ3'), // どんっ 
		'd162'  => array('SA/sweat01.gif', ';;'), // あせあせ 
		'd163'  => array('SA/sweat02.gif', '[たらーっ]'), 
		'd164'  => array('SA/dash.gif', '=3'), // ダッシュ 
		'd165'  => array('SA/sign04.gif', '&#12336;'), // 長音記号1
		'd166'  => array('SA/sign05.gif', '-o-'), // 長音記号2
		'd167'  => array('SA/slate.gif', '[カチンコ]'), 
		'd168'  => array('SA/pouch.gif', '[ふくろ]'), 
		'd169'  => array('SA/pen.gif', '[ペン]'), 
		'd170'  => array('SA/shadow.gif', '[人影]'), 
		'd171'  => array('SA/chair.gif', '[いす]'), 
		'd172'  => array('SA/night.gif', '[夜]'), 
		'd173'  => array('SA/soon.gif', '→SOON'), 
		'd174'  => array('SA/on.gif', '←ON→'), 
		'd175'  => array('SA/end.gif', 'END←'), 
		'd176'  => array('SA/clock.gif', '[時計]'), 
		'd201'  => array('SA/appli01.gif', 'α'), 
		'd202'  => array('SA/appli02.gif', '[α]'), 
		'd203'  => array('SA/t-shirt.gif', '[Tシャツ]'), 
		'd204'  => array('SA/moneybag.gif', '[がま口財布]'), 
		'd205'  => array('SA/rouge.gif', '[化粧]'), 
		'd206'  => array('SA/denim.gif', '[ジーンズ]'), 
		'd207'  => array('SA/snowboard.gif', '[スノボ]'), 
		'd208'  => array('SA/bell.gif', '[チャペル]'), 
		'd209'  => array('SA/door.gif', '[ドア]'), 
		'd210'  => array('SA/dollar.gif', '($)'), // ドル袋
		'd211'  => array('SA/pc.gif', '[パソコン]'), 
		'd212'  => array('SA/loveletter.gif', '[ラブレター]'), 
		'd213'  => array('SA/wrench.gif', '[レンチ]'), 
		'd214'  => array('SA/pencil.gif', '[鉛筆]'), 
		'd215'  => array('SA/crown.gif', '[王冠]'), 
		'd216'  => array('SA/ring.gif', '[指輪]'), 
		'd217'  => array('SA/sandclock.gif', '[砂時計]'), 
		'd218'  => array('SA/bicycle.gif', '[自転車]'), 
		'd219'  => array('SA/japanesetea.gif', '[湯のみ]'), 
		'd220'  => array('SA/watch.gif', '[腕時計]'), 
		'd221'  => array('SA/think.gif', "(?_?)"), // 考えてる顔
		'd222'  => array('SA/confident.gif', "(-.-)"), // ほっとした顔
		'd223'  => array('SA/coldsweats01.gif', "(^_^;"), // 冷や汗
		'd224'  => array('SA/coldsweats02.gif', "('_';"), // 冷や汗
		'd225'  => array('SA/pout.gif', "(`^`)"), // ぷっくっくな顔
		'd226'  => array('SA/gawk.gif', "(-_-)"), // ボケーっとした顔
		'd227'  => array('SA/lovely.gif', "(*_*)"), // 目がハート
		'd228'  => array('SA/good.gif', '[OK]'), // 指でOK
		'd229'  => array('SA/bleah.gif', "(^j^)"), // あっかんべー
		'd230'  => array('SA/wink.gif', "('_-)"), // ウィンク
		'd231'  => array('SA/happy02.gif', "(^o^)"), // うれしい顔
		'd232'  => array('SA/bearing.gif', "(x_x)"), // がまん顔
		'd233'  => array('SA/catface.gif', "(=ω=)"), // 猫顔
		'd234'  => array('SA/crying.gif', "(T_T)"), // 泣き顔
		'd235'  => array('SA/weep.gif', '(;_;)'), // 涙 
		'd236'  => array('SA/ng.gif', '[NG]'), 
		'd237'  => array('SA/clip.gif', '[クリップ]'), 
		'd238'  => array('SA/copyright.gif', '&copy;'), 
		'd239'  => array('SA/tm.gif', '&trade;'), 
		'd240'  => array('SA/run.gif', '[走る人]'), 
		'd241'  => array('SA/secret.gif', '[秘]'), 
		'd242'  => array('SA/recycle.gif', '[リサイクル]'), 
		'd243'  => array('SA/r-mark.gif', '&reg;'), 
		'd244'  => array('SA/danger.gif', '[!]'), 
		'd245'  => array('SA/ban.gif', '[禁止]'), 
		'd246'  => array('SA/empty.gif', '[空席]'), 
		'd247'  => array('SA/pass.gif', '[合格]'), 
		'd248'  => array('SA/full.gif', '[満席]'), 
		'd249'  => array('SA/leftright.gif', '⇔'), 
		'd250'  => array('SA/updown.gif', '↑↓'), 
		'd251'  => array('SA/school.gif', '[学校]'), 
		'd252'  => array('SA/wave.gif', '[波]'), 
		'd253'  => array('SA/fuji.gif', '[富士山]'), 
		'd254'  => array('SA/clover.gif', '[クローバー]'), 
		'd255'  => array('SA/cherry.gif', '[さくらんぼ]'), 
		'd256'  => array('SA/tulip.gif', '[チューリップ]'), 
		'd257'  => array('SA/banana.gif', '[バナナ]'), 
		'd258'  => array('SA/apple.gif', '[林檎]'),
		'd259'  => array('SA/bud.gif', '[芽]'), 
		'd260'  => array('SA/maple.gif', '[もみじ]'), 
		'd261'  => array('SA/cherryblossom.gif', '[桜]'), 
		'd262'  => array('SA/riceball.gif', '[おにぎり]'), 
		'd263'  => array('SA/cake.gif', '[ショートケーキ]'), 
		'd264'  => array('SA/bottle.gif', '[とっくり]'), 
		'd265'  => array('SA/noodle.gif', '[どんぶり]'), 
		'd266'  => array('SA/bread.gif', '[パン]'), 
		'd267'  => array('SA/snail.gif', '[かたつむり]'), 
		'd268'  => array('SA/chick.gif', '[ひよこ]'), 
		'd269'  => array('SA/penguin.gif', '[ペンギン]'), 
		'd270'  => array('SA/fish.gif', '[魚]'), 
		'd271'  => array('SA/delicious.gif', "(^+^)"), // うまい! 
		'd272'  => array('SA/smile.gif', "(^◇^)"), //ウッシッシ 
		'd273'  => array('SA/horse.gif', '[馬]'), 
		'd274'  => array('SA/pig.gif', '[豚]'), 
		'd275'  => array('SA/wine.gif', '[ワイングラス]'), 
		'd276'  => array('SA/shock.gif', ")@_@("), // げっそり
 		'e253'  => '[人差し指]', 
		'e254'  => '[カップル]', 
		'e255'  => '[初心者]', 
		'e256'  => '[ギター]', 
		'e257'  => '[株価]', 
		'e258'  => '[18歳]', 
		'e259'  => '[麻雀]', 
		'e260'  => '[コミュニティ]', 
		'e261'  => '[ヒット]', 
		'e262'  => '[新聞]', 
		'e263'  => '[サンタクロース]', 
		'e264'  => '[家族]', 
		'e265'  => '　', // [未使用]
		'e266'  => '[コイン]', 
		'e267'  => '　', // [未使用]
		'e268'  => '　', // [未使用]
		'e269'  => 'EM', 
		'e270'  => 'EM', 
		'e271'  => '　', // [未使用]
		'e272'  => '　', // [未使用]
		'e273'  => '　', // [未使用]
		'e274'  => '　', // [未使用]
		'e275'  => '　', // [未使用]
		'e276'  => '　', // [未使用]
		'e277'  => '　', // [未使用]
		'e278'  => '　', // [未使用]
		'wf040' => array('SA/heart.gif', '&hearts;'), 
		'wf041' => array('SA/clock.gif', '[掛時計]'), 
		'wf042' => array('SA/telephone.gif', '[電話]'), 
		'wf043' => '[マグカップ]', 
		'wf044' => '*', // アスタリスク
		'wf045' => array('SA/updown.gif', '↑↓'), 
		'wf046' => array('SA/leftright.gif', '⇔'), 
		'wf047' => '(((', 
		'wf048' => array('SA/sun.gif', '[晴れ]'), 
		'wf049' => array('SA/rain.gif', '[雨]'), 
		'wf04a' => array('SA/cloud.gif', '[曇り]'), 
		'wf04b' => '[涙]', 
		'wf04c' => "(^_^)", // 笑顔 
		'wf04d' => array('SA/kissmark.gif', '[口]'), 
		'wf04e' => '&#9835;', // メロディ (十六分音符)
		'wf04f' => array('SA/car.gif', '[車]'), 
		'wf050' => array('SA/train.gif', '[電車]'), 
		'wf051' => array('SA/airplane.gif', '[飛行機]'), 
		'wf052' => '[バイク]', 
		'wf053' => array('SA/house.gif', '[家]'), 
		'wf054' => array('SA/building.gif', '[ビル]'), 
		'wf055' => array('SA/scissors.gif', '[ピース]'), 
		'wf056' => array('SA/restaurant.gif', '[ディナー]'), 
		'wf057' => array('SA/bar.gif', '[グラス]'), 
		'wf058' => "(;_;)", // 泣く (顔)
		'wf059' => array('SA/tulip.gif', '[チューリップ]'), 
		'wf05a' => '[バット]', 
		'wf05b' => array('SA/baseball.gif', '[ボール]'), 
		'wf05c' => array('SA/rock.gif', '[パンチ]'), 
		'wf05d' => array('SA/thunder.gif', '[雷]'), 
		'wf05e' => array('SA/angry.gif', "(`_`)"), //怒った顔
		'wf05f' => array('SA/beer.gif', '[ビール]'), 
		'wf060' =>  array('SA/memo.gif', '[ノート]'), 
		'wf061' => array('SA/pencil.gif', '[ペン]'), 
		'wf062' => '[ドクロ]', 
		'wf063' => array('SA/mail.gif', '[メール]'), 
		'wf064' => array('SA/nosmoking.gif', '[禁煙]'), 
		'wf065' => array('SA/bell.gif', '[ベル]'), 
		'wf066' => '[花]', 
		'wf067' => array('SA/spa.gif', '[温泉]'), 
		'wf068' => '[月]', // 三日月の反対向き
		'wf069' => '[旗]', 
		'wf06a' => '[マスク顔]', 
		'wf06b' => '[レコード]', 
		'wf06c' => '[肉]', 
		'wf06d' => array('SA/dog.gif', '[犬]'), 
		'wf06e' => array('SA/cat.gif', '[猫]'), 
		'wf06f' => '[猿]', 
		'wf070' => '[象]', 
		'wf071' => '[蛸]', 
		'wf072' => array('SA/noodle.gif', '[ラーメン]'), 
		'wf073' => array('SA/karaoke.gif', '[マイク]'), 
		'wf074' => '[むかっ]', 
		'wf075' => array('SA/chick.gif', '[ひよこ]'), 
		'wf076' => "(~_~)", // 眠り顔
		'wf077' => array('SA/cafe.gif', '[コーヒー]'), 
		'wf078' => array('SA/smoking.gif', '[タバコ]'), 
		'wf079' => array('SA/sweat01.gif', ';;'), // 飛び散る汗 
		'wf07a' => array('SA/riceball.gif', '[おにぎり]'), 
		'wf07b' => array('SA/bag.gif', '[バッグ]'), 
		'wf07c' => '[うんち]', 
		'wf07d' => '[ネクタイ]', 
		'wf07e' => array('SA/t-shirt.gif', '[シャツ]'), 
		'wf080' => '[メロン]', 
		'wf081' => '[カキ氷]', 
		'wf082' => array('SA/sports.gif', '[ビキニ]'), 
		'wf083' => array('SA/hairsalon.gif', '[鋏]'), 
		'wf084' => '[注射器]', 
		'wf085' => '[錨]', 
		'wf086' => '[蛙]', 
		'wf087' => array('SA/paper.gif', '[パー]'), 
		'wf088' => array('SA/heart03.gif', '[失恋]'), 
		'wf089' => array('SA/diamond.gif', '&diams;'), 
		'wf08a' => array('SA/club.gif', '&clubs;'), 
		'wf08b' => array('SA/spade.gif', '&spades;'), 
		'wf08c' => array('SA/clip.gif', '[クリップ]'), 
		'wf08d' => array('SA/snow.gif', '[雪だるま]'), 
		'wf08e' => array('SA/birthday.gif', '[バースデーケーキ]'), 
		'wf08f' => array('SA/apple.gif', '[林檎]'), 
		'wf090' => array('SA/present.gif', '[プレゼント]'), 
		'wf091' => array('SA/dollar.gif', '($)'), //お金 (ドル) 
		'wf092' => array('SA/door.gif', '[EXIT]'), 
		'wf093' => array('SA/door.gif', '[ドア]'), 
		'wf094' => '[おやじ]', 
		'wf095' => array('SA/mobilephone.gif', '[PHS]'), 
		'wf096' => '☆', 
		'wf097' => array('SA/ticket.gif', '[チケット]'), 
		'wf098' => array('SA/heart04.gif', '&hearts;&hearts;'), 
		'wf099' => array('SA/flair.gif', '[ひらめき]'), 
		'wf09a' => array('SA/foot.gif', '[足跡]'), 
		'wf09b' => array('SA/smile.gif', "(^o^)"), // 笑顔
		'wf09c' => array('SA/tennis.gif', '[テニス]'), 
		'wf09d' => array('SA/movie.gif', '[映画]'), 
		'wf09e' => array('SA/ski.gif', '[スキー]'), 
		'wf09f' => array('SA/sign03.gif', '!!'), 
		'wf0a0' => array('SA/sign02.gif', '!?'), 
		'wf0a1' => '?', 
		'wf0a2' => '↓', 
		'wf0a3' => '↑', 
		'wf0a4' => array('SA/ok.gif', '[OK]'), 
		'wf0a5' => '[力こぶ]', 
		'wf0a6' => '→&hearts;', 
		'wf0a7' => '[うずまき]', 
		'wf0a8' => array('SA/typhoon.gif', '[台風]'), 
		'wf0a9' => '[海]', 
		'wf0aa' => '[サーフィン]', 
		'wf0ab' => array('SA/fuji.gif', '[山]'), 
		'wf0ac' => array('SA/fuji.gif', '[富士山]'), 
		'wf0ad' => '[キャンプ]', 
		'wf0ae' => array('SA/maple.gif', '[落葉]'), 
		'wf0af' => array('SA/bus.gif', '[バス]'), 
		'wf0b0' => array('SA/soccer.gif', '[サッカー]'), 
		'wf0b1' => '[初心者]', 
		'wf0b2' => '≪',
		'wf0b3' => '≫', 
		'wf0b4' => '[重要]', 
		'wf0b5' => '[18禁]', 
		'wf0b6' => array('SA/danger.gif', '[!]'), 
		'wf0b7' => array('SA/recycle.gif', '[リサイクル]'), 
		'wf0b8' => '▽.i|', // 電界強度 
		'wf0b9' => ')))', 
		'wf0ba' => '×', 
		'wf0bb' => array('SA/copyright.gif', '&copy;'), 
		'wf0bc' => array('SA/r-mark.gif', '&reg;'), 
		'wf0bd' => '[777]', // スリーセブン
		'wf0be' => array('SA/zero.gif', '0'), 
		'wf0bf' => array('SA/one.gif', '1'), 
		'wf0c0' => array('SA/two.gif', '2'), 
		'wf0c1' => array('SA/three.gif', '3'), 
		'wf0c2' => array('SA/four.gif', '4'), 
		'wf0c3' => array('SA/five.gif', '5'), 
		'wf0c4' => array('SA/six.gif', '6'), 
		'wf0c5' => array('SA/seven.gif', '7'), 
		'wf0c6' => array('SA/eight.gif', '8'), 
		'wf0c7' => array('SA/nine.gif', '9'), 
		'wf0c8' => array('SA/aries.gif', '[牡羊座]'),
		'wf0c9' => array('SA/taurus.gif', '[牡牛座]'),
		'wf0ca' => array('SA/gemini.gif', '[双子座]'), 
		'wf0cb' => array('SA/cancer.gif', '[蟹座]'), 
		'wf0cc' => array('SA/leo.gif', '[獅子座]'), 
		'wf0cd' => array('SA/virgo.gif', '[乙女座]'), 
		'wf0ce' => array('SA/libra.gif', '[天秤座]'), 
		'wf0cf' => array('SA/scorpius.gif', '[蠍座]'), 
		'wf0d0' => '[蛇遣座]', 
		'wf0d1' => array('SA/sagittarius.gif', '[射手座]'), 
		'wf0d2' => array('SA/capricornus.gif', '[山羊座]'),
		'wf0d3' => array('SA/aquarius.gif', '[水瓶座]'),  
		'wf0d4' => array('SA/pisces.gif', '[魚座]'), 
		'wf0d5' => array('SA/shadow.gif', '[地蔵]'), 
		'wf0d6' => array('SA/fastfood.gif', '[マクドナルド]'), 
		'wf0d7' => array('SA/fastfood.gif', '[モスバーガー]'), 
		'wf0d8' => '　', // [未使用]
		'wf0d9' => array('SA/fastfood.gif', '[ロッテリア]'), 
		'wf0da' => '　', // [未使用]
		'wf0db' => array('SA/cafe.gif', '[ドトールコーヒー]'), 
		'wf0dc' => '　', // [未使用]
		'wf0dd' => '　', // [未使用]
		'wf0de' => '　', // [未使用]
		'wf0df' => '　', // [未使用]
		'wf0e0' => '[もも]', // (バーミヤン?)
		'wf0e1' => '　', // [未使用]
		'wf0e2' => '　', // [未使用]
		'wf0e3' => array('SA/restaurant.gif', '[びっくりドンキー]'), 
		'wf0e4' => '　', // [未使用]
		'wf0e5' => '　', // [未使用]
		'wf0e6' => '　', // [未使用]
		'wf0e7' => array('SA/24hours.gif', '[ローソン]'), 
		'wf0e8' => '　', // [未使用]
		'wf0e9' => array('SA/24hours.gif', '[am/pm]'), 
		'wf0ea' => '　', // [未使用]
		'wf0eb' => '　', // [未使用]
		'wf0ec' => '　', // [未使用]
		'wf0ed' => '　', // [未使用]
		'wf0ee' => '　', // [未使用]
		'wf0ef' => '　', // [未使用]
		'wf0f0' => '[西武]', 
		'wf0f1' => '　', // [未使用]
		'wf0f2' => '　', // [未使用]
		'wf0f3' => '　', // [未使用]
		'wf0f4' => '　', // [未使用]
		'wf0f5' => '　', // [未使用]
		'wf0f6' => '　', // [未使用]
		'wf0f7' => '　', // [未使用]
		'wf0f8' => '　', // [未使用]
		'wf0f9' => '　', // [未使用]
		'wf0fa' => '　', // [未使用]
		'wf0fb' => '　', // [未使用]
		'wf0fc' => array('SA/dog.gif', '[ハチ公]'), 
		'wf140' => array('SA/shadow.gif', '[モヤイ]'), 
		'wf141' => '　', // [未使用]
		'wf142' => '　', // [未使用]
		'wf143' => '　', // [未使用]
		'wf144' => '　', // [未使用]
		'wf145' => '　', // [未使用]
		'wf146' => '　', // [未使用]
		'wf147' => '　', // [未使用]
		'wf148' => array('SA/bank.gif', '[UFJ]'), 
		'wf149' => '　', // [未使用]
		'wf14a' => '　', // [未使用]
		'wf14b' => '　', // [未使用]
		'wf14c' => '　', // [未使用]
		'wf14d' => array('SA/door.gif', '[EXIT]'), 
		'wf14e' => '　', // [未使用]
		'wf14f' => '　', // [未使用]
		'wf150' => '(T_T)', // 泣き顔
		'wf151' => '　', // [未使用]
		'wf152' => '　', // [未使用]
		'wf153' => '　', // [未使用]
		'wf154' => '　', // [未使用]
		'wf155' => '　', // [未使用]
		'wf156' => '　', // [未使用]
		'wf157' => '　', // [未使用]
		'wf158' => '　', // [未使用]
		'wf159' => '　', // [未使用]
		'wf15a' => '　', // [未使用]
		'wf15b' => '　', // [未使用]
		'wf15c' => '　', // [未使用]
		'wf15d' => '　', // [未使用]
		'wf15e' => '　', // [未使用]
		'wf15f' => '　', // [未使用]
	);
}

/* ==================================================
 * @param	string $key
 * @return	mix    $value
 */
public function get($key) {
	switch ($key) {
	case 'charset':
		return isset($this) ? $this->charset : self::DEFAULT_CHARSET;
	case 'iana_charset':
		$charset = isset($this) ? $this->charset : self::DEFAULT_CHARSET;
		$charset = preg_replace('/^SJIS(-win)?$/', 'Shift_JIS', $charset);
		$charset = preg_replace('/^eucJP(-win)?$/', 'EUC-JP', $charset);
		return $charset;
	case 'preamble':
		return isset($this) ? str_replace('__CHARSET__', $this->get('iana_charset'), $this->preamble) : NULL;
	case 'term_name':
		return (isset($this) && isset($this->term_name) ) ? $this->term_name : 'N/A';
	default:
		return (isset($this) && isset($this->$key) ) ? $this->$key : NULL;
	}
}

/* ==================================================
 * @param	string  $key
 * @param	mix     $value
 * @return	mix     $value
 */
public function set($key, $value = NULL) {
	if (is_null($value)) {
		unset($this->$key);
	} else {
		$this->$key = $value;
	}
	return $value;
}

/* ==================================================
 * @param	none
 * @return	boolean $is_search_engine
 */
public function is_search_engine() {
	return isset($this) && isset($this->search_engine) ? $this->search_engine : NULL;
}

/* ==================================================
 * @param	array   $networks
 * @param	boolean $allow_search_engine
 * @return	boolean $in_network
 */
public function in_network($networks = NULL, $allow_search_engine = false) {
	if ($allow_search_engine) {
		$search_ip = apply_filters('ktai_mobile_search_ip', self::$search_ip);
		$search_ip = apply_filters('mobile_search_ip/ktai_style.php',$search_ip);
		$networks = array_merge($networks, $search_ip);
	}
	if (! $networks) {
		return false;
	}
	$in_network = false;
	$ip = ip2long($_SERVER['REMOTE_ADDR']);
	foreach ( (array) $networks as $n) {
		if (strpos($n, '/') !== false) {
			// parse NN.NN.NN.NN/MASK
			list($network, $mask) = explode('/', $n);
			$net = ip2long($network);
			if (! $net || $mask < 8 || $mask > 32) {
				continue;
			}
			if ($ip >> (32 - $mask) == $net >> (32 - $mask)) {
				$in_network = true;
				break;
			}	
		} elseif (strpos($n, '-') !== false) {
			// parse MM.MM.MM.MM-NN.NN.NN.NN
			list($start, $end) = array_map('ip2long', explode('-', $n));
			if ($ip >= $start && $ip <= $end) {
				$in_network = true;
				break;
			}
		}
	}
	return $in_network;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function shrink_pre_encode($buffer) {
	if (strtolower(get_bloginfo('charset')) == 'utf-8' && function_exists('mb_regex_encoding')) {
		mb_regex_encoding('UTF-8');
		$buffer = mb_ereg_replace("\xc2\xa0", "&nbsp;", $buffer); // no-break space
		$buffer = mb_ereg_replace("\xe2\x99\xa0", "&#9824;", $buffer); // spade
		$buffer = mb_ereg_replace("\xe2\x99\xa4", "&#9824;", $buffer); // white spade
		$buffer = mb_ereg_replace("\xe2\x99\xa3", "&#9827;", $buffer); // club
		$buffer = mb_ereg_replace("\xe2\x99\xa7", "&#9827;", $buffer); // white club
		$buffer = mb_ereg_replace("\xe2\x99\xa5", "&#9829;", $buffer); // heart
		$buffer = mb_ereg_replace("\xe2\x99\xa1", "&#9825;", $buffer); // white heart
		$buffer = mb_ereg_replace("\xe2\x99\xa6", "&#9830;", $buffer); // diamond
		$buffer = mb_ereg_replace("\xe2\x99\xa2", "&#9830;", $buffer); // white diamond
		$buffer = mb_ereg_replace("\xe3\x80\xb0", "&#12336;", $buffer); // wavy dash
	}
	if (function_exists('mb_convert_encoding')) {
		$revert_cockney = array(
			'&#215;'  => 'x',
			'&#8211;' => '--', 
			'&#8212;' => '---', 
			'&#8217;' => "'", 
			'&#8220;' => mb_convert_encoding('“', get_bloginfo('charset'), 'UTF-8'),
			'&#8221;' => mb_convert_encoding('”', get_bloginfo('charset'), 'UTF-8'),
			'&#8230;' => mb_convert_encoding('…', get_bloginfo('charset'), 'UTF-8'),
			'&hellip;' => mb_convert_encoding('…', get_bloginfo('charset'), 'UTF-8'),
			'&#8482;' => '(tm)',
			'&laquo;' => mb_convert_encoding('≪', get_bloginfo('charset'), 'UTF-8'),
			'&raquo;' => mb_convert_encoding('≫', get_bloginfo('charset'), 'UTF-8'),
		);
		$buffer = str_replace(array_keys($revert_cockney), $revert_cockney, $buffer);
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function shrink_pre_split($buffer) {
	$buffer = str_replace("\r\n", "\n", $buffer);
	if ($this->get('mime_type') != 'application/xhtml+xml') {
		$buffer = $this->strip_styles($buffer);
	}
	// ----- save pre elements -----
	$pre = array();
	while (preg_match('!<pre>.*?</pre>!s', $buffer, $p, PREG_OFFSET_CAPTURE)) {
		$buffer = substr_replace($buffer, "\376\376\376" . count($pre) . "\376\376\376", $p[0][1], strlen($p[0][0]));
		$pre[] = $p[0][0];
		if (count($pre) > 9999) { // infinity loop check
			 break;
		}
	}
	// ----- remove redudant spaces -----
	$buffer = preg_replace('!<(p|div)( (id|class|align)=([\'"])[-_ a-zA-Z0-9]+\\4)*>\s*</\\1>\s*!', '', $buffer); //"syntax highlighting fix
	$buffer = preg_replace('!^[ \t]+!m', '', $buffer);
	$buffer = preg_replace('!>\t+<!', '><', $buffer);
	$buffer = preg_replace('!>\s+<!', ">\n<", $buffer);
	$buffer = preg_replace('!/>[\r\n]+!', "/>\n", $buffer);
	$buffer = preg_replace('![\r\n]+</!', "\n</", $buffer);
	// ----- restore pre elements -----
	$buffer = preg_replace('/\376\376\376(\d+)\376\376\376/e', '$pre[$1]', $buffer);
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function strip_styles($buffer) {
	$buffer = preg_replace('!</?span([^>]*?)?>!s', '', $buffer); // <?php /* syntax hilighting fix */
	$buffer = preg_replace(
		'!<([a-z]+?[^>]*?) style=([\'"])' . KtaiStyle::QUOTED_STRING_REGEX . '\\2( [^>]*?)?>!s', // <?php /* syntax hilighting fix */
		'<$1$3>', 
		$buffer);
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function replace_smiley($buffer, $smiles = NULL) {
	if ($smiles && preg_match_all('!<img src=([\'"])([^>]*?/([-_.a-zA-Z0-9]+))\\1( alt=([\'"])' . KtaiStyle::QUOTED_STRING_REGEX . '\\5)? class=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\6 ?/?>!s', $buffer, $images, PREG_SET_ORDER)) { // <?php /* syntax hilighting fix */
		foreach($images as $i) {
			$img      = $i[0];
			$src      = $i[2];
			$basename = $i[3];
			$alt_elem = $i[4];
			$class    = $i[7];
			if (preg_match('/(^| )wp-smiley( |$)/', $class)) {
				if (preg_match('/(^| )ktai( |$)/', $class)) {
					$buffer = str_replace($img, sprintf('<img src="%s"%s />', $src, $alt_elem), $buffer);
				} else {
					$buffer = str_replace($img, $smiles[$basename], $buffer);
				}
			}
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function convert_pict($buffer) {
	if ( isset($this) && isset($this->charset) ) {
		$charset = $this->charset;
	} else {
		$charset = get_bloginfo("charset");
	}
	$buffer = preg_replace(
		'!<img localsrc="([^"]+)"( alt="(' . KtaiStyle::DOUBLE_QUOTED_STRING_REGEX . ')")?[^/>]*/?>!se', // <?php /* syntax hilighting fix */
		'self::pict_replace("$1", "$2", "$3", $charset)', 
		$buffer);
	return $buffer;
}

/* ==================================================
 * @param	string   $code
 * @param	boolean  $has_alt
 * @param	string   $alt
 * @param	string   $charset
 * @return	string   $converted
 */
public static function pict_replace($code, $has_alt, $alt, $charset) {
	if ( !isset(self::$pictograms) ) {
		self::set_variables();
	}
	if ( !isset(self::$translated) ) {
		$charset = strtoupper(get_bloginfo('charset'));
		if ( $charset != 'UTF-8' && function_exists('mb_convert_encoding')) {
			$code = create_function('$c', 
				'return (is_array($c) 
				 ? array($c[0], mb_convert_encoding($c[1], $charset , "UTF-8")) 
				 : mb_convert_encoding($c, $charset , "UTF-8")
			);');
			self::$translated = array_map($code, self::$pictograms);
		} else {
			self::$translated = self::$pictograms;
		}
		self::$translated = apply_filters('ktai_pictogram_table', self::$translated, __CLASS__, $charset);
	}
	if (is_array(self::$translated[$code])) {
		$converted = sprintf('<img src="%1$s" alt="%2$s" class="%3$s" style="%4$s" />', 
			self::$pict_url . self::$translated[$code][0],
			($has_alt ? $alt : self::$translated[$code][1]),
			self::PICT_CLASS,
			self::PICT_STYLE
		);
	} else {
		$converted = $has_alt ? $alt : self::$translated[$code];
	}
	$converted = apply_filters('ktai_convert_pict_other', $converted, $code, ($has_alt ? $alt : NULL), $charset);
	$converted = apply_filters('convert_pict_other/ktai_style.php', $converted, $code, ($has_alt ? $alt : NULL), $charset);
	return $converted;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function pickup_pics($buffer) {
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function shrink_post_split($buffer) {
	if ($this->get('mime_type') == 'application/xhtml+xml') {
		$buffer = $this->body_to_style($buffer);
		$buffer = preg_replace('/<a name=/', '<a id=', $buffer);
		$buffer = $this->block_align_to_style($buffer);
		$buffer = $this->horizontal_rule_to_style($buffer);
		$buffer = $this->font_to_style($buffer);
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function body_to_style($buffer) {
	if (preg_match('!</head>\s*<body([^>]+?)>!', $buffer, $body)) {
		$body_style = '';
		$head_style = '';
		$has_bgcolor = preg_match('/bgcolor="([^"]+)"/', $body[1], $bgcolor);
		$has_text    = preg_match('/text="([^"]+)"/', $body[1], $text);
		$has_image   = preg_match('/background="([^"]+)"/', $body[1], $image);
		if ($has_bgcolor || $has_text || $has_image) {
			$body_style = ' style="' 
			. (isset($text[1]) ? 'color:' . $text[1] . ';' : '') 
			. (isset($bgcolor[1]) ? 'background-color:' . $bgcolor[1] . ';' : '') 
			. (isset($image[1]) ? 'background-image:url(' . $image[1] . ');' : '') 
			. '"';
		}
		if (preg_match('/link="([^"]+)"/', $body[1], $color)) {
			$head_style .= 'a:link {color:' . $color[1] . ';} ';
		}
		if (preg_match('/alink="([^"]+)"/', $body[1], $color)) {
			$head_style .= 'a:focus {color:' . $color[1] . ';} ';
		}
		if (preg_match('/vlink="([^"]+)"/', $body[1], $color)) {
			$head_style .= 'a:visited {color:' . $color[1] . ';} ';
		}
		if ($head_style) {
			if (preg_match('#(<style[^>]*>\s*(<!\[CDATA\[)?.*?)((\]\]>)?\s*</style>)#s', $buffer, $head)) {
				$buffer = str_replace($head[0], $head[1] . " " . $head_style . $head[3], $buffer);
			} else {
				$buffer = str_replace('</head>', '<style type="text/css"><![CDATA[ ' . $head_style . ']]></style></head>', $buffer);
			}
		}
		if ($body_style) {
			$buffer = str_replace($body[0], '</head><body' . $body_style . '>', $buffer);
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function block_align_to_style($buffer) {
	if (preg_match_all('!(<h[1-6]|div|p) ([^>]*?)/?>!s', $buffer, $block, PREG_SET_ORDER)) { // <?php /* syntax hilighting fix */
		foreach ($block as $b) {
			$old_style = '';
			$style = '';
			$html = $b[0];
			$elem = $b[1];
			if (preg_match_all('/ ?(\w+)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', $b[2], $attr, PREG_SET_ORDER)) { //"syntax highlighting fix
				foreach ($attr as $a) {
					$key   = $a[1];
					$value = $a[3];
					switch ($key) {
					case 'style':
						if (strlen($value)) {
							$old_style = $value . (substr_compare($value, ';', -1, 1) !== 0 ? ';' : '');
							$html = str_replace($a[0], '', $html);
						}
						break;
					case 'align':
						$style .= "text-align:$value;"; 
						$html = str_replace($a[0], '', $html);
						break;			
					}
				}
			}
			if ($style) {
				$style = ' style="' . $old_style . $style . '"';
				$html = str_replace($elem, $elem . $style, $html);
				$buffer = preg_replace('!' . preg_quote($b[0], '!') . '!', $html, $buffer, 1);
			}
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function horizontal_rule_to_style($buffer) {
	if (preg_match_all('!<hr ([^>]*?)/?>!s', $buffer, $hr, PREG_SET_ORDER)) { // <?php /* syntax hilighting fix */
		foreach ($hr as $h) {
			$old_style = '';
			$style = '';
			if (preg_match_all('/ ?(\w+)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', $h[1], $attr, PREG_SET_ORDER)) { //"syntax highlighting fix
				foreach ($attr as $a) {
					$key   = $a[1];
					$value = $a[3];
					switch ($key) {
					case 'style':
						if (strlen($value)) {
							$old_style = $value . (substr_compare($value, ';', -1, 1) !== 0 ? ';' : '');
						}
						break;
					case 'color':
						$style .= "color:$value;border-style:solid;border-color:$value;";
						break;
					case 'size':
						$style .= "height:$value;";
					case 'width':
						$style .= "width:$value;";
						break;
					case 'align':
						$style .= 'float:' . str_replace('center','none', $value); 
						break;			
					}
				}
			}
			if ($style) {
				$style = ' style="' . $old_style . $style . '"';
				$buffer = preg_replace('!' . preg_quote($h[0], '!') . '!', "<hr$style />", $buffer, 1);
			}
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function font_to_style($buffer) {
	$buffer = str_replace(array("\375", "\376"), array('', ''), $buffer);
	$buffer = str_replace(array('<font', '</font>'), array("\375", "\376"), $buffer, $num_replaced);
	$loop = 0;
	while (preg_match('!\\375([^<>]*)>([^\\375\\376]*?)\\376!s', $buffer, $f)) {
		if ($loop++ > $num_replaced) { // infinity loop check
			break;
		}
		$old_style = '';
		$style = '';
		$font = $f[1];
		$html = $f[2];
		if (preg_match_all('/ ?(\w+)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', $font, $attr, PREG_SET_ORDER)) { //"syntax highlighting fix
			foreach ($attr as $a) {
				$key   = $a[1];
				$value = $a[3];
				switch ($key) {
				case 'style':
					if (strlen($value)) {
						$old_style = $value . (substr_compare($value, ';', -1, 1) !== 0 ? ';' : '');
					}
					break;
				case 'size':
					if ($value === '+1') {
						$style .= 'font-size:larger;';
					} else {
						switch ($value) {
						case '-1':
							$style .= 'font-size:smaller;';
							break;
						case '1':
							$style .= 'font-size:x-small;';
							break;
						case '2':
							$style .= 'font-size:small;';
							break;
						case '3':
							$style .= ' ';
							break;
						case '4':
							$style .= 'font-size:large;';
							break;
						case '5':
							$style .= 'font-size:x-large;';
							break;
						case '6':
						case '7':
							$style .= 'font-size:xx-large;';
							break;
						}
					}
					break;
				case 'face':
					$style .= "font-family:$value;";
					break; 
				default:
					$style .= "$key:$value;";
				}
			}
		}
		if ($style) {
			$style = ' style="' . $old_style . $style . '"';
			$html = "<span$style>" . $html . '</span>';
		} else {
			$html = str_replace(array("\375", "\376"), array('<font', '</font>'), $f[0]);
		}
		$buffer = preg_replace('!' . preg_quote($f[0], '!') . '!', $html, $buffer, 1);
	}
	$buffer = str_replace(array("\375", "\376"), array('<font', '</font>'), $buffer, $num_replaced); // restore rest font tags
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function input_to_style($buffer) {
	if (preg_match_all('|<input ([^>]*?)/?>|', $buffer, $input, PREG_SET_ORDER)) { // <?php /* syntax hilighting fix */
		foreach ($input as $i) {
			$old_style = '';
			$style = '';
			$html = $i[0];
			$attr = $i[1];
			if (preg_match('/ ?\bstyle=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\1/s', $attr, $s) && strlen($s[2])) { //"syntax highlighting fix
				$old_style = $s[2] . (substr_compare($s[2], ';', -1, 1) !== 0 ? ';' : '');
				$html = str_replace($s[0], '', $html);
			}
			if (preg_match('/ ?\bistyle=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\1/s', $attr, $istyle)) { //"syntax highlighting fix
				switch ($istyle[2]) {
				case 1: // Fullwidth Kana
					$style .= '-wap-input-format:*M;';
					break; 
				case 2: // Halfwidth Kana
					$style .= '-wap-input-format:*M;';
					break; 
				case 3: // Alphabet
					$style .= '-wap-input-format:*m;';
					break; 
				case 4: // Numeric
					$style .= '-wap-input-format:*N;';
					break;
				}
				$html = str_replace($istyle[0], '', $html);
			}
			if ($style) {
				$style = ' style="' . $old_style . $style . '"';
				$html = str_replace('<input', '<input' . $style, $html);
				$buffer = preg_replace('!' . preg_quote($i[0], '!') . '!', $html, $buffer, 1);
			}
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	int     $comment_ID
 * @param	string  $comment_approved
 * @return	none
 * @since	2.0.0
 */
public function store_term_id ($comment_ID, $comment_approved) {
	if ($this->term_ID) {
		add_comment_meta($comment_ID, 'terminal_id', $this->term_ID);
	}
	if ($this->usim_ID) {
		add_comment_meta($comment_ID, 'usim_id', $this->usim_ID);
	}
	if ($this->sub_ID) {
		add_comment_meta($comment_ID, 'subscriber_id', $this->sub_ID);
	}
}

/* ==================================================
 * @param	string  $user_agent
 * @return	string  $user_agent
 */
public function add_term_id ($user_agent) {
	$id = array();
	if ($this->term_ID) {
		$id[] = "Term ID: {$this->term_ID}";
	}
	if ($this->usim_ID) {
		$id[] = "USIM ID: {$this->usim_ID}";
	}
	if ($this->sub_ID) {
		$id[] = "Sub ID: {$this->sub_ID}";
	}
	if ($id) {
		$user_agent .= ' (' . implode(' ', $id) . ')';
	}
	return $user_agent;
}

/* ==================================================
 * @param	object  $comment
 * @return	array   $id
 */
public function read_term_id($comment) {
	$id = array(NULL, NULL, NULL);
	if (function_exists('get_comment_meta')) {
		$id[0] = get_comment_meta($comment->comment_ID, 'terminal_id', true);
		$id[1] = get_comment_meta($comment->comment_ID, 'usim_id', true);
		$id[2] = get_comment_meta($comment->comment_ID, 'subscriber_id', true);
	}
	if ( empty($id[0]) && empty($id[1]) && empty($id[2]) 
	&& preg_match('/\((Term ID: ([^ ]*)( USIM ID: ([^ ]*))?)? ?(Sub ID: ([^)]*))?\)$/', $comment->comment_agent, $agent)) {
		$id[0] = isset($agent[2]) ? $agent[2] : NULL;
		$id[1] = isset($agent[4]) ? $agent[4] : NULL;
		$id[2] = isset($agent[6]) ? $agent[6] : NULL;
	}
	return $id;
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_Other class
   ================================================== */

class KtaiService_Other extends KtaiServices {
	private $convert_fullwidth_tild = false;
	public static $dcm_smartphones = array(
		'DCM06' => 'htcZ',
	);

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->use_redir  = false;
	$this->show_plugin_icon = true;
	$this->user_agent = $user_agent;
	if (preg_match('/Windows CE; (.*)$/', $user_agent, $specs)) {
		$this->convert_fullwidth_tild = true;
		if (preg_match('!SHARP/([^;]*)!', $specs[1], $term_name)) { // W-ZERO3, EM-ONE
			$this->term_name = $term_name[1];
		} elseif (preg_match('/IEMobile [\d.]*\) (FOMA )?(\w+)/', $specs[1], $term_name)) {
			$this->term_name = $term_name[2];
		} elseif (preg_match('/DCM\d+/', $specs[1], $term_name)) { // htc Z
			$this->term_name = self::$dcm_smartphones[$term_name[0]];
		} elseif (preg_match('/^([^;]*);/', $specs[1], $term_name)) {
			$this->term_name = $term_name[1];
		}
	} elseif (preg_match('/Opera Mobi\b[^)]*\) ?(\w*)/', $user_agent, $term_name)) {
			$this->term_name = $term_name[1]; // S21HT
			if (empty($this->term_name) && preg_match('!SHARP/([^;]*)!', $user_agent, $term_name)) { // W-ZERO3 (modified)
				$this->term_name = $term_name[1];
			}
	}
	return;
}

/* ==================================================
 * @param	string  $code
 * @return	string  $buffer
 */
public function convert_pict($buffer) {
	$buffer = preg_replace(
		'!<img localsrc="([^"]+)"( alt="(' . KtaiStyle::DOUBLE_QUOTED_STRING_REGEX . ')")? ?/?>!se', // <?php /* syntax hilighting fix */
		'parent::pict_replace("$1", "$2", "$3", $this->charset)', 
		$buffer);
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function shrink_post_split($buffer) {
	$buffer = $this->horizontal_rule_to_style($buffer);
	if (strtoupper($this->charset) == 'UTF-8' && $this->convert_fullwidth_tild) {
		$buffer = preg_replace("/\x{301c}/u", "\xef\xbd\x9e", $buffer);
	}
	return parent::shrink_post_split($buffer);
}

/* ==================================================
 * @param	boolean $allow_search_engine
 * @return	boolean $in_network
 */
public function in_network($allow_search_engine = false) {
	return parent::in_network(NULL, $allow_search_engine);
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_Other_Japan class
   ================================================== */

class KtaiService_Other_Japan extends KtaiService_Other {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	if (preg_match('#PDA; (SL-\w+)/#', $user_agent, $specs)) {
		$this->term_name = 'Zaurus ' . $specs[1];
	}
	$this->charset = 'SJIS-win';
	return;
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_Touch class
   ================================================== */

class KtaiService_Touch extends KtaiService_Other {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 * @since	1.81
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->theme = ks_option('ks_theme_touch');
	$this->type  = 'TouchPhone';
	add_action('ktai_wp_head', array($this, 'viewport') );
	return;
}

public function viewport() {
	echo '<meta name="viewport" content="width=device-width,initial-scale=1.0" />' . "\n";
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_Preview class
   ================================================== */

class KtaiService_Preview extends KtaiService_Other {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 * @since	2.0.0
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->type = 'Theme_Preview';
	$this->use_redir = true;
	$this->theme = stripslashes($_GET['template']);
	add_action('setup_theme', array($this, 'check_mobile_preview'), 9);
	remove_action('setup_theme', 'preview_theme');
	add_action('setup_theme', array('KtaiThemes', 'preview_theme'));
	add_filter('ktai_mime_type', create_function('', 'return "text/html";')); // prevent downloading against Internet Explorer
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function check_mobile_preview() {
	if ( !KtaiStyle::verify_anon_nonce($_GET['_wpnonce'], 'switch-theme_' . stripslashes($_GET['template'])) ) {
		wp_die('Invalid Mobile Preview');
	}
}

// ===== End of class ====================
}

/* ==================================================
 * @param	boolean  $echo
 * @param	boolean  $detect_search_engine
 * @return	none
 */
function ks_term_name($echo = true, $detect_search_engine = true) {
	global $Ktai_Style;
	$term_name = $detect_search_engine ? $Ktai_Style->get('search_engine') : NULL;
	if ( !$term_name ) {
		$term_name = $Ktai_Style->get('term_name');
	}
	if ($echo) {
		echo esc_html($term_name);
	}
	return $term_name;
}

/* ==================================================
 * @param	none
 * @return	srting  $type
 */
function ks_service_type() {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('type') : false;
}

/* ==================================================
 * @param	none
 * @return	boolean $is_flat_rate
 */
function ks_is_flat_rate() {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('flat_rate') : false;
}

/* ==================================================
 * @param	none
 * @return	boolean $cookie_available
 */
function ks_cookie_available() {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('cookie_available') : false;
}

/* ==================================================
 * @param	none
 * @return	boolean $ext_css_available
 */
function ks_ext_css_available() {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('ext_css_available') : false;
}

/* ==================================================
 * @param	boolean $allow_search_engine
 * @return	boolean $in_network
 */
function ks_in_network($allow_search_engine = false) {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->in_network($allow_search_engine) : false;
}

/* ==================================================
 * @param	none
 * @return	none
 */
function ks_use_appl_xhtml() {
	global $Ktai_Style;
	if ($Ktai_Style->ktai->get('xhtml_head')) {
		$Ktai_Style->ktai->set('mime_type', 'application/xhtml+xml');
		echo $Ktai_Style->ktai->get('xhtml_head');
	} else { ?>
<html>
<?php }	
}

/* ==================================================
 * @param	none
 * @return	none
 */
function ks_applied_appl_xhtml() {
	global $Ktai_Style;
	return ($Ktai_Style->ktai->get('mime_type') == 'application/xhtml+xml');
}

/* ==================================================
 * @param	none
 * @return	none
 */
function ks_force_text_html() {
	global $Ktai_Style;
	$Ktai_Style->ktai->set('mime_type', 'text/html');
}

/* ==================================================
 * @param	none
 * @return	none
 */
function ks_mimetype($echo = true) {
	global $Ktai_Style;
	if ($echo) {
		echo esc_html($Ktai_Style->get('mime_type'));
	}
	return $Ktai_Style->get('mime_type');
}

/* ==================================================
 * @param	none
 * @return	none
 */
function ks_charset($echo = true) {
	global $Ktai_Style;
	if ($echo) {
		echo esc_html($Ktai_Style->get('iana_charset'));
	}
	return $Ktai_Style->get('iana_charset');
}

?>