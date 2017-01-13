<?php
/**
 * Amimoto_Dash_Stat
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-dashboard
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Change AMIMOTO's plugin status
 *
 * @class Amimoto_Dash_Stat
 * @since 0.0.1
 */
class Amimoto_Dash_Stat extends Amimoto_Dash_Base {
	private static $instance;
	private static $text_domain;
	public $amimoto_plugins = array();

	private function __construct() {
		self::$text_domain = Amimoto_Dash_Base::text_domain();
		$this->amimoto_plugins = $this->get_amimoto_plugin_file_list();
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
	public function activate( $plugin_type ) {
		switch ( $plugin_type ) {
			case 'c3-cloudfront-clear-cache':
				$c3 = Amimoto_C3::get_instance();
				$result = $c3->activate( $this->amimoto_plugins );
				break;

			case 'nephila-clavata':
				$s3 = Amimoto_S3::get_instance();
				$result = $s3->activate( $this->amimoto_plugins );
				break;

			case 'nginxchampuru':
				$ncc = Amimoto_Ncc::get_instance();
				$result = $ncc->activate( $this->amimoto_plugins );
				break;

			default:
				$result = false;
				break;
		}
		return $result;
	}

	/**
	 *  Deactivate Plugin
	 *
	 * @access public
	 * @param none
	 * @return boolean | WP_Error
	 * @since 0.0.1
	 */
	public function deactivate( $plugin_type ) {
		switch ( $plugin_type ) {
			case 'c3-cloudfront-clear-cache':
				$c3 = Amimoto_C3::get_instance();
				$result = $c3->deactivate( $this->amimoto_plugins );
				break;

			case 'nephila-clavata':
				$s3 = Amimoto_S3::get_instance();
				$result = $s3->deactivate( $this->amimoto_plugins );
				break;

			case 'nginxchampuru':
				$ncc = Amimoto_Ncc::get_instance();
				$result = $ncc->deactivate( $this->amimoto_plugins );
				break;

			default:
				$result = false;
				break;
		}
		return $result;
	}

}
