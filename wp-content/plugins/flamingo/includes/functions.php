<?php

function flamingo_plugin_url( $path = '' ) {
	$url = untrailingslashit( FLAMINGO_PLUGIN_URL );

	if ( ! empty( $path ) && is_string( $path ) && false === strpos( $path, '..' ) )
		$url .= '/' . ltrim( $path, '/' );

	return $url;
}

function flamingo_array_flatten( $input ) {
	if ( ! is_array( $input ) )
		return array( $input );

	$output = array();

	foreach ( $input as $value )
		$output = array_merge( $output, flamingo_array_flatten( $value ) );

	return $output;
}

?>