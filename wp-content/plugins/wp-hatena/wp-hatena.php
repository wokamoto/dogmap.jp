<?php
/*
Plugin Name: wp-hatena 拡張版
Plugin URI: http://wppluginsj.sourceforge.jp/wp-hatena-extended/
Description: エントリにはてなブックマーク等に追加するリンクタグなどを挿入します。
Author: hiromasa
Extended version Author: inocco (hibiki)
Versionin: 0.8 ( Base wp-hatena Version: 0.93j )
Author URI: http://hiromasa.zone.ne.jp/blog/
Extended version Author URI: http://wp.graphact.com/
Special Thanks: Castaway. (http://bless.babyblue.jp/wp/)
Bug Report: Masayan (http://wp.mmrt-jp.net/)
Bug Report: kohaku (http://aoiro-blog.com/)
*/

/*  Copyright 2006 hiromasa  (email : webmaster@hiromasa.zone.ne.jp)

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/******************************************************************************
 * 使い方 :
 *  プラグインを有効にした後 WP テーマ内の **エントリ表示位置** に、
 *   はてなの場合      : <?php if(isset($wph)) $wph->addHatena(); ?>
 *   del.icio.usの場合 : <?php if(isset($wph)) $wph->adddelicious(); ?>
 *  を挿入してください。
 *****************************************************************************/

/******************************************************************************
 * WpHatena function define.
 *****************************************************************************/
//if(class_exists('WpHatena')) {
//	
//	$wph = & new WpHatena();
//	
//	// JavaScript 画像説明ポップアップ用
//	// add_action('wp_head', array(&$wph, 'addScript'));
//	
//}

/******************************************************************************
 * 管理画面
 *****************************************************************************/
// 管理メニューに追加するフック
add_action('admin_menu', 'WpHatenaHook');

// フックに対するaction関数
function WpHatenaHook()
{
	// 設定メニュー下にサブメニューを追加:
	add_options_page('wp-hatena', 'wp-hatena', 8, 'file', 'WpHatenaPluginView');
}

// プラグイン設定画面のコンテンツ表示のコンテンツを表示する。
function WpHatenaPluginView()
{

	//保存されている場合読み込む
	$wph_hatebu_type  = get_option('wph_hatebu_type');
	$wph_twitter_type = get_option('wph_twitter_type');
	$wph_fcbk_type    = get_option('wph_fcbk_type');
	$wph_fcbk_width   = get_option('wph_fcbk_width');
	// 設定が保存されていない場合用デフォルト値を設定
	if ($wph_hatebu_type==null) { $wph_hatebu_type  = 'standard'; }
	if ($wph_twitter_type==null) { $wph_twitter_type = 'none'; }
	if ($wph_fcbk_type==null) { $wph_fcbk_type = 'button_count'; }
	if ($wph_fcbk_width==null) { $wph_fcbk_width = '100'; }
	// 設定変更画面を表示する
?>
	<div class="wrap">
		<h2>wp-hatena 管理画面</h2>
		<p>ほとんどのものは空欄でも動きます。細かい指定をして使いたい場合向け。<br />※mixi チェックを使う場合は mixi key の指定は必須です。</p>
		<form method="post" action="options.php">
			<?php wp_nonce_field('update-options'); ?>
			<table class="form-table">
			<tr>
			<th>はてなブックマーク<br />表示タイプ</th>
			<td>
			<select id="wph_hatebu_type" name="wph_hatebu_type">
			<option value="standard"<?php if($wph_hatebu_type=='standard'){ echo ' selected="selected"';} ?>>スタンダード (B!＋ブックマーク数を表示)</option>
			<option value="vertical"<?php if($wph_hatebu_type=='vertical'){ echo ' selected="selected"';} ?>>バーティカル (大きめのサイズでB!＋ブックマーク数を表示)</option>
			<option value="simple"<?php if($wph_hatebu_type=='simple'){ echo ' selected="selected"';} ?>>シンプル (B!のみでブックマーク数は表示されません）</option>
			</select>
			<br />独自アイコンを利用したい場合は、シンプルを選択してください
			</td>
			</tr>

			<tr>
			<th>Tweet ボタン<br />表示タイプ</th>
			<td>
			<select id="wph_twitter_type" name="wph_twitter_type">
			<option value="horizontal"<?php if($wph_twitter_type=='horizontal'){ echo ' selected="selected"';} ?>>スタンダード (つぶやきボタン＋水平方向にカウント数を表示)</option>
			<option value="vertical"<?php if($wph_twitter_type=='vertical'){ echo ' selected="selected"';} ?>>バーティカル (大きめのサイズでつぶやきボタン＋垂直方向にカウント数を表示)</option>
			<option value="none"<?php if($wph_twitter_type=='none'){ echo ' selected="selected"';} ?>>シンプル (つぶやきボタンのみでカウント数は表示されません）</option>
			</select>
			<br />独自アイコンを利用したい場合は、シンプルを選択してください
			</td>
			</tr>

			<tr>
			<th>Evernote Clip<br />クリップ画面で表示するブログ名</th>
			<td>
				<input type="text" name="wph_ever_blogname" value="<?php echo get_option('wph_ever_blogname'); ?>" style="width: 400px;" />
				<br />クリップ画面で表示するブログ名（未指定の場合ドメインが表示されます）
			</td>
			</tr>
			<tr>
			<th>Evernote Clip<br />クリップする範囲の id</th>
			<td>
				<input type="text" name="wph_ever_clip_id" value="<?php echo get_option('wph_ever_clip_id'); ?>" style="width: 400px;" />
				<br />クリップしたい部分が含まれる HTML 内の id を指定
			</td>
			</tr>
			<tr>
			<th>Evernote Clip<br />Evernote add code</th>
			<td>
				<input type="text" name="wph_ever_add" value="<?php echo get_option('wph_ever_add'); ?>" style="width: 400px;" />
				<br />※利用したい場合のみ Evernote add へ登録して記入
			</td>
			</tr>

			<tr>
			<th>Facebook いいね！<br />表示タイプ</th>
			<td>
			<select id="wph_fcbk_type" name="wph_fcbk_type">
			<option value="standard"<?php if($wph_fcbk_type=='standard'){ echo ' selected="selected"';} ?>>スタンダード (いいね！ボタン＋水平方向にテキストやアイコンを表示)</option>
			<option value="box_count"<?php if($wph_fcbk_type=='box_count'){ echo ' selected="selected"';} ?>>バーティカル (いいね！ボタン＋素直方向にカウント数を表示)</option>
			<option value="button_count"<?php if($wph_fcbk_type=='button_count'){ echo ' selected="selected"';} ?>>シンプル (いいね！ボタン＋水平方向にカウント数を表示)</option>
			</select>
			<p>スタンダード: Minimum width: 225 pixels. Default width: 450 pixels. Height: 35 pixels (without photos) or 80 pixels (with photos).<br />
				バーティカル: Minimum width: 55 pixels. Default width: 55 pixels. Height: 65 pixels.<br />
				シンプル: Minimum width: 90 pixels. Default width: 90 pixels. Height: 20 pixels.
			</p>
			</td>
			</tr>
			<tr>
			<th>Facebook いいね！<br />ボタンの横幅</th>
			<td><input type="text" name="wph_fcbk_width" value="<?php echo get_option('wph_fcbk_width'); ?>" style="width: 400px;" /> px</td>
			</tr>

			<tr>
			<th>mixi チェック<br />mixi key</th>
			<td>
				<input type="text" name="wph_mixi_key" value="<?php echo get_option('wph_mixi_key'); ?>" style="width: 400px;" />
				<br />※mixi チェックを利用したい場合のみ mixiディベロッパーセンターに登録して記入
			</td>
			</tr>

			<tr>
			<th>アイコン画像の場所</th>
			<td>
				<input type="text" name="wph_img_path" value="<?php echo get_option('wph_img_path'); ?>" style="width: 400px;" />
				<br />アイコン画像の設置場所を変更したい場合、アイコン画像のある場所の URL を記入してください。<br />※http:// から記入してください。 例）http://hoge.com/img/
			</td>
			</tr>
			</table>

			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="wph_hatebu_type,wph_twitter_type,wph_ever_blogname, wph_ever_clip_id, wph_ever_add, wph_fcbk_type, wph_fcbk_width, wph_mixi_key, wph_img_path" />
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>
<?php
}
/******************************************************************************
 * プラグイン停止の際に追加したフィールドを削除
 *****************************************************************************/
add_action('deactivate_wp-hatena/wp-hatena.php', 'WpHatenaDel');
function WpHatenaDel()
{
	delete_option('wph_hatebu_type');
	delete_option('wph_twitter_type');
	delete_option('wph_ever_blogname');
	delete_option('wph_ever_clip_id');
	delete_option('wph_ever_add');
	delete_option('wph_fcbk_type');
	delete_option('wph_fcbk_width');
	delete_option('wph_mixi_key');
	delete_option('wph_img_path');
}

/******************************************************************************
 * WpHatena - Extended version
 * 
 * @author		hiromasa	// @extended version author		hibiki
 * @version	0.93j			// @extended version 0.8
 *
 *****************************************************************************/
class WpHatena {
	
	var $plugin_path;
	var $popup_jsname;
	var $blog_charset;
	var $css_path;
	var $img_path;
	var $lazy_loading_scripts = array();

	// WP_CONTENT_URL
	function wp_content_url($path = '') {
		return trailingslashit( trailingslashit(WP_CONTENT_URL) . preg_replace('/^\//', '', $path) );
	}

	// WP_PLUGIN_URL
	function wp_plugin_url($path = '') {
		return $this->wp_content_url( 'plugins/' . preg_replace('/^\//', '', $path) );
	}

	/**
	 * The Constructor
	 * 
	 * @param none
	 * @return Object reference
	 */
	function WpHatena() {
		
//		$this->plugin_path  = get_settings('siteurl');
//		$this->plugin_path .= '/wp-content/plugins/wp-hatena/';
		$this->plugin_path  = $this->wp_plugin_url("wp-hatena");
		$this->popup_jsname = 'popup.js';
		$this->blog_charset = get_settings('blog_charset');
		$this->css_path     = $this->wp_plugin_url("wp-hatena") . 'wp-hatena.css';
		
		//管理画面系のデータ
		$this->hatebu_type      = get_option('wph_hatebu_type');
		$this->twitter_type     = get_option('wph_twitter_type');
		$this->blogname         = get_option('wph_ever_blogname');
		$this->ever_clip_id     = get_option('wph_ever_clip_id');
		$this->ever_add         = get_option('wph_ever_add');
		$this->fcbk_type        = get_option('wph_fcbk_type');
		$this->fcbk_width       = get_option('wph_fcbk_width');
		$this->mixi_key         = get_option('wph_mixi_key');
		$this->img_path         = get_option('wph_img_path');
		
		if ($this->hatebu_type==null)  { $this->hatebu_type  = 'simple';}
		if ($this->twitter_type==null) { $this->twitter_type = 'none';}
		if ($this->fcbk_type==null)    { $this->fcbk_type    = 'button_count';}
		if ($this->img_path==null)     { $this->img_path     = $this->wp_plugin_url("wp-hatena") . 'img/';}
		
		if (!is_admin()) {
			add_action('wp_head', array(&$this, 'echoCss'));
			add_action('wp_footer', array(&$this, 'JSLazyLoading'));
		}
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (はてなブックマーク用のタグを echo)
	 */
	function addHatena() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeTag(
				//href, class, otheratt, pagetitle, img, alt, script
				'http://b.hatena.ne.jp/entry/' . get_permalink(),
				'hatena-bookmark-button wph',
				' data-hatena-bookmark-title="' . $title . '" data-hatena-bookmark-layout="' . $this->hatebu_type . '"',
				$title,
				$this->img_path . 'hatena.gif',
				'このエントリーをはてなブックマークに追加',
				'<script type="text/javascript" src="http://b.st-hatena.com/js/bookmark_button.js" charset="utf-8" async="async"></script>',
				true
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (del.icio.us 用のタグを echo)
	 */
	function adddelicious() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeBookmarkURL(
				'del.icio.us',
				'http://del.icio.us/post?url=' . get_permalink() . '&amp;title=' . urlencode($title),
				'delicious.gif'
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (Livedoor Clip用のタグを echo)
	 */
	function addLivedoor() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeBookmarkURL(
				'Livedoor Clip',
				'http://clip.livedoor.com/clip/add?link=' . get_permalink() . '&amp;title=' . urlencode($title) . '&amp;jump=ref',
				'livedoor.gif'
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (Yahoo! ブックマーク用のタグを echo)
	 */
	function addYahoo() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeBookmarkURL(
				'Yahoo!ブックマーク',
				'http://bookmarks.yahoo.co.jp/bookmarklet/showpopup?t=' . urlencode($title) . '&amp;u=' . get_permalink() . '&amp;opener=bm&amp;ei=UTF-8',
				'yahoo.gif'
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (FC2ブックマーク用のタグを echo)
	 */
	function addFC2() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeBookmarkURL(
				'FC2ブックマーク',
				'http://bookmark.fc2.com/user/post?url=' . get_permalink() . '&amp;title=' . urlencode($title),
				'fc2.gif'
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (Nifty用のタグを echo)
	 */
	function addNifty() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeBookmarkURL(
				'Nifty Clip',
				'http://clip.nifty.com/create?url=' . get_permalink() . '&amp;title=' . urlencode($title),
				'nifty.gif'
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (POOKMARK用のタグを echo)
	 */
	function addPOOKMARK() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeBookmarkURL(
				'POOKMARK. Airlines',
				'http://pookmark.jp/post?url=' . get_permalink() . '&amp;title=' . urlencode($title),
				'pookmark.gif'
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (buzzurl用のタグを echo)
	 */
	function addBuzzurl() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeBookmarkURL(
				'Buzzurl（バザール）',
				'http://news.ecnavi.jp/config/add/confirm?url=' . get_permalink() . '&amp;title=' . urlencode($title),
				'buzzurl.gif'
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (Choix用のタグを echo)
	 */
	function addChoix() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeBookmarkURL(
				'Choix',
				'http://www.choix.jp/bloglink/' . get_permalink(),
				'choix.gif'
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (newsing用のタグを echo)
	 */
	function addnewsing() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeBookmarkURL(
				'newsing',
				'http://newsing.jp/nbutton?title=' . urlencode($title) . '&amp;url=' . get_permalink(),
				'newsing.gif'
			);
		
	}

	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (はてなブックマーク被ブックマーク用のタグを echo)
	 */
	function addHatenaCount() {
		
		echo
			$this->makeTag(
				//href, class, otheratt, pagetitle, img, alt, script
				'http://b.hatena.ne.jp/entry/' . get_permalink(),
				'wph',
				'',
				'',
				'http://b.hatena.ne.jp/entry/image/' . get_permalink(),
				'このエントリのはてなブックマーク数'
			);
		
	}

	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (はてなブックマーク被ブックマークテキスト表示用のタグを echo)
	 */
	function addHatenaCountTxt() {
		
		echo
			$this->makeBookmarkCountTxtTag(
				//url
				'http://api.b.st-hatena.com/entry.count?url=' . get_permalink(),
				//linkurl
				'http://b.hatena.ne.jp/entry/' . get_permalink()
			);
		
	}

	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (mixiチェック用のタグを echo)
	 */
	function addMixicheck() {
		echo
			$this->makeTag(
				//href, class, otheratt, pagetitle, img, alt, script
				'http://mixi.jp/share.pl',
				'mixi-check-button wph',
				' data-key="' . $this->mixi_key . '"',
				'',
				$this->img_path . 'mixi.png',
				'このエントリをmixiチェックする',
				'<script type="text/javascript" src="http://static.mixi.jp/js/share.js"></script>'
			);
	}

	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (twitterツイートボタン用のタグを echo)
	 */
	function addTweetBtn() {
		
		$title = $this->utf8_encode(get_the_title());
		
		if ($this->twitter_type=='none') {
			$twit_css = 'wph';
			$twit_att = ' onclick="if(window.open(this.href,\'tweetWin\',\'width=550,height=450,personalbar=0,toolbar=0,scrollbars=1,resizable=1\'))return false;"';
		}
		else {
			$twit_css = 'wph twitter-share-button';
			$twit_att = '';
		}
		
		echo
			$this->makeTag(
				//href, class, otheratt, pagetitle, img, alt, script
				'http://twitter.com/share?' . 'url=' . get_permalink() . '&amp;text=' . urlencode($title) . '&amp;lang=ja' . '&amp;count=' . $this->twitter_type,
				$twit_css,
				$twit_att,
				'urlencode($title)',
				$this->img_path . 'twitter.gif',
				'このエントリをつぶやく',
				'<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>',
				true
			);
	}

	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (Evernote Clip 用のタグを echo)
	 */
	function addEvernoteClip() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeTag(
				//href, class, otheratt, pagetitle, img, alt, script
				'#',
				'wph',
				' onclick="Evernote.doClip({providerName:\'' . $this->blogname . '\', code:\'' . $this->ever_add . '\', title:\'' . $title . '\', url:\'' . get_permalink() . '\', contentId:\''. $this->ever_clip_id . '\'}); return false;"',
				'',
				$this->img_path . 'evernote.png',
				'Clip to Evernote',
				'<script type="text/javascript" src="http://static.evernote.com/noteit.js"></script>',
				true
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (Facebook いいね！用のタグを echo)
	 */
	function addFacebook() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeFacebookTag(
				'http://www.facebook.com/plugins/like.php?href=' . get_permalink()
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (Facebook Share 用のタグを echo)
	 */
	function addFacebookShare() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeTag(
				//href, class, otheratt, pagetitle, txt, script
				'http://www.facebook.com/sharer.php',
				'wph fcbk_share',
				' expr:share_url="data:post.url" name="fb_share" type="button_count" share_url="' . get_permalink() . '"',
				'',
				false,
				'シェア',
				'<script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>',
				true
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (Instapaper 用のタグを echo)
	 */
	function addInstapaper() {
		
		$title = $this->utf8_encode(get_the_title());
		$url = $this->utf8_encode(get_permalink());
		
		echo
			$this->makeTag(
				//href, class, otheratt, pagetitle, img, alt, script
				'http://www.instapaper.com/hello2?url=' . urlencode($url) . '&amp;title=' . urlencode($title),
				'wph',
				'',
				'',
				$this->img_path . 'instapaper.gif',
				'Instapaper に保存する',
				''
			);
		
	}
	
	/**
	 * WP interface.
	 * 
	 * @param none
	 * @return none (Read It Later 用のタグを echo)
	 */
	function addReadItLater() {
		
		$title = $this->utf8_encode(get_the_title());
		
		echo
			$this->makeTag(
				//href, class, otheratt, pagetitle, img, alt, script
				'http://readitlaterlist.com/edit?BL=&url=' . get_permalink() . '&amp;title=' . urlencode($title),
				'wph',
				'',
				'',
				$this->img_path . 'readitlater.gif',
				'Read It Later に保存する',
				''
			);
		
	}
	
	/**
	 * Bookmark URL maker.
	 * 
	 * @param $sitename (サイト名称文字列)
	 * @param $url (URL)
	 * @param $iconfile (画像ファイル URL)
	 * @param $ext_url (その他の追加 URL)
	 * @return $tag (画像リンクタグ)
	 */
	function makeBookmarkURL($sitename, $url, $iconfile) {
		
		$tag  = '<a';
		$tag .= ' href="' . $url . '"';
		$tag .= ' target="_blank"';
		$tag .= ' class="wph" ';
		$tag .= '>';
		$tag .= '<img';
		$tag .= ' src="' . $this->img_path . $iconfile . '"';
		$tag .= ' alt="このエントリを' . $sitename . 'に追加"';
		$tag .= ' title="このエントリを' . $sitename . 'に追加"';
		//$tag .= ' onmouseover="wpHatenaPopup()"';
		$tag .= '/>';
		$tag .= '</a>';
		
		return $tag;
		
	}

	/**
	 * tag maker.
	 * 
	 * @param $url (aタグhref=""の中身)
	 * @param $class (aタグclass=""の中身)
	 * @param $otheratt (aタグにその他属性があった場合記述)
	 * @param $pagetitle (ページ名称文字列)
	 * @param $iconfile (imgのURLまたは説明)
	 * @param $alt (imgのalt,title)
	 * @param $script (末尾にscriptが必要な場合記述)
	 * @param $lazy_loading (scriptを遅延ローディングさせる場合 true をセット)
	 */
	function makeTag($url, $class, $otheratt, $pagettl, $iconfile, $alt = '', $script = '', $lazy_loading = false) {
		
		$tag  = '<a';
		$tag .= ' href="' . $url . '"';
		$tag .= ' class="' . $class . '"';
		$tag .= ' target="_blank"';
		$tag .= ' title="' . $alt . '"';
		$tag .= $otheratt;
		$tag .= '>';
		if ($iconfile !== FALSE) {
			$tag .= '<img';
			$tag .= ' src="' . $iconfile . '"';
			$tag .= ' alt="' . $alt . '"';
			$tag .= '/>';
		} else {
			$tag .= $alt;
		}
		$tag .= '</a>';

		if ($lazy_loading) {
			if (array_search($script, $this->lazy_loading_scripts) === FALSE) {
				$this->lazy_loading_scripts[] = $script;
			}
		} else {
			$tag .= $script;
		}
		
		return $tag;
		
	}

	/**
	 * for hatebu count.
	 * 
	 * @param $url (javascript URL)
	 * @param $linkurl (URL)
	 * @return $tag (画像リンクタグ)
	 */
	function makeBookmarkCountTxtTag($url, $linkurl) {
		
		$tag  = '<a';
		$tag .= ' href="' . $linkurl . '"';
		$tag .= ' target="_blank"';
		$tag .= ' class="wph hatebu-count"';
		$tag .= ' title="このエントリのはてなブックマーク数"';
		$tag .= '>';
		$tag .= '<script src="' . $url . '&amp;callback=document.write"></script>';
		$tag .= '</a>';
		
		return $tag;
		
	}

	/**
	 * for Facebook like btn.
	 * 
	 * @param $url (URL)
	 * @return $tag (画像リンクタグ)
	 */
	function makeFacebookTag($url) {
		
		if ($this->fcbk_type=='button_count') {
			$height = 20;
			if ($this->fcbk_width == null) {
				$this->fcbk_width = 100;
			}
		}
		elseif ($fcbk_type=='standard') {
			$height = 80;
			if ($this->fcbk_width == null) {
				$this->fcbk_width = 225;
			}
		}
		else {
			$height = 65;
			if ($this->fcbk_width == null) {
				$this->fcbk_width = 55;
			}
		}
		
		$tag  = '<iframe';
		$tag .= ' src="' . $url;
		$tag .= '&amp;layout=button_count&amp;show_faces=true&amp;width=' . $this->fcbk_width . '&amp;action=like&amp;colorscheme=light&amp;height=' . $height . '" scrolling="no" frameborder="0" class="wph facebook" allowTransparency="true"';
		$tag .= ' style="width:' . $this->fcbk_width . 'px; height:' . $height . 'px;"';
		$tag .= '>';
		$tag .= '</iframe>';
		
		return $tag;
		
	}

	/**
	 * UTF-8 encoder.
	 * 
	 * @param $text
	 * @return $text (UTF-8 に変換した文字列)
	 */
	function utf8_encode($text) {
		
		if(!preg_match ("/UTF-8/i", $this->blog_charset)) {
			if(function_exists('mb_convert_encoding')) {
				$text = 
					mb_convert_encoding(
						$text,
						'UTF-8',
						$this->blog_charset
					);
			}
		}
		
		return $text;
		
	}

	/**
	 * WP filter interface.(wp_head)
	 *  - 未使用 (popup.js 未実装のため) 
	 * 
	 * @param none
	 * @return none (画像説明ポップアップ JavaScript を echo)
	 */
	function addScript() {
		
		echo '<script src="http://www.google.com/jsapi" type="text/javascript"></script><script type="text/javascript">google.load("jquery", "1.4.2");</script>';
		echo '<script type="text/javascript"';
		echo ' src="'. $this->plugin_path . $this->popup_jsname . '"';
		echo '>';
		echo '</script>' . "\n";
	}

	/**
	 * WP filter interface.(wp_head)
	 * 
	 * @param none
	 * @return none (CSS を echo)
	 */
	function echoCss() {
		
		echo '<link rel="stylesheet"';
		echo ' href="' . $this->css_path . '"';
		echo 'type="text/css" media="screen" />' . "\n";
		
	}
	

	/**
	 * WP filter interface.(wp_footer)
	 * JS Lazy Loading.
	 * 
	 * @param none
	 * @return none (JS を echo)
	 */
	function JSLazyLoading() {
		if (count($this->lazy_loading_scripts) > 0) {
			echo implode("\n", $this->lazy_loading_scripts) . "\n";
		}
	}
}

/******************************************************************************
 * wp-hatena css - WordPress Interface Define
 *****************************************************************************/
//$wph = & new WpHatena();
?>