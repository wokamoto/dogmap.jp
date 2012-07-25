<?php

/* ==================================================
 *   Patch for WP-Cache
   ================================================== */

/*
  Usage:

  1) Duplicate wp-cache-config-sample.php as wp-cache-config.php 
     and insert below codeinto line 19 (WP-Cache);
     AFTER setting $cache_rejected_user_agent.

if (file_exists(ABSPATH. 'wp-content/plugins/ktai-style/patches/wpcache.php')) {
        include ABSPATH. 'wp-content/plugins/ktai-style/patches/wpcache.php';
}

  2) Place the config file to wp-content/wp-cache-config.php.
  3) Enable WP-Cache or WP Super Cache plugin.

*/

if (! defined('KTAI_COOKIE_PCVIEW')) :
define ('KTAI_COOKIE_PCVIEW', 'ktai_pc_view');
endif;

if (! isset($_COOKIE[KTAI_COOKIE_PCVIEW])) {
	$ks_mobile_agents = array(
		'DoCoMo/', 'J-PHONE/', 'J-EMULATOR/', 'Vodafone/', 
		'MOT-', 'MOTEMULATOR-', 'SoftBank/', 'emulator/', 
		'DDIPOCKET;', 'WILLCOM;', 'KDDI-', 'UP.Browser/', 
		'emobile/', 'Huawei/', 'IAC/', 'Nokia', 'Opera Mini', 'Opera Mobi', 
		'PalmOS', 'Windows CE;', 'PDA; SL-',
		'PlayStation Portable', 'SONY/COM', 
		'Nitro', 'Nintendo',
		'mixi-mobile-converter/',
		'iPhone;', 'iPod;', 'Android',
	);
	
	$ua = $_SERVER['HTTP_USER_AGENT'];
	foreach ($ks_mobile_agents as $a) {
		if (stripos($ua, $a) !== false) {
			$cache_enabled = false;
			$super_cache_enabled = false;
			break;
		}
	}
	
	$cache_rejected_user_agent = array_merge($cache_rejected_user_agent, $ks_mobile_agents);
}
?>