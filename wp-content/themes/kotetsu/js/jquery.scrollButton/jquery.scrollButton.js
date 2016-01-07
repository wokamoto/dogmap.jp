/**
 * Plugin Name: jquery.scrollButton.js
 * Plugin URI: http://2inc.org
 * Description: スクロールすると自動的にトップへ戻るボタンを表示
 * Version: 0.1.2
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : Jun 21, 2012
 * Modified: March 8, 2013
 * License: GPL2
 *
 * @param	{ duration, offset, startPosY, string, target, theClass )
 *
 * Copyright 2013 Takashi Kitajima (email : inc@2inc.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
( function( $ ) {
	$.fn.scrollButton = function( params ) {
		var defaults = {
			duration  : 200,
			offset    : 20,
			startPosY : 1000,
			string    : '↑',
			target    : '#container',
			theClass  : 'fadeTopBtn'
		};
		params = $.extend( defaults, params );

		return this.each( function( i, e ) {
			$( e ).append( '<div id="fadeTopBtn"><a href="' + params.target + '">' + params.string + '</a></div>' );
			var topBtn = $( e ).find( '#fadeTopBtn' );
			topBtn.addClass( params.theClass );
			// fixed有効判別（ie6, 7, 8 = false）
			var isCssFixed = ( $.support.opacity ) ? true : false;

			if ( isCssFixed ) {
				topBtn.css( {
					opacity : 0,
					position: 'fixed',
					right   : params.offset,
					bottom  : params.offset
				} );
			} else {
				topBtn.css( {
					opacity : 0,
					position: 'absolute'
				} );
				var topBtnWidth  = topBtn.get( 0 ).offsetWidth;
				var topBtnHeight = topBtn.get( 0 ).offsetHeight;
			}
			// 現在の状態（透明でない = true）
			var isVisible = false;
			$( window ).scroll( function () {
				if ( !isCssFixed ) {
					topBtn.css( {
						left: $( window ).scrollLeft() + $( window ).width() - topBtnWidth  - params.offset,
						top : $( window ).scrollTop() + $( window ).height() - topBtnHeight - params.offset
					} );
				}
				var scrollTop = $( this ).scrollTop();
				if ( scrollTop > params.startPosY && !isVisible ) {
					isVisible = true;
					topBtn.show();
					topBtn.animate(
						{ opacity: 0.8 },
						{
							duration: params.duration
						}
					);
				} else if ( scrollTop <= params.startPosY && isVisible ) {
					isVisible = false;
					topBtn.animate( { opacity: 0 },
						{
							duration: params.duration,
							complete: function() {
								topBtn.hide();
							}
						}
					);
				}
			});
		});
	};
})( jQuery );