<?php
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
    wp_enqueue_style( 'twentytwelve-style', get_template_directory_uri() . '/style.css', array(), '20120725' );
    wp_enqueue_style( 'twentytwelve-child-style', get_stylesheet_uri(), array('twentytwelve-style'), '20120726' );
}

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
