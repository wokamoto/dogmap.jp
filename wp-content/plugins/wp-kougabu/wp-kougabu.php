<?php
/*
 Plugin Name: wp-kougabu
 Plugin URI: http://wppluginsj.sourceforge.jp/wp-kougabu/
 Description: 投稿記事から思い出の写真を抽出してページにサムネイル一覧表示する [kougabu] ショートコードを提供します。
 Version: 1.12
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
 * WpKougabu - WordPress Interface Define
 *****************************************************************************/

if(!(defined('ABSPATH') && defined('WPINC'))) return;
if(is_admin()) return;

/******************************************************************************
 * WpKougabu - Define
 *****************************************************************************/

define('KOUGABU_MAX_SYNCHRO_CACHE', 10);

/******************************************************************************
 * WpKougabu
 *
 * @author		hiromasa
 * @version		1.10
 *
 *****************************************************************************/
class WpKougabu {
	
	var $imageInfo;
	var $optionImageInfo;
	var $cachePath;
	var $cached;
	
	/**
	 * The Constructor
	 *
	 * @param none
	 * @return none
	 */
	function WpKougabu() {
		
		$this->cachePath = WP_CONTENT_DIR . '/cache/wp-kougabu/';
		$this->cached = 0;
		
	}
	
	/**
	 * WordPress shortcode Interface
	 *
	 * @param $attr
	 * @return $output
	 */
	function executeKougabuTag($attr) {
		
		extract(
			shortcode_atts(array(
				'itemtag'    => 'dl',
				'icontag'    => 'dt',
				'captiontag' => 'dd',
				'columns'    => 3,
				'max_width'  => 180,
				'max_height' => 180,
				'post_id'    => 0,
				'start'      => -1,
				'count'      => 50,
				'start_date' => '',
				'end_date'   => '',
				'title'      => '',
				'sort_key'   => 'date',
				'order_by'   => 'DESC',
			), $attr));
		
		$listtag = tag_escape($listtag);
		$itemtag = tag_escape($itemtag);
		$captiontag = tag_escape($captiontag);
		$columns = intval($columns);
		$itemwidth = $columns > 0 ? floor(100 / $columns) : 100;
		// internet explorer 7 hack.
		$itemwidth = $itemwidth - 1;
		$max_width = intval($max_width);
		$max_height = intval($max_height);
		
		$output = '';
		if(!$this->setImageInfo(
			$post_id
			, $start
			, $count
			, $start_date
			, $end_date
			, $title
			, $sort_key
			, $order_by)) {
			return "<p>There is no photograph in this page yet. </p>";
		}
		
		// for dynamic width
		$output .= "
			<style type='text/css'>
			.gallery {
				margin: auto;
			}
			.gallery-item {
				float: left;
				margin-top: 10px;
				text-align: center;
				width: {$itemwidth}%;
			}
			.gallery img {
				border: 0px;
			}
			.gallery-caption {
				margin-left: 0;
			}
			</style>
			<div class='gallery'>";
		
		foreach ($this->imageInfo as $guid => $image) {
			$image_size = wp_constrain_dimensions(
				$image['width']
				, $image['height']
				, $max_width
				, $max_height);
			$image_url = $this->getKougabuImageUrl(
				$image['image_url']
				, $image['post_id']
				, $image_size[0]
				, $image_size[1]);
			
			$link  = "<a href=\"{$image['link_url']}\">";
			$link .=
					"<img src=\"{$image_url}\" width=\"{$image_size[0]}\"" .
					" height=\"{$image_size[1]}\" alt=\"{$image['name']}\" style='border: 0;' />";
			$link .= "</a>";
			
			$output .= "<{$itemtag} class='gallery-item'>";
			$output .= "
			<{$icontag} class='gallery-icon'>
			$link
			</{$icontag}>";
			if ($captiontag && trim($image['post_title']) ) {
				$output .= "
				<{$captiontag} class='gallery-caption'>
				{$image['post_title']}
				</{$captiontag}>";
			}
			$output .= "</{$itemtag}>";
			if ( $columns > 0 && ++$i % $columns == 0 )
			$output .= '<br style="clear: both" />';
		}
		
		$output .= "
			<br style=\"clear: both\" /></div>";

		$this->updateKougabu();
		
		return $output;
		
	}
	
	/**
	 * kourabu_get_images
	 *
	 * @param unknown_type $args
	 * @return unknown
	 */
	function kougabu_get_images($args = '') {
		
		$defaults = array(
			'before'     => '<li>',
			'after'      => '</li>',
			'echo'       => true,
			'array'      => false,
			'array_ext'  => false,
			'max_width'  => 120,
			'max_height' => 120,
			'post_id'    => 0,
			'start'      => 0,
			'count'      => 4,
			'start_date' => '',
			'end_date'   => '',
			'title'      => '',
			'sort_key'   => 'random',
			'order_by'   => 'DESC',
		);
		
		$r = wp_parse_args($args, $defaults);
		extract($r, EXTR_SKIP);
		
		if(!$this->setImageInfo(
			$post_id
			, $start
			, $count
			, $start_date
			, $end_date
			, $title
			, $sort_key
			, $order_by)) {
			return '';
		}
		
		$output = '';
		$urls = array();
		$info = array();
		foreach ($this->imageInfo as $guid => $image) {
			$image_size = wp_constrain_dimensions(
				$image['width']
				, $image['height']
				, $max_width
				, $max_height);
			$image_url = $this->getKougabuImageUrl(
				$image['image_url']
				, $image['post_id']
				, $image_size[0]
				, $image_size[1]);
			$link  = "<a href=\"{$image['link_url']}\">";
			$link .=
					"<img src=\"{$image_url}\" width=\"{$image_size[0]}\"" .
					" height=\"{$image_size[1]}\" alt=\"{$image['name']}\" style='border: 0;' />";
			$link .= "</a>";
			$output .= $before . $link . $after . "\n";
			if(!is_array($urls[$image['post_id']])) {
				$urls[$image['post_id']] = array();
			}
			if($array_ext == false) {
				array_push($urls[$image['post_id']], $link);
			} else {
				$info['href'] = $image['link_url'];
				$info['src'] = $image_url;
				$info['width'] = $image_size[0];
				$info['height'] = $image_size[1];
				$info['alt'] = $image['name'];
				$info['title'] = $image['post_title'];
				$info['guid'] = $guid;
				$info['link'] = $link;
				array_push($urls[$image['post_id']], $info);
			}
		}
		
		$this->updateKougabu();
		
		if($array) return $urls;
		if($echo) echo $output;
		else return $output;
		
	}
	
	/**
	 * setImageInfo
	 *
	 * @param unknown_type $pid
	 * @param unknown_type $start
	 * @param unknown_type $count
	 * @param unknown_type $start_date
	 * @param unknown_type $end_date
	 * @param unknown_type $title
	 * @param unknown_type $sort_key
	 * @param unknown_type $order_by
	 * @return unknown
	 */
	function setImageInfo(
		$pid = 0
		, $start = -1
		, $count = 50
		, $start_date = ''
		, $end_date = ''
		, $title = ''
		, $sort_key = 'date'
		, $order_by = 'DESC') {
		
		global $wpdb;
		
		$start = intval($start);
		$count = intval($count);
		if(!is_array($pid)) {
			$pid = intval($pid);
		}
		
		if($start_date != '') {
			if (strlen($start_date) == 4) $start_date .= "/01/01";
			if (strlen($start_date) == 7) $start_date .= "/01";
			$start_date = @date("Y-m-d 00:00:00", strtotime($start_date)); 
		}
		if($end_date != '') {
			if (strlen($end_date) == 4) $end_date .= "/12/31";
			if (strlen($end_date) == 7) $end_date .= "/31";
			$end_date = @date("Y-m-d 23:59:59", strtotime($end_date)); 
		}
		if($start_date == false) {
			$start_date = ''; 
		}
		if($end_date == false) {
			$end_date = ''; 
		}
		
		$sort_key = strtolower($sort_key);
		if (!($sort_key == "date" || $sort_key == "title" || $sort_key == "random")) {
			$sort_key = "date";
		}
		$order_by = strtoupper($order_by);
		if (!($order_by == "DESC" || $order_by == "ASC")) {
			$order_by = "DESC";
		}
		
		// http://ja.forums.wordpress.org/topic/387#post-1839
		$query =
			"SELECT"
			. "	 p1.ID as ID"
			. "	,p1.post_title as title"
			. "	,p2.guid as guid"
			. "	,m.meta_value as meta_value"
			. "	,p1.post_date as date"
			. " ,RAND(SUBSTRING(NOW() + 0, 1, 14)) as random"
			. "  FROM"
			. "    {$wpdb->posts} as p1"
			. "    ,{$wpdb->posts} as p2"
			. "    ,{$wpdb->postmeta} as m"
			. "  WHERE"
			. "    p1.post_status = 'publish'"
			. "    AND p2.ID = m.post_id"
			. "    AND p2.post_status = 'inherit'"
			. "    AND p2.post_mime_type like 'image%'"
			. "    AND m.meta_key = '_wp_attachment_metadata'"
			. "    AND p1.ID = p2.post_parent"
			. "    AND p1.post_password = ''";
		if (!is_array($pid) && $pid > 0) {
			$query .= $wpdb->prepare(
				 " AND p1.ID = %d", $pid);
		}
		if(is_array($pid)) {
			$query .=
				"  AND p1.ID IN (" . implode(', ', array_map('intval', $pid)) . ")";
		}
		if ($start_date != '' && $end_date != '') {
			$query .= $wpdb->prepare(
				 " AND (p2.post_date between '%s' AND '%s')", $start_date, $end_date);
		}
		if ($start_date != '' && $end_date == '') {
			$query .= $wpdb->prepare(
				 " AND (p2.post_date > '%s')", $start_date);
		}
		if($title != '') {
			$query .=
				 " AND p1.post_title like '%" . $wpdb->escape($title) . "%'";
		}
		$query .=
				 " AND p2.post_content not like '%nokougabu%'";		
		$query .=
			   " ORDER BY ";
		if(is_array($pid)) {
			$query .=
			   "   FIELD(p1.ID, " . implode(', ', array_map('intval', $pid)) . "), ";
		}
		$query .=  $wpdb->escape($sort_key) . ' ' . $wpdb->escape($order_by);
		if(!is_array($pid) && $start != -1) {
			$query .= $wpdb->prepare(
			"    LIMIT %d, %d", $start, $count);
		}
		
		$images = $wpdb->get_results($query);
		if(count($images) == 0) {
			return false;
		}
		
		$this->imageInfo = array();
		foreach($images as $image) {
			$meta = maybe_unserialize($image->meta_value);
			$gguid =
				$this->getGGuid($image->guid, $image->ID);
			$this->imageInfo[$gguid]['post_id'] = (int)$image->ID;
			$this->imageInfo[$gguid]['link_url'] = get_permalink((int)$image->ID);
			$this->imageInfo[$gguid]['post_title'] = $image->title;
			$this->imageInfo[$gguid]['image_url'] = $image->guid;
			$this->imageInfo[$gguid]['width'] = $meta['width'];
			$this->imageInfo[$gguid]['height'] = $meta['height'];
			$this->imageInfo[$gguid]['name'] = esc_attr(basename($meta['file']));
		}
		
		return true;
		
	}
	
	/**
	 * getKougabuImageUrl
	 *
	 * @param unknown_type $image_url
	 * @param unknown_type $width
	 * @param unknown_type $height
	 */
	function getKougabuImageUrl($guid, $post_id, $width, $height) {
		
		$image_url = '';
		$cachePath =
			$this->getCachePath($guid, $width, $height);
		if(file_exists($cachePath)) {
			$image_url = content_url('cache/wp-kougabu/');
			$image_url .= $this->getCacheName($guid, $width, $height);
		} else {
			$image_url = plugins_url('wp-kougabu');
			$image_url .= '/wp-kougabu-resize.php';
			$image_url .= '?guid=' . urlencode($guid);
			$image_url .= '&post_id=' . $post_id;
			$image_url .= '&width=' . $width;
			$image_url .= '&height=' . $height;
			$gguid = $this->getGGuid($guid, $post_id);
			$this->imageInfo[$gguid]['cached'][md5($width. $height)] = array($width, $height);
			$this->cached++;
		}
		if($this->cached > KOUGABU_MAX_SYNCHRO_CACHE) {
			$image_url = $guid;
		}
		
		return $image_url;
	}
	
	/**
	 * updateKougabu
	 *
	 */
	function updateKougabu() {
		
		if($this->cached == 0) return;
		
		if(empty($this->optionImageInfo)) {
			$this->optionImageInfo = get_option('wp-kougabu');
		}
		if(!is_array($this->optionImageInfo)) {
			$this->optionImageInfo = array();
		}
		if(!empty($this->optionImageInfo['lastupdate'])) {
			if(time() - $this->optionImageInfo['lastupdate'] > 180) {
				$this->optionImageInfo = array();
			}
		}
		
		foreach ($this->imageInfo as $gguid => $images) {
			$this->optionImageInfo[$gguid]['post_id'] = $images['post_id'];
			$this->optionImageInfo[$gguid]['link_url'] = $images['link_url'];
			$this->optionImageInfo[$gguid]['post_title'] = $images['post_title'];
			$this->optionImageInfo[$gguid]['image_url'] = $images['image_url'];
			$this->optionImageInfo[$gguid]['width'] = $images['width'];
			$this->optionImageInfo[$gguid]['height'] = $images['height'];
			if(!is_array($images['cached'])) continue;
			foreach($images['cached'] as $hash => $size) {
				$this->optionImageInfo[$gguid]['cached'][$hash] = $size;
			}
		}
		
		$this->optionImageInfo['lastupdate'] = time();
		update_option('wp-kougabu', $this->optionImageInfo);
		
	}
	
	/**
	 * getCachePath
	 *
	 * @return unknown
	 */
	function getCachePath($guid, $width, $height) {
		
		$fileName = $this->getCacheName($guid , $width , $height);
		$filePath = $this->cachePath . $fileName;
		
		return $filePath;
		
	}
	
	/**
	 * getCacheName
	 *
	 * @return unknown
	 */
	function getCacheName($guid, $width, $height) {
		
		$fileName = md5($guid . $width . $height) . '.jpg';
		
		return $fileName;
		
	}
	
	/**
	 * getGGuid
	 *
	 * @param unknown_type $guid
	 * @param unknown_type $post
	 * @return unknown
	 */
	function getGGuid($guid, $post) {
		
		return $guid . ':' . $post;
		
	}
	
}

/******************************************************************************
 * WpKougabu - Global Template Tag
 *****************************************************************************/
function kougabu_get_images($args = '') {
	global $wpKougabu;
	return $wpKougabu->kougabu_get_images($args);
}

/******************************************************************************
 * WpKougabu - WordPress Interface Define
 *****************************************************************************/

$wpKougabu = & new WpKougabu();
add_shortcode('kougabu', array (&$wpKougabu, 'executeKougabuTag'));

?>
