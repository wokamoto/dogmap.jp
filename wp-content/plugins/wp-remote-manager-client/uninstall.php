<?php
if ( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
	exit;
}

if (!class_exists('WP_OAuthDataStore'))
        require_once dirname(__FILE__) . '/includes/class-wp-oauth-datastore.php';
$datastore = new WP_OAuthDataStore();
$datastore->drop_tables();
unset($datastore);
?>