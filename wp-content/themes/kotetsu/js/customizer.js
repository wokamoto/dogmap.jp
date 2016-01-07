( function( $ ) {
	// Site title and description
	wp.customize( 'blogname', function( value ) {
		value.bind( function( to ) {
			$( '.site-title a' ).text( to );
		} );
	} );
	wp.customize( 'blogdescription', function( value ) {
		value.bind( function( to ) {
			$( '.site-description' ).text( to );
		} );
	} );

	// Font color
	wp.customize( 'font_color', function( value ) {
		value.bind( function( to ) {
			$( 'body' ).css( 'color', to );
			$( '.hentry .entry-title a' ).css( 'color', to );
		} );
	} );

	// Link color
	wp.customize( 'link_color', function( value ) {
		value.bind( function( to ) {
			$( 'a:not( .global-nav a, #footer a, .hentry .entry-title a, .bread-crumb a, .entry-thumbnail a )' ).css( 'color', to );
			$( '.entries .hentry .entry-thumbnail a' ).css( 'backgroundColor', to );
		} );
	} );

	// Global Navigation
	wp.customize( 'gnav_color', function( value ) {
		value.bind( function( to ) {
			$( '.global-nav, .global-nav ul li a' ).css( 'backgroundColor', to );
		} );
	} );
} )( jQuery );