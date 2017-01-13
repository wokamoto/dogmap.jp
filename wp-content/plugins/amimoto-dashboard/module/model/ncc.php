<?php
/**
 * Amimoto_Ncc
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-dashboard
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Controle Nginx Cache Controller Plugin
 *
 * @class Amimoto_Ncc
 * @since 0.0.1
 */
class Amimoto_Ncc extends Amimoto_Dash_Base {
	private static $instance;
	private static $text_domain;

	private function __construct() {
		self::$text_domain = Amimoto_Dash_Base::text_domain();
	}

	/**
	 * Get Instance Class
	 *
	 * @return Amimoto_Dash_Menus
	 * @since 0.0.1
	 * @access public
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	/**
	 *  Activate Plugin
	 *
	 * @access public
	 * @param none
	 * @return boolean | WP_Error
	 * @since 0.0.1
	 */
	public function activate( $amimoto_plugins ) {
		$plugin_file_path = path_join( ABSPATH . 'wp-content/plugins', $amimoto_plugins['Nginx Cache Controller on GitHub'] );
		if ( file_exists( $plugin_file_path ) ) {
			activate_plugin( $plugin_file_path, '', $this->is_multisite() );
			return true;
		}

		$plugin_file_path = path_join( ABSPATH . 'wp-content/plugins', $amimoto_plugins['Nginx Cache Controller on WP.org'] );
		if ( file_exists( $plugin_file_path ) ) {
			activate_plugin( $plugin_file_path, '', $this->is_multisite() );
			return true;
		}
		$err = new WP_Error( 'AMIMOTO Dashboard Error', 'Nginx Cache Contoroller Plugin does not exists' );
		return $err;
	}

	/**
	 *  Deactivate Plugin
	 *
	 * @access public
	 * @param none
	 * @return boolean | WP_Error
	 * @since 0.0.1
	 */
	public function deactivate( $amimoto_plugins ) {
		$plugin_file_path = path_join( ABSPATH . 'wp-content/plugins', $amimoto_plugins['Nginx Cache Controller on GitHub'] );
		if ( file_exists( $plugin_file_path ) ) {
			deactivate_plugins( $plugin_file_path, '', $this->is_multisite() );
			return true;
		}

		$plugin_file_path = path_join( ABSPATH . 'wp-content/plugins', $amimoto_plugins['Nginx Cache Controller on WP.org'] );
		if ( file_exists( $plugin_file_path ) ) {
			deactivate_plugins( $plugin_file_path, '', $this->is_multisite() );
			return true;
		}
		$err = new WP_Error( 'AMIMOTO Dashboard Error', 'Fail to Deactivate Nginx Cache Contoroller Plugin' );
		return $err;
	}

}
