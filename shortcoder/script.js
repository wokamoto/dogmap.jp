String.prototype.trim = function() {
    return this.replace(/^[ ]+|[ ]+$/g, '');
}

function add_input_set(curr_key) {
	var next_key = curr_key + 1;

	if (jQuery("p#key_" + next_key).size() > 0) return;

	jQuery("p#key_" + curr_key).css("margin-bottom",".25em").after(jQuery(
		'<p id="key_' + next_key + '" style="margin-top:.25em;">' +
		'<label><small>ショートコードオプション ' + (next_key + 1) + '</small></label>' +
		'<input type="text" name="atts_key_' + next_key + '" id="atts_key_' + next_key + '" size="40" tabindex="9" value="" style="margin-right:0;width:290px;" />' + 
		'<input type="text" name="atts_default_' + next_key + '" id="atts_default_' + next_key + '" size="40" tabindex="9" value="" style="margin-left:5px;" />' +
		'</p>').hide().fadeIn()
	);
}

function add_key(curr_key) {
	var next_key = curr_key + 1;

	if (jQuery("input#atts_key_" + curr_key).attr("value") != "") {
		var key_count = Number(jQuery("input#atts_keynum").attr("value")) + 1;
		add_input_set(curr_key);
		jQuery("input#atts_keynum").attr("value", key_count);
		jQuery("input#atts_key_" + next_key).unbind("change").change((function(curr_key){return function(){add_key(curr_key);}})(next_key));
		jQuery("input#atts_key_" + curr_key).unbind("change").change((function(curr_key){return function(){remove_key(curr_key);}})(curr_key));
	}
}

function remove_key(curr_key) {
	var key_count = Number(jQuery("input#atts_keynum").attr("value"));
	var next_key  = curr_key + 1;
	var prev_key  = curr_key - 1;

	if ( key_count == next_key
	  && jQuery("input#atts_key_" + curr_key).attr("value") == ""
	  && jQuery("input#atts_key_" + next_key).attr("value") == "" ) {
		jQuery("p#key_" + next_key).fadeOut(function(){
			key_count--;
			jQuery("input#atts_keynum").attr("value", key_count);
			jQuery(this).remove();
		});

		jQuery("input#atts_key_" + curr_key).unbind("change").change((function(curr_key){return function(){add_key(curr_key);}})(curr_key));
		if ( prev_key >= 0 )
			jQuery("input#atts_key_" + (prev_key)).unbind("change").change((function(curr_key){return function(){remove_key(curr_key);}})(prev_key));
	}
}

function submit_form(){
	var plugin_name = jQuery('#plugin_name').attr('value');
	var plugin_url = jQuery('#plugin_url').attr('value');
	var plugin_var = jQuery('#plugin_ver').attr('value');
	var plugin_author = jQuery('#plugin_author').attr('value');
	var plugin_authorurl = jQuery('#plugin_authorurl').attr('value');
	var plugin_copyright = jQuery('#plugin_copyright').attr('value');
	var plugin_desc = jQuery('#plugin_desc').attr('value');
	var shortcode_name = jQuery('#shortcode_name').attr('value');
	var plugin_retval = jQuery('#plugin_retval').attr('value');
	var key_count = Number(jQuery('#atts_keynum').attr('value'));
	var key_id, default_val;

	if ( plugin_name.trim() == '' || shortcode_name.trim() == '' || plugin_retval.trim() == '') {
		alert('必須項目を入力してください');
		return false;
	}

	var options = { expires: 7, path: '/shortcoder/' };
	jQuery.cookie('plugin_name', plugin_name, options);
	jQuery.cookie('plugin_url', plugin_url, options);
	jQuery.cookie('plugin_ver', plugin_var, options);
	jQuery.cookie('plugin_author', plugin_author, options);
	jQuery.cookie('plugin_authorurl', plugin_authorurl, options);
	jQuery.cookie('plugin_copyright', plugin_copyright, options);
	jQuery.cookie('plugin_desc', plugin_desc, options);
	jQuery.cookie('shortcode_name', shortcode_name, options);
	jQuery.cookie('atts_keynum', key_count, options);
	jQuery.cookie('plugin_retval', plugin_retval, options);
	for (var curr_key = 0; curr_key < key_count; curr_key++) {
		key_id = jQuery('#atts_key_' + curr_key).attr('value');
		default_val = jQuery('#atts_default_' + curr_key).attr('value');
		jQuery.cookie('atts_key_' + curr_key, key_id, options);
		jQuery.cookie('atts_default_' + curr_key, default_val, options);
	}

//	var plugin_code = encodeURI(jQuery('#plugin_code').val());
//	jQuery('#plugin_code').val(plugin_code);
}

jQuery(function(){
	jQuery("input#submit").unbind('click').click(submit_form);
	jQuery("form#request").unbind('submit').submit(submit_form);

	jQuery('#plugin_name').attr('value', jQuery.cookie('plugin_name') ? jQuery.cookie('plugin_name') : '');
	jQuery('#plugin_url').attr('value', jQuery.cookie('plugin_url') ? jQuery.cookie('plugin_url') : '');
	jQuery('#plugin_ver').attr('value', jQuery.cookie('plugin_ver') ? jQuery.cookie('plugin_ver') : '0.0.1');
	jQuery('#plugin_author').attr('value', jQuery.cookie('plugin_author') ? jQuery.cookie('plugin_author') : '');
	jQuery('#plugin_authorurl').attr('value', jQuery.cookie('plugin_authorurl') ? jQuery.cookie('plugin_authorurl') : '');
	jQuery('#plugin_copyright').attr('value', jQuery.cookie('plugin_copyright') ? jQuery.cookie('plugin_copyright') : 'Copyright 2009' );
	jQuery('#plugin_desc').attr('value', jQuery.cookie('plugin_desc') ? jQuery.cookie('plugin_desc') : '');
	jQuery('#shortcode_name').attr('value', jQuery.cookie('shortcode_name') ? jQuery.cookie('shortcode_name') : '' );
	jQuery('#atts_keynum').attr('value', jQuery.cookie('atts_keynum') ? jQuery.cookie('atts_keynum') : 0);
	jQuery('#plugin_retval').attr('value', jQuery.cookie('plugin_retval') ? jQuery.cookie('plugin_retval') : '$return_text');
	var key_count = Number(jQuery('#atts_keynum').attr('value'));
	for (var curr_key = 0, next_key, key_name, key_default; curr_key < key_count; curr_key++) {
		key_name = jQuery.cookie('atts_key_' + curr_key) ? jQuery.cookie('atts_key_' + curr_key) : '';
		key_default = jQuery.cookie('atts_default_' + curr_key) ? jQuery.cookie('atts_default_' + curr_key) : '';
		jQuery('#atts_key_' + curr_key).attr('value', key_name);
		jQuery('#atts_default_' + curr_key).attr('value', key_default);

		next_key = curr_key + 1;
		if (key_name != '') add_input_set(curr_key);
	}

	if ( jQuery('#atts_key_0').attr('value') == '' && key_count == 1) {
		jQuery("input#atts_key_0").unbind("change").change((function(curr_key){return function(){add_key(curr_key);}})(0));
	} else {
		var curr_key = key_count - 1;
		var next_key = curr_key  + 1;
		jQuery("input#atts_key_" + next_key).unbind("change").change((function(curr_key){return function(){add_key(curr_key);}})(next_key));
		jQuery("input#atts_key_" + curr_key).unbind("change").change((function(curr_key){return function(){remove_key(curr_key);}})(curr_key));
	}

	jQuery('#more_info a').click(function(){
		jQuery('#more_info').fadeOut(function(){
			jQuery(this).remove();
			jQuery('div.content p:hidden').fadeIn(function(){});
		});
	});
});
