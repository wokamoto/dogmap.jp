/******************************************************************************
 * wp-hatena - popup JavaScript
 * 
 * @author	hibiki
 * @version	1.0
 * 
 *****************************************************************************/

//function wpHatenaPopup() {
//	
//	
//	
//}


$(function() {
	$('a.popup').click(function(){
		window.open(this.href, "sbmWindow","width=550,height=400");
		return false;
	});
});
