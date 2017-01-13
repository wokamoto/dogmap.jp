<?php
/**
 * Amimoto_Dash_Menus
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-dashboard
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Define AMMIMOTO Dashboard plugin's admin page menus
 *
 * @class Amimoto_Dash_Menus
 * @since 0.0.1
 */
class Amimoto_Dash_Menus extends Amimoto_Dash_Base {
	private static $instance;
	private static $text_domain;
	private function __construct() {
		self::$text_domain = Amimoto_Dash_Base::text_domain();
	}
	private $amimoto_plugin_menu = array(
		'c3-admin-menu',
		'nginx-champuru',
	);
	private $amimoto_plugin_submenu = array(
		'nephila-clavata',
	);

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
	 *  Init plugin menu.
	 *
	 * @access public
	 * @param none
	 * @since 0.0.1
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'define_menus' ) );
		add_action( 'admin_bar_init', array( $this, 'remove_menus' ) );
	}

	/**
	 *  Remove AMIMOTO's plugin default menus controller
	 *
	 * @access public
	 * @param none
	 * @since 0.0.1
	 */
	public function remove_menus() {
		$this->_remove_top_menu();
		$this->_remove_submenu();
	}

	/**
	 *  Remove AMIMOTO's plugin default submenu
	 *
	 * @access private
	 * @param none
	 * @since 0.0.1
	 */
	private function _remove_submenu() {
		global $submenu;
		foreach ( (array) $submenu['options-general.php'] as $key => $array ) {
			foreach ( $this->amimoto_plugin_submenu as $plugin ) {
				if ( array_search( $plugin, $array ) ) {
					unset( $submenu['options-general.php'][ $key ] );
					break;
				}
			}
		}
	}

	/**
	 *  Remove AMIMOTO's plugin default menu
	 *
	 * @access private
	 * @param none
	 * @since 0.0.1
	 */
	private function _remove_top_menu() {
		global $menu;
		foreach ( (array) $menu as $key => $array ) {
			foreach ( $this->amimoto_plugin_menu as $plugin ) {
				if ( array_search( $plugin, $array ) ) {
					unset( $menu[ $key ] );
					break;
				}
			}
		}
	}

	/**
	 *  Define AMIMOTO Dashboard plugin menus
	 *
	 * @access public
	 * @param none
	 * @since 0.0.1
	 */
	public function define_menus() {
		$base = Amimoto_Dash_Admin::get_instance();
		add_menu_page(
			__( 'Welcome to AMIMOTO Plugin Dashboard', self::$text_domain ),
			__( 'AMIMOTO', self::$text_domain ),
			'administrator',
			self::PANEL_ROOT,
			array( $base, 'init_panel' ),
			'dashicons-admin-settings',
			3
		);
		$amimoto_plugins = $this->get_amimoto_plugin_file_list();
		$active_plugin_urls = get_option( 'active_plugins' );
		if ( array_search( $amimoto_plugins['C3 Cloudfront Cache Controller'], $active_plugin_urls ) ) {
			$c3 = Amimoto_Dash_Cloudfront::get_instance();
			add_submenu_page(
				self::PANEL_ROOT,
				__( 'C3 Cloudfront Cache Controller', self::$text_domain ),
				__( 'CloudFront', self::$text_domain ),
				'administrator',
				self::PANEL_C3,
				array( $c3, 'init_panel' )
			);
		}

		if ( array_search( $amimoto_plugins['Nephila clavata'], $active_plugin_urls ) ) {
			$plugin_file_path = path_join( ABSPATH , 'wp-content/plugins/nephila-clavata/includes/class-NephilaClavata_Admin.php' );
			require_once( $plugin_file_path );
			$nephila_clavata_admin = NephilaClavata_Admin::get_instance();
			add_submenu_page(
				self::PANEL_ROOT,
				__( 'Nephila clavata', self::$text_domain ),
				__( 'Amazon S3', self::$text_domain ),
				'administrator',
				self::PANEL_S3,
				array( $nephila_clavata_admin, 'options_page' )
			);
			add_filter('nephila_clavata_admin_url',function(){ return 'admin.php'; } );
		}

		if ( array_search( $amimoto_plugins['Nginx Cache Controller on GitHub'], $active_plugin_urls ) ||
			 array_search( $amimoto_plugins['Nginx Cache Controller on WP.org'], $active_plugin_urls ) ) {
			$plugin_file_path = path_join( ABSPATH , 'wp-content/plugins/nginx-champuru/includes/admin.class.php' );
			if ( ! file_exists( $plugin_file_path ) ) {
				$plugin_file_path = path_join( ABSPATH , 'wp-content/plugins/nginx-cache-controller/includes/admin.class.php' );
			}
			if ( file_exists( $plugin_file_path ) ) {
				require_once( $plugin_file_path );
				$nginxchampuru_admin = NginxChampuru_Admin::get_instance();
				add_submenu_page(
					self::PANEL_ROOT,
					__( 'Nginx Cache Controller', self::$text_domain ),
					__( 'Nginx Reverse Proxy', self::$text_domain ),
					'administrator',
					self::PANEL_NCC,
					array( $nginxchampuru_admin, "admin_panel")
				);
			}
		}
	}
}
