<?php
/*
Plugin Name: Flamingo
Plugin URI: http://flamingo-eggs.com/
Description: Flamingo manages your contact list on WordPress.
Author: Takayuki Miyoshi
Text Domain: flamingo
Domain Path: /languages/
Version: 1.0.2
*/

define( 'FLAMINGO_VERSION', '1.0.2' );

define( 'FLAMINGO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define( 'FLAMINGO_PLUGIN_NAME', trim( dirname( FLAMINGO_PLUGIN_BASENAME ), '/' ) );

define( 'FLAMINGO_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );

define( 'FLAMINGO_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );

require_once FLAMINGO_PLUGIN_DIR . '/includes/functions.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/formatting.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/capabilities.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/class-contact.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/class-inbound-message.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/user.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/comment.php';

if ( is_admin() )
	require_once FLAMINGO_PLUGIN_DIR . '/admin/admin.php';

/* Init */

add_action( 'init', 'flamingo_init' );

function flamingo_init() {

	/* L10N */
	load_plugin_textdomain( 'flamingo', false, 'flamingo/languages' );

	/* Custom Post Types */
	Flamingo_Contact::register_post_type();
	Flamingo_Inbound_Message::register_post_type();

	do_action( 'flamingo_init' );
}

?>