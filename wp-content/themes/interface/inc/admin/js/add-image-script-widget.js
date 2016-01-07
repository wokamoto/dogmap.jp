// using multiple media upload through themeoptions.php
jQuery(document).ready(function() {
	var formfield;var uploadID = ''; /*setup the var in a global scope*/
			jQuery('.upload-button1').live('click',function() {
				
			uploadID = jQuery(this).prev('input'); 
			jQuery('html').addClass('Image');
			formfield = jQuery('.upload1').attr('name');
			
			post_id = jQuery('#post_ID').val();
			if(typeof post_id === 'undefined')
				post_id = 0;
				
	 		tb_show('', 'media-upload.php?post_id='+post_id+'&amp;type=image&amp;TB_iframe=true');
			return false;
		});
		// user inserts file into post. only run custom if user started process using the above process
		// window.send_to_editor(html) is how wp would normally handle the received data
		window.original_send_to_editor = window.send_to_editor;
		window.send_to_editor = function(html){
			if (formfield) {
				fileurl = jQuery('img',html).attr('src');
				uploadID.val(fileurl); /*assign the value of the image src to the input*/
				tb_remove();
				jQuery('html').removeClass('Image');
			} else {
				window.original_send_to_editor(html);
			}
		};
});
