<?php 
function ipotch_box($echo = true) {
	if (ks_ext_css_available()) {
		$output = '<div class="box">';
	} else {
		$output = '<br /><div style="background-color:white;">';
	}
	if ($echo) {
		echo $output;
	}
	return $output;
}

function ipotch_cbox($echo = true) {
	if (ks_ext_css_available()) {
		$output = '<div class="cbox">';
	} else {
		$output = '<br /><div align="center" style="background-color:white;">';
	}
	if ($echo) {
		echo $output;
	}
	return $output;
}

function ipotch_nbox($echo = true) {
	if (ks_ext_css_available()) {
		$output = '<div class="nbox">';
	} else {
		$output = '<br /><div>';
	}
	if ($echo) {
		echo $output;
	}
	return $output;
}

function ipotch_comment($echo = true, $type = NULL) {
	if (empty($type)) {
		$type = get_comment_type();
	}
	if ($type == 'comment') {
		$class = 'comment';
		$color = 'white';
	} else {
		$class = 'pings';
		$color = '#f8f8f8';
	}
	if (ks_ext_css_available()) {
		$output = '<div class="' . $class . '">';
	} else {
		$output = '<br /><div style="background-color:' . $color . ';">';
	}
	if ($echo) {
		echo $output;
	}
	return $output;
}

function ipotch_link_desc($html) {
	if (ks_applied_appl_xhtml()) {
		return '<span style="color:white;">' . $html . '</span>';
	} else {
		return $html;
	}
}
?>