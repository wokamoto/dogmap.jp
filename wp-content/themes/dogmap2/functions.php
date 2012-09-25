<?php
remove_action( 'wp_scheduled_delete', 'wp_scheduled_delete' );
add_action( 'wp_scheduled_delete', '__return_true' );

//**********************************************************************************
// 言語ファイルの読み込み
//**********************************************************************************
load_theme_textdomain(
    'twentytwelve',
    dirname(__FILE__).'/languages'
);

//**********************************************************************************
// スタイルシートの読み込み
//**********************************************************************************
if ( !is_admin() ) {
    wp_enqueue_style(
        'twentytwelve-style',
        get_template_directory_uri() . '/style.css',
        array(),
        date('YmdHis', filemtime(get_template_directory() . '/style.css'))
        );
    wp_enqueue_style(
        'twentytwelve-child-style',
        get_stylesheet_uri(),
        array('twentytwelve-style'),
        date('YmdHis', filemtime(get_stylesheet_directory() . '/style.css'))
        );
}

add_action('wp_enqueue_scripts', function(){wp_dequeue_style('twentytwelve-fonts');}, 11);

//**********************************************************************************
// 制御用定数
//**********************************************************************************
if (!defined('GM_ANYWHERE_DEBUG_MODE'))
	define('GM_ANYWHERE_DEBUG_MODE', true);

//**********************************************************************************
// サムネールサポート
//**********************************************************************************
add_theme_support( 'post-thumbnails' );
set_post_thumbnail_size( 160, 160, true );

//**********************************************************************************
// Caption 無効
//**********************************************************************************
add_filter('disable_captions', create_function('','return true;'));

//**********************************************************************************
// comment author link フィルターフック
//**********************************************************************************
add_filter('get_comment_author_link', function($link){
	global $commenters_info;

	if (isset($commenters_info))
		$link .= '&nbsp;<span class="commenters-info">' . $commenters_info->get_commenters_info() . '</span>';
	return $link;
});

//**********************************************************************************
// link rel=’prev’ および link rel=’next’ を投稿だけで表示する
//**********************************************************************************
function remove_adjacent_posts_rel_link_wp_head() {
	if ( ! is_single() ) {
		remove_filter( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	}
}
add_action( 'wp_head', 'remove_adjacent_posts_rel_link_wp_head', 0 );
