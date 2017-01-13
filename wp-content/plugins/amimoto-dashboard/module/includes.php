<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once( 'base.php' );
require_once( 'view/component.php' );

// Model
require_once( 'model/plugin_status.php' );
require_once( 'model/ncc.php' );
require_once( 'model/c3.php' );
require_once( 'model/nephila-clavata.php' );
require_once( 'model/wpapi.php' );
require_once( 'model/amimoto_patch.php' );


// View
require_once( 'view/menus.php' );
require_once( 'view/admin.php' );
require_once( 'view/c3-cloudfront-clear-cache.php' );
