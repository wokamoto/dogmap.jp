<?php
//** Your custom code starts here **************************************//
class Is_home_ShortCode {
	// Constructor
	function __construct() {
	}

	function Shortcode_Handler($atts, $content = null) {
		extract( shortcode_atts( array(
			'enable' => TRUE
			), $atts) );

//** Your code *********************************************************//

	$enable = (strtolower($enable) === 'false' ? FALSE : TRUE);
	if ($enable && is_home()) {
		$return_text = $content;
	} else if (!$enable && !is_home()) {
		$return_text = $content;
	}

//** End of Your code **************************************************//

		return $return_text;
	}
}


// This registers the shortcode.
$is_home = new Is_home_ShortCode();
add_shortcode('is_home', array(&$is_home, 'Shortcode_Handler'));
unset($is_home);

