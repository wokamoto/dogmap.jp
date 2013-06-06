<?php
/*
Plugin Name: Posts List
Plugin URI: http://wppluginsj.sourceforge.jp/posts-list/
Description: Adds a posts (or pages) list of your blog pages (not posts) by entering the shortcode [posts-list].
Version: 0.4.2
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: 
Domain Path: 

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2011 - 2013 (email : wokamoto1973@gmail.com)

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

*/
class posts_list {
	var $plugin_ver  = '0.4.0';
	var $plugin_name = 'Posts List';

	// Constructor
	function posts_list() {
		$this->__construct();
	}
	function __construct() {
		add_shortcode('posts-list', array(&$this, 'shortcode_handler'));
	}

	// shortcode handler
	function shortcode_handler($atts, $content = '') {
		global $wpdb;
		extract( shortcode_atts( array(
			'type' => 'post',
			'sort' => 'desc',
			'style' => 'ul',
			'date_format' => get_option('date_format'),
			'year' => '',
			'month' => '',
			'category' => '',
			'class_list' => 'archive-list',
			'class_item' => 'archive-list-item',
			'expiration' => 300,
			), $atts) );

		if (strtolower($date_format) === 'false')
			$date_format = FALSE;

		$transient  = 'posts-list-' . md5($type . $sort . $style . ($date_format ? $date_format : '') . $year . $month . $category . $class_list . $class_item);
		if (false !== ($return_text = get_transient($transient))) {
			return $return_text;
		}

		$return_text = '';
		$list_before = '';
		$list_template = '%s<a title="%s" href="%s">%s</a>';
		$list_after = '';
		$expiration = (is_numeric($expiration) ? $expiration : 5 * 60);

		$type = trim(strtolower($type));
		switch ($type) {
		case 'page':
		case 'post':
		case 'attachment':
			break;
		default:
			$post_types = get_post_types(array('public' => true));
			if ( !in_array($type, $post_types) ) {
				$type = 'post';
			}
		}

		$sort = trim(strtolower($sort));
		switch ($sort) {
		case 'desc':
		case 'asc':
			$sort = ' ' . $sort;
			break;
		default:
			$sort = '';
		}

		$style = trim(strtolower($style));
		$class_list = trim(esc_attr($class_list));
		$class_item = trim(esc_attr($class_item));
		switch ($style) {
		case 'ol':
			$list_before = "\n<ol class=\"$class_list\">\n";
			$list_after = "</ol>\n\n";
			$list_template = "<li class=\"$class_item\">$list_template</li>\n";
			break;
		case 'dl':
			$list_before = "\n<dl class=\"$class_list\">\n";
			$list_after = "</dl>\n\n";
			$list_template = "<dt class=\"$class_item\">$list_template</dt>\n";
			break;
		case 'table':
			$list_before = "\n<table class=\"$class_list\"><tbody>\n";
			$list_after = "</tbody></table>\n\n";
			$list_template = '<tr class=\"$class_item\"><td>%s</td><td><a title="%s" href="%s">%s</a></td></tr>'."\n";
			break;
		case 'div':
			$list_before = "\n\n";
			$list_after = "\n";
			$list_template = "<div class=\"$class_item\">$list_template</div>\n";
			break;
		case 'p':
			$list_before = "\n\n";
			$list_after = "\n";
			$list_template = "<p class=\"$class_item\">$list_template</p>\n";
			break;
		case 'ul':
		default:
			$style = 'ul';
			$list_before = "\n<ul class=\"$class_list\">\n";
			$list_after = "</ul>\n\n";
			$list_template = "<li class=\"$class_item\">$list_template</li>\n";
			break;
		}

		$year = $this->numeric_normalize($year);
		$month = $this->numeric_normalize($month);
		$category = $this->numeric_normalize($category);

		$sql = "
			select
			 p.ID,
			 u.user_nicename,
			 p.post_author,
			 p.post_date,
			 p.post_modified,
			 p.post_name,
			 p.post_title,
			 p.post_type,
			 p.post_parent,
			 p.post_status,
			 min(c.cat_id) as cat_id
			from
			 $wpdb->posts as p
			 left join $wpdb->users as u on u.ID = p.post_author
			 left join (
			  select
			   thiscat.object_id as ID,
			   cat.term_id as cat_id
			  from
			   $wpdb->term_relationships AS thiscat
			   inner JOIN $wpdb->term_taxonomy AS cat on(thiscat.term_taxonomy_id = cat.term_taxonomy_id AND cat.taxonomy = 'category')
			  ) as c on (c.ID = p.ID )
			where
			 p.post_status IN ( 'publish',  'static' )
			 and p.post_password = ''
			 and p.post_type = '$type'" .
			(!empty($year) ? " and DATE_FORMAT(p.post_date, '%Y') in ($year)" : '') .
			(!empty($month) ? " and DATE_FORMAT(p.post_date, '%c') in ($month)" : '') .
			(!empty($category) ? " and c.cat_id in ($category)" : '') . "
			group by
			 p.ID
			order by
			 p.post_date $sort
			";
		$posts = $wpdb->get_results($sql);
		if (count($posts) > 0) {
			$return_text = $list_before;
			foreach ( $posts as $post ) {
				$post_date = $date_format ? mysql2date($date_format, $post->post_date) . ': ' : '';
				$permalink = $this->get_permalink($type, $post);
				$post_title = trim($post->post_title);
				$return_text .= sprintf(
					$list_template ,
					$post_date ,
					esc_attr($post_title),
					$permalink,
					$post_title
					);
			}
			$return_text .= $list_after;
		}
		set_transient($transient, $return_text, $expiration);

		return $return_text;
	}

	function numeric_normalize($string  = '', $delimiter = ',') {
		$retval = '';
		if (!empty($string)) {
			$array_work = explode($delimiter, $string);
			$retval = array();
			foreach ($array_work as $work) {
				if (is_numeric($work)) {
					$retval[] = "'" . $work . "'";
				}
			}
			$retval = (
				count($retval) > 0
				? implode($delimiter, $retval)
				: ''
				);
		}
		return $retval;
	}

	// based on link-template.php of WordPress 3.0.4
	function get_permalink($type, $post) {
		$leavename = ('' == get_option('permalink_structure'));
		$rewritecode = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			$leavename ? '' : '%postname%',
			'%post_id%',
			'%category%',
			'%author%',
			$leavename ? '' : '%pagename%',
		);

		$permalink = '';
		switch ($type) {
		case 'page':
			$permalink = $this->get_page_link($rewritecode, $post, $leavename);
			break;

		case 'attachment':
			$permalink = $this->get_attachment_link($rewritecode, $post, $leavename);
			break;

		case 'post':
		default:
			$permalink = $this->get_post_permalink($rewritecode, $post, $leavename);
			break;
		}

		return $permalink;
	}

	// based on link-template.php of WordPress 3.0.4
	function get_page_link($rewritecode, $page, $leavename, $sample = false) {
		global $wp_rewrite;

		$id = (int) $page->ID;
		if ( 'page' == get_option('show_on_front') && $id == get_option('page_on_front') ) {
			$link = home_url('/');
		} else {
			$link = $wp_rewrite->get_page_permastruct();

			if ( '' != $link && ( ( isset($page->post_status) && 'draft' != $page->post_status && 'pending' != $post->post_status ) || $sample ) ) {
				if ( ! $leavename )
					$link = str_replace('%pagename%', get_page_uri($page), $link);
				$link = home_url($link);
				$link = user_trailingslashit($link, 'page');
			} else {
				$link = home_url("?page_id=$id");
			}

			$link = apply_filters( '_get_page_link', $link, $id );
		}

		return apply_filters('page_link', $link, $id, $sample);
	}

	// based on link-template.php of WordPress 3.0.4
	function get_attachment_link($rewritecode, $post, $leavename) {
		global $wp_rewrite;

		$id = (int) $post->ID;
		if ( $wp_rewrite->using_permalinks() && ($post->post_parent > 0) && ($post->post_parent != $id) ) {
			$parent = get_post($post->post_parent);
			$parentlink = (
				'page' == $parent->post_type
				? _get_page_link( $post->post_parent )
				: get_permalink( $post->post_parent )
				);
			$name = (
				is_numeric($post->post_name) || false !== strpos(get_option('permalink_structure'), '%category%')
				? 'attachment/' . $post->post_name
				: $post->post_name
				);
			if ( strpos($parentlink, '?') === false )
				$link = user_trailingslashit( trailingslashit($parentlink) . $name );
		}

		if ( ! $link )
			$link = home_url( "/?attachment_id=$id" );

		return apply_filters('attachment_link', $link, $id);
	}

	// based on link-template.php of WordPress 3.0.4
	function get_post_permalink($rewritecode, $post, $leavename) {
		$permalink = get_option('permalink_structure');
		$permalink = apply_filters('pre_post_link', $permalink, $post, $leavename);

		if ( '' != $permalink && !in_array($post->post_status, array('draft', 'pending', 'auto-draft')) ) {
			$unixtime = strtotime($post->post_date);

			$category = '';
			if ( strpos($permalink, '%category%') !== false ) {
				$cats = get_the_category($post->ID);
				if ( $cats ) {
					usort($cats, '_usort_terms_by_ID'); // order by ID
					$category = $cats[0]->slug;
					if ( $parent = $cats[0]->parent )
						$category = get_category_parents($parent, false, '/', true) . $category;
				}
				// show default category in permalinks, without
				// having to assign it explicitly
				if ( empty($category) ) {
					$default_category = get_category( get_option( 'default_category' ) );
					$category = is_wp_error( $default_category ) ? '' : $default_category->slug;
				}
			}

			$author = '';
			if ( strpos($permalink, '%author%') !== false ) {
				$author = $post->user_nicename;
			}

			$date = explode(" ",date('Y m d H i s', $unixtime));
			$rewritereplace =
			array(
				$date[0],
				$date[1],
				$date[2],
				$date[3],
				$date[4],
				$date[5],
				$post->post_name,
				$post->ID,
				$category,
				$author,
				$post->post_name,
			);
			$permalink = home_url( str_replace($rewritecode, $rewritereplace, $permalink) );
			$permalink = user_trailingslashit($permalink, 'single');
		} else { // if they're not using the fancy permalink option
			$permalink = home_url('?p=' . $post->ID);
		}

		return apply_filters('post_link', $permalink, $post, $leavename);
	}
}

new posts_list();