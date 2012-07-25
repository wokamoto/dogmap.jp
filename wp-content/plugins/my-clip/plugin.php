<?php
/*
Plugin Name: My Clipping
Plugin URI: 
Description: 
Author: wokamoto
Version: 0.1.0
Author URI: http://dogmap.jp/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2012 (email : wokamoto1973@gmail.com)

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
class MyClip {
	const COOKIE_KEY = 'my_clip';
	const COOKIE_EXPIRES = 7;
	private $clip_text = array();

	function __construct() {
		if ( !is_admin() ) {
			add_filter('the_content', array(&$this, 'add_clip'));
			add_action('wp_enqueue_scripts', array(&$this,'add_scripts'));
			add_action('wp_footer', array(&$this,'footer_scripts'));
		}

		// register ajax
		add_action('wp_ajax_clip_search', array(&$this, 'clip_search'));
		add_action('wp_ajax_nopriv_clip_search', array(&$this, 'clip_search') );

		// register widget
		add_action('widgets_init', array(&$this, 'register_widget'));
		
		$this->set_clip_text('クリップする', 'クリップ済み');
	}

	public function add_scripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery.cookie', plugins_url('/js/jquery.cookie.js', __FILE__), array('jquery'), '1.1', true);
	}
	
	public function footer_scripts() {
		$ajax_url = admin_url('admin-ajax.php') . '?action=clip_search';
		$cookie_key = self::COOKIE_KEY;
		$cookie_expire = self::COOKIE_EXPIRES;
		$clip_text = $this->clip_text[0];
		$clipped_text = $this->clip_text[1];

        echo "<script>\n";
        echo <<<EOT
jQuery(function($){
  if ( $.cookie('$cookie_key') ) {
    $.ajax({
      type: 'GET',
      url: '$ajax_url&posts=' + $.cookie('$cookie_key').replace(/"/g,''),
      dataType: 'json',
      success: clip_set,
    });
  }
  set_clipped_text();

  function set_clipped_text() {
    $('.my-clip').each(function(){
      var clips = $.cookie('$cookie_key');
      var id = $(this).attr('id').replace('clip-','');
      var regexp = new RegExp('\"' + id + '\"');
      if ( !clips || !clips.match(regexp) ) {
        $(this).removeClass('clipped').html('$clip_text');
      } else {
        $(this).addClass('clipped').html('$clipped_text');
      }
    });
  }
  
  function clipped(obj){
    var clips_org = $.cookie('$cookie_key');
    var clips = clips_org;
    var id = obj.attr('id').replace(/(clip|clipped)-/,'');
    if ( clips ) {
      if ( !clips.match(new RegExp('"' + id + '"')) ) {
        clips = '"' + id + '"' + (clips ? ',' + clips : '');
      } else {
        clips = clips.replace('"' + id + '"', '').replace(',,',',').replace(/,$/,'').replace(/^,/,'');
      }
    } else {
      clips = '"' + id + '"';
    }
    if ( clips !== clips_org ) {
      $.cookie('$cookie_key', clips, { expires: $cookie_expire, path: '/' });
      $.ajax({
        type: 'GET',
        url: '$ajax_url&posts=' + clips.replace(/"/g,''),
        dataType: 'json',
        success: clip_set,
      });
    }
  }

  $('.my-clip').unbind('click').click(function(){clipped($(this));return false;});

  $('.more-clip').unbind('click').click(function(){
    $(this).hide()
      .parent().prev('ul').children('li').show();
    return false;
  });
  
  function clip_set(data, dataType){
    $('.my-clip_wrap').each(function(){
      var limit = $(this).attr('class').match(/limit-([0-9]+)/i);
      var count = 0;
      var ul = $('<ul></ul>');
      var moreclip = false;
      var hideclip = $('.more-clip', $(this)).css('display') !== 'none' || $('li', $(this)).length <= limit[1];
      $.each(data, function(){
        var li = $('<li id="my-clip-post-' + this.id + '"></li>');
        var thumb = $('<div class="thumbnail"><img src="' + this.thumbnail + '"></div>');
        var content = $('<div class="content"></div>')
          .append('<a href="' + this.permalink + '" class="clip-link">' + this.title + '</a><br>' + this.excerpt);
        var remove = $('<div class="del-link"><a href="#" class="my-clip-remove" id="clipped-' + this.id + '">x</a></div>');
        li.append(thumb).append(content).append(remove);
        count++;
        if ( count > limit[1] && hideclip ) {
          li.hide();
          moreclip = true;
        }
        ul.append(li);
      });
      if ( $('ul', $(this)).length <= 0 ) {
        $(this).prepend('<ul></ul>');
      }
      $('ul', $(this)).replaceWith(ul);
      if ( moreclip )
        $('.more-clip', $(this)).show();
      else
        $('.more-clip', $(this)).hide();
      if ( $('li', $(this)).length <= limit[1] )
        $('.more-clip', $(this)).hide();
      $('.my-clip-remove').unbind('click').click(function(){clipped($(this));return false;});
    });
    set_clipped_text();
  }
});
EOT;
        echo "</script>\n";
	}
	
	public function add_clip($content) {
		if ( !is_feed() && !empty($content) )
			return $this->clip_icon(get_the_ID()) . $content;
		else
			return $content;
	}

	public function set_clip_text($clip_text, $clipped_text) {
		$this->clip_text = array($clip_text, $clipped_text);
	}

	public function clip_icon($id, $before = '', $after = '') {
		return sprintf(
			'<div class="clip_icon alignright">%s<a href="#" id="clip-%d" class="my-clip">%s</a>%s</div>',
			$before ,
			$id ,
			$this->clip_text[0] ,
			$after
			);
	}
	
	private function clip_posts_id(){
		if ( isset($_GET['posts'])) {
			return (array)explode(',',$_GET['posts']);
		} else if ( isset($_COOKIE[self::COOKIE_KEY]) ) {
			return explode(',',$_COOKIE[self::COOKIE_KEY]);
		} else {
			return array();
		}
	}
	
	// get the thumbnail
	private function get_the_thumbnail($post) {
		$thumb = '';
		if ( is_numeric($post) ) {
			$post_id = $post;
			$post = get_post($post_id);
		} else if ( is_object($post) && isset($post->ID) ) {
			$post_id = $post->ID;
		} else {
			return $thumb;
		}
	
		if ( function_exists('has_post_thumbnail') && has_post_thumbnail($post_id) ) {
			$thumb = preg_replace("/^.*['\"](https?:\/\/[^'\"]*)['\"].*/i","$1", get_the_post_thumbnail($post_id, 'thumbnail'));
		} else {
			$attachments = get_children(array(
				'post_parent' => $post_id ,
				'post_type' => 'attachment' ,
				'post_mime_type' => 'image' ,
				'orderby' => 'menu_order' ,
				));
			foreach ($attachments as $attachment) {
				$image_src = wp_get_attachment_image_src($attachment->ID);
				$thumb = (isset($image_src[0]) ? $image_src[0] : '');
				unset($image_src);
				break;
			}
			unset($attachments);
		}
		if (empty($thumb) && preg_match_all('/<img .*src=[\'"]([^\'"]+)[\'"]/', $post->post_content, $matches, PREG_SET_ORDER)) {
			$thumb = $matches[0][1];
		}
		unset($matches);
		
		return $thumb;
	}

	// get the excerpt
	private function get_the_excerpt($post){
		global $wpmp_conf;

		$excerpt = '';
		if ( is_numeric($post) ) {
			$post_id = $post;
			$post = get_post($post_id);
		} else if ( is_object($post) && isset($post->ID) ) {
			$post_id = $post->ID;
		} else {
			return $excerpt;
		}

		$excerpt =
			!post_password_required($post)
			? get_the_excerpt()
			: __('There is no excerpt because this is a protected post.');
		if (empty($excerpt)) {
			$strwidth = (
				isset($wpmp_conf["excerpt_mblength"])
				? $wpmp_conf["excerpt_mblength"]
				: 255
				);
			$excerpt = trim(preg_replace(
				array('/[\n\r]/', '/\[[^\]]+\]/'),
				array('', ' '),
				strip_tags($post->post_content)
				));
			$excerpt = (
				function_exists('mb_strimwidth')
				? mb_strimwidth($excerpt, 0, $strwidth, '...', get_option('blog_charset'))
				: ( strlen($excerpt) > $strwidth ? substr($excerpt, 0, $strwidth - 3) . '...' : $excerpt)
				);
			$excerpt = apply_filters('get_the_excerpt', $excerpt);
		}
		
		return $excerpt;
	}

	private function clip_posts(){
		$post_ids = $this->clip_posts_id();
		$results = array();
		foreach ( $post_ids as $post_id ) {
			$post_id = intval(preg_replace('/[^0-9]/', '', $post_id));
			$transient_key = 'my_clip-tran-'.$post_id;
			if ( $result = get_transient($transient_key) ) {
				$results[] = $result;
			} else if ( $post = get_post($post_id) ) {
				$result = array(
					'id' => $post->ID,
					'type' => $post->post_type,
					'title' => $post->post_title,
					'date' => $post->post_date,
					'permalink' => get_permalink($post->ID),
					'thumbnail' => $this->get_the_thumbnail($post),
					'excerpt' => $this->get_the_excerpt($post),
					//'post' => $post,
				);
				set_transient($transient_key, $result, 5 * 60 );	// 5min * 60sec
				$results[] = $result;
			}
		}
		return $results;
	}
	
	public function clip_search() {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($this->clip_posts());
	    die();
	}

	function register_widget() {
		if ( class_exists('WP_Widget') )
			register_widget('MyClipWidget');
	}
}

/******************************************************************************
 * MyClipWidget Class ( for WP2.8+ )
 *****************************************************************************/
if ( class_exists('WP_Widget') ) :

class MyClipWidget extends WP_Widget {
	function __construct() {
		$widget_ops = array(
			'classname' => 'widget_my-clip' ,
			'description' => 'My Clip',
			);
		$this->WP_Widget('my-clip', 'My Clip', $widget_ops);
	}

	public function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters('widget_title', 
			isset($instance['title']) ? trim($instance['title']) : '' ,
			$instance ,
			$this->id_base);
		echo $before_widget;
		if ( !empty($title) )
			echo $before_title . $title . $after_title;
		printf(
			'<div class="my-clip_wrap limit-%1$d"><ul></ul><p><a href="#" class="more-clip" style="display:none">%2$s</a></p></div>' . "\n",
			intval($instance['limit']) ,
			'すべて表示'
			);
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $new_instance;
		if (isset($instance['title']))
			$instance['title'] = strip_tags($instance['title']);
		return $instance;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array('title' => '', 'limit' => '5') );
		$input_format = '<p><label for="%2$s">%1$s</label><input class="widefat" id="%2$s" name="%3$s" type="text" value="%4$s" /></p>'."\n";
		printf(
			$input_format ,
			__('Title:') ,
			$this->get_field_id('title') ,
			$this->get_field_name('title') ,
			esc_attr(strip_tags($instance['title']))
		);
		printf(
			$input_format ,
			__('Limit:') ,
			$this->get_field_id('limit') ,
			$this->get_field_name('limit') ,
			intval($instance['limit'])
		);
	}
}

endif;

/******************************************************************************
 * functions
 *****************************************************************************/
function init_my_clip_text($clip_text, $clipped_text){
	global $my_clip;
	
	if (!isset($my_clip))
		$my_clip = New MyClip();
	echo $my_clip->set_clip_text($clip_text, $clipped_text);
}

function my_clip($post_id, $before = '', $after = ''){
	global $my_clip;
	
	if (!isset($my_clip))
		$my_clip = New MyClip();
	echo $my_clip->clip_icon($post_id, $before, $after);
}

/******************************************************************************
 * Go Go Go!
 *****************************************************************************/
global $my_clip;
$my_clip = New MyClip();