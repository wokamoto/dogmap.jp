<?php
/**
 * Amimoto_WPAPI
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-dashboard
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Connect WP REST API
 *
 * @class Amimoto_WPAPI
 * @since 0.1.0
 */
class Amimoto_WPAPI extends Amimoto_Dash_Base {
	private static $instance;
	private static $text_domain;

	private function __construct() {
		self::$text_domain = Amimoto_Dash_Base::text_domain();
	}

	/**
	 * Get Instance Class
	 *
	 * @return Amimoto_WPAPI
	 * @since 0.1.0
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
	 *  Connect amimoto-ami.com's WP REST API
	 *
	 * @access public
	 * @param none
	 * @return array | WP_Error
	 * @since 0.1.0
	 */
	public function get_post( $per_page = 5, $category_id = false ) {
		$query = "?per_page={$per_page}";
		if ( $category_id ) {
			$query .= "&categories={$category_id}";
		}
		$result = wp_remote_get( "https://amimoto-ami.com/wp-json/wp/v2/posts{$query}", array( 'timeout' => 30 ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return json_decode( $result['body'] , true );

	}


}
