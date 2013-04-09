/*	Copyright (C) 2013 Amazon.com, Inc. or its affiliates. All rights reserved.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
	51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

(function ($) {
	'use strict';
	$(function () {
		// One time only, make sure the background matches the theme.
		if ($('input[name*="theme"]:checked').val() === 'dark') {
			$('#preview').css('background', 'black');
		}

		// button icon
		$('input[name*="color"],input[name*="size"]').change(function () {
			var src = $('.kindleWidget > img').attr('src');
			var color = $('input[name*="color"]:checked').val();
			var size = {
				'small': 15,
				'large': 25
			}[$('input[name*="size"]:checked').val()];
			src = src.replace(/\w+-\d+\.png$/i, color + '-' + size + '.png');
			$('.kindleWidget img').attr('src', src);
		});

		// button theme
		$('input[name*="theme"],input[name*="border"]').change(function () {
			// reset our theming classes
			$('.kindleWidget').removeClass('kindleLight kindleDark kindleDarkText');

			// set up the preview area and text color
			if ($('input[name*="theme"]:checked').val() === 'light') {
				$('#preview').css('background', '');
			} else {
				$('#preview').css('background', 'black');
				$('.kindleWidget').addClass('kindleDarkText');
			}

			// add the appropriate theme class if necessary
			if ($('input[name*="border"]').is(':checked')) {
				if ($('#text').val() === 'none') {
					$('#text').val('kindle').change();
				}
				if ($('input[name*="theme"]:checked').val() === 'light') {
					$('.kindleWidget').addClass('kindleLight');
				} else {
					$('.kindleWidget').addClass('kindleDark');
				}
			}
		});

		// button text
		$('#text').change(function () {
			var text = $('#text option:selected').text();
			if (text === 'None') {
				$('.kindleWidget span').remove();
			} else {
				if ($('.kindleWidget > span').length === 0) {
					$('.kindleWidget').append('<span>');
				}
				$('.kindleWidget > span').text(text);
			}
		});

		// button font
		$('#font').change(function (e) {
			$('.kindleWidget').css('font-family', $(e.target).val());
		});
	});
})(jQuery);