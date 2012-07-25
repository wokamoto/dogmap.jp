/*!
 * FlexPlayer v0.1.0
 * http://www.wktklabs.com/flexplayer/
 * Date: 2009-06-03
 * Copyright (c) 2009 galara
 * Dual licensed under the MIT licenses.
 *
 * This is jQuery Plugin. Therefore, you have to follow a jQuery license.
 */
(function($) {
	var funcs = {
		flexplayer : function(config){
			config = jQuery.extend({
					width: 1,
					height: 1,
					swfPath : "flexplayer.swf",
					bgcolor: "#ffffff",
					standbyImagePath : "",
					standbyImageScale : "inFit",
					swfReady : false,
					onProgress : false,
					onSoundComplete : false
				},config);
			var id = "external" + $(this).attr("id");
			if (config.swfReady!==false) $(this).swfReady(config.swfReady);
			if (config.onProgress!==false) $(this).onProgress(config.onProgress);
			if (config.onSoundComplete!==false) $(this).onSoundComplete(config.onSoundComplete);
			var object_html;
			if ($(this).checkFlashVersion()>=9) object_html = (($.browser.msie) ? '<object id="'+id+'" width='+config.width+' height='+config.height+' classid=clsid:D27CDB6E-AE6D-11cf-96B8-444553540000 type="application/x-shockwave-flash" codeBase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab">' : 
												'<object id="'+id+'" width='+config.width+' height='+config.height+' data="'+config.swfPath+'" type="application/x-shockwave-flash">') +
							(($.browser.msie) ? '<param name="movie" value="'+config.swfPath+'" />' : '') +
							'<param name="quality" value="high" />' +
							'<param name="allowFullscreen" value="true" />' +
							'<param name="FlashVars" value="id=' + 
								escape($(this).attr("id")) + 
								'&standbyImagePath=' + escape(config.standbyImagePath) + 
								'&standbyImageScale=' + escape(config.standbyImageScale) + 
								'&bgcolor=' + escape(config.bgcolor) + 
							'" />' +
							'<param name="bgcolor" value="'+ config.bgcolor +'" />' +
							'<param name="wmode" value="transparent" />' +
							'<param name="allowNetworking" value="always" />' +
							'<param name="allowScriptAccess" value="all" />' +
							'</object>';
			else  object_html = '<a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /> このコンテンツを表示するには、最新のFlashPlayerをインストールする必要があります。</a>';
			$(this).get(0).innerHTML = object_html;
			return $(this);
		},
		swfReady: function(func){
			$(this).data("swfReady", func);
		},
		onProgress: function(func){
			$(this).data("onProgress", func);
		},
		onSoundComplete: function(func){
			$(this).data("onSoundComplete", func);
		},
		play : function(config){
			config = jQuery.extend({
					type: "",
					path: "",
					videoScale: "inFit",
					playSoon: true,
					volume: -1
				},config);
			($(this).getDocumentObj()).flexplayer_play(config.type, $(this).getAbsolutePath(config.path), config.playSoon, config.volume, config.videoScale);
		},
		stop: function(){
			($(this).getDocumentObj()).flexplayer_stop();
		},
		pause: function(){
			($(this).getDocumentObj()).flexplayer_pause();
		},
		resume: function(){
			($(this).getDocumentObj()).flexplayer_resume();
		},
		volume: function(num){
			if (!num) num = -1;
			return ($(this).getDocumentObj()).flexplayer_volume(num);
		},
		seek: function(time){
			($(this).getDocumentObj()).flexplayer_seek(time);
		},
		status: function(){
			return ($(this).getDocumentObj()).flexplayer_status();
		},
		playedTime: function(){
			return ($(this).getDocumentObj()).flexplayer_playedTime();
		},
		totalTime: function(){
			return ($(this).getDocumentObj()).flexplayer_totalTime();
		},
		checkFlashVersion: function(){
			if(navigator.plugins && navigator.mimeTypes.length) return (navigator.plugins["Shockwave Flash"].description.match(/([0-9]+)/))[0]-0;
			else return (new ActiveXObject("ShockwaveFlash.ShockwaveFlash").GetVariable("$version").match(/([0-9]+)/))[0]-0;
		},
		getDocumentObj: function(){
			var id = "external" + $(this).attr("id");
			return (document.all? window[id] : document[id]);
		},
		getAbsolutePath: function(path){
			var e = document.createElement('span');
			e.innerHTML = '<a href="' + path + '" />';
			return e.firstChild.href;
		}
	}
	$.each(funcs, function(i) {
		$.fn[i] = this;
	});
})(jQuery);
function flexPlayer_swf2js_swfReady(id){
	($("#"+id).data("swfReady"))();
}
function flexPlayer_swf2js_onProgress(id, bytesLoaded, bytesTotal, playedTime, totalTime){
	($("#"+id).data("onProgress"))(bytesLoaded, bytesTotal, playedTime, totalTime);
}
function flexPlayer_swf2js_onSoundComplete(id){
	($("#"+id).data("onSoundComplete"))();
}
