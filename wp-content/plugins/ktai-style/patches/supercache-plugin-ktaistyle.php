<?php

/* ==================================================
 *   Plugin for WP Suer Cache
   ================================================== */

/* 
 * Put this file to the directory "wp-content/plugins/wp-super-cache/plugins/"
 * as "wp-content/plugins/wp-super-cache/plugins/ktaistyle.php"
 */

if (function_exists('add_cacheaction')) :

function wp_supercache_ktai_conditions($conditions) {
	$last = array_pop($conditions);
	if (! preg_match('/^RewriteCond %{HTTP_USER_AGENT}/', $last)) {
		$conditions[] = $last;
	}
	$conditions[] = 'RewriteCond %{HTTP_USER_AGENT} !^(DoCoMo/|J-PHONE/|J-EMULATOR/|Vodafone/|MOT(EMULATOR)?-|SoftBank/|[VS]emulator/|KDDI-|UP\.Browser/|emobile/|Huawei/|IAC/|Nokia|mixi-mobile-converter/)';
	$conditions[] = 'RewriteCond %{HTTP_USER_AGENT} !(DDIPOCKET;|WILLCOM;|Opera\ Mini|Opera\ Mobi|PalmOS|Windows\ CE;|PDA;\ SL-|PlayStation\ Portable;|SONY/COM|Nitro|Nintendo)';
	if (function_exists('ks_option') && ks_option('ks_theme_touch')) {
		$conditions[] = 'RewriteCond %{HTTP_USER_AGENT} !(iPhone;|iPod;|Android)';
	}
	return $conditions;
}

function wp_supercache_add_action_ktaistyle() {
	add_filter( 'supercacherewriteconditions', 'wp_supercache_ktai_conditions' );
}
add_cacheaction( 'add_cacheaction', 'wp_supercache_add_action_ktaistyle' );

function wp_supercache_ktaistyle_admin() {
	global $valid_nonce, $wp_cache_config_file, $cache_ktaistyle, $wp_cache_mobile_browsers, $orig_wp_cache_mobile_browsers;
	if (! isset($cache_ktaistyle)) {
		$cache_ktaistyle = 0;
	}
	$ktaistyle_browsers = 'DoCoMo/, J-PHONE/, J-EMULATOR/, Vodafone/, MOT-, MOTEMULATOR-, SoftBank/, emulator/, DDIPOCKET;, WILLCOM;, KDDI-, UP.Browser/, emobile/, Huawei/, IAC/, Nokia, Opera Mini, Opera Mobi, Palm OS, Windows CE;, PDA; SL-, PlayStation Portable, SONY/COM, Nitro, Nintendo, mixi-mobile-converter/';
	if (function_exists('ks_option') && ks_option('ks_theme_touch')) {
		$ktaistyle_browsers .= ', iPhone;, iPod;, Android';
	}

	if(isset($_POST['cache_ktaistyle']) && $valid_nonce) {
		if( !class_exists('KtaiStyle') && !class_exists('Ktai_Style') ) {
			$_POST[ 'cache_ktaistyle' ] = __( 'Disable', 'wp-super-cache' );
			$err = __( 'Ktai Style not found. Please check your install.', 'wp-super-cache' );
		}
		$cache_ktaistyle = $_POST['cache_ktaistyle'] == __( 'Disable', 'wp-super-cache' ) ? 0 : 1;
		wp_cache_replace_line('^ *\$cache_ktaistyle', "\$cache_ktaistyle = '$cache_ktaistyle';", $wp_cache_config_file);
		if ($cache_ktaistyle) {
			if (! isset($orig_wp_cache_mobile_browsers)) {
				wp_cache_replace_line('^ *\$orig_wp_cache_mobile_browsers', "\$orig_wp_cache_mobile_browsers = '$wp_cache_mobile_browsers';", $wp_cache_config_file);
			}
			wp_cache_replace_line('^ *\$wp_cache_mobile_browsers ', "\$wp_cache_mobile_browsers = '$ktaistyle_browsers';", $wp_cache_config_file);
		} elseif (isset($orig_wp_cache_mobile_browsers) && $orig_wp_cache_mobile_browsers != $ktaistyle_browsers) {
			wp_cache_replace_line('^ *\$wp_cache_mobile_browsers ', "\$wp_cache_mobile_browsers = '$orig_wp_cache_mobile_browsers';", $wp_cache_config_file);
			wp_cache_replace_line('^ *\$orig_wp_cache_mobile_browsers', '', $wp_cache_config_file);
		}
	}
	echo '<form name="wp_supercache_ktaistyle_admin" action="'. $_SERVER["REQUEST_URI"] . '" method="post">';
	wp_nonce_field('wp-cache');
	if( $cache_ktaistyle == 0 ) {
		$ks_status = __( 'disabled', 'wp-super-cache' );
	} else {
		$ks_status = __( 'enabled', 'wp-super-cache' );
		wp_super_cache_disable();
	}
	echo '<strong>' . sprintf( __( 'Ktai Style support is %s', 'wp-super-cache' ), $ks_status );
	echo '.</strong>';
	printf( __( '(Changing supporting mobile devices. Requires <a href="http://wppluginsj.sourceforge.jp/ktai_style/">Ktai Style</a>.) ', 'wp-super-cache' ) );
	if( $cache_ktaistyle == 0 ) {
		echo '<input type="submit" name="cache_ktaistyle" value="' . __( 'Enable', 'wp-super-cache' ) . '" />';
	} else {
		echo '<input type="submit" name="cache_ktaistyle" value="' . __( 'Disable', 'wp-super-cache' ) . '" />';
	}
	echo "</form>\n";
	if( $err )
		echo "<p><strong>" . __( 'Warning!', 'wp-super-cache' ) . "</strong> $err</p>";

}
add_cacheaction( 'cache_admin_page', 'wp_supercache_ktaistyle_admin' );

endif;
?>