<?php
/*
function zenback_title($title) {
	if (is_singular() && have_posts())
		return '<!-- zenback_title_begin -->'.$title.'<!-- zenback_title_end -->';
	else
		return $title;
}
add_filter( 'the_title', 'zenback_title' );
*/

function zenback_body($content) {
	if (is_singular())
		return "<!-- zenback_body_begin -->\n".$content."<!-- zenback_body_end -->\n<!-- zenback_date ".get_the_time('Y-m-d')." -->\n";
	else
		return $content;
}
add_filter( 'the_content', 'zenback_body', 99999);

