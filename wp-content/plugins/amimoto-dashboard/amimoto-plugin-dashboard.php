<?php
/**
 * Plugin Name: AMIMOTO Plugin Dashboard
 * Version: 0.4.1
 * Description: Control AMIMOTO helper plugins
 * Author: hideokamoto,amimotoami
 * Author URI: https://amimoto-ami.com
 * Plugin URI: https://github.com/amimoto-ami/amimoto-dashboard/
 * Text Domain: amimoto-dashboard
 * Domain Path: /languages
 * @package Amimoto-dashboard
 */
if ( ! is_admin() ) {
	return;
}
require_once( 'module/includes.php' );
define( 'AMI_DASH_PATH', plugin_dir_path( __FILE__ ) );
define( 'AMI_DASH_URL', plugin_dir_url( __FILE__ ) );
define( 'AMI_DASH_ROOT', __FILE__ );

$amimoto_dash = Amimoto_Dash::get_instance();
$amimoto_dash->init();

/**
 * Amimoto_Dash
 *
 * Root Class of this plugin
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-dashboard
 * @since 0.0.1
 */
class Amimoto_Dash {
	private $base;
	private static $instance;
	private static $text_domain;

	private function __construct() {
	}

	/**
	 * Get Instance Class
	 *
	 * @return Amimoto_Dash
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
	 *  Initialize Plugin
	 *
	 * @access public
	 * @param none
	 * @return none
	 * @since 0.0.1
	 */
	public function init() {
		$this->base = Amimoto_Dash_Base::get_instance();
		$menu = Amimoto_Dash_Menus::get_instance();
		$menu->init();
		add_action( 'admin_init',    array( $this, 'update_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_theme_style' ) );

		$patch = new Amimoto_patch();
		$patch->register_patch();
	}

	public function admin_theme_style() {
		wp_enqueue_style( 'amimoto-admin-style',  path_join( AMI_DASH_URL, 'assets/admin.css' ) , array() , '2016062301' );
	}

	/**
	 *  Update Plugin Setting
	 *
	 * @access public
	 * @param none
	 * @return none
	 * @since 0.0.1
	 */
	public function update_settings() {
		if ( empty( $_POST ) ) {
			return;
		}

		// For Nginx Cache Controller
		if ( isset( $_POST['expires'] ) && $_POST['expires'] ) {
			wp_redirect( admin_url( 'admin.php?page=nginx-champuru&message=true' ) );
		}

		$result = false;
		$plugin_stat = Amimoto_Dash_Stat::get_instance();
		if ( $this->is_trust_post_param( Amimoto_Dash_Base::PLUGIN_SETTING ) ) {

		}

		if ( $this->is_trust_post_param( Amimoto_Dash_Base::PLUGIN_ACTIVATION ) ) {
			if ( isset( $_POST['plugin_type'] ) && $_POST['plugin_type'] ) {
				$result = $plugin_stat->activate( esc_attr( $_POST['plugin_type'] ) );
			}
		}

		if ( $this->is_trust_post_param( Amimoto_Dash_Base::PLUGIN_DEACTIVATION ) ) {
			if ( isset( $_POST['plugin_type'] ) && $_POST['plugin_type'] ) {
				$result = $plugin_stat->deactivate( esc_attr( $_POST['plugin_type'] ) );
			}
		}

		if ( $this->is_trust_post_param( Amimoto_Dash_Base::CLOUDFRONT_SETTINGS ) ) {
			$c3 = Amimoto_C3::get_instance();
			$result = $c3->update_setting();
		}

		if ( $this->is_trust_post_param( Amimoto_Dash_Base::CLOUDFRONT_INVALIDATION ) ) {
			$c3 = Amimoto_C3::get_instance();
			if ( isset( $_POST['invalidation_target'] ) && $_POST['invalidation_target'] ) {
				$target = $_POST['invalidation_target'];
			} else {
				$target = 'all';
			}
			$result = $c3->invalidation( $target );
		}

		if ( $this->is_trust_post_param( Amimoto_Dash_Base::CLOUDFRONT_UPDATE_NCC ) ) {
			$c3 = Amimoto_C3::get_instance();
			$result = $c3->overwrite_ncc_settings();
		}

		if ( ! is_wp_error( $result ) && $result ) {
			if ( isset( $_POST['redirect_page'] ) && $_POST['redirect_page'] ) {
				wp_safe_redirect( menu_page_url( $_POST['redirect_page'], false ) );
			}
		}
	}

	/**
	 *  Check plugin nonce key
	 *
	 * @access public
	 * @param none
	 * @return none
	 * @since 0.0.1
	 */
	private function is_trust_post_param( $key ) {
		if ( isset( $_POST[ $key ] ) && $_POST[ $key ] ) {
			if ( check_admin_referer( $key, $key ) ) {
				return true;
			}
		}
		return false;
	}
}
