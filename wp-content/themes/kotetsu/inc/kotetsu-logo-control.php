<?php
/**
 * Kotetsu_Logo_Control
 * Version: 1.0.0
 * Author: inc2734
 * Author URI: http://2inc.org
 */
class Kotetsu_Logo_Control extends WP_Customize_Image_Control {
	public function __construct( $manager, $id, $args ) {
		parent::__construct( $manager, $id, $args );
		$this->add_tab( 'tab_defaults', 'default', array( $this, 'tab_defaults' ) );
	}
	public function tab_defaults() {
		?>
		<div class="uploaded-target"></div>
		<?php
		$defaults = array(
			get_template_directory_uri() . '/images/common/logo.png',
		);
		foreach ( $defaults as $default ) {
			$this->print_tab_image( esc_url_raw( $default ) );
		}
	}
}