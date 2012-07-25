$(function() {
	$.deck('.slide', {
		selectors: {
			container: 'body > article'
		},
		
		keys: {
			goto: -1 // No key activation
		}
	});
	$('.slide').css({visibility:'visible'});
	Modernizr.addTest('pointerevents',function(){
		return document.documentElement.style.pointerEvents === '';
	});
});