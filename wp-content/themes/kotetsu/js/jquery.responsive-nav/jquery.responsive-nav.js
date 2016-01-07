/**
 * jquery.responsive-nav
 * Description: レスポンシブなナビゲーションを実装。プルダウンナビ <=> オフキャンバスナビ
 * Version: 1.0.7
 * Author: Takashi Kitajima
 * Autho URI: http://2inc.org
 * created : February 20, 2014
 * modified: August 12, 2014
 * package: jquery
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
;( function( $ ) {
	$.fn.responsive_nav = function( config ) {
		var is_open = false;
		var container, wrapper, global_nav;
		var _global_nav = this;

		return this.each( function( i, e ) {
			global_nav = _global_nav.clone( true );
			_global_nav.addClass( 'responsive-nav' );

			if ( $( '#responsive-btn' ).css( 'display' ) !== 'none' ) {
				wrap_all();
				set_off_canvas_nav();
			} else {
				_global_nav.show();
			}

			var menu = $( this ).find( 'ul:first-child' );
			menu.children( 'li' ).children( 'ul' ).children( 'li' ).hover( function() {
				var children = $( this ).children( 'ul' );
				if ( children.length ) {
					if ( $( window ).width() < ( children.width() + children.offset().left ) ) {
						children.addClass( 'reverse-pulldown' );
						children.find( 'ul' ).addClass( 'reverse-pulldown' );
					}
				}
			} );

			$( window ).resize( function() {
				var children = menu.children( 'li' ).children( 'ul' ).children( 'li' );
						children.removeClass( 'reverse-pulldown' );
						children.find( 'ul' ).removeClass( 'reverse-pulldown' );
				if ( $( '#responsive-btn' ).css( 'display' ) !== 'none' ) {
					wrap_all();
					set_off_canvas_nav();
					if ( is_open ) {
						container.css( 'marginLeft', get_slide_width() );
						var height = $( window ).height();
						global_nav.css( {
							height: height
						} );
						wrapper.css( {
							height: height,
							overflow: 'hidden'
						} );
					}
				} else {
					unwrap();
					unset_off_canvas_nav();
				}
			} );

			$( '#responsive-btn' ).click( function() {
				if ( is_open ) {
					nav_close();
				} else {
					nav_open();
				}
			} );
			$( 'body' ).click( function() {
				if ( is_open ) {
					nav_close();
				}
			} );
		} );

		function get_slide_width() {
			return global_nav.width();
		}

		function nav_open() {
			var height = $( window ).height();
			global_nav.css( {
				height: height
			} );
			global_nav.show();
			container.css( 'width', container.width() );
			container.animate( {
				marginLeft: get_slide_width()
			}, 200, function() {
				is_open = true;
				wrapper.css( {
					height: height,
					overflow: 'hidden'
				} );
			} );
		}

		function nav_close() {
			container.animate( {
				marginLeft: 0
			}, 200, function() {
				unwrap();
				wrap_all();
			} );
		}

		function unwrap() {
			is_open = false;
			if ( container ) {
				container.children().unwrap();
				wrapper.children().unwrap();
				container = '';
				_global_nav.show();
				global_nav.remove();
			}
		}

		function wrap_all() {
			if ( !container ) {
				$( 'body' ).children( '*:not(#wpadminbar)' ).wrapAll( '<div id="responsive-nav-container" />' );
				container = $( '#responsive-nav-container' );
				container.wrapAll( '<div id="responsive-nav-wrapper" />' );
				wrapper = $( '#responsive-nav-wrapper' );
				container.css( 'position', 'relative' );
				container.prepend( global_nav );
				_global_nav.hide();
			}
		}

		function set_off_canvas_nav() {
			global_nav.addClass( 'off-canvas-nav' );
			global_nav.css( 'left', - get_slide_width() );
		}

		function unset_off_canvas_nav() {
			global_nav.removeClass( 'off-canvas-nav' );
			global_nav.css( 'display', 'block' );
		}
	}
} )( jQuery );