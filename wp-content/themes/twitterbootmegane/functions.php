<?php
//カスタムヘッダー
add_custom_image_header('','__return_false');

define('HEADER_IMAGE', '%s/image/top/main_image.png');
define('HEADER_IMAGE_WIDTH', 950);
define('HEADER_IMAGE_HEIGHT', 295);

//カスタムメニュー
register_nav_menus(
array(
'place_global' => 'グローバル' ,
'place_utility' => 'ユーティリティー' ,
)
);


//アイキャッチ
add_theme_support('post-thumbnails');
set_post_thumbnail_size(200, 200, ture);
add_image_size('s-thumbnail',100, 100, ture);
add_image_size('l-thumbnail',300, 300, ture);

//画像の幅
if ( ! isset( $content_width ) )
    $content_width = 600;

//投稿画面のスタイル
add_editor_style();

//カスタム背景
add_custom_background() ;


//ウィジェットTOP右
register_sidebar(array(
'name' => 'トップ画像右側',
'id' => 'top-widget-area',
'description' => 'トップページメイン画像の右側に表示するウィジェットエリアです',
'before_widget' => '<aside id="%1$&" class="widget-container %2$&">',
'after_widget' => '</aside>',
'before_title' => '<h2 class="wiget-title">',
'after_title' => '</h2>',
));


//ウィジェットTOP中央
register_sidebar(array(
'name' => 'トップページ中央',
'id' => 'primary-widget-area',
'description' => 'TOPページ中央部に表示するウィジェットエリアです',
'before_widget' => '<aside id="%1$&" class="widget-container %2$&">',
'after_widget' => '</aside>',
'before_title' => '<h2 class="wiget-title">',
'after_title' => '</h2>',
));


//ウィジェット右
register_sidebar(array(
'name' => 'サイドウィジェット',
'id' => 'secondary-widget-area',
'description' => 'サイドバーに表示するウィジェットエリアです',
'before_widget' => '<aside id="%1$&" class="widget-container %2$&">',
'after_widget' => '</aside>',
'before_title' => '<h2 class="wiget-title">',
'after_title' => '</h2>',
));

//ウィジェット下1
register_sidebar(array(
'name' => 'フッタウィジェット1',
'id' => 'footer01',
'description' => 'フッタの最左に表示するウィジェットエリアです',
'before_widget' => '<aside id="%1$&" class="widget-container %2$&">',
'after_widget' => '</aside>',
'before_title' => '<h2 class="wiget-title">',
'after_title' => '</h2>',
));

//ウィジェット下2
register_sidebar(array(
'name' => 'フッタウィジェット2',
'id' => 'footer02',
'description' => 'フッタの左から2番目に表示するウィジェットエリアです',
'before_widget' => '<aside id="%1$&" class="widget-container %2$&">',
'after_widget' => '</aside>',
'before_title' => '<h2 class="wiget-title">',
'after_title' => '</h2>',
));

//ウィジェット下3
register_sidebar(array(
'name' => 'フッタウィジェット3',
'id' => 'footer03',
'description' => 'フッタの左から3番目に表示するウィジェットエリアです',
'before_widget' => '<aside id="%1$&" class="widget-container %2$&">',
'after_widget' => '</aside>',
'before_title' => '<h2 class="wiget-title">',
'after_title' => '</h2>',
));

//ウィジェット下4
register_sidebar(array(
'name' => 'フッタウィジェット4',
'id' => 'footer04',
'description' => 'フッタの最右に表示するウィジェットエリアです',
'before_widget' => '<aside id="%1$&" class="widget-container %2$&">',
'after_widget' => '</aside>',
'before_title' => '<h2 class="wiget-title">',
'after_title' => '</h2>',
));


//記事下のユーティリティー（Twenty Tenより流用）
if ( ! function_exists( 'twitterbootmegane_posted_in' ) ) :
/**
 * Prints HTML with meta information for the current post (category, tags and permalink).
 *
 * @since Twenty Ten 1.0
 */
function twitterbootmegane_posted_in() {
	// Retrieves tag list of current post, separated by commas.
	$tag_list = get_the_tag_list( '', ', ' );
	if ( $tag_list ) {
		$posted_in = __( '%1$s %2$s.', 'twitterbootmegane' );
	} elseif ( is_object_in_taxonomy( get_post_type(), 'category' ) ) {
		$posted_in = __( '%1$s.', 'twitterbootmegane' );
	} else {
		$posted_in = __( '', 'twitterbootmegane' );
	}
	// Prints the string, replacing the placeholders.
	printf(
		$posted_in,
		get_the_category_list( ', ' ),
		$tag_list,
		get_permalink(),
		the_title_attribute( 'echo=0' )
	);
}
endif;


//RSSのリンク
add_theme_support( 'automatic-feed-links' );


//抜粋文の表示の変更
function cms_excerpt_more() {
	return ' …';
}
add_filter('excerpt_more','cms_excerpt_more');

function cms_excerpt_length() {
	return 60;
}
add_filter('excerpt_mblength','cms_excerpt_length');


