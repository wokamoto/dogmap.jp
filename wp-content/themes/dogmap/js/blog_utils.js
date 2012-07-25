// image preload
/*@cc_on (function(){var a=new Array('/wp-content/themes/dogmap/images/arrow_1.gif','/wp-content/themes/dogmap/images/arrow_2.gif','/wp-content/themes/dogmap/images/no_arrow.gif','/wp-content/themes/dogmap/images/side-hide.gif','/wp-content/themes/dogmap/images/side-show.gif');jQuery.each(a,function(){var o=new Image();o.src= this;});})(); @*/

jQuery(function(){
	var a1 = '/wp-content/themes/dogmap/images/arrow_1.gif';
	var a2 = '/wp-content/themes/dogmap/images/arrow_2.gif';
	var na = '/wp-content/themes/dogmap/images/no_arrow.gif';
	var sh = '/wp-content/themes/dogmap/images/side-hide.gif';
	var ss = '/wp-content/themes/dogmap/images/side-show.gif';

	// カテゴリリストの折りたたみ表示
	var c = jQuery('#categories-list');
	jQuery('ul.children', c).hide();
	jQuery('li', c).children('a').css({'padding-left':'12px',background:'transparent url(' + na + ') no-repeat left 3px'});
	jQuery('li', c).each(function(){
		if(jQuery(this).children('ul.children').length > 0) {
			jQuery(this).children('a').css('background-image','url(' + a1 + ')');
			jQuery(this).children('a').click(function(){
				jQuery(this).css('background-image','url(' + (jQuery(this).css('background-image').indexOf(a1) != -1 ? a2 : a1) + ')');
				jQuery(this).siblings('ul.children').toggle('fast');
				return false;
			});
		}
	});

//	// blogpeople リスト表示
//	jQuery("#blogpeople").empty().append(jQuery("#linklist").children("div"));
//	jQuery("#linklist").remove();

/*
	// slide bar
	var slide_button;
	if (jQuery.cookie('sidebar') == 'none')
		jQuery('#r_sidebar').animate({opacity:'hide', width:'hide'}, 'normal', function(){jQuery('#content').animate({width:'97.5%'}, 'normal', function(){jQuery('#slide_button').attr('src', ss);});});
	if (jQuery('#r_sidebar').css('display') == 'none') {
		slide_button = jQuery('<img id="slide_button" src="' + ss + '" border="0" title="クリックするとサイドバーが表示されます" />');
		jQuery.cookie('sidebar','none',{expires:7, path: '/'});
	} else {
		slide_button = jQuery('<img id="slide_button" src="' + sh + '" border="0" title="クリックするとサイドバーが隠れます" />');
		jQuery.cookie('sidebar','block',{expires:7, path: '/'});
	}
	slide_button.click(function() {
		if (jQuery('#r_sidebar').css('display') == 'none') {
			jQuery('#content').animate({width:'75%'}, 'normal', function(){jQuery('#r_sidebar').animate({opacity:'show', width:'show'},'normal', function(){jQuery('#slide_button').attr('src', sh);});});
			jQuery.cookie('sidebar','block',{expires:7, path: '/'});
			jQuery(this).attr('title','クリックするとサイドバーが隠れます');
		} else {
			jQuery('#r_sidebar').animate({opacity:'hide', width:'hide'}, 'normal', function(){jQuery('#content').animate({width:'97.5%'}, 'normal', function(){jQuery('#slide_button').attr('src', ss);});});
			jQuery.cookie('sidebar','none',{expires:7, path: '/'});
			jQuery(this).attr('title','クリックするとサイドバーが表示されます');
		}
	});
	jQuery('#r_sidebar').css('width','22.5%').children('.sidebar').css('margin-left',0).children('ul').css('margin-left',0);
	(jQuery('<div style="float:right;margin-top:2.5em;width:2%;height:100%;overflow:hidden;"></div>').append(slide_button)).insertAfter(jQuery('#r_sidebar'));
*/
});
