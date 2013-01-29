jQuery(document).ready( function($) {
	//jQuery UI 1.5.2 doesn't expect tab Id's at DIV, so we have to apply a hotfix instead
	var needs_jquery_hotfix = (($.ui.version === undefined) || !$.ui.version.match(/^(1\.[7-9]|[2-9]\.)/));
	$("#wpsh-tabs"+(needs_jquery_hotfix ? ">ul" : "")).tabs({
		selected: 0
	}); 
	//because of jQuery 1.3.2 IE8 bug: http://dev.jquery.com/ticket/4661  we can't use toggle at them moment
	//IE 8 doesn't evaluate is(':visible') and also is(':hidden'), so the toggle() function doesn't work !!! 
	$('.wpsh-toggle-section').live('click', function() {
		//additionally the jQuery 1.2.6 has an other bug related to table displayment also worked around !!!
		$(this).blur();
		if (!needs_jquery_hotfix) {
			$(this).find('span').each(function(i, elem) {
				$(elem).toggle($(elem).css('display') == 'none');
			});
			$('.'+$(this).attr('id')).each(function(i, elem) {
				$(elem).toggle($(elem).css('display') == 'none');
			});
		}else {
			/* this works in all browser except IE 8 at jQuery 1.3.2 and seems to affect Safari 4 too. 
			$(this).find('span').toggle();
			$('.'+$(this).attr('id')).toggle();
			*/
			$(this).find('span').each(function(i, elem) {
				$(elem).css('display') == 'none' ? $(elem).css('display', 'table-row') : $(elem).hide();
			});
			$('.'+$(this).attr('id')).each(function(i, elem) {
				$(elem).css('display') == 'none' ? $(elem).css('display', 'table-row') : $(elem).hide();
			});
		}
		return false;
	});
	$('.wpsh-sect-memory:last').show().removeClass('wpsh-sect-memory');

	//memory testing
	function call_test_mem(mb) {
		$.post(wpsh_values.ajax,  {action: 'wp_system_healts_check_memory', size : mb }, function(data) {
			var details = data.replace(/<br\s\/>/g, '').split('|');
			if (details.length >= 3){
				$('#wsph-check-memory-limit-results').prepend('<li class="mem-test-success">'+wpsh_values.label_requested+'&nbsp;'+mb+'&nbsp;MB</li>');
				if (mb < wpsh_values.max_mem_provider) { 
					call_test_mem(mb+1);
				}else{
					$('#wpsh-mem-max').html(wpsh_values.max_mem_provider+' MB');
					$('#wpsh-mem-max-perc').html(wpsh_values.label_fullsize);
					$('#wsph-check-memory-limits').show().parent().next().hide();
				}
			}
			else{
				$('#wsph-check-memory-limit-results').prepend('<li class="mem-test-failed">'+wpsh_values.label_requested+'&nbsp;'+mb+'&nbsp;MB<div><small>'+details+'</small></div></li>');
				$('#wpsh-mem-max').html((mb-1)+' MB');
				if (mb < wpsh_values.max_mem_loader && wpsh_values.max_mem_loader == wpsh_values.max_mem_provider) {
					$('#wpsh-mem-max-perc').html( '<b>'+Math.round((mb-1) * 100.0 / wpsh_values.max_mem_provider)+'%</b>&nbsp;|&nbsp;'+wpsh_values.label_failed);
				}else{
					$('#wpsh-mem-max-perc').html( '<b>'+Math.round((mb-1) * 100.0 / wpsh_values.max_mem_provider)+'%</b>&nbsp;|&nbsp;'+wpsh_values.label_halfsize);
				}
				$('#wsph-check-memory-limits').show().parent().next().hide();
			}
		});		
	}
	
	$('#wsph-check-memory-limits').live('click', function(event) {
		event.preventDefault();
		$(this).blur();
		$(this).hide().parent().next().show();
		$('#wsph-check-memory-limit-results').html('');
		$('#wpsh-mem-max').html('-n.a.-');
		$('#wpsh-mem-max-perc').html('-n.a.-');
		call_test_mem(1);
	});
	
});