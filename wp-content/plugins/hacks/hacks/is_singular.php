<?php
//** Your custom code starts here **************************************//
class Is_singular_ShortCode {
	// Constructor
	function __construct() {
		add_shortcode('is_singular', array(&$this, 'Shortcode_Handler'));
	}

	function Shortcode_Handler($atts, $content = null) {
		extract( shortcode_atts( array(
			'enable' => TRUE
			), $atts) );

//** Your code *********************************************************//

	$enable = (strtolower($enable) === 'false' ? FALSE : TRUE);
	if ($enable && is_singular()) {
		$return_text = $content;
	} else if (!$enable && !is_singular()) {
		$return_text = $content;
	}

//** End of Your code **************************************************//

		return $return_text;
	}
}


// This registers the shortcode.
new Is_singular_ShortCode();

