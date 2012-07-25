<?php
mysql_query("SET NAMES utf8");
remove_filter('get_shortlink', 'wpme_get_shortlink_handler', 10, 4);

//wp_deregister_script('jquery');
//wp_enqueue_script('jquery', 'http://ajax.microsoft.com/ajax/jquery/jquery-1.7.1.min.js', array(), '1.7.1');

function yoast_add_google_profile( $contactmethods ) {
	// Add Google Profiles
	$contactmethods['google_profile'] = 'Google Profile URL';
	return $contactmethods;
}
add_filter( 'user_contactmethods', 'yoast_add_google_profile', 10, 1);

//**********************************************************************************
// for Feedback Champru!
//**********************************************************************************
function champru_comments($comments_array, $type){
		$comments = array();
		foreach ($comments_array as $comment) {
			switch ($type){
			case 'tweet':
			case 'hatena':
			case 'delicious':
				if ($comment->comment_author != 'wokamoto' && $comment->comment_author != 'dogmap_jp' && $comment->comment_author != 'roland_808' && $comment->comment_author != 'jkuns_cafe'  && $comment->comment_author != 'jkunrss')
					$comments[] = $comment;
				break;
			default:
				$comments[] = $comment;
				break;
			}
		}
		return $comments;
}
add_filter('feedback-champuru/comments_array', 'champru_comments', 10, 2);

//**********************************************************************************
// capital_P_dangit disable
//**********************************************************************************
remove_filter( 'the_content', 'capital_P_dangit' );
remove_filter( 'the_title', 'capital_P_dangit' );
remove_filter( 'comment_text', 'capital_P_dangit' );

//**********************************************************************************
// #more-$id を削除する。
//**********************************************************************************
function custom_content_more_link( $output, $more_link_text ) {
	$output = preg_replace('/#more\-[\d]+/i', '', $output );
	return $output;
}
add_filter( 'the_content_more_link', 'custom_content_more_link', 10, 2 );

//**********************************************************************************
// シングルクォーテーション、ダブルクォーテーションの変換
//**********************************************************************************
add_filter('the_content', create_function('$content', 'return str_replace(array("&#8216;","&#8217;","&#8220;","&#8221;"),array("&#39;","&#39;","&quot;","&quot;"),$content);'), 10000);

//**********************************************************************************
// 携帯電話からのアクセスかどうかをチェックする
//**********************************************************************************
if (!function_exists('is_mobile')) {

function is_mobile() {
	return (function_exists('is_ktai')
		? is_ktai()
		: false
		);
}

}

//**********************************************************************************
// ブラウザが JavaScript 対応かどうかチェックする
//**********************************************************************************
if (!function_exists('is_javascript')) {

function is_javascript() {
return true;
	if (function_exists('get_browser')) {
		$browser = @get_browser(null, true);
		if (is_array($browser) && $browser['javascript']) {
			return true;
		} else {
			$ua = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
			return (preg_match("/(AppleWebKit|chrome|Mozilla\/4\.0 \(compatible; +MSIE)/i", $ua)
				? true
				: false
				);
		}
	} else {
		return !is_mobile();
	}
}

}

//**********************************************************************************
// 3点リーダを「MS Pゴシック」に
//**********************************************************************************
function replace_3pointlead($content){
	if ( !is_mobile() && !is_feed() ) {
		$content = preg_replace(
			'/(>?[^<…;]*)((…|&#8230;)+)([^>…&]*<?)/' ,
			"$1<span style=\"font-family: 'ＭＳ Ｐゴシック'\">$2</span>$4" ,
			$content);
	}
	return $content;
}
add_filter('the_content', 'replace_3pointlead');