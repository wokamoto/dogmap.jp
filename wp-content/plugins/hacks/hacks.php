<?php
/*
Plugin Name: Hacks
Plugin URI: 
Description: 
Version: 0.1
Author: 
Author URI: 
*/
$hack_dir = trailingslashit(dirname(__FILE__)) . 'hacks/';
opendir($hack_dir);
while(($ent = readdir()) !== false) {
	if(!is_dir($ent) && strtolower(substr($ent,-4)) == ".php") {
		include_once($hack_dir.$ent);
	}
}
closedir();

add_filter('pre_option_link_manager_enabled', '__return_true');

// remove jetpack open graph tags
add_filter( 'jetpack_enable_open_graph', '__return_false' );
add_action('plugins_loaded', function(){
	if ( function_exists('jetpack_og_tags') ) {
		remove_action('wp_head','jetpack_og_tags');
	}
});

//**********************************************************************************
// Nginx Cache Controller で消す URL キーを追加
//**********************************************************************************
/*
add_filter('nginxchampuru_get_cache', 'nginxchampuru_get_cache', 10, 2);
function nginxchampuru_get_cache($key, $url = null) {
    global $nginxchampuru;
    if (!$url) {
        $url = $nginxchampuru->get_the_url();
    }
    $keys = array(
        $key,
        $nginxchampuru->get_cache_key($url.'@ktai'),
        //$nginxchampuru->get_cache_key($url.'@smartphone'),
    );
    if ($key !== $nginxchampuru->get_cache_key($url)) {
        $keys[] = $nginxchampuru->get_cache_key($url);
    }
    return $nginxchampuru->get_cache_file($keys);
}
*/

//**********************************************************************************
// 翻訳の書き換え
//**********************************************************************************
add_filter('gettext', 'change_translated');
add_filter('gettext_with_context', 'change_translated');
add_filter('ngettext', 'change_translated');
add_filter('ngettext_with_context', 'change_translated');
function change_translated($translated) {
     $translated = str_ireplace('インストール済みプラグイン', 'プラグイン', $translated);
     return $translated;
}

//**********************************************************************************
//  applied to the comment author's IP address prior to saving the comment in the database.
//**********************************************************************************
function auto_reverse_proxy_pre_comment_user_ip() {
	if ( isset($_SERVER['X_FORWARDED_FOR']) && !empty($_SERVER['X_FORWARDED_FOR']) ) {
		$X_FORWARDED_FOR = (array)explode(",", $_SERVER['X_FORWARDED_FOR']);
		$REMOTE_ADDR = trim($X_FORWARDED_FOR[0]); //take the last
	} else {
		$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
	}
	return $REMOTE_ADDR;
}
add_filter('pre_comment_user_ip','auto_reverse_proxy_pre_comment_user_ip');

add_filter('got_rewrite','__return_true');
add_action('init', function(){
	if ( function_exists('is_user_logged_in') && is_user_logged_in() ) {
		nocache_headers();
	}
});

//**********************************************************************************
// ウィジェットでショートコードを使用可能に
//**********************************************************************************
add_filter('widget_text', 'do_shortcode');

//**********************************************************************************
// ヘッダーで読み込む JavaScript を登録する
//**********************************************************************************
function add_print_scripts() {
	// jQuery
	wp_enqueue_script('jquery');

//	// Gravater
//	if( is_singular() )
//		wp_enqueue_script( 'gprofiles', 'http://s.gravatar.com/js/gprofiles.js', array( 'jquery' ), 'e', true);
}
add_action('wp_print_scripts', 'add_print_scripts');

add_action('wp_head', create_function( '', 
	'echo \'<link rel="dns-prefetch" href="//s0.wp.com">\'."\n";'
));

//**********************************************************************************
// Ktai Style 修正
//**********************************************************************************
/*
add_action('init', function () {
	global $Ktai_Style;
	if ( isset($Ktai_Style) && is_feed() ) {
		remove_action('wp_head', array(&$Ktai_Style, 'show_mobile_url'));
//		remove_action('atom_head', array(%$Ktai_Style, 'show_mobile_url_atom_head'));
//		remove_action('atom_entry', array(%$Ktai_Style, 'show_mobile_url_atom_entry'));
		remove_action('rss2_ns', array(&$Ktai_Style, 'show_mobile_url_rss2_ns'));
		remove_action('rss2_head', array(&$Ktai_Style, 'show_mobile_url_rss2_head'));
		remove_action('rss2_item', array(&$Ktai_Style, 'show_mobile_url_rss2_item'));
	}
});
*/

//**********************************************************************************
// static.dogmap.jp に変換
//**********************************************************************************
/*
function content_static_URI( $content ) {
	$content_url = untrailingslashit(site_url('/wp-content/'));
	$includes_url = untrailingslashit(site_url('/wp-includes/'));
        $site_url = untrailingslashit(site_url());
        $content = str_replace(
                  array($site_url.'/wp-content/uploads/', $site_url.'/wp-content/themes/dogmap/images/', $site_url.'/wp-content/videopop/', $site_url.'/wp-admin/images/', $site_url.'/wp-includes/images/', $site_url.'/wp-content/cache/')
                , array('http://static.dogmap.jp/','http://static.dogmap.jp/theme_icons/','http://static.dogmap.jp/movies/','http://static.dogmap.jp/images/wp-admin/','http://static.dogmap.jp/images/wp-includes/', 'http://static.dogmap.jp/dogmap/cache/')
                , $content);
        $content = preg_replace(
		'/<(script|link|a|img)([^>]*)(src|href)=["\']('.preg_quote($content_url,'/').'|\/wp-content)\/(cache|plugins|themes)\/([^"\']*)¥.(png|gif|jpe?g|css|js|mp3|wav)["\']/i',
		'<$1$2$3="http://static.dogmap.jp/dogmap/$5/$6.$7"',
		$content
		);
        $content = preg_replace(
                '/<(script|link|a|img)([^>]*)(src|href)=["\']('.preg_quote($includes_url,'/').'|\/wp-includes)\/([^"\']*)¥.(png|gif|jpe?g|css|js|mp3|wav)["\']/i',
                '<$1$2$3="http://static.dogmap.jp/dogmap/includes/$5.$6"',
                $content
                );
//	$content = preg_replace(
//		'/<(script|link|a|img)([^>]*)(src|href)=["\']'.preg_quote($site_url,'/').'(\/[^"\']*)["\']/i',
//		'<$1$2$3="$4"',
//		$content
//		);
	return $content;
}
add_filter( 'head-cleaner/head_cleaner', 'content_static_URI' );
add_filter( 'head-cleaner/pre_html_cleaner', 'content_static_URI' );
add_filter( 'head-cleaner/footer_cleaner', 'content_static_URI' );
add_filter( 'the_excerpt', 'content_static_URI', 11, 1 );
add_filter( 'the_content', 'content_static_URI', 11, 1 );
*/

//**********************************************************************************
// ヘッダーの link を削除
//**********************************************************************************
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'parent_post_rel_link', 10, 0);
remove_action('wp_head', 'start_post_rel_link', 10, 0);
remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);

function my_style_loader_tag($tag, $handle){
	$tag = trim(preg_replace('/id=["\'][^"\']*["\'] /i', '', $tag)) . "\n";
	return $tag;
}
add_filter('style_loader_tag', 'my_style_loader_tag', 10, 2);

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
#pagetop a { padding:5px;background:#ccc;color:#fff;display:block;font-size:20px;font-weight:bold;-webkit-border-radius:8px;-moz-border-radius:8px;border-radius:8px;text-decoration:none; }
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

//**********************************************************************************
// My Clip
//**********************************************************************************
add_action('plugins_loaded', function(){
if ( function_exists('init_my_clip_text') ) {
	init_my_clip_text('clip', 'clipped');
}
});

//**********************************************************************************
// Custome Search
//**********************************************************************************
function custom_search_taxonomy_username($search, $wp_query) {
	global $wpdb;

	// サーチページ以外だったら終了
	if (!$wp_query->is_search)
		return;
	if (!isset($wp_query->query_vars))
		return;

	// ユーザー名とか、タグ名・カテゴリ名も検索対象に
	$search_words = explode(' ', isset($wp_query->query_vars['s']) ? $wp_query->query_vars['s'] : '');
	if ( count($search_words) > 0 ) {
		$search = '';
		foreach ( $search_words as $word ) {
			if ( !empty($word) ) {
				$search_word = $wpdb->escape("%{$word}%");
				$search .= " AND (
 ({$wpdb->prefix}posts.post_title LIKE '{$search_word}')
 OR ({$wpdb->posts}.post_content LIKE '{$search_word}')
 OR {$wpdb->posts}.ID in (
 select distinct r.object_id
 from {$wpdb->term_relationships} r
 inner join {$wpdb->term_taxonomy} tt on r.term_taxonomy_id = tt.term_taxonomy_id
 inner join {$wpdb->terms} t on tt.term_id = t.term_id
 where t.name like '$search_word' OR t.slug like '$search_word' OR tt.description like '$search_word')
 OR {$wpdb->posts}.post_author in (
 select distinct ID
 from {$wpdb->users}
 where display_name like '$search_word')
)";
			}
		}
	}

	if ( function_exists('dbgx_trace_var') )
		dbgx_trace_var($search);

	return $search;
}
add_filter('posts_search','custom_search_taxonomy_username', 1, 2);
