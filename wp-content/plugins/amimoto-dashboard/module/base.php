<?php
/**
 * Amimoto_Dash_Base Class file
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-dashboard
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Define AMMIMOTO Dashboard plugin's basic function and parameters
 *
 * @class Amimoto_Dash_Base
 * @since 0.0.1
 */
class Amimoto_Dash_Base {
	private static $instance;
	private static $text_domain;
	private static $version;

	//Panel key
	const PANEL_ROOT = 'amimoto_dash_root';
	const PANEL_C3 = 'amimoto_dash_c3';
	const PANEL_S3 = 'nephila-clavata';
	const PANEL_NCC = 'nginx-champuru';

	// Action key
	const PLUGIN_SETTING = 'amimoto_setting';
	const PLUGIN_ACTIVATION = 'amimoto_activation';
	const PLUGIN_DEACTIVATION = 'amimoto_deactivation';
	const CLOUDFRONT_SETTINGS = 'amimoto_cf_setting';
	const CLOUDFRONT_INVALIDATION = 'amimoto_cf_invalidation';
	const CLOUDFRONT_UPDATE_NCC = 'amimoto_cf_ncc_setting';

	private function __construct() {
	}

	/**
	 * Get Instance Class
	 *
	 * @return Amimoto_Dash_Base
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
	 * Get Plugin version
	 *
	 * @return string
	 * @since 0.1.0
	 */
	public static function version() {
		static $version;

		if ( ! $version ) {
			$data = get_file_data( AMI_DASH_ROOT , array( 'version' => 'Version' ) );
			$version = $data['version'];
		}
		return $version;
	}

	/**
	 * Get Plugin text_domain
	 *
	 * @return string
	 * @since 0.1.0
	 */
	public static function text_domain() {
		static $text_domain;

		if ( ! $text_domain ) {
			$data = get_file_data( AMI_DASH_ROOT , array( 'text_domain' => 'Text Domain' ) );
			$text_domain = $data['text_domain'];
		}
		return $text_domain;
	}

	/**
	 * Get AMIMOTO plugin file path list
	 *
	 * @return array
	 * @since 0.0.1
	 * @access public
	 */
	public function get_amimoto_plugin_file_list() {
		$amimoto_plugins = array(
			'C3 Cloudfront Cache Controller' => 'c3-cloudfront-clear-cache/c3-cloudfront-clear-cache.php',
			'Nephila clavata' => 'nephila-clavata/plugin.php',
			'Nginx Cache Controller on GitHub' => 'nginx-cache-controller/nginx-champuru.php',
			'Nginx Cache Controller on WP.org' => 'nginx-champuru/nginx-champuru.php',
		);
		return $amimoto_plugins;
	}

	/**
	 * Check is multisite
	 *
	 * @return boolean
	 * @since 0.0.1
	 * @access public
	 */
	public function is_multisite() {
		return function_exists('is_multisite') && is_multisite();
	}

	/**
	 * Check is Nginx Cache Controller Activated
	 *
	 * @return boolean
	 * @since 0.0.1
	 * @access public
	 */
	public function is_activated_ncc() {
		$amimoto_plugins = $this->get_amimoto_plugin_file_list();
		$activate_plugins = get_option('active_plugins');
		if ( array_search( $amimoto_plugins['Nginx Cache Controller on GitHub'], $activate_plugins ) ||
			 array_search( $amimoto_plugins['Nginx Cache Controller on WP.org'], $activate_plugins ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check is Nginx Cache Controller file exists
	 *
	 * @return boolean
	 * @since 0.0.1
	 * @access public
	 */
	public function is_exists_ncc() {
		$amimoto_plugins = $this->get_amimoto_plugin_file_list();
		if ( file_exists( path_join( ABSPATH . 'wp-content/plugins', $amimoto_plugins['Nginx Cache Controller on GitHub'] ) ) ||
			 file_exists( path_join( ABSPATH . 'wp-content/plugins', $amimoto_plugins['Nginx Cache Controller on WP.org'] ) ) ) {
			return true;
		}
		return false;
	}
}
