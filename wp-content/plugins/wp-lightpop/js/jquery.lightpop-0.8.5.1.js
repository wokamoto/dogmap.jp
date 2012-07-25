/**
 * jQuery lightpop plugin
 * This jQuery plugin was inspired on jQuery lightBox plugin by Leandro Vieira Pinho (http://leandrovieira.com/projects/jquery/lightbox/)
 * @name jquery.lightpop-0.8.5.js
 * @author wokamoto - http://dogmap.jp
 * @version 0.8.5.1
 * @date November 11, 2010
 * @category jQuery plugin
 * @copyright (c) 2007-2010 wokamoto (dogmap.jp)
 * @license  Released under the GPL license (http://www.gnu.org/copyleft/gpl.html)
 * @example http://dogmap.jp/lightpop_sample/
 */
(function(jQuery){
//	jQuery.browser = jQuery.extend(
//		{chrome: /chrome/i.test(navigator.userAgent)} ,
//		jQuery.browser
//	);

	jQuery.fn.lightpop = function(settings, fileTypes, plugin_name, image_path, pre_image, pre_icon){
		document._write = document.write;

		plugin_name = plugin_name || 'lightpop';
		image_path  = image_path  || 'images/';
		pre_image   = pre_image   || image_path + plugin_name + '-';
		pre_icon    = pre_icon    || image_path + 'icon-';

		settings = jQuery.extend( true, {
			overlayBgColor:		'#000' ,
			overlayOpacity:		0.7 ,
			contentFrameType:	'border' ,
			contentBorder:		'none' ,
			boxBorderSize:		6 ,
			containerBorderSize:	10 ,
			containerResizeSpeed:	'normal' ,
			contentBgColor:		'#FFF' ,
			imageBox:			pre_image + 'box.gif' ,
			imageBorderL:		pre_image + 'border-l.gif' ,
			imageBorderR:		pre_image + 'border-r.gif' ,
			imageLoading:		pre_image + 'ico-loading.gif' ,
			imageBtnPrev:		pre_image + 'btn-prev.gif' ,
			imageBtnNext:		pre_image + 'btn-next.gif' ,
			imageBtnClose:		pre_image + 'btn-close.gif' ,
			imageBlank:			pre_image + 'blank.gif' ,
			imageBtnPrevWidth:	63 ,
			imageBtnNextWidth:	63 ,
			imageBtnCloseWidth:	66 ,
			txtImage:			null ,
			txtOf:				'of' ,
			setLinkToTitle:		false ,
			keyToClose:			'c' ,
			keyToPrev:			'p' ,
			keyToNext:			'n' ,
			filter:				'a,area' ,
			flvplayer:			'flvplayer.swf' ,
			iconAdd:			true ,
			detailsEnabled:		true ,
			initSize:			250 ,
			defaultLater:		1500 ,
			grouping:			true ,
			Image:				{enabled: true,	icon: pre_icon + 'image.png', param: {}, size: [0, 0]} ,
			Contents:			{enabled: true,	icon: pre_icon + 'contents.png', param: {}, size: [0, 0], iframeEnabled: true, scrolling:'no', style:'border:solid 1px #CCC;'} ,
			Video:				{enabled: true,	icon: pre_icon + 'video.png', param: {}, size: [320, 240]} ,
			YouTube:			{enabled: true,	icon: pre_icon + 'youtube.png', param: {hl:'ja'}, size: [425, 355]} ,
			Metacafe:			{enabled: true,	icon: pre_icon + 'metacafe.png', param: {}, size: [400, 345]} ,
			LiveLeak:			{enabled: true,	icon: pre_icon + 'liveleak.png', param: {}, size: [450, 370]} ,
			GoogleVideo:		{enabled: true,	icon: pre_icon + 'googlevideo.png', param: {hl:'ja'}, size: [400, 326]} ,
//			ifilm:				{enabled: true,	icon: pre_icon + 'ifilm.png', param: {}, size: [448, 365]} ,
			Dailymotion:		{enabled: true,	icon: pre_icon + 'dailymotion.png', param: {}, size: [420, 330]} ,
			superdeluxe:		{enabled: true,	icon: pre_icon + 'superdeluxe.png', param: {}, size: [400, 350]} ,
			nicovideo:			{enabled: true,	icon: pre_icon + 'nicovideo.png', param: {}, size: [485, 385]}
		}, settings);
		if (settings.imageMaxWidth || settings.imageMaxHeight) {
			settings = jQuery.extend( true, settings, {
				Image: {size: [(settings.imageMaxWidth || 0), (settings.imageMaxHeight || 0)]}
			});
		}
		var frameBorder = /^border$/i.test(settings.contentFrameType);

		var arrContent = [];

		var	d = document ,
			w = window;
		var	b = d.body ,
			e = d.documentElement ,
			images = d.images;
		var	msie   = jQuery.browser.msie ,
			gecko  = jQuery.browser.mozilla ,
			opera  = jQuery.browser.opera ,
			webkit = jQuery.browser.safari;
//			webkit = jQuery.browser.safari || jQuery.browser.chrome;
		var boxModel = jQuery.support.boxModel;

		fileTypes = jQuery.extend( true, {
			Image:{
				match: function(strUrl){
					return (settings.Image.enabled && /\.(jpe?g|gif|png|bmp)$/i.test(strUrl));
				} ,
				defaultSize: settings.Image.size ,
				set: function(contentNo){image_set(contentNo, true);} ,
				preload: function(contentNo){image_set(contentNo, false);}
			} ,
			Video:{
				match: function(strUrl){
					return (settings.Video.enabled && /\.(flv|swf|rm|mov|3gp|mp4|asf|avi|mpg|wmv)$/i.test(strUrl));
				} ,
				defaultSize: settings.Video.size ,
				getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo, strSrc){
					strSrc = strSrc || strUrl.replace(/\?.*$/, '');
					var arrParam = {};
					var playerAutoPlay = 'true';
					var playerEmbedOption = false;
					var embed_param = {
						rm:  {id:'CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA', type:'audio/x-pn-realaudio-plugin'} ,
						mp4: {id:'02BF25D5-8C17-4B23-BC80-D3488ABDDC6B', type:'video/quicktime', url:'http://www.apple.com/qtactivex/qtplugin.cab'} ,
						wmv: {id:'6BF52A52-394A-11d3-B153-00C04F79FAA6', type:'application/x-oleobject', url:'http://www.microsoft.com/windows/windowsmedia'}
					};

					var video_type = strSrc.toLowerCase().match(/\.(flv|swf|rm|mov|3gp|mp4|asf|avi|mpg|wmv)$/i)[1];
					switch( video_type ){
					case 'flv':
						intHeight += 22;
						arrParam = {flashvars:'movieURL=' + strSrc + '&autoStart=' + playerAutoPlay};
						strSrc = settings.flvplayer + '?file=' + strSrc;
					case 'swf':
						arrParam = jQuery.extend( true, arrParam, {quality:'high',bgcolor:'#000'});
						strSrc = flash_src(strSrc, intWidth, intHeight, arrParam);
						break;
					case 'rm':
						arrParam = {autostart: playerAutoPlay ,controls: 'imagewindow,controlpanel'};
						strSrc = embed_src(strSrc, embed_param.rm.id, embed_param.rm.type, intWidth, intHeight, arrParam, '', '', playerEmbedOption, true);
						break;
					case 'mov':
					case 'mp4':
					case '3gp':
						intHeight += 20;
						arrParam = {
							href: strSrc ,
							controller: 'true' ,
							pluginspage: 'http://www.apple.com/quicktime/download/' ,
							autoplay: playerAutoPlay ,
							bgcolor: '000000'
						};
						strSrc = embed_src(strSrc, embed_param.mp4.id, embed_param.mp4.type, intWidth, intHeight, arrParam, ' codebase="' + embed_param.mp4.url + '"', '', playerEmbedOption, true);
						break;
					case 'wmv':
					default:
						intHeight += 20;
						if ( video_type == 'wmv' ) {
							arrParam = {
								URL: strSrc ,
								AutoStart: playerAutoPlay ,
								uiMode: 'full'
							};
							playerEmbedOption = 
								'src="' + strSrc + '" ' +
								'name="player" ' + 
								'width="' + intWidth + '" ' +
								'height="' + intHeight + '" ' +
								'type="' + embed_param.wmv.type + '" ' +
								'pluginurl="' + embed_param.wmv.url + '" ' +
								'allowchangedisplaysize="1" autosize="1" displaysize="1" showcontrols="1" showstatusbar="0" autorewind="1" ' +
								'autostart="' + (playerAutoPlay == 'false' ? '0' : '1') + '"';
						} else {
							arrParam = {
								href: strSrc ,
								autostart: playerAutoPlay ,
								uiMode: 'full'
							};
						}
						strSrc = embed_src(strSrc, embed_param.wmv.id, embed_param.wmv.type, intWidth, intHeight, arrParam, '', '', playerEmbedOption, false);
						break;
					}
					return {
						content:strSrc ,
						width:intWidth ,
						height:intHeight ,
						later:Math.round(settings.defaultLater * 2 / 3)
					}
				}
			} ,
			YouTube:{
				match: function(strUrl){
					return (settings.YouTube.enabled && /\.youtube\.com\/watch/i.test(strUrl));
				} ,
				base: 'www.youtube.com/v/' ,
				defaultSize:  settings.YouTube.size ,
				getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo, vid, strSrc){
					vid = vid || strUrl.replace(/^.*\?v=(.*)$/i, '$1');
					strSrc = strSrc || arrInfo.base + vid;
					var arrParam = {allowFullScreen:'true'};
					for (var key in arrUrlParam) {
						if (/^fmt$/i.test(key)) {
							strSrc += '&ap=%2526fmt%3D' + arrUrlParam[key];
						} else {
							strSrc += '&' + key + '=' + arrUrlParam[key];
							if (!/^hl$/i.test(key)) arrParam[key] = arrUrlParam[key];
							if (!/^fs$/i.test(key)) arrParam['allowFullScreen'] = ( arrUrlParam[key] == '1' ? 'true' : 'false' );
						}
					}
					strSrc = flash_src(strSrc, intWidth, intHeight, arrParam);
					return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
				}
			} ,
			Metacafe:{
				match: function(strUrl){
					return (settings.Metacafe.enabled && /\.metacafe\.com\/watch/i.test(strUrl));
				} ,
				base: 'www.metacafe.com/fplayer/' ,
				defaultSize: settings.Metacafe.size ,
				getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo, vid, strSrc){
					vid = vid || strUrl.replace(/^.*\/watch\/([\d]+\/[^\/]*)\/?.*$/i, '$1');
					strSrc = strSrc || arrInfo.base + vid + '.swf';
					var arrParam = {};
					strSrc = flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
					return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
				}
			} ,
			LiveLeak:{
				match: function(strUrl){
					return (settings.LiveLeak.enabled && /\.liveleak\.com\/view/i.test(strUrl));
				} ,
				base: 'www.liveleak.com/player.swf?autostart=false&amp;token=' ,
				defaultSize: settings.LiveLeak.size ,
				getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo, vid, strSrc){
					vid = vid || strUrl.replace(/^.*\?i=(.*)$/i, '$1');
					strSrc = strSrc || arrInfo.base + vid;
					var arrParam = {quality:'high'};
					strSrc = flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
					return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
				}
			} ,
			GoogleVideo:{
				match: function(strUrl){
					return (settings.GoogleVideo.enabled && /video\.google\.com\/videoplay/i.test(strUrl));
				} ,
				base: 'video.google.com/googleplayer.swf?docId=' ,
				defaultSize: settings.GoogleVideo.size ,
				getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo, vid, strSrc){
					vid = vid || strUrl.replace(/^.*\?docid=([a-z\d\-]+).*$/i, '$1');
					strSrc = strSrc || arrInfo.base + vid;
					var arrParam = {flashvars:''};
					strSrc = flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
					return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
				}
			} ,
//			ifilm:{
//				match: function(strUrl){
//					return (settings.ifilm.enabled && /\.ifilm\.com\/video/i.test(strUrl));
//				} ,
//				base: 'www.ifilm.com/efp' ,
//				defaultSize: settings.ifilm.size ,
//				getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo, vid, strSrc){
//					vid = vid || strUrl.replace(/\?.*$/, '').replace(/^.*\/video\/([^\/]*)[\/]?$/i, '$1');
//					strSrc = strSrc || arrInfo.base;
//					strSrc = flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
//					var arrParam = {
//						flashvars: 'flvbaseclip=' + vid + '&' ,
//						quality: 'high' ,
//						bgcolor: '000000'
//					};
//					return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
//				}
//			} ,
			Dailymotion:{
				match: function(strUrl){
					return (settings.Dailymotion.enabled && /\.dailymotion\.com\/video/i.test(strUrl));
				} ,
				base: 'www.dailymotion.com/swf/' ,
				defaultSize: settings.Dailymotion.size ,
				getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo, vid, strSrc){
					vid = vid || strUrl.replace(/^.*\/video\/([^_]*).*$/i, '$1');
					strSrc = strSrc || arrInfo.base + vid;
					var arrParam = {
						allowFullScreen: 'true' ,
						allowScriptAccess: 'always'
					};
					strSrc = flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
					return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
				}
			} ,
			superdeluxe:{
				match: function(strUrl){
					return (settings.superdeluxe.enabled && /\.superdeluxe\.com\/sd/i.test(strUrl));
				} ,
				base: 'i.cdn.turner.com/sdx/static/swf/share_vidplayer.swf' ,
				defaultSize: settings.superdeluxe.size ,
				getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo, vid, strSrc){
					vid = vid || strUrl.replace(/^.*\?id=(.*)$/i, '$1');
					strSrc = strSrc || arrInfo.base;
					var arrParam = {
						allowFullScreen: 'true' ,
						FlashVars: 'id=' + vid
					};
					strSrc = flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
					return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
				}
			} ,
			nicovideo:{
				match: function(strUrl){
					return (settings.nicovideo.enabled && /\.nicovideo\.jp\/watch/i.test(strUrl));
				} ,
				base: 'www.nicovideo.jp/watch/' ,
				defaultSize: settings.nicovideo.size ,
				getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo, vid, strSrc){
					vid = vid || strUrl.replace(/^.*\/watch\/(.*)$/i, '$1');
					strSrc = strSrc || arrInfo.base + vid;
					strSrc = '<iframe width="' + intWidth + '" height="' + intHeight + '" src="http://www.nicovideo.jp/thumb/' + vid + '" scrolling="no" style="border:solid 1px #CCC;" frameborder="0"><a href="' + strSrc + '">' + arrInfo.tytle + '</iframe>';
					var output = [];
					document.write = function(v){ output.push(v); }
					jQuery.getScript('http://ext.nicovideo.jp/thumb_watch/' + vid, function(){
						arrInfo.content = output.join('');;
						jQuery('#lightpop-content').html(arrInfo.content);
						output = [];
						document.write = document._write;
					});
					return {content:strSrc, width:intWidth, height:intHeight, later:Math.round(settings.defaultLater / 3)}
				}
			} ,
			Contents:{
				match: function(strUrl){
					return (settings.Contents.enabled && RegExp(w.location.host, 'i').test(strUrl) && !/\.(jpe?g|gif|png|bmp)$/i.test(strUrl));
				} ,
				defaultSize: settings.Contents.size ,
				set: function(contentNo, strSrc){
					var arrSizes = get_sizes_from_str(arrContent[contentNo].href, settings.Contents.size);
					var strSizes = 'width="' + arrSizes[0] + '" height="' + arrSizes[1] + '"';
					if (settings.Contents.iframeEnabled) {
						strSrc = '<iframe ' + strSizes + ' src="' + arrContent[contentNo].href + '" scrolling="' + settings.Contents.scrolling + '" style="' + settings.Contents.style + '" frameborder="0"><a href="' + arrContent[contentNo].href + '">' + arrContent[contentNo].tytle + '</a></iframe>';
						arrContent[contentNo] = jQuery.extend(arrContent[contentNo], {content:strSrc, width:arrSizes[0], height:arrSizes[1], later:500});
						set_content_to_view(contentNo);
					} else {
						jQuery.get(arrContent[contentNo].href, function(responseText, status){
							strSrc = '<div ' + strSizes + '>' + responseText.replace(/[\r\n]/g, '').replace(/.*<body.*?>(.*?)<\/body>.*$/, '$1').replace(/<script.*?>.*<\/script>/g, '') + '</div>';
							arrContent[contentNo] = jQuery.extend(arrContent[contentNo], {content:strSrc, width:arrSizes[0], height:arrSizes[1], later:500});
							set_content_to_view(contentNo);
						});
					}
				} ,
				preload: function(contentNo){} ,
				content_css: {'text-align':'left', 'line-height':'1.2em'}
			}
		}, fileTypes);

		// DOM Elements ID and CSS Path
		var	Elements_ID = {
				overlay: 'overlay' ,
				lightpop: plugin_name ,
				box:      plugin_name + '-box' ,
				inner:    plugin_name + '-box-inner' ,
				content:  plugin_name + '-content' ,
				nav:      plugin_name + '-nav' ,
				next:     plugin_name + '-nav-prev' ,
				prev:     plugin_name + '-nav-next' ,
				data:     plugin_name + '-data' ,
				wrap:     plugin_name + '-data-wrap' ,
				close:    plugin_name + '-data-close' ,
				details:  plugin_name + '-details' ,
				caption:  plugin_name + '-details-caption' ,
				number:   plugin_name + '-details-number' ,
				loading:  plugin_name + '-loading'
			} ,
			csspath = {};
		for (var key in Elements_ID) {
			csspath[key] = '#' + Elements_ID[key];
		}

		// initialize
		var init = function(jQueryObj, domMatchedObj){
			// image preload
			image_load(settings.imageBtnPrev,  function(img){
				settings.imageBtnPrevWidth  = (img.width > 0 ? img.width : settings.imageBtnPrevWidth);
				image_load(settings.imageBtnNext,  function(img){
					settings.imageBtnNextWidth  = (img.width > 0 ? img.width : settings.imageBtnNextWidth);
					image_load(settings.imageBtnClose, function(img){
						settings.imageBtnCloseWidth = (img.width > 0 ? img.width : settings.imageBtnCloseWidth);
						if (!frameBorder) {
							image_load(settings.imageBox, function(img){
								image_load(settings.imageBorderL, function(img){
									image_load(settings.imageBorderR, function(img){
										settings.contentBorder = 'none';
									});
								});
							});
						}
					});
				});
			});

			// get matched object
			domMatchedObj = domMatchedObj || [];
			var intImage = 0;
			jQueryObj.filter(settings.filter).each(function() {
				var location = w.location;
				var jQuery_this = jQuery(this);
				if (!/^https?:/i.test(jQuery_this.attr('href')) && /^https?:/i.test(location.protocol)) {
					jQuery_this.attr('href', (
						/^\//i.test(jQuery_this.attr('href'))
						? location.protocol + '//' + location.hostname + '/' + jQuery_this.attr('href')
						: location.href.replace(/^(.*\/).*$/i, '$1') + jQuery_this.attr('href').replace(/^\/?(.*)$/i, '$1')
					));
				}

				for (var key in fileTypes) {
					var filetype = fileTypes[key];
					if (filetype.match(jQuery_this.attr('href').replace(/\?.*$/, ''))) {
						var filetype_option = settings[key];
						// set icons to link
						if (settings.iconAdd && jQuery_this.children('img').length === 0)
							jQuery_this.css({background:'transparent url(' + filetype_option.icon + ') no-repeat scroll 1px 0pt', paddingLeft:'20px'});

						// content info
						arrContent.push({
							type:	key ,
							href:	jQuery_this.attr('href') ,
							title:	jQuery_this.attr('title') || jQuery_this.html().replace(/<[^>]*>/ig, '') ,
							base:	(filetype.base ? 'http://' + filetype.base : '/')
						});

						// bind click event
						jQuery_this.unbind('click').click(
							(function(i){return function(){start_lightpop(i); return false;}})(intImage)
						);

						// push array
						domMatchedObj.push(this);
						intImage++;
						break;
					}
				}
			});
			return new jQuery(domMatchedObj);
		};

		// start!
		var start_lightpop = function(intClickedContent, content_htm, data_htm, navi_htm, lightpop_htm){
			settings.grouping = (arrContent.length > 1 ? settings.grouping : false);

			// hide embed, object, select element
			set_visibility(jQuery('embed, object, select'), false);

			// set interface
			content_htm  = content_htm || '<div id="' + Elements_ID.content + '-wrap"><div id="' + Elements_ID.content + '" /></div>';
			data_htm     = data_htm || (settings.detailsEnabled ? '<div id="' + Elements_ID.wrap + '"><div id="' + Elements_ID.data + '"><div id="' + Elements_ID.details + '"><div id="' + Elements_ID.caption + '" /><div id="' + Elements_ID.number + '" /></div><div id="' + Elements_ID.data + '-nav"><a href="#" id="' + Elements_ID.close + '"><img src="' + settings.imageBtnClose + '" /></a></div></div></div>' : '');
			navi_htm     = navi_htm || '<div id="' + Elements_ID.nav + '"><a href="#" id="' + Elements_ID.prev + '" /><a href="#" id="' + Elements_ID.next + '" /></div><div id="' + Elements_ID.loading + '"><a href="#" id="' + Elements_ID.loading + '-link"><img src="' + settings.imageLoading + '" /></a></div>';
			lightpop_htm = lightpop_htm ||
				'<div id="' + Elements_ID.overlay +'" />' +
				'<div id="' + Elements_ID.lightpop + '"><div id="' + Elements_ID.box + '">' +
				'<div id="' + Elements_ID.inner + '">' +
				( frameBorder
				? content_htm + '</div>' + data_htm
				: '<div id="' + Elements_ID.box + '-hd"><div id="' + Elements_ID.box + '-hdc" /></div>' +
				  '<div id="' + Elements_ID.box + '-bd"><div id="' + Elements_ID.box + '-bdc">' + content_htm + data_htm + '</div></div>' +
				  '<div id="' + Elements_ID.box + '-ft"><div id="' + Elements_ID.box + '-ftc" /></div>' +
				  '</div>'
				) +
				navi_htm +
				'</div></div>';
			jQuery('body').children(':first').before(jQuery(lightpop_htm).hide());

			// set interface CSS
			var pageSize   = page_size();
			var pageScroll = page_scroll();
			var containerBorderSize = settings.containerBorderSize;

			// set position
			jQuery(csspath.overlay + ',' + csspath.lightpop + ',' + csspath.nav + ',' + csspath.loading).css({position:'absolute'});
			jQuery(csspath.box + ',' + csspath.data).css({position:'relative'});

			// overlay
			var lightpopPosition = get_popup_position(pageSize, pageScroll);
			jQuery(csspath.overlay)
				.css({top:0, left:0, backgroundColor:settings.overlayBgColor, opacity:settings.overlayOpacity, width:pageSize.page.width, height:pageSize.page.height, zIndex:90});
			jQuery(csspath.lightpop)
				.css({width:'100%', height:0, top:lightpopPosition.top, left:lightpopPosition.left, overflow:'visible', 'text-align':'center', 'line-height':0, border:'none', zIndex:100});
			jQuery(csspath.lightpop + ' a img').css({border:'none'});

			// container
			jQuery(csspath.box).css({width:settings.initSize, height:settings.initSize, top:0, margin:'0 auto', padding:0, backgroundColor:settings.contentBgColor, border:settings.contentBorder, overflow:(frameBorder ? 'hidden' : 'visible')});
			jQuery(csspath.inner).css({width:settings.initSize, height:settings.initSize, backgroundColor:'transparent', margin:'0 auto', padding:(frameBorder ? containerBorderSize : 0), overflow:(frameBorder ? 'hidden' : 'visible')});
			jQuery(csspath.content + '-wrap').css({backgroundColor:'transparent'});
			jQuery(csspath.content).css({margin:(frameBorder ? 0 : '0 auto'), padding:(frameBorder ? 0 : containerBorderSize + 'px 0'), zIndex:110});
			if (!frameBorder) {
				var boxBorderSize = settings.boxBorderSize;
				set_box_css(false).css({position:'relative'}).hide();
				jQuery(csspath.box + '-hd'  + ',' + csspath.box + '-hdc').css({height:boxBorderSize, top:0});
				jQuery(csspath.box + '-ft'  + ',' + csspath.box + '-ftc').css({height:boxBorderSize, bottom:0});
				jQuery(csspath.box + '-hd'  + ',' + csspath.box + '-ft' ).css({margin:'0 ' + boxBorderSize + 'px 0 0'});
				jQuery(csspath.box + '-hdc' + ',' + csspath.box + '-ftc').css({margin:'0 ' + boxBorderSize*-1 + 'px 0 ' + boxBorderSize + 'px'});
			}

			// navigation
			jQuery(csspath.nav).css({top:0, left:0, height:'10px', width:'100%', padding:0, margin:(frameBorder ? '0' : settings.boxBorderSize + 'px') + ' auto', zIndex:10});
			jQuery(csspath.nav + ' a').css({display:'block', height:'100%', zoom:1, margin:(frameBorder ? 0 : '0 ' + settings.boxBorderSize + 'px'), outline:'none'});
			if (settings.grouping) {
				jQuery(csspath.prev).css({width:settings.imageBtnPrevWidth, left:0,  'float':'left'});
				jQuery(csspath.next).css({width:settings.imageBtnNextWidth, right:0, 'float':'right'});
			} else {
				jQuery(csspath.prev + ',' + csspath.next).css({display:'none'});
			}

			// loading image
			jQuery(csspath.loading).css({top:'40%', left:0, height:'20%', width:'100%', margin:'0 auto', 'text-align':'center', 'line-height':0});

			// details
			if (settings.detailsEnabled) {
				jQuery(csspath.wrap).css({font:'10px Verdana, Helvetica, sans-serif', backgroundColor:settings.contentBgColor, 'line-height':'1.4em', width:'100%', margin:'0 auto', padding:'0 ' + containerBorderSize + 'px 0', overflow:'hidden'}).hide();
				jQuery(csspath.data).css({padding:'0 ' + containerBorderSize + 'px', color:'#666', left:0, bottom:0});
				jQuery(csspath.details).css({width:'70%', 'float':'left', 'text-align':'left'});
				jQuery(csspath.caption).css({'float':'left', 'font-weight':'bold', width:'100%'});
				jQuery(csspath.number).css({'float':'left', clear:'left', width:'100%', 'padding-bottom':'1em'});
				jQuery(csspath.close).css({'float':'right', width:settings.imageBtnCloseWidth, padding:'.35em 0'});
			}

			// bind event
			jQuery(csspath.overlay).fadeIn(settings.containerResizeSpeed, function(){
				jQuery(csspath.lightpop).show();
			});
			jQuery(csspath.overlay + ',' + csspath.loading + '-link,' + csspath.close).click(function(){
				finish_lightpop();
				return false;
			});
			window_resize(true);

			// set content
			set_content(intClickedContent);
		};

		// set content
		var set_content = function(contentNo){
			set_visibility(jQuery(csspath.data + ',' + csspath.details + ',' + csspath.caption + ',' + csspath.number + ',' + csspath.close), false);
			jQuery(csspath.nav + ',' + csspath.prev + ',' + csspath.next).hide();
			jQuery(csspath.content).hide().children().remove();
			jQuery(csspath.loading + ',' + csspath.box + ',' + csspath.inner).show();
			set_box_css(false);
			if(settings.detailsEnabled && !frameBorder && webkit){
				jQuery(csspath.wrap).height('auto');
			}
			set_content_to_view(contentNo);
		};

		// set content to view
		var set_content_to_view = function(contentNo){
			(arrContent[contentNo].content
			? function(n){jQuery(csspath.content).append(jQuery(arrContent[n].content)); setTimeout(function(){show_container(n);}, arrContent[n].later);}
			: (fileTypes[arrContent[contentNo].type].set ? fileTypes[arrContent[contentNo].type].set : (function(contentNo){set_video_info(contentNo); set_content_to_view(contentNo);}))
			)(contentNo);
		};

		// show data box
		var show_data_box = function(data_box, intWidth, intHeight, intDataboxHeight){
			if(settings.detailsEnabled) {
				intDataboxHeight = (intDataboxHeight < 28 ? 28 : intDataboxHeight);
				intHeight += intDataboxHeight;
			}
			(frameBorder
			? (function(d){
				jQuery(csspath.box).animate({width:intWidth, height:intHeight}, 'fast', function(){set_visibility(d, true);});
				})
			: (function(d){
				if(settings.detailsEnabled && webkit){
					jQuery(csspath.wrap).height(intDataboxHeight);
				}
				set_visibility(d, true);
				})
			)(data_box);
		};

		// show container
		var show_container = function(contentNo){
			var	containerBorderSize = settings.containerBorderSize ,
				boxBorderSize = settings.boxBorderSize ,
				contentInfo = arrContent[contentNo] ,
				pageSize   = page_size() ,
				pageScroll = page_scroll();
			var	intWidth  = contentInfo.width  + (containerBorderSize + (frameBorder ? 0 : boxBorderSize)) * 2 ,
				intHeight = contentInfo.height + containerBorderSize * 2;

			jQuery(csspath.lightpop).animate(get_popup_position(pageSize, pageScroll), settings.containerResizeSpeed, function(){
				jQuery(csspath.box).animate({width:intWidth, height:intHeight}, settings.containerResizeSpeed, function(){
					var fyletype = fileTypes[contentInfo.type];

					// resize content and data
					jQuery(csspath.inner).css({width:(frameBorder ? contentInfo.width : intWidth), height:contentInfo.height});
					jQuery(csspath.content).css({width:contentInfo.width, height:contentInfo.height});
					if (settings.detailsEnabled) {
						jQuery(csspath.wrap).css({width:contentInfo.width});
					}

					// set content css
					if (fyletype.content_css) {
						jQuery(csspath.content).children().css(fyletype.content_css);
					}

					// show content
					jQuery(csspath.loading).hide();
					if (/<object.*>/i.test(contentInfo.content)) {
						jQuery(csspath.content).show();
					} else {
						jQuery(csspath.content).fadeIn();
					}
					jQuery(csspath.overlay).css(pageSize.page);
					set_box_css(true);

					// set content data
					jQuery(csspath.caption).html(
						(settings.setLinkToTitle
						? '<a href="' + contentInfo.href + '" title="' + contentInfo.title + '">' + contentInfo.title + '</a>'
						: contentInfo.title
						)
					);
					jQuery(csspath.number).html(
						(settings.txtImage || contentInfo.type) +
						(settings.grouping && arrContent.length > 1
						? ' ' + (contentNo + 1) + ' ' + settings.txtOf + ' ' + arrContent.length
						: ''
						)
					);
					var data_box = set_visibility(jQuery(csspath.data + ',' + csspath.details + ',' + csspath.caption + ',' + csspath.number + ',' + csspath.close), false).show();
					(settings.detailsEnabled
					? function(d,w,h){jQuery(csspath.wrap).slideDown('fast', function(){show_data_box(d, w, h, (!webkit ? jQuery(this).height() : this.scrollHeight));});}
					: function(d,w,h){show_data_box(d, w, h);}
					)(data_box, intWidth, intHeight);

					// set navigation
					jQuery(csspath.nav).css({width:intWidth}).show(function(){
						if(settings.grouping){
							jQuery(csspath.prev + ',' + csspath.next).css({height:Math.round(intHeight / 3), background:'transparent url(' + settings.imageBlank + ') no-repeat'}).unbind();
							if(contentNo != 0){
								jQuery(csspath.prev).hover(
									function(){jQuery(this).css({background:'url(' + settings.imageBtnPrev + ') left 30% no-repeat'});} ,
									function(){jQuery(this).css({background:'transparent url(' + settings.imageBlank + ') no-repeat'});}
								).show().click((function(i){return function(){set_content(i); return false;}})(contentNo - 1));
							}
							if(contentNo != (arrContent.length - 1)){
								jQuery(csspath.next).hover(
									function(){jQuery(this).css({background:'url(' + settings.imageBtnNext + ') right 30% no-repeat'});} ,
									function(){jQuery(this).css({background:'transparent url(' + settings.imageBlank + ') no-repeat'});}
								).show().click((function(i){return function(){set_content(i); return false;}})(contentNo + 1));
							}
						}
						keyboard_navigation(true, contentNo);
					});

					// preload contents
					if(arrContent.length - 1 > contentNo){preload(contentNo + 1);}
					if(contentNo > 0){preload(contentNo - 1);}
				});
			});
		};

		// preload
		var preload = function(contentNo){
			if(!arrContent[contentNo].content) {
				(fileTypes[arrContent[contentNo].type].preload
				? fileTypes[arrContent[contentNo].type].preload
				: (function(n){set_video_info(n);})
				)(contentNo);
			}
		};

		// get content information
		var get_sizes_from_str = function(strText, defaultSizes, maxSizes){
			var numWidth = 0, numHeight = 0;
			var arrText = strText.toLowerCase().replace(/[\r\n]/g,'').replace(/["']/g,'').match(/(width|height)=(\d+)/ig);
			if (arrText) {
				jQuery.each(arrText, function(){
					if (/^width=\d+$/i.test(this))  {numWidth  = Number(this.replace(/^width=(\d+)$/, '$1'));}
					if (/^height=\d+$/i.test(this)) {numHeight = Number(this.replace(/^height=(\d+)$/,'$1'));}
				});
			}
			if (numWidth === 0 || numHeight === 0) {
				if (defaultSizes) {
					numWidth  = defaultSizes[0];
					numHeight = defaultSizes[1];
				} else {
					var pageSize = page_size();
					numWidth  = pageSize.page.width / 4;
					numHeight = pageSize.page.height / 4;
				}
			}
			if (maxSizes) {
				if (maxSizes[0] != 0 && numWidth > maxSizes[0]) {
					numHeight = numHeight * (maxSizes[0] / numWidth);
					numWidth  = maxSizes[0];
				}
				if (maxSizes[1] != 0 && numHeight > maxSizes[1]) {
					numWidth  = numWidth * (maxSizes[1] / numHeight);
					numHeight = maxSizes[1];
				}
			}
			return [Math.round(numWidth), Math.round(numHeight)];
		};

		var set_video_info = function(contentNo, vid, strSrc) {
			var 	arrSizes ,
				arrUrlParam = {} ,
				contentInfo = arrContent[contentNo];
			var	filetype_option = settings[contentInfo.type] ,
				fyletype = fileTypes[contentInfo.type] ,
				strUrl = contentInfo.href.replace(/&.*$/i,'');
			if (/\?.*$/.test(contentInfo.href)) {
				var arrParams = contentInfo.href.replace(/^.*\?/,'').split("&");
				if (arrParams.length > 0) {
					jQuery.each(arrParams, function(){
						var wk_matched, wk_str = this.toString();
						if (!/^(width|height|v|i|docid|id)\=.*$/i.test(wk_str)) {
							wk_matched = (wk_str).match(/^([^=]*)=(.*)$/i);
							if (wk_matched.length > 2) arrUrlParam[wk_matched[1]] = wk_matched[2];
						}
					});
				}
			}
			arrUrlParam = jQuery.extend(arrUrlParam, filetype_option.param);
			arrSizes = get_sizes_from_str(contentInfo.href, fyletype.defaultSize);

			arrContent[contentNo] = jQuery.extend(contentInfo, fyletype.getInfo(strUrl, arrSizes[0], arrSizes[1], arrUrlParam, contentInfo));
		}

		var flash_src = function(url, width, height, param, url_param, strSrc) {
			if (typeof url_param === 'object') {
				jQuery.each(url_param, function(key){
					url += (url.indexOf(key + '=') < 0
						? (url.indexOf('?') < 0 ? '?' : '&') + key + '=' + this
						: ''
						);
				});
			}
			var wkParam = {movie: url, wmode: 'transparent'};
			param = (param ? jQuery.extend(wkParam, param) : wkParam);
			strSrc = strSrc || '<object width="' + width + '" height="' + height + '"' + ' data="' + url + '" wmode="' + param.wmode + '" type="application/x-shockwave-flash">';
			jQuery.each(param, function(key){
				strSrc += '<param name="' + key + '" value="' + this + '" />';
			});
			strSrc += '</object>';
			return strSrc;
		};

		var embed_src = function(url, clsid, apl_type, width, height, param, url_param, obj_option, embed_option, flg_noembed) {
			var	strSrc = '' ,
				strEmb = '' ,
				wkParam = {src: url, width: width, height: height, type: apl_type};
			param = (param ? jQuery.extend(wkParam, param) : wkParam);
			strSrc += '<object width="' + width + '" height="' + height + '"' + (clsid != ' ' ? 'classid="clsid:' + clsid + '"' : '') + ' type="' + apl_type + '" ' + obj_option + '>';
			jQuery.each(param, function(key){
				strSrc += '<param name="' + key + '" value="' + this + '" />';
			});
			strEmb += '<embed ' +
				(embed_option !== false 
				? embed_option 
				: 'width="' + width + '" height="' + height + '"'
				);
			if (embed_option === false) {
				jQuery.each(param, function(key){strEmb += ' ' + key + '="' + this + '"';});
			}
			strEmb += '>' +
				(flg_noembed 
				? '<noembed><a href="' + url + '">' + url + '</a></noembed>'
				: ''
				) +
				'</embed>';
			strSrc += strEmb + '</object>';
			return strSrc;
		};

		// set box css
		var set_box_css = function(enable) {
			if (!frameBorder) {
				var	jQueryObj = jQuery(csspath.box + '-hd, ' + csspath.box + '-hdc, ' + csspath.box + '-bd, ' + csspath.box + '-bdc, ' + csspath.box + '-ft, ' + csspath.box + '-ftc') ,
					bg_transparent = 'transparent',
					bg_box = bg_transparent + ' url(' + settings.imageBox + ')' ,
					bg_color = settings.contentBgColor;
				if (enable) {
					jQuery(csspath.box).css({backgroundColor:bg_transparent});
					jQuery(csspath.box + '-hd' ).css({background:bg_box + ' left top no-repeat'});
					jQuery(csspath.box + '-hdc').css({background:bg_box + ' right top no-repeat'});
					jQuery(csspath.box + '-bd' ).css({background:bg_color + ' url(' + settings.imageBorderL + ') left top repeat-y'});
					jQuery(csspath.box + '-bdc').css({background:bg_transparent + ' url(' + settings.imageBorderR + ') right top repeat-y'});
					jQuery(csspath.box + '-ft' ).css({background:bg_box + ' left bottom no-repeat'});
					jQuery(csspath.box + '-ftc').css({background:bg_box + ' right bottom no-repeat'});
					jQueryObj.show();
				} else {
					jQuery(csspath.box).css({backgroundColor:bg_color});
					jQueryObj.css({background:bg_transparent});
				}
				return jQueryObj;
			}
		};

		// get Image true size
		var image_size = function(image){
			var	w = image.width ,
				h = image.height ;

			if ( typeof image.naturalWidth !== 'undefined' ) {	// for Firefox, Safari, Chrome
				w = image.naturalWidth;
				h = image.naturalHeight;

			} else if ( typeof image.runtimeStyle !== 'undefined' ) {	 // for IE
				var run = image.runtimeStyle;
				var mem = { w: run.width, h: run.height };	// keep runtimeStyle
				run.width  = 'auto';
				run.height = 'auto';
				w = image.width;
				h = image.height;
				run.width  = mem.w;
				run.height = mem.h;

			} else {		 // for Opera
				var mem = { w: image.width, h: image.height };	// keep original style
				image.removeAttribute('width');
				image.removeAttribute('height');
				w = image.width;
				h = image.height;
				image.width  = mem.w;
				image.height = mem.h;
			}

			return {width:w, height:h, src:image.src};
		}

		var image_set = function(contentNo, setFlag){
			image_load(arrContent[contentNo].href, (function(n, f){return function(img){
				var arrSizes = get_sizes_from_str(img.src, [img.width, img.height], settings.Image.size);
				arrContent[n] = jQuery.extend(arrContent[n], {
					content: '<img src="' + img.src.replace(/\?.*$/, '') + '" width="' + arrSizes[0] + '" height="' + arrSizes[1] + '" />' ,
					width:   arrSizes[0] ,
					height:  arrSizes[1] ,
					later:   (msie ? 250 : 100)
				});
				if (f) set_content_to_view(n);
			}})(contentNo, setFlag));
		}

		// image loader
		var image_load = function(src, onLoad, onError, delay, timeout) {
			onLoad  = onLoad  || function(){};
			onError = onError || function(){};
			delay   = delay   || 10;
			timeout = timeout || 2000;
			for (var i = 0, sz = images.length; i < sz; ++i) {
				if (images[i].src === src && images[i].complete) {
					onLoad(image_size(images[i]));
					return;
				}
			}
			var img = new Image(), tick = 0;

			img.finish = false;
			img.onabort = img.onerror = function() {
				if (img.finish) { return; }
				img.finish = true;
				onError(src);
			};
			img.onload  = function() {
				img.finish = true;
				if (opera && !img.complete) {
					onError(src);
					return;
				}
				onLoad(image_size(img));
			};
			img.src = src;
			if (!img.finish && timeout) {
				setTimeout(function() {
					if (img.finish) { return; }
					if (img.complete) {
						img.finish = true;
						if (img.width) { return; }
						onError(src);
						return;
					}
					if ((tick += delay) > timeout) {
						img.finish = true;
						onError(src);
						return;
					}
					setTimeout(arguments.callee, delay);
				}, 0);
			}
		}

		// set visibility
		var set_visibility = function(jQueryObj, enable){
			return jQueryObj.css({visibility:(enable ? 'visible' : 'hidden')});
		}

		// set keydown event
		var keyboard_navigation = function(enable, contentNo){
			jQuery(d).unbind('keydown');
			if (enable) {
				jQuery(d).keydown(function(objEvent){
					var keycode = (objEvent || event).keyCode;
					var key = String.fromCharCode(keycode).toLowerCase();
					if (key == settings.keyToClose) {
						finish_lightpop();
					} else if (settings.grouping) {
						if (key == settings.keyToPrev && contentNo != 0) {
							set_content(contentNo - 1);
						} else if (key == settings.keyToNext && contentNo != (arrContent.length - 1)) {
							set_content(contentNo + 1);
						}
					}
				});
			}
		};

		var get_popup_position = function(pageSize, pageScroll){
			pageSize   = pageSize   || page_size();
			pageScroll = pageScroll || page_scroll();

			var default_position = {
				top:  pageScroll.top + Math.round(pageSize.window.height / 10) ,
				left: pageScroll.left
				}
			var popup_position   = ( typeof jQuery.fn.lightpop.position != 'undefined'
				? jQuery.fn.lightpop.position
				: default_position
			);

			return ({
				top:  typeof popup_position.top  != 'undefined' ? popup_position.top  : default_position.top ,
				left: typeof popup_position.left != 'undefined' ? popup_position.left : default_position.left
				});
		}

		// set window resize event
		var window_resize = function(enable){
			jQuery(w).unbind('resize');
			if (enable) {
				jQuery(w).resize(function(){
					var pageSize = page_size();
					jQuery(csspath.overlay).css(pageSize.page);
					jQuery(csspath.lightpop).css(get_popup_position(pageSize, page_scroll()));
				});
			}
		};

		// get page sizes
		var page_size = function(){
			var	D = ( boxModel ? e : b );
			var	pageWidth  = D.scrollWidth,
				pageHeight = D.scrollHeight,
				windowWidth  = D.clientWidth,
				windowHeight = D.clientHeight;
			return {
				page:   {width: (pageWidth  < windowWidth  ? windowWidth  : pageWidth),   height: (pageHeight < windowHeight ? windowHeight : pageHeight)} ,
				window: {width: windowWidth, height: windowHeight}
			};
		};

		// get page scroll
		var page_scroll = function(){
			var P = (e && e.scrollTop ? e : b);
			return { left: P.scrollLeft, top: P.scrollTop };
		};

		// finish!
		var finish_lightpop = function(){
			set_visibility(jQuery('object,embed',jQuery(csspath.lightpop)), false).remove();
			jQuery(csspath.lightpop).height(jQuery(csspath.box).height()).slideUp(function(){
				jQuery(this).remove();
				jQuery(csspath.overlay).fadeOut(function(){
					jQuery(this).remove();
					set_visibility(jQuery('embed, object, select'), true);	// show embed, object, select element
				});
			});
			keyboard_navigation(false);
			window_resize(false);
			document.write = document._write;
		};

		(function(){
			if(!msie || (jQuery.browser.version > 6.0 && boxModel))
				return;
			var container = jQuery( boxModel ? 'html' : 'body' );
			var img = container.css('background-image');
			if(img == 'none'){
				container.css({'background-image':'url(null)'});
			} else if( boxModel){
				container.css({'background-image':'url(null)'});
				jQuery('body').css({
					'background-image':img,
					'background-attachment':container.css('background-attachment')
				})
			}
			container.css({'background-attachment':'fixed'});
		})();

		return init(this);
	};

})(jQuery);
