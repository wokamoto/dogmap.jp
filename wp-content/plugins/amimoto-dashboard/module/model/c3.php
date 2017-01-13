<?php
/**
 * Amimoto_C3
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-dashboard
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Controle C3 CloudFront Cache Contoroller Plugin
 *
 * @class Amimoto_C3
 * @since 0.0.1
 */
class Amimoto_C3 extends Amimoto_Dash_Base {
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
		$plugin_file_path = path_join( ABSPATH . 'wp-content/plugins', $amimoto_plugins['C3 Cloudfront Cache Controller'] );
		if ( file_exists( $plugin_file_path ) ) {
			activate_plugin( $plugin_file_path, '', $this->is_multisite() );
			$this->overwrite_ncc_settings();
			return true;
		}

		$err = new WP_Error( 'AMIMOTO Dashboard Error', 'C3 Cloudfront Cache Controller Plugin does not exists' );
		return $err;
	}

	/**
	 *  Overwrite Nginx Cache Controller Settings
	 *
	 * @access public
	 * @param none
	 * @return boolean | WP_Error
	 * @since 0.0.1
	 */
	public function overwrite_ncc_settings() {
		if ( $this->is_activated_ncc() ) {
			$expires = get_option( 'nginxchampuru-cache_expires' );
			$updated_expires = array();
			foreach ( $expires as $key => $value ) {
				$updated_expires[ $key ] = 30;
			}
			update_option( 'nginxchampuru-cache_expires', $updated_expires );
		}
		return true;
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
		$plugin_file_path = path_join( ABSPATH . 'wp-content/plugins', $amimoto_plugins['C3 Cloudfront Cache Controller'] );
		if ( file_exists( $plugin_file_path ) ) {
			deactivate_plugins( $plugin_file_path, '', $this->is_multisite() );
			return true;
		}

		$err = new WP_Error( 'AMIMOTO Dashboard Error', 'Fail to Deactivate C3 Cloudfront Cache Controller Plugin' );
		return $err;
	}

	/**
	 *  Update Plugin Setting
	 *
	 * @access public
	 * @param none
	 * @return boolean | WP_Error
	 * @since 0.0.1
	 */
	public function update_setting() {
		$updated_setting = array();
		foreach ( $_POST['c3_settings'] as $key => $value ) {
			$updated_setting[ $key ] = esc_attr( $value );
		}
		update_option( 'c3_settings', $updated_setting );
		return true;
	}

	/**
	 *  Invalidation
	 *
	 * @access public
	 * @param (string) $target
	 * @return boolean | WP_Error
	 * @since 0.0.1
	 */
	public function invalidation( $target ) {
		$plugin_file_path = path_join( ABSPATH , 'wp-content/plugins/c3-cloudfront-clear-cache/c3-cloudfront-clear-cache.php' );
		require_once( $plugin_file_path );
		if ( 'all' === $target ) {
			$c3 = CloudFront_Clear_Cache::get_instance();
			$result = $c3->c3_invalidation();
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return true;
	}

}
