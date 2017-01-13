<?php
/**
 * Amimoto_Dash_Cloudfront Class file
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-dashboard
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Amimoto Plugin Dashboard admin page to set-up CloudFront
 *
 * @class Amimoto_Dash_Cloudfront
 * @since 0.0.1
 */
class Amimoto_Dash_Cloudfront extends Amimoto_Dash_Component {
	private static $instance;
	private static $text_domain;
	private function __construct() {
		self::$text_domain = Amimoto_Dash_Base::text_domain();
	}

	/**
	 * Get Instance Class
	 *
	 * @return Amimoto_Dash_Cloudfront
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
	 *  Show admin page html
	 *
	 * @access public
	 * @param none
	 * @return none
	 * @since 0.0.1
	 */
	public function init_panel() {
		$this->show_panel_html();
	}

	/**
	 *  Get admin page html content
	 *
	 * @access public
	 * @param none
	 * @return string(HTML)
	 * @since 0.0.1
	 */
	public function get_content_html() {
		$html = '';
		$html .= $this->_get_cf_invalidation_form();
		$html .= $this->_get_cf_setting_form();
		if ( $this->is_activated_ncc() ) {
			$html .= '<hr/>';
			$html .= $this->_get_ncc_update_form();
		}
		return apply_filters( 'amimoto_c3_add_settings', $html );
	}

	/**
	 *  Get CloudFront Invalidation Form html
	 *
	 * @access private
	 * @param none
	 * @return string(HTML)
	 * @since 0.0.1
	 */
	private function _get_cf_invalidation_form() {
		$c3_settings = get_option( 'c3_settings' );
		$html = '';
		if ( ! $c3_settings ) {
			return $html;
		}
		$html .= "<form method='post' action=''>";
		$html .= "<table class='wp-list-table widefat plugins'>";
		$html .= '<thead>';
		$html .= "<tr><th colspan='2'><h2>" . __( 'CloudFront Cache Control', self::$text_domain ). '</h2></th></tr>';
		$html .= '</thead>';
		$html .= '<tbody>';
		$html .= '<tr><th>'. __( 'Flush All Cache', self::$text_domain ). '</th>';
		$html .= '<td>';
		$html .= "<input type='hidden' name='invalidation_target' value='all' />";
		$html .= wp_nonce_field( self::CLOUDFRONT_INVALIDATION , self::CLOUDFRONT_INVALIDATION , true , false );
		$html .= get_submit_button( __( 'Flush All Cache', self::$text_domain ) );
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</tbody></table>';
		$html .= '</form>';
		$html .= '<hr/>';
		return $html;
	}

	/**
	 *  Update Nginx Cache Controller Setting for CDN
	 *
	 * @access private
	 * @param none
	 * @return string(HTML)
	 * @since 0.0.1
	 */
	private function _get_ncc_update_form() {
		$html = '';
		$html .= "<form method='post' action=''>";
		$html .= "<table class='wp-list-table widefat plugins'>";
		$html .= '<thead>';
		$html .= "<tr><th colspan='2'><h2>" . __( 'Nginx Cache Settings', self::$text_domain ). '</h2></th></tr>';
		$html .= '</thead>';
		$html .= '<tbody>';
		$html .= '<tr><th><b>'. __( 'Change Nginx Cache Expires Shorten', self::$text_domain ). '</b>';
		$html .= '<p>' . __( 'All Nginx Cache Expires change 30sec.', self::$text_domain ) . '</p></th>';
		$html .= '<td>';
		$html .= "<input type='hidden' name='invalidation_target' value='all' />";
		$html .= wp_nonce_field( self::CLOUDFRONT_UPDATE_NCC , self::CLOUDFRONT_UPDATE_NCC , true , false );
		$html .= get_submit_button( __( 'Change Expires', self::$text_domain ) );
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</tbody></table>';
		$html .= '</form>';
		return $html;
	}


	/**
	 *  Get CloudFront Setting Form html
	 *
	 * @access private
	 * @param none
	 * @return string(HTML)
	 * @since 0.0.1
	 */
	private function _get_cf_setting_form() {
		$c3_settings = get_option( 'c3_settings' );
		$has_ec2_instance_role = apply_filters( 'amimoto_has_ec2_instance_role', false );
		if ( ! isset( $c3_settings['distribution_id'] ) || ! $c3_settings['distribution_id'] ) {
			$c3_settings['distribution_id'] = '';
		}
		if ( ! isset( $c3_settings['access_key'] ) || ! $c3_settings['access_key'] ) {
			$c3_settings['access_key'] = '';
		}
		if ( ! isset( $c3_settings['secret_key'] ) || ! $c3_settings['secret_key'] ) {
			$c3_settings['secret_key'] = '';
		}
		$c3_settings = apply_filters( 'c3_settings', $c3_settings );
		if ( ( ! isset( $c3_settings['access_key'] ) || ! $c3_settings['access_key'] ) && ( ! isset( $c3_settings['secret_key'] ) || ! $c3_settings['secret_key'] ) ) {
			$has_ec2_instance_role = true;
		}
		$html = '';
		$html .= "<form method='post' action=''>";
		$html .= "<table class='wp-list-table widefat plugins'>";
		$html .= '<thead>';
		$html .= "<tr><th colspan='2'><h2>" . __( 'CloudFront Connection Settings', self::$text_domain ). '</h2></th></tr>';
		$html .= '</thead>';
		$html .= '<tbody>';
		$html .= '<tr><th>'. __( 'CloudFront Distribution ID', self::$text_domain ). '</th>';
		$html .= "<td><input type='text' class='regular-text code' name='c3_settings[distribution_id]' value='{$c3_settings['distribution_id']}' /></td>";
		$html .= '</tr>';
		if ( ! $has_ec2_instance_role ) {
			$html .= '<tr><th>'. __( 'AWS Access Key', self::$text_domain ). '</th>';
			$html .= "<td><input type='text' class='regular-text code' name='c3_settings[access_key]' value='{$c3_settings['access_key']}' /></td>";
			$html .= '</tr>';
			$html .= '<tr><th>'. __( 'AWS Secret Key', self::$text_domain ). '</th>';
			$html .= "<td><input type='password' class='regular-text code' name='c3_settings[secret_key]' value='{$c3_settings['secret_key']}' /></td>";
			$html .= '</tr>';
		}
		$html .= "<tr><td colspan='2'>";
		$html .= wp_nonce_field( self::CLOUDFRONT_SETTINGS , self::CLOUDFRONT_SETTINGS , true , false );
		$html .= get_submit_button( __( 'Update CloudFront Settings', self::$text_domain ) );
		$html .= '</td></tr>';
		$html .= '</tbody></table>';
		$html .= '</form>';
		return $html;
	}
}
