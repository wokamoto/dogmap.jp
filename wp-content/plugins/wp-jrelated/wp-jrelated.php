<?php
/*
 Plugin Name: WordPress Related Post for Japanese
 Plugin URI: http://wppluginsj.sourceforge.jp/wp-jrelated/
 Description: Yahoo! Japan が提供する「日本語形態素解析Webサービス」を使って投稿の内容を分析し関連する投稿などを表示します。
 Version: 1.52
 Author: hiromasa
 Author URI: http://another.maple4ever.net/
 */

/*  Copyright 2008 hiromasa (email : h1romas4@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/******************************************************************************
 * WpJRelated - WordPress Interface Define
 *****************************************************************************/

if(!(defined('ABSPATH') && defined('WPINC'))) return;

/******************************************************************************
 * WpJRelated
 *
 * @author		hiromasa
 * @version		1.50
 *
 *****************************************************************************/
class WpJRelatedController {
	
	var $plugin_name;
	var $model;
	var $modeDictionary;
	
	/**
	 * Constructor
	 *
	 * @return WpJRelatedController
	 */
	function WpJRelatedController() {
		
		$this->plugin_name = 'WpJRelated';
		$this->modeDictionary = false;
		$this->model = $this->getModelObject();
		//model version check and update
		if($this->model->modelUpdate()) {
			$this->updateWpOption($this->model);
		}
		
	}
	
	/**
	 * executeSchedulePublisd
	 *
	 * @param unknown_type $postID
	 */
	function executeSchedulePublisd($postID) {
		
		wp_schedule_single_event(
			time(),
			'makeDictionary',
			array($postID, true));
		
	}

	/**
	 * executeScheduleView
	 *
	 * @param unknown_type $postID
	 */
	function executeScheduleView($content) {
		
		global $post;
		
		if(!is_single()) return $content;
		if($this->modeDictionary)  return $content;
		
		$postID = $post->ID;
		if($this->model->isExistsMorpheme($postID)) {
			return $content;
		}
		
		wp_schedule_single_event(
			time(),
			'makeDictionary',
			array($postID, false));
		
		return $content;
		
	}
	
	/**
	 * outputRelated
	 *
	 * @param unknown_type $content
	 * @return unknown
	 */
	function outputRelated($content) {
		
		global $post;
		
		if(!is_single()) return $content;
		if($this->modeDictionary)  return $content;
		if ( !is_feed() && !empty($content) )
			$content .= $this->getRelated($post->ID);
		
		return $content;
		
	}
	
	/**
	 * getRelated
	 *
	 */
	function getRelated($postID, $array = false) {
		
		return $this->model->getRelatedPost($postID, $array);
		
	}
	
	/**
	 * makeDictionary
	 *
	 * @param unknown_type $postID
	 */
	function makeDictionary($postID, $update = true) {
		
		$this->modeDictionary = true;
		$this->model->makeDictionary($postID, $update);
		
	}
	
	/**
	 * getTopWord
	 *
	 * @param unknown_type $postID
	 * @param unknown_type $count
	 * @return unknown
	 */
	function outputTopWord($postID, $count, $delimiter = ', ') {
		
		if(!$this->isEnabled()) return;
		
		$texts = $this->model->getTopWord($postID, $count);
		$output = '';
		foreach($texts as $text) {
			$output .= $text . $delimiter;
		}
		$output = substr($output, 0, strlen($output) - strlen($delimiter));
		
		return $output;
		
	}
	
	/**
	 * isEnabled
	 *
	 * @return unknown
	 */
	function isEnabled() {
		
		if(trim($this->model->yahooId) != '') {
			return true;
		}
		return false;
		
	}
	
	/**
	 * isEnabledRelated
	 *
	 * @return unknown
	 */
	function isEnabledRelated() {
		
		return $this->model->outputRelated;
		
	}

	/**
	 * getNotifyPublish
	 *
	 * @return unknown
	 */
	function getNotifyPublish() {
		
		return $this->model->makeNotify['publish'];
		
	}
	
	/**
	 * getNotifyPublish
	 *
	 * @return unknown
	 */
	function getNotifyView() {
		
		return $this->model->makeNotify['view'];
		
	}
	
	/**
	 * Define WordPress Admin Interface
	 *
	 */
	function addAdminMenu() {
		
		add_options_page(
			'JRelated',
			'JRelated',
			'manage_options',
			basename(__FILE__),
			array (&$this, 'executeAdmin')
		);
		
	}
	
	/**
	 * Execute WordPress Admin Interface
	 *
	 */
	function executeAdmin() {
		
		$requestVO = new WpJRelatedHTTPRequestVO();
		$messages = array();
		
		$action = $requestVO->getParam('action');
		if($action == 'update') {
			if($this->model->updateOption($requestVO, $messages)) {
				$this->updateWpOption($this->model);
			}
		}
		
		$view = new WpJRelatedView();
		$resultVO = & new WpJRelatedValueObject();
		$this->model->getOption($resultVO);
		$view->echoAdmin($resultVO, $messages);
		
	}
	
	/**
	 * getModelObject
	 * 
	 * @return $model
	 */
	function getModelObject() {
		
		$option = get_option($this->plugin_name);
		
		$model = null;
		//strtolower for PHP4
		if(strtolower(get_class($option)) == strtolower('WpJRelated')) {
			$model = $option;
		} else {
			$model = new WpJRelated();
			$this->addWpOption($model);
		}
		
		return $model;
		
	}
	
	/**
	 * Add WordPress Option
	 *
	 * @param WpJRelated $optionValue
	 */
	function addWpOption($optionValue) {
		
		$option_description = $this->plugin_name . " Options";
		add_option($this->plugin_name, $optionValue, $option_description);
		
	}
	
	/**
	 * Update WordPress Option
	 *
	 * @param WpJRelated $optionValue
	 */
	function updateWpOption($optionValue) {
		
		$option_description = $this->plugin_name . " Options";
		update_option($this->plugin_name, $optionValue, $option_description);
		
	}
	
}

/******************************************************************************
 * WpJRelated
 *
 * @author		hiromasa
 * @version		1.50
 *
 *****************************************************************************/
class WpJRelated {
	
	var $version;
	var $staleKey;
	var $yahooId;
	var $makeNotify = array();
	var $relatedHeader;
	var $outputRelated;
	var $outputLogo;
	var $stopWord;
	var $outputEntryCount;
	var $searchWordCount;
	var $outputWordCount;
	
	var $yahooURL = 'http://jlp.yahooapis.jp/MAService/V1/parse';
	
	/**
	 * WpJRelated
	 *
	 * @return WpJRelated
	 */
	function WpJRelated() {
		
		$this->version = 'WpJRelated/1.2';
		$this->staleKey = $this->getStaleKey();
		$this->yahooId = "";
		$this->makeNotify['publish'] = true;
		$this->makeNotify['view'] = true;
		$this->relatedHeader = '<h3>このブログで関連すると思われる他の投稿</h3>';
		$this->outputRelated = true;
		$this->outputLogo = true;
		$this->stopWord = 'this,that,it,is,of,in,and,the,こと,ところ,もの,こちら,あと,これ,やつ,ため,わけ,の,ふ,0,1,2,3,4,5,6,7,8,9';
		$this->outputEntryCount = 5;
		$this->searchWordCount = 15;
		$this->outputWordCount = 20;
		
		$this->createTable();
		
	}
	
	/**
	 * modelUpdate
	 *
	 * @return unknown
	 */
	function modelUpdate() {
		
		$currentVersion = "WpJRelated/1.2";
		
		if($this->version == $currentVersion) return false;
		
		// version 1.50
		if($this->version == 'WpJRelated/1.1') {
			$this->version = $currentVersion;
			$this->yahooURL = 'http://jlp.yahooapis.jp/MAService/V1/parse';
		}
		// version 1.0
		if($this->version == 'WpJRelated/1.0') {
			$this->version = $currentVersion;
			$this->stopWord = '';
			$this->outputEntryCount = 5;
			$this->searchWordCount = 10;
			$this->outputWordCount = 20;
		}
		
		return true;
		
	}
	
	/**
	 * makeDictionary
	 *
	 * @param unknown_type $postID
	 * @param unknown_type $update
	 */
	function makeDictionary($postID, $update) {
		
		global $wpdb;
		
		//新規ならテーブルに INSERT、既存なら UPDATE
		$existes = $this->isExistsMorpheme($postID);
		//既に存在していて投稿の更新がなければ処理しない
		if($update == false && $existes == true) return; 
		
		//投稿取得
		$post = get_post($postID);
		if(empty($post)) return;
		//フィルター適応(is_singleではないのでスケジュール重複はしない)
		$post = apply_filters('the_content', $post->post_content);
		//タイトルと投稿を合成して整形（HTMLタグ除去）
		$content = strip_tags($post->post_title . ' ' . $post);	
		//HTML実体参照削除(TODO:とりあえず)
		$content =
			str_replace('&', '', preg_replace('/(#[0-9]+|&[^;]+);/', '', $content));
		
		//日本語形態素解析Webサービスに問い合わせ
		$responseMaXml = $this->requestYahoo($content, 'ma');
		$responseUniqXml = $this->requestYahoo($content, 'uniq');
		
		//レスポンス不正なら次回に期待してなにもしない
		$response = @simplexml_load_string($responseUniqXml);
		if($response === false) return;
		
		//辞書用情報作成
		$delimitar_text = '';
		$delimitar_text_hash = '';
		
		//名詞のみ抽出
		$words = $response->uniq_result->word_list->word;
		if(count($words) == 0) return;
		foreach ($words as $word) {
			if($word->pos != '名詞') continue;
			$delimitar_text .= $word->surface . ',';
			$delimitar_text_hash .= md5($word->surface) . ',';
		}
		$delimitar_text =
			substr($delimitar_text, 0, strlen($delimitar_text) - 1);
		$delimitar_text_hash =
			substr($delimitar_text_hash, 0, strlen($delimitar_text_hash) - 1);
		
		//RAWデータと辞書データをデータベースに INSERT/UPDATE
		if(!$existes) {
			$this->insertMorpheme(	
				$this->yahooId, 
				$postID,
				$responseMaXml,
				$responseUniqXml,
				$delimitar_text,
				$delimitar_text_hash);
		} else {
			$this->updateMorpheme(	
				$this->yahooId, 
				$postID,
				$responseMaXml,
				$responseUniqXml,
				$delimitar_text,
				$delimitar_text_hash);
		}
		
	}
	
	/**
	 * requestYahoo
	 *
	 * @param unknown_type $content
	 * @param unknown_type $kind
	 * @return unknown
	 */
	function requestYahoo($content, $kind) {
		
		$request = array(
			'appid' => $this->yahooId,
			'sentence' => $content,
			'results' => $kind);
		$request = http_build_query($request, "", "&");
		$header = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: ' . strlen($request)
		);
		$context = array(
			'http' => array(
				'method' => 'POST',
				'header' => implode("\r\n", $header),
				'content' => $request
			)
		);
		$context = stream_context_create($context);
		$response = @file_get_contents($this->yahooURL, false, $context);
		if($response === false) return false;
		
		return $response;
		
	}

	/**
	 * getRelatedPost
	 *
	 * @param unknown_type $postID
	 * @return unknown
	 */
	function getRelatedPost($postID, $array = false) {
		
		global $wpdb;
		
		//検索用頻出単語リスト取得
		$hashes = $this->getTopWord($postID, $this->searchWordCount, true);
		if($hashes === false) {
			if($array) return array();
			else return;
		}
		//がんばってSQLつくる
		$sql_select = '';
		$sql_where = '';
		foreach($hashes as $hash) {
			$sql_select .= $wpdb->prepare(
				"IF(INSTR(mo.uniq_delimitar_hash, '%s') <> 0,INSTR(mo.uniq_delimitar_hash, '%s'), 10000) +"
				,$hash
				,$hash
			);
			$sql_where .= $wpdb->prepare(
				"mo.uniq_delimitar_hash LIKE '%s' or "
				,'%' . $hash . '%'
			);
		}
		$sql_select = substr($sql_select , 0, strlen($sql_select) - 1) . "\n";
		$sql_where = substr($sql_where , 0, strlen($sql_where) - 3) . "\n";
		$appId = $wpdb->prepare("mo.appid = '%s'", $this->yahooId);
		$limit =
			$wpdb->prepare("LIMIT 0, %d", intval($this->outputEntryCount));
		$query =
			"SELECT" .
			"    mo.ID" .
			"    ,$sql_select as rank" .
			"    ,left(mo.uniq_delimitar_text, 1024) as texts" .
			"  FROM" .
			"    {$wpdb->prefix}morpheme as mo" .
			"    ,{$wpdb->prefix}posts as po" .
			"  WHERE" .
			"    ($sql_where) " .
			"    AND mo.ID <> $postID" .
			"    AND $appId " .
			"    AND po.post_status = 'publish'" .
			"    AND po.post_password = ''" .
			"    AND mo.ID = po.ID" .
			"  ORDER BY" .
			"  	 rank" .
			"  $limit";
		
		//クエリーなげて関連投稿リンクの文字列作成
		$entries = $wpdb->get_results($query);
		if(count($entries) == 0) {
			if($array) return array();
			else return;
		}
		//リンク文字列作成
		$content .= '<div id="wpjr">' . "\n";;
		if(trim($this->relatedHeader) != '') {
			$content .= $this->relatedHeader . "\n";
		}
		$content .= '<ul id="wpjrelated">' . "\n";
		$related = array();
		foreach($entries as $entry) {
			$title = get_the_title($entry->ID);
			$link = get_permalink($entry->ID);
			$text = join(", ", $this->excludeStopWord($entry->texts, $this->outputWordCount));
			$content .= "<li><a href=\"{$link}\">{$title}</a></li>\n";
			$related[$entry->ID]['title'] = $title;
			$related[$entry->ID]['link'] = $link;
			$related[$entry->ID]['text'] = htmlspecialchars($text);
		}
		$content .= '</ul>' . "\n";
		if($this->outputLogo == true) {
			$content .= '<div class="yahoologo">' . "\n";
			$content .= '<!-- Begin Yahoo! JAPAN Web Services Attribution Snippet -->' . "\n";
			$content .= '<a href="http://developer.yahoo.co.jp/about"><img src="http://i.yimg.jp/images/yjdn/yjdn_attbtn1_125_17.gif" title="Webサービス by Yahoo! JAPAN" alt="Web Services by Yahoo! JAPAN" width="125" height="17" border="0" style="margin:15px 15px 15px 15px" /></a>' . "\n";
			$content .= '<!-- End Yahoo! JAPAN Web Services Attribution Snippet -->' . "\n";
			$content .= '</div>' . "\n";;
		}
		$content .= '</div>' . "\n";
		
		$returns = $content; 
		if($array) {
			$returns = $related;
		}
		
		return $returns;
		
	}
	
	/**
	 * getTopWord
	 *
	 * @param unknown_type $postID
	 * @param unknown_type $count
	 * @return unknown
	 */
	function getTopWord($postID, $count, $hash = false) {
		
		global $wpdb;
		
		//投稿の形態素分析結果から頻出単語上位10個のテキストを取得する。
		$morpheme = $wpdb->get_row($wpdb->prepare(
			"SELECT uniq_delimitar_text FROM {$wpdb->prefix}morpheme WHERE appid = '%s' AND ID = '%s' LIMIT 0, 1;"
			,$this->yahooId
			,$postID
		));
		$returns = 
			$this->excludeStopWord($morpheme->uniq_delimitar_text, $count, $hash);
		
		return $returns;
		
	}
	
	/**
	 * excludeStopWord
	 *
	 * @param unknown_type $text
	 * @param unknown_type $count
	 * @param unknown_type $hash
	 * @return unknown
	 */
	function excludeStopWord($text, $count, $hash = false) {
		
		$count = intval($count);
		$texts = split(',', $text);
		if($texts === false) return false;
		
		//上位10(ストップワード除く)以外は捨てる
		$returns = array();
		$pattern = '/^(' . str_replace(',', '|', preg_quote($this->stopWord)) . ')$/i';
		for ($i=0, $j=0; $i < count($texts); $i++) {
			if (!preg_match($pattern, $texts[$i])) {
		    	//ストップワード以外の頻出単語上位10個のハッシュ/文字列を取得する
		    	if($hash) {
			    	$returns[$j] = md5($texts[$i]);
		    	} else {
			    	$returns[$j] = $texts[$i];
		    	}
		    	$j++;
			}
			if ($j >= $count) break;
		}
		
		return $returns; 
		
	}
	
	/**
	 * isExistsMorpheme
	 *
	 * @param unknown_type $yahooId
	 * @param unknown_type $postID
	 * @return unknown
	 */
	function isExistsMorpheme($postID) {
		
		global $wpdb;
		
		//morpheme レコード存在確認
		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT count(*) FROM {$wpdb->prefix}morpheme WHERE appid = '%s' AND ID = '%s';",
			$this->yahooId,
			$postID
			));
		if($count == 0) return false;
		
		return true; 
		
	}
	
	/**
	 * insertMorpheme
	 *
	 * @param unknown_type $yahooId
	 * @param unknown_type $postID
	 * @param unknown_type $responseMaXml
	 * @param unknown_type $responseUniqXml
	 * @param unknown_type $delimitar_text
	 * @param unknown_type $delimitar_text_hash
	 * @return unknown
	 */
	function insertMorpheme(	
		$yahooId, 
		$postID,
		$responseMaXml,
		$responseUniqXml,
		$delimitar_text,
		$delimitar_text_hash) {
		
		global $wpdb;
		
		$wpdb->query($wpdb->prepare(
			"INSERT" .
			 " INTO" .
			 "   {$wpdb->prefix}morpheme(" .
			 "     appid" .
			 "     ,ID" .
			 "     ,xml_ma" .
			 "     ,xml_uniq" .
			 "     ,uniq_delimitar_text" .
			 "     ,uniq_delimitar_hash" .
			 "     ,update_time" .
			 "     ,make_time" .
			 "   )" .
			 " VALUES" .
			 "   (" .
			 "     '%s'" .
			 "     ,'%s'" .
			 "     ,'%s'" .
			 "     ,'%s'" .
			 "     ,'%s'" .
			 "     ,'%s'" .
			 "     ,NOW()" .
			 "     ,NOW()" .
			 "   )",
			$yahooId, 
			$postID,
			$responseMaXml,
			$responseUniqXml,
			$delimitar_text,
			$delimitar_text_hash));
		
		return true;
		
	}
	
	/**
	 * updateMorpheme
	 *
	 * @param unknown_type $yahooId
	 * @param unknown_type $postID
	 * @param unknown_type $responseMaXml
	 * @param unknown_type $responseUniqXml
	 * @param unknown_type $delimitar_text
	 * @param unknown_type $delimitar_text_hash
	 * @return unknown
	 */
	function updateMorpheme(	
		$yahooId, 
		$postID,
		$responseMaXml,
		$responseUniqXml,
		$delimitar_text,
		$delimitar_text_hash) {
		
		global $wpdb;
		
		$wpdb->query($wpdb->prepare(
			"UPDATE". 
			 "    {$wpdb->prefix}morpheme". 
			 "  SET". 
			 "    xml_ma = '%s'". 
			 "    ,xml_uniq = '%s'". 
			 "    ,uniq_delimitar_text = '%s'". 
			 "    ,uniq_delimitar_hash = '%s'". 
			 "    ,update_time = NOW()". 
			 "  WHERE". 
			 "    appid = '%s'". 
			 "    AND ID = '%s'"
			,$responseMaXml
			,$responseUniqXml
			,$delimitar_text
			,$delimitar_text_hash
			,$yahooId
			,$postID));
		
		return true;
		
	}
	
	/**
	 * createTable
	 *
	 * @return unknown
	 */
	function createTable() {
		
		global $wpdb;
		
		$charset_collate = '';
		if ($wpdb->supports_collation()) {
			if (!empty($wpdb->charset))
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if (!empty($wpdb->collate))
				$charset_collate .= " COLLATE $wpdb->collate";
		}
		
		$wpdb->query(
			"DROP TABLE {$wpdb->prefix}morpheme;");
		$wpdb->query(
			"CREATE TABLE {$wpdb->prefix}morpheme(" .
			"  appid VARCHAR(255) NOT NULL," .
			"  ID BIGINT(20) NOT NULL," .
			"  xml_ma text," .
			"  xml_uniq text," .
			"  uniq_delimitar_text text," .
			"  uniq_delimitar_hash text," .
			"  update_time DATETIME  NOT NULL default '0000-00-00 00:00:00'," .
			"  make_time DATETIME  NOT NULL default '0000-00-00 00:00:00'" .
			") {$charset_collate};");
		$wpdb->query(
			"ALTER TABLE {$wpdb->prefix}morpheme add unique morpheme_key (" .
			"   appid," .
			"   ID" .
			");");
		
		return true;
		
	}
	
	/**
	 * getOption
	 *
	 * @param unknown_type $resultVO
	 */
	function getOption(&$resultVO) {
		
		$resultVO->setParam('yahooid', $this->yahooId);
		$resultVO->setParam('publish', $this->makeNotify['publish']);
		$resultVO->setParam('view', $this->makeNotify['view']);
		$resultVO->setParam('relatedheader', $this->relatedHeader);
		$resultVO->setParam('outputrelated', $this->outputRelated);
		$resultVO->setParam('outputlogo', $this->outputLogo);
		$resultVO->setParam('stopword', $this->stopWord);
		$resultVO->setParam('outputentrycount', $this->outputEntryCount);
		$resultVO->setParam('searchwordcount', $this->searchWordCount);
		$resultVO->setParam('outputwordcount', $this->outputWordCount);
		$resultVO->setParam('staleKey', $this->staleKey);
		
	}
	
	/**
	 * updateOption
	 *
	 * @param unknown_type $request
	 * @param unknown_type $messages
	 * @return unknown
	 */
	function updateOption(&$requestVO, &$messages) {
		
		if($this->staleKey != $requestVO->getParam('stalekey')) {
			array_push(
				$messages,
				"データが他で更新されている可能性があるため、変更を破棄し更新を中止しました。");
			return false;
		}
		
		$this->yahooId =
			stripslashes($requestVO->getParam('yahooid'));
		$this->makeNotify['publish'] =
			$requestVO->getParam('publish') != '' ? true : false;
		$this->makeNotify['view'] = 
			 $requestVO->getParam('view') != '' ? true : false;
		$this->relatedHeader =
			stripslashes($requestVO->getParam('relatedheader'));
		$this->outputRelated = 
			 $requestVO->getParam('outputrelated') != '' ? true : false;
		$this->outputLogo = 
			 $requestVO->getParam('outputlogo') != '' ? true : false;
		$this->stopWord = 
			 stripslashes($requestVO->getParam('stopword'));
		$this->outputEntryCount = 
			 stripslashes($requestVO->getParam('outputentrycount'));
		$this->searchWordCount = 
			 stripslashes($requestVO->getParam('searchwordcount'));
		$this->outputWordCount = 
			 stripslashes($requestVO->getParam('outputwordcount'));
		
		$this->staleKey = $this->getStaleKey();
		
		return true;
		
	}
	
	/**
	 * getStaleKey
	 *
	 * @return unknown
	 */
	function getStaleKey() {
		
		return md5(time() . SECURE_AUTH_KEY);
		
	}
	
}

/******************************************************************************
 * WpJRelatedView
 *
 * @author		hiromasa
 * @version		1.50
 *
 *****************************************************************************/
class WpJRelatedView {
	
	var $result;
	
	/**
	 * echoAdmin
	 *
	 * @param unknown_type $result
	 * @param unknown_type $messages
	 */
	function echoAdmin(&$result = null, &$messages = array()) {
		
		$this->result = $result;
		
		$yahooId = $this->outputValue($this->result->getParam('yahooid'), false);
		$makeNotify['publish'] =
			$this->outputValue($this->result->getParam('publish') == true ? 'checked' : '', false);
		$makeNotify['view'] = 
			$this->outputValue($this->result->getParam('view') == true ? 'checked' : '', false);
		$outputRelated = 
			$this->outputValue($this->result->getParam('outputrelated') == true ? 'checked' : '', false);
		$outputLogo = 
			$this->outputValue($this->result->getParam('outputlogo') == true ? 'checked' : '', false);
		$relatedHeader = $this->outputValue($this->result->getParam('relatedheader'), false);
		$stopWord = $this->outputValue($this->result->getParam('stopword'), false);
		$outputEntryCount = $this->outputValue($this->result->getParam('outputentrycount'), false);
		$searchWordCount = $this->outputValue($this->result->getParam('searchwordcount'), false);
		$outputWordCount = $this->outputValue($this->result->getParam('outputwordcount'), false);
		
		$this->echoMessage($messages);
		$this->echoAdminHeader();
		
		echo '<table class="form-table">' . "\n";
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="yahooid">Yahoo! JAPAN Webサービス アプリケーションID</label></th>' . "\n";
		echo '<td>';
		echo "<input name=\"yahooid\" type=\"text\" id=\"yahooid\" value=\"{$yahooId}\" size=\"40\" />";
		echo " (必須)" . "\n";
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="makeindex">インデックス作成契機</label></th>' . "\n";
		echo '<td>' . "\n";
		echo '<label for="publish">';
		echo '<input name="publish" type="checkbox" id="publish" style="vertical-align: text-bottom;"';
		echo " {$makeNotify['publish']} />";
		echo ' 投稿時</label><br />' . "\n";
		echo '<label for="view">';
		echo '<input name="view" type="checkbox" id="view" style="vertical-align: text-bottom;"';
		echo " {$makeNotify['view']} />";
		echo ' 閲覧時(投稿を閲覧した時にその投稿の辞書がなければ作成します)</label><br />' . "\n";
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="relatedheader">記事下への関連自動出力</label></th>' . "\n";
		echo '<td>';
		echo '<input name="outputrelated" type="checkbox" id="outputrelated" style="vertical-align: text-bottom;"';
		echo " {$outputRelated} /> シングルページ表示時に投稿の最後に関連投稿のリンクを付与する。<br />";
		echo "出力時に付与するヘッダー" . "\n";
		echo "<input name=\"relatedheader\" type=\"text\" id=\"relatedheader\" value=\"{$relatedHeader}\" size=\"40\" /> <br />";
		echo '<input name="outputlogo" type="checkbox" id="outputlogo" style="vertical-align: text-bottom;"';
		echo " {$outputLogo} /> Yahoo! JAPAN Webサービスロゴを結果フッターに出力する。";
		echo '</td>' . "\n";
		echo '<td>' . "\n";;
		echo '</tr>' . "\n";
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="outputentrycount">関連出力記事数</label></th>' . "\n";
		echo '<td>';
		echo "<input name=\"outputentrycount\" type=\"text\" id=\"outputentrycount\" value=\"{$outputEntryCount}\" size=\"4\" /><br />";
		echo " 出力する記事数を指定します。デフォルトは 5 です。\n";
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="stopword">ストップワード指定</label></th>' . "\n";
		echo '<td>';
		echo "<input name=\"stopword\" type=\"text\" id=\"stopword\" value=\"{$stopWord}\" size=\"70\" /><br />";
		echo " 「こと,ところ,もの,こちら」など記事の特徴を表さない一般的な言葉をカンマ区切りで指定します。\n";
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="searchwordcount">検索単語数</label></th>' . "\n";
		echo '<td>';
		echo "<input name=\"searchwordcount\" type=\"text\" id=\"searchwordcount\" value=\"{$searchWordCount}\" size=\"4\" /><br />";
		echo " その記事の特徴を示す単語が頻出語上位何個目までかを指定します。デフォルトは 15 です。\n";
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
		echo '<tr valign="top">' . "\n";
		echo '<th scope="row"><label for="outputwordcount">出力単語数</label></th>' . "\n";
		echo '<td>';
		echo "<input name=\"outputwordcount\" type=\"text\" id=\"outputwordcount\" value=\"{$outputWordCount}\" size=\"4\" /><br />";
		echo " jrelated_get_array 関数利用時に text に出力される単語数を指定します。\n";
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
		echo '</table>' . "\n";
		echo '<p class="submit">' . "\n";
		echo '<input type="submit" name="Submit" value="Save Changes" />' . "\n";
		echo '</p>' . "\n";
		
		$this->echoAdminFooter();
		
	}
	
	/**
	 * echoMessage
	 *
	 * @param unknown_type $messages
	 */
	function echoMessage($messages) {
		
		if(count($messages) == 0) return;
		
		$information = '';
		foreach($messages as $message) {
			$information .= '<p><strong>'
				. $this->outputValue($message, false)
				. '</strong></p>' . "\n";
		}
		
		echo '<div id="message" class="updated fade">' . "\n";
		echo $this->outputValue($information, false, false);
		echo '</div>' . "\n";
		
	}
	
	/**
	 * echoAdminHeader
	 *
	 */
	function echoAdminHeader() {
		
		echo '<div class="wrap">' . "\n";;
		echo '<h2>JRelated</h2>' . "\n";
		echo '<form method="post">' . "\n";
		
	}
	
	/**
	 * echoAdminFooter
	 *
	 */
	function echoAdminFooter() {
		
		$stakeKey =
			$this->outputValue($this->result->getParam('staleKey'), false);
		
		echo "<input type=\"hidden\" name=\"stalekey\" value=\"$stakeKey\" />" . "\n";
		echo '<input type="hidden" name="action" id="action" value="update" />' . "\n";
		echo '</form>';
		echo '</div><!-- wrap -->';
		
	}
	
	/**
	 * outputValue
	 *
	 * @param unknown_type $value
	 * @param unknown_type $echo
	 * @param unknown_type $escape
	 * @return unknown
	 */
	function outputValue($value, $echo = true, $escape = true) {
		
		$result = stripslashes($value);
		if($escape) {
			$result = attribute_escape($value);
		}
		if($echo) {
			echo $result;
		}
		return $result;
		
	}
	
}

/******************************************************************************
 * ValueObject Class
 * 
 * @author     hiromasa
 * @version    0.1a
 * 
 *****************************************************************************/
class WpJRelatedValueObject {
	
	var $paramsMap = Array ();
	
	/**
	 * setParam
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	function setParam($name, $value) {
		
		$this->paramsMap[$name] = $value;
		
	}
	
	/**
	 * getParam
	 *
	 * @param unknown_type $name
	 * @return unknown
	 */
	function getParam($name) {
		
		return $this->paramsMap[$name];
		
	}
	
}

/******************************************************************************
 * HTTPRequestVO Class
 * 
 * @author     hiromasa
 * @version    0.1a
 * 
 *****************************************************************************/
class WpJRelatedHTTPRequestVO extends WpJRelatedValueObject {
	
	/**
	 * Constructor
	 *
	 * @return WpJRelatedHTTPRequestVO
	 */
	function WpJRelatedHTTPRequestVO() {
		
		if(is_array($_REQUEST)) {
			foreach ($_REQUEST as $name => $value) {
				$this->setParam($name, $value);
			}
		}
		
	}
	
}

/******************************************************************************
 * WpJRelated - Global Template Tag
 *****************************************************************************/
function jrelated_get_array($postID) {
	global $wpjr;
	return $wpjr->getRelated($postID, true);
}

/******************************************************************************
 * WpJRelated - WordPress Interface Define
 *****************************************************************************/

$wpjr = new WpJRelatedController();

if(is_admin()) {
	add_action('admin_menu', array (&$wpjr, 'addAdminMenu'));
}

if(!$wpjr->isEnabled()) return;

add_action('makeDictionary', array (&$wpjr, 'makeDictionary'), 10, 2);

if($wpjr->getNotifyPublish()) {
	add_action('publish_post', array (&$wpjr, 'executeSchedulePublisd'));
}
if($wpjr->getNotifyView()) {
	add_filter('the_content', array (&$wpjr, 'executeScheduleView'));
}
if($wpjr->isEnabledRelated()) {
	add_filter('the_content', array (&$wpjr, 'outputRelated'));
}

?>
