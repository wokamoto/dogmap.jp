<?php
//**********************************************************************************
// 制御用定数
//**********************************************************************************
define('WRITE_BACK', false);
define('CONTENT_CACHE_USE_OBJ_CACHE', false);
if (!defined('GM_ANYWHERE_DEBUG_MODE'))
	define('GM_ANYWHERE_DEBUG_MODE', true);

//**********************************************************************************
// サムネールサポート
//**********************************************************************************
add_theme_support( 'post-thumbnails' );
set_post_thumbnail_size( 160, 160, true );

//**********************************************************************************
// サイドバー ウィジェット
//**********************************************************************************
if (function_exists('register_sidebar'))
	register_sidebar();

//**********************************************************************************
// ヘッダーで読み込む JavaScript を登録する
//**********************************************************************************
function add_print_scripts() {
	global $current_user;

	$wp_siteurl     = get_bloginfo('wpurl');
	$wp_themeurl    = $wp_siteurl.'/wp-content/themes/dogmap/';
	$wp_themeimages = 'http://static.dogmap.jp/images/icons/';

	// jQuery
	wp_enqueue_script('jquery');

//	// Gravater
//	if( is_singular() )
//		wp_enqueue_script( 'gprofiles', 'http://s.gravatar.com/js/gprofiles.js', array( 'jquery' ), 'e', true);

}
add_action('wp_print_scripts', 'add_print_scripts');

//**********************************************************************************
// ヘッダーで読み込む JavaScript 等を指定する
//**********************************************************************************
function add_wp_head() {
	global $current_user, $script_manager;

	$meta = "";

	// robots
	$meta .= "\n<meta name=\"robots\" content=\"" . (!(is_home() || is_single()) ? 'no' : '') . "index,follow\" />\n";

	// for iPhone / iPod / iPad
	$meta .= "<meta name=\"viewport\" content=\"width=device-width\" />\n";

	echo $meta;

	$css = "";
	$js  = "";

	// add Load Event
	$js .= "var addLoadEvent = function(func){";
	$js .= " if(typeof jQuery!='undefined'){jQuery(document).ready(func);}";
	$js .= " else if(typeof google.setOnLoadCallback!='undefined'){google.setOnLoadCallback(func);}";
	$js .= " else if(typeof wpOnload!='function'){wpOnload=func;}";
	$js .= " else {var oldonload=wpOnload; wpOnload=function(){oldonload();func();}}";
	$js .= "};\n";

	if (is_singular()) {
		// set commnet author from cookie
		$js .= 'jQuery(function($){';
		$js .= '$.each(document.cookie.split(/(?:^|; )/), function(){';
		$js .= 'var c=this.split(/=([^;]*)/);';
		$js .= 'if (/^comment_author_email/i.test(c[0]))';
		$js .= '$("#email").val(decodeURIComponent(c[1]));';
		$js .= 'else if (/^comment_author_url/i.test(c[0]))';
		$js .= '$("#url").val(decodeURIComponent(c[1]));';
		$js .= 'else if (/^comment_author/i.test(c[0]))';
		$js .= '$("#author").val(decodeURIComponent(c[1]));';
		$js .= '});';
		$js .= "});\n";
	}

	if ($css != "") {
		echo "\n<style type=\"text/css\" media=\"all\">/*<![CDATA[ */\n";
		echo $css;
		echo "/*]]>*/</style>\n";
	}

	if ($js != "") {
		if (isset($script_manager)) {
			add_filter('head_script/ScriptManager', create_function('$js', 'return $js . "'.addcslashes($js,'"').'";'), 1);
		} else {
			echo "\n<script type=\"text/javascript\">/*<![CDATA[ */\n";
			echo $js;
			echo "/* ]]>*/</script>\n";
		}
	}
}
add_action('wp_head', 'add_wp_head');

//**********************************************************************************
// フッターで読み込む JavaScript 等を指定する
//**********************************************************************************
function add_wp_footer() {
	global $script_manager, $current_user;

	$platform = @get_browser(null, true);
	$js = "";
	$js_inline = "";
	$js_tag = "<script type=\"text/javascript\" src=\"%s\"%s></script>\n";
	$wp_siteurl  = get_bloginfo('wpurl').'/';
	$wp_includes = $wp_siteurl.'wp-includes/';
	$wp_themeurl = $wp_siteurl.'wp-content/themes/dogmap/';
	$wp_themeimages = 'http://static.dogmap.jp/images/icons/';

	// blog util
	$js .= sprintf($js_tag, $wp_themeurl.'js/blog_utils.js', '');

//	// lazyload
//	$js .= sprintf($js_tag, $wp_themeurl.'js/jquery.lazyload.mini.js', '');
//	$js_inline .= "jQuery(function(){";
//	$js_inline .= "jQuery('img').lazyload({effect : 'fadeIn'});";
//	$js_inline .= "});";

	if ( is_singular() ) {
		$js_inline .= "jQuery(function(){";
		$js_inline .= "jQuery('#trackbacks a').unbind('click').click(function(){var t=jQuery('.trackback-list');if(t.css('display')=='block'){this.innerHTML='&raquo; 表示する';t.slideUp();}else{this.innerHTML='&laquo; 隠す';t.slideDown();}return false;});";
		$js_inline .= "jQuery('a.tweet-this').unbind('click').click(function(){window.twttr=window.twttr||{};var D=550,A=450,C=screen.height,B=screen.width,H=Math.round((B/2)-(D/2)),G=0,F=document,E;if(C>A) G=Math.round((C/2)-(A/2));window.twttr.shareWin=window.open('http://twitter.com/share','','left='+H+',top='+G+',width='+D+',height='+A+',personalbar=0,toolbar=0,scrollbars=1,resizable=1');E=F.createElement('script');E.src='http://platform.twitter.com/bookmarklets/share.js?v=1';F.getElementsByTagName('head')[0].appendChild(E);return false;});";
		$js_inline .= "});";
	}

	// IE Hack
	if (is_array($platform) && $platform['browser'] == 'IE')
		$js_inline .= "jQuery(function(){jQuery('.sidebar ul li').css('zoom',1);});\n";

	$js_inline .= "if (typeof wpOnload=='function') wpOnload();\n";
	if ($js_inline != '') {
		if (isset($script_manager)) {
			add_filter('footer_script/ScriptManager', create_function('$js', 'return $js . "'.addcslashes($js_inline,'"').'";'), 999);
		} else {
			$js .= "<script type=\"text/javascript\"> /*<![CDATA[ */\n";
			$js .= $js_inline;
			$js .= "/*]]>*/</script>\n";
		}
	}
	echo $js;

	unset($platform);
}
add_action('wp_footer', 'add_wp_footer');

//**********************************************************************************
// comment list
//**********************************************************************************
function custom_comments($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment;
	global $commentcount;
	if(!$commentcount) {
		$commentcount = 0;
	}
	if ($comment->comment_type != "trackback" && $comment->comment_type != "pingback" && !ereg("<pingback />", $comment->comment_content) && !ereg("<trackback />", $comment->comment_content)) :
?>
<li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
<p class="comment-time">
<?php if (function_exists('commenters_info')) { commenters_info(); echo '&nbsp;'; } ?>
<?php comment_date(); ?> <?php comment_time(); ?>
</p>
<div class="comment">
<div class="hd"><div class="c"></div></div>
<div class="bd"><div class="c">
<div class="comment-body">
<?php comment_text(); ?>
</div>
</div></div>
<div class="ft"><div class="c">
<?php if ( get_option('show_avatars') ) : ?>
<div class="comment-post" style="background:none;padding-top:7px;padding-left:30px;height:30px;">
<div style="float:left;padding-right:.5em;"><?php echo get_avatar($comment, 24, 'http://static.dogmap.jp/dogmap/themes/dogmap/images/default_mini.png'); ?></div>
<?php elseif (the_author('nickname',false)==$comment->comment_author) : ?>
<div class="comment-post-author">
<?php else : ?>
<div class="comment-post">
<?php endif; ?>
<?php comment_author_link(); ?>
<span style="float:right;margin-top:-1.5em;">
<?php
	if (function_exists('qc_comment_reply_link'))
		qc_comment_reply_link('返信', '', '' );
	else
		comment_reply_link(array_merge( $args, array('depth' => $depth, 'reply_text' => '返信', 'before' => '', 'after' => '') ) );

	if (function_exists('qc_comment_quote_link'))
		qc_comment_quote_link('引用', ' &nbsp; ', '' );

	if (function_exists('qc_comment_edit_link'))
		qc_comment_edit_link('編集', ' &nbsp; ', '' );
	else
		edit_comment_link(__("Edit This"), ' &nbsp; ');

//	if (function_exists('commenters_info'))
//		commenters_info();
?>
</span>
</div>
</div></div>
</div>
<?php
	endif;
}

//**********************************************************************************
// メディアボタンの変更
//**********************************************************************************
/*
function my_media_buttons() {
	global $post_ID, $temp_ID;

	remove_action( 'media_buttons', 'media_buttons' );

	$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);
	$media_upload_iframe_src = get_settings('siteurl')."/wp-admin/media-upload.php?post_id={$uploading_iframe_ID}";
	$context = apply_filters('media_buttons_context', __('Upload/Insert %s'));
	$link_tag = "<a href=\"%s&amp;TB_iframe=true\" class=\"thickbox\" title=\"%s\"><img src=\"%s\" alt=\"%s\" /></a>\n";
	$out = "";

	$image_upload_iframe_src = apply_filters('image_upload_iframe_src', "$media_upload_iframe_src&amp;type=image");
	$image_title = __('Add an Image');
	$out .= sprintf($link_tag, $image_upload_iframe_src, $image_title, "images/media-button-image.gif", $image_title);

	$video_upload_iframe_src = apply_filters('video_upload_iframe_src', "$media_upload_iframe_src&amp;type=video");
	$video_title = __('Add Video');
	$out .= sprintf($link_tag, $video_upload_iframe_src, $video_title, "images/media-button-video.gif", $video_title);

	printf($context, $out);
}
add_action( 'media_buttons', 'my_media_buttons', 9 );
*/

//**********************************************************************************
// コメント欄の > を <blockquote> に変換
//**********************************************************************************
function comment_content_edit($comment_content) {
	$comment_content = preg_replace("/^\s*(&gt;|>|＞)(.*)$/im", "<blockquote>$2</blockquote>", $comment_content);
	$comment_content = str_replace("</blockquote>\n<blockquote>", "\n", $comment_content);
	$comment_content = preg_replace("/^<blockquote>/i", "<blockquote style=\"margin:0;\">", $comment_content);
	return $comment_content;
}
add_filter('get_comment_text', 'comment_content_edit',99);

//**********************************************************************************
// コメント欄の内部リンクを変換
//**********************************************************************************
function convert_internal_link($html_text) {
	$pattern = '/' . preg_quote(get_bloginfo('wpurl'), '/') . '/i';
	if (preg_match($pattern, $html_text)) {
		$base_url = untrailingslashit(preg_replace('/^(https?:\/\/[^\/]*\/).*$/i', '$1', get_bloginfo('wpurl')));
		$pattern = '/(["\'])' . preg_quote($base_url, '/') . '(\/[^"\']*[^"\'])/i';
		$html_text = preg_replace($pattern, '$1$2', $html_text);
	}
	return ($html_text);
}
add_filter('comment_text', 'convert_internal_link', 99);

//**********************************************************************************
// Caption 無効
//**********************************************************************************
add_filter('disable_captions', create_function('','return true;'));

//**********************************************************************************
// wp-mail.php 無効
//**********************************************************************************
if (preg_match('/\/wp-mail\.php(\?.*)?$/i', $_SERVER['REQUEST_URI'])) {
	header('HTTP/1.0 403 Forbidden');
	wp_die(__("You don't have permission to access the URL on this server."));
}

//**********************************************************************************
// Remove Custome Smilies
//**********************************************************************************
if (function_exists('cs_print_smilies'))
	remove_action('comment_form', 'cs_print_smilies');

//**********************************************************************************
// Add Scroll to Top
//**********************************************************************************
add_action('wp_head', 'add_scroll_to_top_style');
function add_scroll_to_top_style() {
    ?>
<style type="text/css">
#pagetop { display:none;position:fixed;right:10px;bottom:10px;z-index:100; }
#pagetop a { padding:5px;background:#ccc;color:#fff;display:block;font-size:20px;font-weight:bold;-webkit-border-radius:8px;-moz-border-radius:8px;border-radius:8px; }
</style>
    <?php
}

add_action('wp_footer', 'add_scroll_to_top');
function add_scroll_to_top() {
    ?>
<script type="text/javascript">
jQuery(function($){
    $('body').append(
        $('<div id="pagetop">')
        .append(
            $('<a href="#">↑</a>')
            .click(function(){$('html,body').animate({scrollTop:0}, 800, 'swing')})
            )
        );
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('#pagetop').fadeIn();
        } else {
            $('#pagetop').fadeOut();
        }
    });
});
</script>
    <?php
}
?>