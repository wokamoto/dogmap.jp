<?php
include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

class Remote_Upgrader_Skin extends WP_Upgrader_Skin {
	var $output = array();

	var $in_loop = false;
	var $error = false;

	function __construct($args = array()) {
		$defaults = array( 'url' => '', 'nonce' => '' );
		$args = wp_parse_args($args, $defaults);

		parent::__construct($args);
	}

	function add_strings() {
		$this->upgrader->strings['skin_upgrade_start'] = __('The update process is starting. This process may take a while on some hosts, so please be patient.');
		$this->upgrader->strings['skin_update_failed_error'] = __('An error occurred while updating %1$s: <strong>%2$s</strong>.');
		$this->upgrader->strings['skin_update_failed'] = __('The update of %1$s failed.');
		$this->upgrader->strings['skin_update_successful'] = __('%1$s updated successfully.').' <a onclick="%2$s" href="#" class="hide-if-no-js"><span>'.__('Show Details').'</span><span class="hidden">'.__('Hide Details').'</span>.</a>';
		$this->upgrader->strings['skin_upgrade_end'] = __('All updates have been completed.');
	}

	function feedback($string) {
		if ( isset( $this->upgrader->strings[$string] ) )
			$string = $this->upgrader->strings[$string];

		if ( strpos($string, '%') !== false ) {
			$args = func_get_args();
			$args = array_splice($args, 1);
			if ( !empty($args) )
				$string = vsprintf($string, $args);
		}
		if ( empty($string) )
			return;
		if ( $this->in_loop )
			$this->output[] = "$string<br />\n";
		else
			$this->output[] = "<p>$string</p>\n";
	}

	function header() {
		// Nothing, This will be displayed within a iframe.
	}

	function footer() {
		// Nothing, This will be displayed within a iframe.
	}
	function error($error) {
		if ( is_string($error) && isset( $this->upgrader->strings[$error] ) )
			$this->error = $this->upgrader->strings[$error];

		if ( is_wp_error($error) ) {
			foreach ( $error->get_error_messages() as $emessage ) {
				if ( $error->get_error_data() )
					$messages[] = $emessage . ' ' . $error->get_error_data();
				else
					$messages[] = $emessage;
			}
			$this->error = implode(', ', $messages);
		}
	}

	function bulk_header() {
		$this->feedback('skin_upgrade_start');
	}

	function bulk_footer() {
		$this->feedback('skin_upgrade_end');
	}

	function before($title = '') {
		$this->in_loop = true;
		$this->flush_output();
	}

	function after($title = '') {
		$this->reset();
		$this->flush_output();
	}

	function reset() {
		$this->in_loop = false;
		$this->error = false;
	}

	function flush_output() {
		$levels = ob_get_level();
		for ($i=0; $i<$levels; $i++) {
			$this->output[] = ob_get_contents();
			ob_end_clean();
		}
	}

}
