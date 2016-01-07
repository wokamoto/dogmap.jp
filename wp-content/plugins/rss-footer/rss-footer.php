<?php
/*
Plugin Name: RSS Footer
Version: 0.9.9
Plugin URI: http://yoast.com/wordpress/rss-footer/
Description: Allows you to add a line of content to the end of your RSS feed articles.
Author: Joost de Valk
Author URI: http://yoast.com/
*/

function rss_footer_upgrade_warning() {
	if ( is_admin() && current_user_can( 'manage_options' ) ) {
		echo '<div class="updated" id="message"><p>RSS Footer has been discontinued and isn\'t actively maintained. Please <a href="http://local.wordpress.dev/wp-admin/plugin-install.php?tab=search&s=yoast+seo">install Yoast SEO</a> as that\'s both well maintained and more secure. Import your settings from this plugin, and then disable this plugin entirely.</p></div>';
	}
}

add_action( 'admin_notices', 'rss_footer_upgrade_warning' );
