<?php
/**
 * Patch code for AMIMOTO AMI
 *
 * @author hideokamoto <hide.okamoto@digitalcube.jp>
 * @package Amimoto-dashboard
 * @since 0.3.0
 */

 if ( ! defined( 'ABSPATH' ) ) {
	 exit;
 }

 /**
  * Patch code for AMIMOTO AMI
  *
  * @class Amimoto_patch
  * @since 0.3.0
  **/
class Amimoto_patch {

	/**
	 * Regist patch code to WordPress
	 *
	 * @since 0.3.0
	 * @access public
	 **/
	public function register_patch() {
		add_filter( 'wp_mail_from', array( $this, 'patch_mail_address' ) );
	}

	/**
	 * Replace email address if use default.conf on Nginx
	 *
	 * @since 0.3.0
	 * @access public
	 * @param string $original_email_address
	 * @return string $original_email_address
	 **/
	public function patch_mail_address( $original_email_address  ) {
		if ( '_' === $_SERVER['SERVER_NAME'] ) {
			$original_email_address = 'wordpress@' . parse_url( get_home_url( get_current_blog_id() ), PHP_URL_HOST );
		}
		return apply_filters( 'amimoto_patch_mailaddress', $original_email_address );
	}
}
