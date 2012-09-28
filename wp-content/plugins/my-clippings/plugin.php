<?php
/*
Plugin Name: My Clippings
Plugin URI: http://dogmap.jp/tag/my-clippings/
Description: This plugin lets your site visitors create a list of their favorite posts. When they "clip" posts on your site, the post ID information is stored in their browser Cookie.
Author: wokamoto
Version: 0.3.1
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
class MyClippings {
	const COOKIE_KEY = 'my_clippings';
	const COOKIE_EXPIRES = 7;
	private $clip_text = array();

	function __construct() {
		$this->set_clip_text(
			'<img src="'.plugins_url('/images/clipping.png', __FILE__).'" alt="clip!" title="clip!" class="clipping-image" width="36" height="30" />',
			'<img src="'.plugins_url('/images/clipped.png', __FILE__).'" alt="clipped" title="unclip" class="clipped-image" width="36" height="30" />'
			);

		// register widget
		add_action('widgets_init', array(&$this, 'register_widget'));
		
		add_action('init', array(&$this, 'initialize'));
	}

	public function initialize() {
		if ( !is_admin() ) {
			add_filter('the_content', array(&$this, 'add_clip'));
			add_action('wp_enqueue_scripts', array(&$this,'add_scripts'));
		}

		// register ajax
		add_action('wp_ajax_clip_search', array(&$this, 'clip_search'));
		add_action('wp_ajax_nopriv_clip_search', array(&$this, 'clip_search') );
	}

	public function add_scripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery.cookie', plugins_url('/js/jquery.cookie.min.js', __FILE__), array('jquery'), '1.1', true);
	}
	
	public function footer_scripts() {
		$ajax_url = admin_url('admin-ajax.php') . '?action=clip_search';
		$cookie_key = self::COOKIE_KEY;
		$cookie_expire = self::COOKIE_EXPIRES;
		$clip_text = str_replace('"', '\"', $this->clip_text[0]);
		$clipped_text = str_replace('"', '\"', $this->clip_text[1]);

        echo "<script>\n";
        echo <<<EOT
jQuery(function(a){function b(){a(".my-clip").each(function(){var b=a.cookie("$cookie_key");var c=a(this).attr("id").replace("clip-","");var d=new RegExp('"'+c+'"');if(!b||!b.match(d)){a(this).removeClass("clipped").html("$clip_text")}else{a(this).addClass("clipped").html("$clipped_text")}})}function c(b){var c=a.cookie("$cookie_key");var e=c;var f=b.attr("id").replace(/(clip|clipped)-/,"");if(e){if(!e.match(new RegExp('"'+f+'"'))){e='"'+f+'"'+(e?","+e:"")}else{e=e.replace('"'+f+'"',"").replace(",,",",").replace(/,$/,"").replace(/^,/,"")}}else{e='"'+f+'"'}if(e!==c){a.cookie("$cookie_key",e,{expires:$cookie_expire,path:"/"});a.ajax({type:"GET",url:"$ajax_url&posts="+e.replace(/"/g,""),dataType:"json",success:d})}}function d(d,e){a(".my-clip_wrap").each(function(){var b=a(this).attr("class").match(/limit-([0-9]+)/i);var e=0;var f=a("<ul></ul>");var g=false;var h=a(".more-clip",a(this)).css("display")!=="none"||a("li",a(this)).length<=b[1];a.each(d,function(){var c=a('<li id="my-clip-post-'+this.id+'"></li>');var d,i,j,k;d=a('<div class="thumbnail"></div>').append('<img src="'+this.thumbnail+'">');i="";a.each(this.categories,function(){i+=(i!==""?", ":"")+this});i=a('<div class="categories"></div>').append(i);j=a('<div class="content"></div>').append('<a href="'+this.permalink+'" class="clip-link">'+this.title+"</a><br>"+this.excerpt);k=a('<div class="del-link"></div>').append('<a href="#" class="my-clip-remove" id="clipped-'+this.id+'">x</a>');c.append(d).append(i).append(j).append(k);e++;if(e>b[1]&&h){c.hide();g=true}f.append(c)});if(a("ul",a(this)).length<=0){a(this).prepend("<ul></ul>")}a("ul",a(this)).replaceWith(f);if(g)a(".more-clip",a(this)).show();else a(".more-clip",a(this)).hide();if(a("li",a(this)).length<=b[1])a(".more-clip",a(this)).hide();a(".my-clip-remove").unbind("click").click(function(){c(a(this));return false})});b()}if(a.cookie("$cookie_key")){a.ajax({type:"GET",url:"$ajax_url&posts="+a.cookie("$cookie_key").replace(/"/g,""),dataType:"json",success:d})}b();a(".my-clip").unbind("click").click(function(){c(a(this));return false});a(".more-clip").unbind("click").click(function(){a(this).hide().parent().prev("ul").children("li").show();return false})});
EOT;
/*
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
        var thumb, categories, content, remove;
        thumb = $('<div class="thumbnail"></div>')
          .append('<img src="' + this.thumbnail + '">');
        categories = '';
        $.each(this.categories, function(){
          categories += (categories !== '' ? ', ' : '') + this;
        });
        categories = $('<div class="categories"></div>')
          .append(categories);
        content = $('<div class="content"></div>')
          .append('<a href="' + this.permalink + '" class="clip-link">' + this.title + '</a><br>' + this.excerpt);
        remove = $('<div class="del-link"></div>')
          .append('<a href="#" class="my-clip-remove" id="clipped-' + this.id + '">x</a>');
        li.append(thumb).append(categories).append(content).append(remove);
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
*/
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
					'id'        => $post->ID,
					'type'      => $post->post_type,
					'title'     => $post->post_title,
					'date'      => $post->post_date,
					'permalink' => get_permalink($post->ID),
					'thumbnail' => $this->get_the_thumbnail($post),
					'excerpt'   => $this->get_the_excerpt($post),
					'categories'=> explode(',', get_the_category_list(',', '', $post->ID)),
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
			register_widget('MyClippingsWidget');
	}
}

/******************************************************************************
 * MyClippingsWidget Class ( for WP2.8+ )
 *****************************************************************************/
if ( class_exists('WP_Widget') ) :

class MyClippingsWidget extends WP_Widget {
	function __construct() {
		$widget_ops = array(
			'classname' => 'widget_my-clippings' ,
			'description' => 'Lets your site visitors create a list of their favorite posts.',
			);
		$this->WP_Widget('my-clippings', 'My Clippings', $widget_ops);
	}

	public function widget( $args, $instance ) {
		global $my_clippings;
	
		if (!isset($my_clippings))
			$my_clippings = New MyClippings();

		extract($args);
		add_action('wp_footer', array(&$my_clippings, 'footer_scripts'));
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
			'Show all Clips'
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
function init_my_clippings($clip_text, $clipped_text){
	global $my_clippings;
	
	if (!isset($my_clippings))
		$my_clippings = New MyClippings();
	echo $my_clippings->set_clip_text($clip_text, $clipped_text);
}

function my_clippings($post_id, $before = '', $after = ''){
	global $my_clippings;
	
	if (!isset($my_clippings))
		$my_clippings = New MyClippings();
	echo $my_clippings->clip_icon($post_id, $before, $after);
}

/******************************************************************************
 * Go Go Go!
 *****************************************************************************/
global $my_clippings;
$my_clippings = New MyClippings();
