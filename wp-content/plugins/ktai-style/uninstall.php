<?php

/* ==================================================
 *   Ktai Style Uninstall script
   ================================================== */

if ( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
	exit;
}
require_once dirname(__FILE__) . '/config/panel.php';
KtaiStyle_Config::delete_options();
?>