<?php
function ks_detect_smartphone($ktai, $ua) {
	if ( preg_match('/\b(iP(hone|[ao]d);|Android|BlackBerry|Windows Phone OS)/', $ua, $name) ) {
		if ($ktai) unset($ktai);
	}
	return $ktai;
}
add_filter('ktai_detect_agent', 'ks_detect_smartphone', 10, 2);

function wptouch_add_bbb($useragents){
	$useragents[] = "blackberry";
	$useragents[] = "windows phone os";
	return $useragents;
}
add_filter('wptouch_user_agents', 'wptouch_add_bbb');
