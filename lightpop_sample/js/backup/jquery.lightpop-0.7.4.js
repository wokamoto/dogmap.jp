/**
 * jQuery lightpop plugin
 * This jQuery plugin was inspired on jQuery lightBox plugin by Leandro Vieira Pinho (http://leandrovieira.com/projects/jquery/lightbox/)
 * @name jquery.lightpop-0.7.4.js
 * @author wokamoto - http://dogmap.jp
 * @version 0.7.4
 * @date January 8, 2009
 * @category jQuery plugin
 * @copyright (c) 2007-2009 wokamoto (dogmap.jp)
 * @license  Released under the GPL license (http://www.gnu.org/copyleft/gpl.html)
 * @example http://dogmap.jp/lightpop_sample/sample.html
 */
(function(jQuery){
jQuery.browser = jQuery.extend(
  {chrome: /chrome/.test(navigator.userAgent.toLowerCase())}
 ,jQuery.browser
);

jQuery.fn.lightpop = function(settings){
 var plugin_name = 'lightpop';
 var image_path  = 'images/';
 var pre_image   = image_path + plugin_name + '-'
   , pre_icon    = image_path + 'icon-';

 settings = jQuery.extend( true, {
   overlayBgColor:	'#000'
  ,overlayOpacity:	0.7
  ,contentFrameType:	'border'
  ,contentBorder:	'none'
  ,boxBorderSize:	6
  ,containerBorderSize:	10
  ,containerResizeSpeed:'normal'
  ,contentBgColor:	'#FFF'
  ,imageBox:		pre_image + 'box.gif'
  ,imageBorderL:	pre_image + 'border-l.gif'
  ,imageBorderR:	pre_image + 'border-r.gif'
  ,imageLoading:	pre_image + 'ico-loading.gif'
  ,imageBtnPrev:	pre_image + 'btn-prev.gif'
  ,imageBtnNext:	pre_image + 'btn-next.gif'
  ,imageBtnClose:	pre_image + 'btn-close.gif'
  ,imageBlank:		pre_image + 'blank.gif'
  ,imageBtnPrevWidth:	63
  ,imageBtnNextWidth:	63
  ,imageBtnCloseWidth:	66
  ,txtImage:		null
  ,txtOf:		'of'
  ,setLinkToTitle:	false
  ,keyToClose:		'c'
  ,keyToPrev:		'p'
  ,keyToNext:		'n'
  ,flvplayer:		'mediaplayer.swf'
  ,iconAdd:		true
  ,detailsEnabled:	true
  ,initSize:		250
  ,defaultLater:	1500
  ,Image:		{enabled: true,	icon: pre_icon + 'image.png',		param: {},		size: new Array(0, 0)}
  ,Contents:		{enabled: true,	icon: pre_icon + 'contents.png',	param: {},		size: new Array(0, 0), iframeEnabled: true}
  ,Video:		{enabled: true,	icon: pre_icon + 'video.png',		param: {},		size: new Array(320, 240)}
  ,YouTube:		{enabled: true,	icon: pre_icon + 'youtube.png',		param: {hl:'ja'},	size: new Array(425, 355)}
  ,Metacafe:		{enabled: true,	icon: pre_icon + 'metacafe.png',	param: {},		size: new Array(400, 345)}
  ,LiveLeak:		{enabled: true,	icon: pre_icon + 'liveleak.png',	param: {},		size: new Array(450, 370)}
  ,GoogleVideo:		{enabled: true,	icon: pre_icon + 'googlevideo.png',	param: {hl:'ja'},	size: new Array(400, 326)}
  ,ifilm:		{enabled: true,	icon: pre_icon + 'ifilm.png',		param: {},		size: new Array(448, 365)}
  ,Dailymotion:		{enabled: true,	icon: pre_icon + 'dailymotion.png',	param: {},		size: new Array(420, 330)}
  ,superdeluxe:		{enabled: true,	icon: pre_icon + 'superdeluxe.png',	param: {},		size: new Array(400, 350)}
  ,nicovideo:		{enabled: true,	icon: pre_icon + 'nicovideo.png',	param: {},		size: new Array(312, 176)}
 }, settings);
 if (settings.imageMaxWidth || settings.imageMaxHeight) {
  settings = jQuery.extend( true, settings, {
   Image: {size: new Array((settings.imageMaxWidth || 0), (settings.imageMaxHeight || 0))}
  });
 }
 var frameBorder = (/^border$/i).test(settings.contentFrameType);

 var arrContent = new Array();
 arrContent.length = 0;

 var d = document
   , w = window;
 var b = d.body
   , e = d.documentElement
   , images = d.images;
 var msie   = jQuery.browser.msie
   , gecko  = jQuery.browser.mozilla
   , opera  = jQuery.browser.opera
   , webkit = jQuery.browser.safari || jQuery.browser.chrome
   , back_compat = !(d.compatMode && d.compatMode !== "BackCompat");

 var fileTypes = {
  Image:{
   match: function(strUrl){return (settings.Image.enabled && (/\.(jpe?g|gif|png|bmp)$/i).test(strUrl));}
  ,base: '/'
  ,defaultSize: settings.Image.size
  ,set: function(contentNo){image_set(contentNo, true);}
  ,preload: function(contentNo){image_set(contentNo, false);}
  }
 ,Video:{
   match: function(strUrl){return (settings.Video.enabled && (/\.(flv|swf|rm|mov|3gp|mp4|asf|avi|mpg|wmv)$/i).test(strUrl));}
  ,defaultSize: settings.Video.size
  ,getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo){
    var arrParam = {};
    var strSrc = strUrl.replace(/\?.*$/, '');
    var playerAutoPlay = 'true';
    var playerEmbedOption = false;
    switch(strSrc.toLowerCase().match(/\.(flv|swf|rm|mov|3gp|mp4|asf|avi|mpg|wmv)$/i)[1]){
     case 'flv':
      intHeight += 22;
      strSrc = settings.flvplayer + '?file=' + strSrc;
     case 'swf':
      arrParam = {quality:'high',bgcolor:'#000'};
      strSrc = get_flash_src(strSrc, intWidth, intHeight, arrParam);
      break;
     case 'rm':
      arrParam = {autostart:	playerAutoPlay
                 ,controls:	'imagewindow,controlpanel'};
      strSrc = get_embed_src(strSrc, 'CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA', 'audio/x-pn-realaudio-plugin', intWidth, intHeight, arrParam, '', '', playerEmbedOption, true);
      break;
     case 'mov':
     case 'mp4':
     case '3gp':
      intHeight += 20;
      arrParam = {href:		strSrc
                 ,controller:	'true'
                 ,pluginspage:	'http://www.apple.com/quicktime/download/'
                 ,autoplay:	playerAutoPlay
                 ,bgcolor:	'000000'};
      strSrc = get_embed_src(strSrc, '02BF25D5-8C17-4B23-BC80-D3488ABDDC6B', 'video/quicktime', intWidth, intHeight, arrParam, ' codebase="http://www.apple.com/qtactivex/qtplugin.cab"', '', playerEmbedOption, true);
      break;
     case 'wmv':
      intHeight += 20;
      arrParam = {AutoStart:	playerAutoPlay
                 ,URL:		strSrc
                 ,uiMode:	'full'};
      playerEmbedOption = 'src="' + strSrc + '" name="player" width="' + intWidth + '" height="' + intHeight + '" type="application/x-mplayer2" pluginurl="http://www.microsoft.com/windows/windowsmedia" allowchangedisplaysize="1" autosize="1" displaysize="1" showcontrols="1" showstatusbar="0" autorewind="1" autostart="' + (playerAutoPlay == 'false' ? '0' : '1') + '"';
      strSrc = get_embed_src(strSrc, '6BF52A52-394A-11d3-B153-00C04F79FAA6', 'application/x-oleobject', intWidth, intHeight, arrParam, '', '', playerEmbedOption, false);
      break;
     default:
      intHeight += 20;
      arrParam = {href:		strSrc
                 ,autostart:	playerAutoPlay
                 ,uiMode:	'full'};
      strSrc = get_embed_src(strSrc, '6BF52A52-394A-11d3-B153-00C04F79FAA6', 'application/x-oleobject', intWidth, intHeight, arrParam, '', '', playerEmbedOption, false);
      break;
    }
    return {content:strSrc, width:intWidth, height:intHeight, later:Math.round(settings.defaultLater * 2 / 3)}
   }
  }
 ,YouTube:{
   match: function(strUrl){return (settings.YouTube.enabled && (/\.youtube\.com\/watch/i).test(strUrl));}
  ,base: 'http://www.youtube.com/v/'
  ,defaultSize:  settings.YouTube.size
  ,getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo){
    var arrParam = {};
    var vid = strUrl.replace(/^.*\?v=(.*)$/i, '$1');
    var strSrc = arrInfo.base + vid;
    for (var key in arrUrlParam) {strSrc += '&' + (key!=='fmt' ? key + '=' + arrUrlParam[key] : '&ap=%2526fmt%3D' + arrUrlParam[key]);}
    strSrc = get_flash_src(strSrc, intWidth, intHeight, arrParam);
    return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
   }
  }
 ,Metacafe:{
   match: function(strUrl){return (settings.Metacafe.enabled && (/\.metacafe\.com\/watch/i).test(strUrl));}
  ,base: 'http://www.metacafe.com/fplayer/'
  ,defaultSize: settings.Metacafe.size
  ,getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo){
    var arrParam = {};
    var vid = strUrl.replace(/^.*\/watch\/([\d]+\/[^\/]*)\/?.*$/i, '$1');
    var strSrc = arrInfo.base + vid + '.swf';
    strSrc = get_flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
    return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
   }
  }
 ,LiveLeak:{
   match: function(strUrl){return (settings.LiveLeak.enabled && (/\.liveleak\.com\/view/i).test(strUrl));}
  ,base: 'http://www.liveleak.com/player.swf?autostart=false&amp;token='
  ,defaultSize: settings.LiveLeak.size
  ,getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo){
    var arrParam = {quality:'high'};
    var vid = strUrl.replace(/^.*\?i=(.*)$/i, '$1');
    var strSrc = arrInfo.base + vid;
    strSrc = get_flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
    return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
   }
  }
 ,GoogleVideo:{
   match: function(strUrl){return (settings.GoogleVideo.enabled && (/video\.google\.com\/videoplay/i).test(strUrl));}
  ,base: 'http://video.google.com/googleplayer.swf?docId='
  ,defaultSize: settings.GoogleVideo.size
  ,getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo){
    var arrParam = {flashvars:''};
    var vid = strUrl.replace(/^.*\?docid=([a-z\d\-]+).*$/i, '$1');
    var strSrc = arrInfo.base + vid;
    strSrc = get_flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
    return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
   }
  }
 ,ifilm:{
   match: function(strUrl){return (settings.ifilm.enabled && (/\.ifilm\.com\/video/i).test(strUrl));}
  ,base: 'http://www.ifilm.com/efp'
  ,defaultSize: settings.ifilm.size
  ,getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo){
    var arrParam = {flashvars:	'flvbaseclip=' + vid + '&'
                   ,quality:	'high'
                   ,bgcolor:	'000000'};
    var vid = strUrl.replace(/\?.*$/, '').replace(/^.*\/video\/([^\/]*)[\/]?$/i, '$1');
    var strSrc = arrInfo.base;
    strSrc = get_flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
    return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
   }
  }
 ,Dailymotion:{
   match: function(strUrl){return (settings.Dailymotion.enabled && (/\.dailymotion\.com\/video/i).test(strUrl));}
  ,base: 'http://www.dailymotion.com/swf/'
  ,defaultSize: settings.Dailymotion.size
  ,getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo){
    var arrParam = {allowFullScreen:	'true'
                   ,allowScriptAccess:	'always'};
    var vid = strUrl.replace(/^.*\/video\/([^_]*).*$/i, '$1');
    var strSrc = arrInfo.base + vid;
    strSrc = get_flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
    return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
   }
  }
 ,superdeluxe:{
   match: function(strUrl){return (settings.superdeluxe.enabled && (/\.superdeluxe\.com\/sd/i).test(strUrl));}
  ,base: 'http://i.cdn.turner.com/sdx/static/swf/share_vidplayer.swf'
  ,defaultSize: settings.superdeluxe.size
  ,getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo){
    var arrParam = {allowFullScreen:'true'
                   ,FlashVars:	'id=' + vid};
    var vid = strUrl.replace(/^.*\?id=(.*)$/i, '$1');
    var strSrc = arrInfo.base;
    strSrc = get_flash_src(strSrc, intWidth, intHeight, arrParam, arrUrlParam);
    return {content:strSrc, width:intWidth, height:intHeight, later:settings.defaultLater}
   }
  }
 ,nicovideo:{
   match: function(strUrl){return (settings.nicovideo.enabled && (/\.nicovideo\.jp\/watch/i).test(strUrl));}
  ,base: 'http://www.nicovideo.jp/watch/'
  ,defaultSize: settings.nicovideo.size
  ,getInfo: function(strUrl, intWidth, intHeight, arrUrlParam, arrInfo){
    var vid = strUrl.replace(/^.*\/watch\/(.*)$/i, '$1');
    var strSrc = arrInfo.base + vid;
    strSrc = '<iframe width="' + intWidth + '" height="' + intHeight + '" src="http://www.nicovideo.jp/thumb/' + vid + '" scrolling="no" style="border:solid 1px #CCC;" frameborder="0"><a href="' + strSrc + '">' + arrInfo.tytle + '</iframe>';
    return {content:strSrc, width:intWidth, height:intHeight, later:Math.round(settings.defaultLater / 3)}
   }
  }
 ,Contents:{
   match: function(strUrl){return (settings.Contents.enabled && RegExp(w.location.host, 'i').test(strUrl) && !(/\.(jpe?g|gif|png|bmp)$/i).test(strUrl));}
  ,defaultSize: settings.Contents.size
  ,set: function(contentNo){
    var strSrc;
    var arrSizes = get_sizes_from_str(arrContent[contentNo].href);
    var strSizes = 'width="' + arrSizes[0] + '" height="' + arrSizes[1] + '"';
    if (settings.Contents.iframeEnabled) {
     strSrc = '<iframe ' + strSizes + ' src="' + arrContent[contentNo].href + '" scrolling="no" style="border:solid 1px #CCC;" frameborder="0"><a href="' + arrContent[contentNo].href + '">' + arrContent[contentNo].tytle + '</a></iframe>';
     arrContent[contentNo] = jQuery.extend(arrContent[contentNo], {content:strSrc, width:arrSizes[0], height:arrSizes[1], later:500});
     set_content_to_view(contentNo);
    } else {
     jQuery.get(arrContent[contentNo].href, function(responseText, status){
      strSrc = '<div ' + strSizes + '>' + responseText.replace(/[\r\n]/g, '').replace(/.*<body.*?>(.*?)<\/body>.*$/, '$1').replace(/<script.*?>.*<\/script>/g, '') + '</div>';
      arrContent[contentNo] = jQuery.extend(arrContent[contentNo], {content:strSrc, width:arrSizes[0], height:arrSizes[1], later:500});
      set_content_to_view(contentNo);
     });
    }
   }
  ,preload: function(contentNo){}
  ,content_css: {textAlign:'left', lineHeight:'1.2em'}
  }
 };

 // DOM Elements ID and CSS Path
 var overlay_ID  = 'overlay'
   , lightpop_ID = plugin_name
   , box_ID      = plugin_name + '-box'
   , content_ID  = plugin_name + '-content'
   , nav_ID      = plugin_name + '-nav'
   , data_ID     = plugin_name + '-data'
   , details_ID  = plugin_name + '-details'
   , loading_ID  = plugin_name + '-loading';

 var overlay_csspath  = '#' + overlay_ID
   , lightpop_csspath = '#' + lightpop_ID
   , box_csspath      = '#' + box_ID
   , content_csspath  = '#' + content_ID
   , nav_csspath      = '#' + nav_ID
   , data_csspath     = '#' + data_ID
   , details_csspath  = '#' + details_ID
   , loading_csspath  = '#' + loading_ID;

 // initialize
 var init = function(jQueryObj){
  // image preload
  image_load(settings.imageBtnPrev,  function(s, w, h){
   settings.imageBtnPrevWidth  = (w > 0 ? w : settings.imageBtnPrevWidth);
   image_load(settings.imageBtnNext,  function(s, w, h){
    settings.imageBtnNextWidth  = (w > 0 ? w : settings.imageBtnNextWidth);
    image_load(settings.imageBtnClose, function(s, w, h){
     settings.imageBtnCloseWidth = (w > 0 ? w : settings.imageBtnCloseWidth);
     if (!frameBorder) {
      image_load(settings.imageBox, function(s, w, h){
       image_load(settings.imageBorderL, function(s, w, h){
        image_load(settings.imageBorderR, function(s, w, h){
         settings.contentBorder = 'none';
        });
       });
      });
     }
    });
   });
  });

  // get matched object
  var domMatchedObj = new Array();
  var intImage = 0;
  jQueryObj.filter('a').each(function() {
   var location = w.location;
   var jQuery_this = jQuery(this);
   if (!(/^https?:/i).test(jQuery_this.attr('href')) && (/^https?:/i).test(location.protocol)) {
    jQuery_this.attr('href', (
     (/^\//i).test(jQuery_this.attr('href'))
       ? location.protocol + '//' + location.hostname + '/' + jQuery_this.attr('href')
       : location.href.replace(/^(.*\/).*$/i, '$1') + jQuery_this.attr('href').replace(/^\/?(.*)$/i, '$1')
    ));
   }

   for (var key in fileTypes) {
    var filetype = fileTypes[key];
    if (filetype.match(jQuery_this.attr('href').replace(/\?.*$/, ''))) {
     var filetype_option = settings[key];
     // set icons to link
     if (settings.iconAdd && jQuery_this.children('img').length === 0) {
      jQuery_this.css({background:'transparent url(' + filetype_option.icon + ') no-repeat scroll 1px 0pt', paddingLeft:'20px'});
     }

     // content info
     arrContent.push({
      type:	key
     ,href:	jQuery_this.attr('href')
     ,title:	jQuery_this.attr('title') || jQuery_this.html().replace(/<[^>]*>/ig, '')
     ,base:	(filetype.base ? filetype.base : '/')
     });

     // bind click event
     jQuery_this.unbind('click').click((function(i){return function(){start_lightpop(i); return false;}})(intImage));

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
 var start_lightpop = function(intClickedContent){
  // hide embed, object, select element
  set_visibility(jQuery('embed, object, select'), false);

  // set interface
  var content_htm = '<div id="' + content_ID + '-wrap"><div id="' + content_ID + '" /></div>';
  var data_htm = (settings.detailsEnabled ? '<div id="' + data_ID + '-wrap"><div id="' + data_ID + '"><div id="' + details_ID + '"><div id="' + details_ID + '-caption" /><div id="' + details_ID + '-number" /></div><div id="' + data_ID + '-nav"><a href="#" id="' + data_ID + '-nav-close"><img src="' + settings.imageBtnClose + '" /></a></div></div></div>' : '');
  var navi_htm = '<div id="' + nav_ID + '"><a href="#" id="' + nav_ID + '-prev" /><a href="#" id="' + nav_ID + '-next" /></div><div id="' + loading_ID + '"><a href="#" id="' + loading_ID + '-link"><img src="' + settings.imageLoading + '" /></a></div>';
  var lightpop_htm = 
      '<div id="' + overlay_ID +'" />'
    + '<div id="' + lightpop_ID + '"><div id="' + box_ID + '">'
    + '<div id="' + box_ID + '-inner">'
    + (frameBorder
       ? content_htm + '</div>' + data_htm
       : '<div id="' + box_ID + '-hd"><div id="' + box_ID + '-hdc" /></div>'
        +'<div id="' + box_ID + '-bd"><div id="' + box_ID + '-bdc">' + content_htm + data_htm + '</div></div>'
        +'<div id="' + box_ID + '-ft"><div id="' + box_ID + '-ftc" /></div>'
        +'</div>')
    + navi_htm
    + '</div></div>';
  jQuery('body').append(jQuery(lightpop_htm).hide());

  // set interface CSS
  var arrPageSizes  = get_page_sizes();
  var arrPageScroll = get_page_scroll();
  var containerBorderSize = settings.containerBorderSize;

  // set position
  jQuery(overlay_csspath + ',' + lightpop_csspath + ',' + nav_csspath + ',' + loading_csspath).css({position:'absolute'});
  jQuery(box_csspath + ',' + data_csspath).css({position:'relative'});

  // overlay
  jQuery(overlay_csspath).css({top:0, left:0, backgroundColor:settings.overlayBgColor, opacity:settings.overlayOpacity, width:arrPageSizes[0], height:arrPageSizes[1], zIndex:90});
  jQuery(lightpop_csspath).css({top:arrPageScroll[1] + Math.round(arrPageSizes[3] / 10), left:arrPageScroll[0], width:'100%', height:0, overflow:'visible', textAlign:'center', lineHeight:0, border:'none', zIndex:100});
  jQuery(lightpop_csspath + ' a img').css({border:'none'});

  // container
  jQuery(box_csspath).css({width:settings.initSize, height:settings.initSize, top:0, margin:'0 auto', padding:0, backgroundColor:settings.contentBgColor, border:settings.contentBorder, overflow:(frameBorder ? 'hidden' : 'visible')});
  jQuery(box_csspath + '-inner').css({width:settings.initSize, height:settings.initSize, backgroundColor:'transparent', margin:'0 auto', padding:(frameBorder ? containerBorderSize : 0), overflow:(frameBorder ? 'hidden' : 'visible')});
  jQuery(content_csspath + '-wrap').css({backgroundColor:'transparent'});
  jQuery(content_csspath).css({margin:(frameBorder ? 0 : '0 auto'), padding:(frameBorder ? 0 : containerBorderSize + 'px 0'), zIndex:110});
  if (!frameBorder) {
   var boxBorderSize = settings.boxBorderSize;
   set_box_css(false).css({position:'relative'}).hide();
   jQuery(box_csspath + '-hd'  + ',' + box_csspath + '-hdc').css({height:boxBorderSize, top:0});
   jQuery(box_csspath + '-ft'  + ',' + box_csspath + '-ftc').css({height:boxBorderSize, bottom:0});
   jQuery(box_csspath + '-hd'  + ',' + box_csspath + '-ft' ).css({margin:'0 ' + boxBorderSize + 'px 0 0'});
   jQuery(box_csspath + '-hdc' + ',' + box_csspath + '-ftc').css({margin:'0 ' + boxBorderSize*-1 + 'px 0 ' + boxBorderSize + 'px'});
  }

  // navigation
  jQuery(nav_csspath).css({top:0, left:0, height:'10px', width:'100%', padding:0, margin:(frameBorder ? '0' : settings.boxBorderSize + 'px') + ' auto', zIndex:10});
  jQuery(nav_csspath + ' a').css({display:'block', height:'100%', zoom:1, margin:(frameBorder ? 0 : '0 ' + settings.boxBorderSize + 'px'), outline:'none'});
  jQuery(nav_csspath + '-prev').css({width:settings.imageBtnPrevWidth, left:0, styleFloat:'left'});
  jQuery(nav_csspath + '-next').css({width:settings.imageBtnNextWidth, right:0, styleFloat:'right'});

  // loading image
  jQuery(loading_csspath).css({top:'40%', left:0, height:'20%', width:'100%', margin:'0 auto', textAlign:'center', lineHeight:0});

  // details
  if (settings.detailsEnabled) {
   jQuery(data_csspath + '-wrap').css({font:'10px Verdana, Helvetica, sans-serif', backgroundColor:settings.contentBgColor, lineHeight:'1.4em', width:'100%', margin:'0 auto', padding:'0 ' + containerBorderSize + 'px 0', overflow:'hidden'}).hide();
   jQuery(data_csspath).css({padding:'0 ' + containerBorderSize + 'px', color:'#666', left:0, bottom:0});
   jQuery(details_csspath).css({width:'70%', styleFloat:'left', textAlign:'left'});
   jQuery(details_csspath + '-caption').css({styleFloat:'left', 'font-weight':'bold', width:'100%'});
   jQuery(details_csspath + '-number').css({styleFloat:'left', clear:'left', width:'100%', 'padding-bottom':'1em'});
   jQuery(data_csspath + '-nav-close').css({styleFloat:'right', width:settings.imageBtnCloseWidth, 'padding':'.35em 0'});
  }

  // bind event
  jQuery(overlay_csspath).fadeIn(settings.containerResizeSpeed, function(){jQuery(lightpop_csspath).show();});
  jQuery(overlay_csspath + ',' + loading_csspath + '-link,' + data_csspath + '-nav-close').click(function(){finish_lightpop(); return false;});
  window_resize(true);

  // set content
  set_content(intClickedContent);
 };

 // set content
 var set_content = function(contentNo){
  set_visibility(jQuery(data_csspath + ',' + details_csspath + ',' + details_csspath + '-caption,' + details_csspath + '-number,' + data_csspath + '-nav-close'), false);
  jQuery(nav_csspath + ',' + nav_csspath + '-prev,' + nav_csspath + '-next').hide();
  jQuery(content_csspath).hide().children().remove();
  jQuery(loading_csspath + ',' + box_csspath + ',' + box_csspath + '-inner').show();
  set_box_css(false);
  if(settings.detailsEnabled && !frameBorder && webkit){jQuery(data_csspath + '-wrap').height('auto');}
  set_content_to_view(contentNo);
 };

 // set content to view
 var set_content_to_view = function(contentNo){
  (arrContent[contentNo].content
   ? function(n){jQuery(content_csspath).append(jQuery(arrContent[n].content)); setTimeout(function(){show_container(n);}, arrContent[n].later);}
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
   ? (function(d){jQuery(box_csspath).animate({width:intWidth, height:intHeight}, 'fast', function(){set_visibility(d, true);});})
   : (function(d){if(settings.detailsEnabled && webkit){jQuery(data_csspath + '-wrap').height(intDataboxHeight);}set_visibility(d, true);})
  )(data_box);
 };

 // show container
 var show_container = function(contentNo){
  var containerBorderSize = settings.containerBorderSize, boxBorderSize = settings.boxBorderSize;
  var contentInfo = arrContent[contentNo];
  var intWidth  = contentInfo.width  + (containerBorderSize + (frameBorder ? 0 : boxBorderSize)) * 2;
  var intHeight = contentInfo.height + containerBorderSize * 2;
  var arrPageSizes  = get_page_sizes();
  var arrPageScroll = get_page_scroll();

  jQuery(lightpop_csspath).animate({top:arrPageScroll[1] + Math.round(arrPageSizes[3] / 10), left:arrPageScroll[0]}, settings.containerResizeSpeed, function(){
   jQuery(box_csspath).animate({width:intWidth, height:intHeight}, settings.containerResizeSpeed, function(){
    var fyletype = fileTypes[contentInfo.type];

    // resize content and data
    jQuery(box_csspath + '-inner').css({width:(frameBorder ? contentInfo.width : intWidth), height:contentInfo.height});
    jQuery(content_csspath).css({width:contentInfo.width, height:contentInfo.height});
    if (settings.detailsEnabled) {jQuery(data_csspath + '-wrap').css({width:contentInfo.width});}

    // set content css
    if (fyletype.content_css) {jQuery(content_csspath).children().css(fyletype.content_css);}

    // show content
    jQuery(loading_csspath).hide();
    if ((/<object.*>/i).test(contentInfo.content)) {jQuery(content_csspath).show();} else {jQuery(content_csspath).fadeIn();}
    set_box_css(true);

    // set content data
    jQuery(details_csspath + '-caption').html((settings.setLinkToTitle ? '<a href="' + contentInfo.href + '" title="' + contentInfo.title + '">' + contentInfo.title + '</a>' : contentInfo.title));
    jQuery(details_csspath + '-number').html((settings.txtImage || contentInfo.type) + (arrContent.length > 1 ? ' ' + (contentNo + 1) + ' ' + settings.txtOf + ' ' + arrContent.length : ''));
    var data_box = set_visibility(jQuery(data_csspath + ',' + details_csspath + ',' + details_csspath + '-caption,' + details_csspath + '-number,' + data_csspath + '-nav-close'), false).show();
    (settings.detailsEnabled
     ? function(d,w,h){jQuery(data_csspath + '-wrap').slideDown('fast', function(){show_data_box(d, w, h, (!webkit ? jQuery(this).height() : this.scrollHeight));});}
     : function(d,w,h){show_data_box(d, w, h);}
    )(data_box, intWidth, intHeight);

    // set navigation
    jQuery(nav_csspath).css({width:intWidth}).show(function(){
     jQuery(nav_csspath + '-prev,' + nav_csspath + '-next').css({height:Math.round(intHeight / 3), background:'transparent url(' + settings.imageBlank + ') no-repeat'}).unbind();
     if(contentNo != 0){
      jQuery(nav_csspath + '-prev').hover(
       function(){jQuery(this).css({background:'url(' + settings.imageBtnPrev + ') left 30% no-repeat'});},
       function(){jQuery(this).css({background:'transparent url(' + settings.imageBlank + ') no-repeat'});}
      ).show().click((function(i){return function(){set_content(i); return false;}})(contentNo - 1));
     }
     if(contentNo != (arrContent.length - 1)){
      jQuery(nav_csspath + '-next').hover(
       function(){jQuery(this).css({background:'url(' + settings.imageBtnNext + ') right 30% no-repeat'});},
       function(){jQuery(this).css({background:'transparent url(' + settings.imageBlank + ') no-repeat'});}
      ).show().click((function(i){return function(){set_content(i); return false;}})(contentNo + 1));
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
    if ((/^width=\d+$/i).test(this))  {numWidth  = Number(this.replace(/^width=(\d+)$/, '$1'));}
    if ((/^height=\d+$/i).test(this)) {numHeight = Number(this.replace(/^height=(\d+)$/,'$1'));}
   });
  }
  if (numWidth === 0 || numHeight === 0) {
   if (defaultSizes) {
    numWidth  = defaultSizes[0];
    numHeight = defaultSizes[1];
   } else {
    var arrPageSizes = get_page_sizes();
    numWidth  = arrPageSizes[0] / 4;
    numHeight = arrPageSizes[1] / 4;
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
  return new Array(Math.round(numWidth), Math.round(numHeight));
 };

 var set_video_info = function(contentNo) {
  var contentInfo = arrContent[contentNo];
  var filetype_option = settings[contentInfo.type];
  var fyletype = fileTypes[contentInfo.type];
  var strUrl = contentInfo.href.replace(/&.*$/i,'');
  var arrSizes, arrUrlParam = {};
  var vid, strSrc = null;
  if ((/\?.*$/).test(contentInfo.href)) {
   var arrParams = contentInfo.href.replace(/^.*\?/,'').split("&");
   if (arrParams.length > 0) {
    jQuery.each(arrParams, function(){
     var wk_matched, wk_str = this.toString();
     if (!(/^(width|height|v|i|docid|id)\=.*$/i).test(wk_str)) {
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

 var get_flash_src = function(url, width, height, param, url_param) {
  var strSrc = '';
  if (typeof url_param === 'object') {
   jQuery.each(url_param, function(key){
    if (url.indexOf(key + '=') < 0) url += (url.indexOf('?') < 0 ? '?' : '&') + key + '=' + this;
   });
  }
  var wkParam = {movie: url, wmode: 'transparent'};
  param = (param ? jQuery.extend(wkParam, param) : wkParam);
  strSrc = '<object width="' + width + '" height="' + height + '"' + ' data="' + url + '" type="application/x-shockwave-flash" wmode="' + param.wmode + '">';
  jQuery.each(param, function(key){strSrc += '<param name="' + key + '" value="' + this + '" />';});
  strSrc += '</object>';
  return strSrc;
 };

 var get_embed_src = function(url, clsid, apl_type, width, height, param, url_param, obj_option, embed_option, flg_noembed) {
  var strSrc = '', strEmb = '';
  var wkParam = {src: url, width: width, height: height, type: apl_type};
  param = (param ? jQuery.extend(wkParam, param) : wkParam);
  strSrc += '<object width="' + width + '" height="' + height + '"' + (clsid != ' ' ? 'classid="clsid:' + clsid + '"' : '') + ' type="' + apl_type + '" ' + obj_option + '>';
  jQuery.each(param, function(key){strSrc += '<param name="' + key + '" value="' + this + '" />';});
  strEmb += '<embed ';
  if (embed_option !== false) {
   strEmb += embed_option;
  } else {
   strEmb += 'width="' + width + '" height="' + height + '"';
   jQuery.each(param, function(key){strEmb += ' ' + key + '="' + this + '"';});
  }
  strEmb += '>'
         + (flg_noembed ? '<noembed><a href="' + url + '">' + url + '</a></noembed>' : '')
         + '</embed>';
  strSrc += strEmb + '</object>';
  return strSrc;
 };

 // set box css
 var set_box_css = function(enable) {
  if (!frameBorder) {
   var jQueryObj = jQuery(box_csspath + '-hd, ' + box_csspath + '-hdc, ' + box_csspath + '-bd, ' + box_csspath + '-bdc, ' + box_csspath + '-ft, ' + box_csspath + '-ftc');
   var bg_transparent = 'transparent', bg_content = settings.contentBgColor;
   if (enable) {
    var bg_box = settings.imageBox, bg_borderL = settings.imageBorderL, bg_borderR = settings.imageBorderR;
    jQuery(box_csspath).css({backgroundColor:bg_transparent});
    jQuery(box_csspath + '-hd').css({background:bg_transparent + ' url(' + bg_box + ') left top no-repeat'});
    jQuery(box_csspath + '-hdc').css({background:bg_transparent + ' url(' + bg_box + ') right top no-repeat'});
    jQuery(box_csspath + '-bd').css({background:bg_content + ' url(' + bg_borderL + ') left top repeat-y'});
    jQuery(box_csspath + '-bdc').css({background:bg_transparent + ' url(' + bg_borderR + ') right top repeat-y'});
    jQuery(box_csspath + '-ft').css({background:bg_transparent + ' url(' + bg_box + ') left bottom no-repeat'});
    jQuery(box_csspath + '-ftc').css({background:bg_transparent + ' url(' + bg_box + ') right bottom no-repeat'});
    jQueryObj.show();
   } else {
    jQuery(box_csspath).css({backgroundColor:bg_content});
    jQueryObj.css({background:bg_transparent});
   }
   return jQueryObj;
  }
 };

 var image_set = function(contentNo, setFlag){
  image_load(arrContent[contentNo].href, (function(n, f){return function(src, width, height){
   var arrSizes = get_sizes_from_str(src, new Array(width, height), settings.Image.size);
   arrContent[n] = jQuery.extend(arrContent[n], {
     content:'<img src="' + src.replace(/\?.*$/, '') + '" width="' + arrSizes[0] + '" height="' + arrSizes[1] + '" />'
    ,width:arrSizes[0]
    ,height:arrSizes[1]
    ,later:(msie ? 250 : 100)
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
    onLoad(src, images[i].width, images[i].height);
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
   onLoad(src, img.width, img.height);
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
    var escapeKey = (objEvent ? objEvent.DOM_VK_ESCAPE : 27);
    var key = String.fromCharCode(keycode).toLowerCase();
    if ((key == settings.keyToClose) || (key == 'x') || (keycode == escapeKey)) {
     finish_lightpop();
    } else if (((key == settings.keyToPrev) || (keycode == 37)) && contentNo != 0) {
     set_content(contentNo - 1);
    } else if (((key == settings.keyToNext) || (keycode == 39)) && contentNo != (arrContent.length - 1)) {
     set_content(contentNo + 1);
    }
   });
  }
 };

 // set window resize event
 var window_resize = function(enable){
  jQuery(w).unbind('resize');
  if (enable) {
   jQuery(w).resize(function(){
    var arrPageSizes  = get_page_sizes();
    var arrPageScroll = get_page_scroll();
    jQuery(overlay_csspath).css({width:arrPageSizes[0], height:arrPageSizes[1]});
    jQuery(lightpop_csspath).css({top:arrPageScroll[1] + Math.round(arrPageSizes[3] / 10), left:arrPageScroll[0]});
   });
  }
 };

 // get page sizes
 var get_page_sizes = function(){
  var pageWidth  = (e && e.scrollWidth  ? e : b).scrollWidth;
  var pageHeight = (e && e.scrollHeight ? e : b).scrollHeight;
  var windowWidth  = (back_compat ? b : e).clientWidth;
  var windowHeight = (back_compat ? b : e).clientHeight;
  pageWidth  = (pageWidth  < windowWidth  ? pageWidth : windowWidth);
  pageHeight = (pageHeight < windowHeight ? windowHeight : pageHeight);
  return new Array(pageWidth, pageHeight, windowWidth, windowHeight);
 };

 // get page scroll
 var get_page_scroll = function(){
  var x = (e && e.scrollLeft ? e : b).scrollLeft;
  var y = (e && e.scrollTop  ? e : b).scrollTop;
  return new Array(x, y);
 };

 // finish!
 var finish_lightpop = function(){
  set_visibility(jQuery('object',jQuery(lightpop_csspath)), false).remove();
  jQuery(lightpop_csspath).height(jQuery(box_csspath).height()).slideUp(function(){
   jQuery(this).remove();
   jQuery(overlay_csspath).fadeOut(function(){
    jQuery(this).remove();
    // show embed, object, select element
    set_visibility(jQuery('embed, object, select'), true);
   });
  });
  keyboard_navigation(false);
  window_resize(false);
 };

 return init(this);
};})(jQuery);
