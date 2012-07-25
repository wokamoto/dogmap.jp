<?php

/**
 * FeedLoggerメイン処理クラス
 * 
 * User-Agentのパース、Log/データファイルの書き出し、数値計算など
 */
class FeedLogger {
	//Feedリーダー種類
	//新しいものは随時ここに追加していく
	var $fetcher_type = array(
					'Feedfetcher-Google' => array(
									'name' => 'Google Reader',
									'url' => 'http://www.google.com/reader'),
					'Bloglines' => array(
									'name' => 'Bloglines',
									'url' => 'http://www.bloglines.com'),
					'Rojo' => array(
									'name' => 'Rojo',
									'url' => 'http://www.rojo.com'),
					'Feedpath' => array(
									'name' => 'feedpath Rabbit',
									'url' => 'http://www.feedpath.co.jp'),
					'YahooFeedSeeker' => array(
									'name' => 'Yahoo RSSリーダー',
									'url' => 'http://my.yahoo.co.jp',
									'regex' => 'users ([0-9,]+)'),
					'Netvibes' => array(
									'name' => 'Netvibes',
									'url' => 'http://www.netvibes.com'),
					'FEEDBRINGER' => array(
									'name' => 'FEEDBRINGER',
									'url' => 'http://feedbringer.net'),
					'Hatena RSS' => array(
									'name' => 'はてなRSS',
									'url' => 'http://r.hatena.ne.jp'),
					'livedoor FeedFetcher' => array(
									'name' => 'livedoor Reader',
									'url' => 'http://reader.livedoor.com'),
					'FreshReader' => array(
									'name' => 'FreshReader',
									'url' => 'http://www.freshreader.com'),
					'Fastladder' => array(
									'name' => 'Fastladder',
									'url' => 'http://fastladder.com'),
					'DELCO READER' => array(
									'name' => 'DELCO READER',
									'url' => 'http://reader.freerss.net'),
					'PAIPO-Bot' => array(
									'name' => 'PAIPO READER',
									'url' => 'http://paipo.jp/'),
					'NewsGatorOnline' => array(
									'name' => 'NewsGator Online',
									'url' => 'http://www.newsgator.com'),
					'PluckFeedCrawler' => array(
									'name' => 'Pluck',
									'url' => 'http://www.pluck.com'),
					'NewsAlloy' => array(
									'name' => 'NewsAlloy',
									'url' => 'http://www.NewsAlloy.com'),
					'RssFwd' => array(
									'name' => 'RSSFWD',
									'url' => 'http://www.rssfwd.com'),
					'Feedshow' => array(
									'name' => 'Feedshow',
									'url' => 'http://www.feedshow.com'),
					'AttensaEnterprise' => array(
									'name' => 'attensa',
									'url' => 'http://www.attensa.com'),
					'ThePort' => array(
									'name' => 'ThePort',
									'url' => 'http://theport.com',
									'regex' => 'subscribers ([0-9,]+)'),
					'POCO RSS Spider' => array(
									'name' => 'POCO',
									'url' => 'http://my.poco.cn'),
					'Gougou' => array(
									'name' => 'Gougou',
									'url' => 'http://www.gougou.com'),
					'Opera Bridge crawler' => array(
									'name' => 'opera',
									'url' => 'http://opera.com',
									'regex' => 'subscribers: ([0-9,]+)'),
					'Feedeye Crawler' => array(
									'name' => 'Feedeye',
									'url' => 'http://feedeye.com',
									'regex' => 'for ([0-9,]+) set\(s\)'),
					'feedness-bot' => array(
									'name' => 'opera',
									'url' => 'http://opera.com',
									'regex' => 'suscriptores:([0-9,]+)'),
					'LiveJournal.com' => array(
									'name' => 'LiveJournal',
									'url' => 'http://www.livejournal.com',
									'regex' => '([0-9,]+) readers'),
					);
	var $format_ver = '2';
	var $sub_file = 'subscribers.dat';
	var $df_regex = '([0-9,]+) +subscribe';
	var $datadir = '';
	var $logdir = '';
	var $clogdir = '';
	var $logging = true;
	var $sb_dat = null;
	
	function FeedLogger(){
		$this->datadir = dirname(dirname(__FILE__))."/data";
		
		if (!is_writable($this->datadir)){
			die('FeedLogger:Error '.$this->datadir.'が書き込み可能ではありません');
		}
		
		if ($this->logging){
			$this->logdir = $this->datadir."/logs";
			if (!is_dir($this->logdir)){
				mkdir($this->logdir);
				chmod($this->logdir,0777);
			}
			$this->clogdir = $this->logdir."/".date("Y");
			if (!is_dir($this->clogdir)){
				mkdir($this->clogdir);
				chmod($this->clogdir,0777);
			}
		}
	}
	
	function &singleton(){
		static $feedlogger = null;
		
		if ($feedlogger === null){
			$feedlogger = new FeedLogger;
		}
		
		return $feedlogger;
	}
	
	/**
	 * ログファイル、データファイルに書き出す
	 */
	function write($feed_type){
		$ua_dat = $this->parseUserAgent($_SERVER['HTTP_USER_AGENT']);
		
		if ($ua_dat['reader'] or (strpos($_SERVER['HTTP_USER_AGENT'],"subscribe") !== false)){
			//ログファイルに書き出す
			
			if ($this->logging){
				$filepath = $this->clogdir."/".date("Ymd").".log";
				if (!file_exists($filepath)){
					$fp = @fopen($filepath,"w");
					chmod($filepath,0777);
				}else{
					$fp = @fopen($filepath,"a+");
				}
				if ($fp){
					flock($fp,LOCK_EX);
					fputs($fp,date("Y-m-d H:i:s")." ".$feed_type." ".$_SERVER['HTTP_USER_AGENT']."\n");
					flock($fp,LOCK_UN);
					fclose($fp);
				}
			}
			
			if ($ua_dat['subscriber']){
				$sb_dat = $this->getFeedSummary();
				$sb_dat['_v'] = $this->format_ver;
				$sb_dat[$ua_dat['reader']][$feed_type]['num'] = $ua_dat['subscriber'];
				$sb_dat[$ua_dat['reader']][$feed_type]['time'] = time();
				
				$filepath = $this->datadir."/".$this->sub_file;
				if (!file_exists($filepath)){
					$fp = @fopen($filepath,"w");
					chmod($filepath,0777);
				}else{
					$fp = @fopen($filepath,"w");
				}
				if ($fp){
					flock($fp,LOCK_EX);
					fputs($fp,serialize($sb_dat));
					flock($fp,LOCK_UN);
					fclose($fp);
				}
			}
		}
	}
	
	/**
	 * User-AgentをパースしてReader名と購読者数を取得する
	 */
	function parseUserAgent($ua){
		$reader = "";
		$subscriber = 0;
		foreach (array_keys($this->fetcher_type) as $fetcher_name){
			if (strpos($ua,$fetcher_name) !== false){
				$reader = $fetcher_name;
			}
		}
		
		if ($reader){
			//Subscriber,Agent名を抜く
			$match = array();
			if (isset($this->fetcher_type[$reader]['regex'])){
				$regex = $this->fetcher_type[$reader]['regex'];
			}else{
				$regex = $this->df_regex;
			}
			if (preg_match("/".$regex."/",$ua,$match)){
				$subscriber = strtr($match[1],",","");
			}
		}
		
		return array("reader" => $reader,"subscriber" => $subscriber);
	}
	
	/**
	 * Feedのサマリー情報を取得
	 */
	function getFeedSummary(){
		if ($this->sb_dat === null){
			$filepath = $this->datadir."/".$this->sub_file;
			if (file_exists($filepath)){
				$this->sb_dat = unserialize(file_get_contents($filepath));
				if ($this->sb_dat['_v'] != $this->format_ver){
					$this->sb_dat = array();
				}
			}else{
				$this->sb_dat = array();
			}
		}
		
		return $this->sb_dat;
	}
	
	/**
	 * 購読者数の合計を取得
	 */
	function getTotalSubscribers(){
		$total = 0;
		foreach ($this->getFeedSummary() as $reader => $feed_dat){
			if ($reader == "_v") { continue; }
			foreach ($feed_dat as $feed_type => $data){
				$total += $data['num'];
			}
		}
		
		return $total;
	}
}