jQuery( function() {

		// Search toggle.
		jQuery( '.search-toggle' ).on( 'click', function( event ) {
			var that    = jQuery( this ),
				wrapper = jQuery( '#search-box' );

			that.toggleClass( 'active' );
			wrapper.toggleClass( 'hide' );

			if ( that.is( '.active' ) || jQuery( '.search-toggle' )[0] === event.target ) {
				wrapper.find( '.s' ).focus();
			}
		} );

		// Enable menu toggle for small screens.
		( function() {
			var nav = jQuery( '#access' ), button, menu;
			if ( ! nav ) {
				return;
			}

			button = nav.find( '.menu-toggle' );
			if ( ! button ) {
				return;
			}

			// Hide button if menu is missing or empty.
			menu = nav.find( '.nav-menu' );
			if ( ! menu || ! menu.children().length ) {
				button.hide();
				return;
			}

			jQuery( '.menu-toggle' ).on( 'click', function() {
				nav.toggleClass( 'toggled-on' );
			} );
		} )();
} );