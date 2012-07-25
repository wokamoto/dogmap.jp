/**
 * jQuery lightpop plugin
 * This jQuery plugin was inspired on jQuery lightBox plugin by Leandro Vieira Pinho (http://leandrovieira.com/projects/jquery/lightbox/)
 * @name jquery.lightpop-0.6.2.js
 * @author wokamoto - http://dogmap.jp
 * @version 0.6.2
 * @date October 3, 2008
 * @category jQuery plugin
 * @copyright (c) 2007-2008 wokamoto (dogmap.jp)
 * @license  Released under the GPL license (http://www.gnu.org/copyleft/gpl.html)
 * @example http://dogmap.jp/lightpop_sample/sample.html
 */
(function(jQuery){
var userAgent = navigator.userAgent.toLowerCase();
jQuery.browser = jQuery.extend(jQuery.browser,{chrome: /chrome/.test( userAgent )});
jQuery.fn.lightpop = function(settings){
 settings = jQuery.extend({
   overlayBgColor:	'#000'
  ,overlayOpacity:	0.7
  ,contentFrameType:	'border'
  ,contentBorder:	'none'
  ,boxBorderSize:	6
  ,containerBorderSize:	10
  ,containerResizeSpeed:'normal'
  ,contentBgColor:	'#FFF'
  ,imageBox:		'images/lightpop-box.gif'
  ,imageBorderL:	'images/lightpop-border-l.gif'
  ,imageBorderR:	'images/lightpop-border-r.gif'
  ,imageLoading:	'images/lightpop-ico-loading.gif'
  ,imageBtnPrev:	'images/lightpop-btn-prev.gif'
  ,imageBtnNext:	'images/lightpop-btn-next.gif'
  ,imageBtnClose:	'images/lightpop-btn-close.gif'
  ,imageBlank:		'images/lightpop-blank.gif'
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
  ,imageMaxWidth:	0
  ,imageMaxHeight:	0
  ,iframeEnabled:	true
//  ,iconImage:		'images/icon-image.png'
//  ,iconVideo:		'images/icon-video.png'
//  ,iconContents:	'images/icon-contents.png'
//  ,iconYouTube:	'images/icon-youtube.png'
//  ,iconMetacafe:	'images/icon-metacafe.png'
//  ,iconLiveLeak:	'images/icon-liveleak.png'
//  ,iconGoogleVideo:	'images/icon-googlevideo.png'
//  ,iconifilm:		'images/icon-ifilm.png'
//  ,iconDailymotion:	'images/icon-dailymotion.png'
//  ,iconsuperdeluxe:	'images/icon-superdeluxe.png'
//  ,iconnicovideo:	'images/icon-nicovideo.png'
//  ,paramYouTube:	[]
//  ,paramMetacafe:	[]
//  ,paramLiveLeak:	[]
//  ,paramGoogleVideo:	[]
//  ,paramifilm:	[]
//  ,paramDailymotion:	[]
//  ,paramsuperdeluxe:	[]
 }, settings);
 var frameBorder = ((/^border$/i).test(settings.contentFrameType));
 var arrContent = new Array();
 var objImageLoader = {};

 var fileTypes = new Array(
  {
   type: 'Image'
  ,match: function(strUrl){return ((/\.(jpe?g|gif|png|bmp)$/i).test(strUrl));}
  ,set: function(contentNo){image_set(contentNo, true);}
  ,preload: function(contentNo){if(!jQuery.browser.msie){image_set(contentNo, false);}}
  }
 ,{
   type: 'Video'
  ,match: function(strUrl){return ((/\.(flv|swf|rm|mov|3gp|mp4|asf|avi|mpg|wmv)$/i).test(strUrl));}
  ,base: '/'
  ,defaultSize: new Array(320, 240)
  }
 ,{
   type: 'YouTube'
  ,match: function(strUrl){return ((/\.youtube\.com\/watch/i).test(strUrl));}
  ,base: 'http://www.youtube.com/v/'
  ,defaultSize: new Array(425, 355)
  }
 ,{
   type: 'metacafe'
  ,match: function(strUrl){return ((/\.metacafe\.com\/watch/i).test(strUrl));}
  ,base: 'http://www.metacafe.com/fplayer/'
  ,defaultSize: new Array(400, 345)
  }
 ,{
   type: 'LiveLeak'
  ,match: function(strUrl){return ((/\.liveleak\.com\/view/i).test(strUrl));}
  ,base: 'http://www.liveleak.com/player.swf?autostart=false&amp;token='
  ,defaultSize: new Array(450, 370)
  }
 ,{
   type: 'GoogleVideo'
  ,match: function(strUrl){return ((/video\.google\.com\/videoplay/i).test(strUrl));}
  ,base: 'http://video.google.com/googleplayer.swf?docId='
  ,defaultSize: new Array(400, 326)
  }
 ,{
   type: 'IFILM'
  ,match: function(strUrl){return ((/\.ifilm\.com\/video/i).test(strUrl));}
  ,base: 'http://www.ifilm.com/efp'
  ,defaultSize: new Array(448, 365)
  }
 ,{
   type: 'Dailymotion'
  ,match: function(strUrl){return ((/\.dailymotion\.com\/video/i).test(strUrl));}
  ,base: 'http://www.dailymotion.com/swf/'
  ,defaultSize: new Array(420, 330)
  }
 ,{
   type: 'superdeluxe'
  ,match: function(strUrl){return ((/\.superdeluxe\.com\/sd/i).test(strUrl));}
  ,base: 'http://i.cdn.turner.com/sdx/static/swf/share_vidplayer.swf'
  ,defaultSize: new Array(400, 350)
  }
 ,{
   type: 'nicovideo'
  ,match: function(strUrl){return ((/\.nicovideo\.jp\/watch/i).test(strUrl));}
  ,base: 'http://www.nicovideo.jp/watch/'
  ,defaultSize: new Array(312, 176)
  }
 ,{
   type: 'Contents'
  ,match: function(strUrl){return (RegExp(window.location.host, 'i').test(strUrl));}
  ,set: function(contentNo){
    var strSrc;
    var arrSizes = get_sizes_from_str(arrContent[contentNo].href);
    if (settings.iframeEnabled) {
     strSrc = '<iframe width="' + arrSizes[0] + '" height="' + arrSizes[1] + '" src="' + arrContent[contentNo].href + '" scrolling="no" style="border:solid 1px #CCC;" frameborder="0"><' + 'a href="' + arrContent[contentNo].href + '" rel="nofollow">' + arrContent[contentNo].tytle + '</a></iframe>';
     arrContent[contentNo] = jQuery.extend(arrContent[contentNo], {content:strSrc, width:arrSizes[0], height:arrSizes[1], later:500});
     set_content_to_view(contentNo);
    } else {
     jQuery.get(arrContent[contentNo].href, function(responseText, status){
      strSrc = '<div width="' + arrSizes[0] + '" height="' + arrSizes[1] + '">' + responseText.replace(/[\r\n]/g, '').replace(/.*<body.*?>(.*?)<\/body>.*$/, '$1').replace(/<script.*?>.*<\/script>/g, '') + '</div>';
      arrContent[contentNo] = jQuery.extend(arrContent[contentNo], {content:strSrc, width:arrSizes[0], height:arrSizes[1], later:500});
      set_content_to_view(contentNo);
     });
    }
   }
  ,preload: function(contentNo){}
  ,content_css: {textAlign:'left', lineHeight:'1.2em'}
  }
 );

 // initialize
 var initialize = function(jQueryObj){
  // image preload
  image_load(settings.imageBtnPrev,  function(){
   settings.imageBtnPrevWidth  = (this.width > 0 ? this.width : 63);
   image_load(settings.imageBtnNext,  function(){
    settings.imageBtnNextWidth  = (this.width > 0 ? this.width : 63);
    image_load(settings.imageBtnClose, function(){
     settings.imageBtnCloseWidth = (this.width > 0 ? this.width : 66);
     if (!frameBorder) {
      jQuery.each(new Array(settings.imageBox, settings.imageBorderL, settings.imageBorderR), function(){
       image_load(this);
      });
      settings.contentBorder = 'none';
     }
    });
   });
  });

  // get matched object
  var domMatchedObj = new Array();
  var intImage = 0;
  var location = window.location;
  arrContent.length = 0;
  jQueryObj.filter('a').each(function() {
   var jQuery_this = jQuery(this);
   if (!(/^https?:/i).test(jQuery_this.attr('href')) && (/^https?:/i).test(location.protocol)) {
    jQuery_this.attr('href', (
     (/^\//i).test(jQuery_this.attr('href'))
       ? location.protocol + '//' + location.hostname + '/' + jQuery_this.attr('href')
       : location.href.replace(/^(.*\/).*$/i, '$1') + jQuery_this.attr('href').replace(/^\/?(.*)$/i, '$1')
    ));
   }

   for (var filetype = fileTypes[0], optindex = 0; optindex < fileTypes.length; optindex++, filetype = fileTypes[optindex]) {
    if (filetype.match(jQuery_this.attr('href').replace(/\?.*$/, ''))) {
     // set icons to link
     if (settings.iconAdd && jQuery_this.children('img').length == 0) {
      jQuery_this.css({background:'transparent url(' + (settings['icon' + filetype.type] ? settings['icon' + filetype.type] :'images/icon-' + filetype.type.toLowerCase() + '.png') + ') no-repeat scroll 1px 0pt', paddingLeft:'20px'});
     }

     // content info
     arrContent.push({
      index:	optindex
     ,type:	filetype.type
     ,href:	jQuery_this.attr('href')
     ,title:	(jQuery_this.attr('title') ? jQuery_this.attr('title') : jQuery_this.html().replace(/<[^>]*>/ig, ''))
     ,base:	(filetype.base ? filetype.base : '/')
     ,defaultSize: (filetype.defaultSize ? filetype.defaultSize : new Array(settings.imageMaxWidth, settings.imageMaxHeight))
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
  jQuery('body').append(
   jQuery('<div id="overlay" /><div id="lightpop"><div id="lightpop-box"><div id="lightpop-box-content">' + (
    frameBorder
      ? '<div id="lightpop-content-box"><div id="lightpop-content" /></div></div><div id="lightpop-data-box"><div id="lightpop-data"><div id="lightpop-details"><div id="lightpop-details-caption" /><div id="lightpop-details-number" /></div><div id="lightpop-data-nav"><a href="#" id="lightpop-data-nav-close"><img src="'+settings.imageBtnClose+'" /></a></div></div></div>'
      : '<div id="lightpop-box-hd"><div id="lightpop-box-hdc" /></div><div id="lightpop-box-bd"><div id="lightpop-box-bdc"><div id="lightpop-content-box"><div id="lightpop-content" /></div><div id="lightpop-data-box"><div id="lightpop-data"><div id="lightpop-details"><div id="lightpop-details-caption" /><div id="lightpop-details-number" /></div><div id="lightpop-data-nav"><a href="#" id="lightpop-data-nav-close"><img src="'+settings.imageBtnClose+'" /></a></div></div></div></div></div><div id="lightpop-box-ft"><div id="lightpop-box-ftc" /></div></div>'
   ) + '<div id="lightpop-nav"><a href="#" id="lightpop-nav-prev" /><a href="#" id="lightpop-nav-next" /></div><div id="lightpop-loading"><a href="#" id="lightpop-loading-link"><img src="'+settings.imageLoading+'" /></a></div></div></div>').hide()
  );

  // set interface CSS
  var arrPageSizes  = get_page_sizes();
  var arrPageScroll = get_page_scroll();
  var initSize = 250;

  // overlay
  jQuery('#overlay').css({position:'absolute', top:0, left:0, backgroundColor:settings.overlayBgColor, opacity:settings.overlayOpacity, width:arrPageSizes[0], height:arrPageSizes[1], zIndex:90});
  jQuery('#lightpop').css({position:'absolute', top:arrPageScroll[1] + Math.round(arrPageSizes[3] / 10), left:arrPageScroll[0], width:'100%', textAlign:'center', lineHeight:0, border:'none', zIndex:100});
  jQuery('#lightpop a img').css({border:'none'});

  // container
  jQuery('#lightpop-box').css({position:'relative', width:initSize, height:initSize, top:0, margin:'0 auto', padding:0, backgroundColor:settings.contentBgColor, border:settings.contentBorder, overflow:(frameBorder ? 'hidden' : 'visible')});
  jQuery('#lightpop-content-box').css({backgroundColor:'transparent'});
  jQuery('#lightpop-content').css({margin:(frameBorder ? 0 : '0 auto'), padding:(frameBorder ? 0 : settings.containerBorderSize + 'px 0')});
  jQuery('#lightpop-box-content').css({position:'relative', width:initSize, height:initSize, backgroundColor:'transparent', margin:'0 auto', padding:(frameBorder ? settings.containerBorderSize : 0), overflow:(frameBorder ? 'hidden' : 'visible')});
  if (!frameBorder) {
   set_box_css(false).css({position:'relative'}).hide();
   jQuery('#lightpop-box-hd').css({height:settings.boxBorderSize, top:0, margin:'0 ' + settings.boxBorderSize + 'px 0 0'});
   jQuery('#lightpop-box-hdc').css({height:settings.boxBorderSize, top:0, margin:'0 ' + settings.boxBorderSize*-1 + 'px 0 ' + settings.boxBorderSize + 'px'});
   jQuery('#lightpop-box-ft').css({height:settings.boxBorderSize, bottom:0, margin:'0 ' + settings.boxBorderSize + 'px 0 0'});
   jQuery('#lightpop-box-ftc').css({height:settings.boxBorderSize, bottom:0, margin:'0 ' + settings.boxBorderSize*-1 + 'px 0 ' + settings.boxBorderSize + 'px'});
  }

  // navigation
  jQuery('#lightpop-nav').css({position:'absolute', top:0, left:0, height:'10px', width:'100%', padding:0, margin:(frameBorder ? '0' : settings.boxBorderSize + 'px') + ' auto', zIndex:10});
  jQuery('#lightpop-nav a').css({display:'block', height:'100%', zoom:1, margin:(frameBorder ? 0 : '0 ' + settings.boxBorderSize + 'px'), outline:'none'});
  jQuery('#lightpop-nav-prev').css({width:settings.imageBtnPrevWidth, left:0, styleFloat:'left'});
  jQuery('#lightpop-nav-next').css({width:settings.imageBtnNextWidth, right:0, styleFloat:'right'});

  // loading image
  jQuery('#lightpop-loading').css({position:'absolute', top:'40%', left:0, height:'20%', width:'100%', margin:'0 auto', textAlign:'center', lineHeight:0});

  // content data
  jQuery('#lightpop-data-box').css({font:'10px Verdana, Helvetica, sans-serif', backgroundColor:settings.contentBgColor, lineHeight:'1.4em', width:'100%', margin:'0 auto', padding:'0 ' + settings.containerBorderSize + 'px 0', overflow:'hidden'}).hide();
  jQuery('#lightpop-data').css({position:'relative', padding:'0 ' + settings.containerBorderSize + 'px', color:'#666', left:0, bottom:0});
  jQuery('#lightpop-details').css({width:'70%', styleFloat:'left', textAlign:'left'});
  jQuery('#lightpop-details-caption').css({styleFloat:'left', 'font-weight':'bold', width:'100%'});
  jQuery('#lightpop-details-number').css({styleFloat:'left', clear:'left', width:'100%', 'padding-bottom':'1.0em'});
  jQuery('#lightpop-data-nav-close').css({styleFloat:'right', width:settings.imageBtnCloseWidth, 'padding':'0.35em 0'});

  // bind event
  jQuery('#overlay').click(finish_lightpop).fadeIn(settings.containerResizeSpeed, function(){jQuery('#lightpop').show();});
  jQuery('#lightpop-loading-link, #lightpop-data-nav-close').click(function(){finish_lightpop(); return false;});
  window_resize(true);

  // set content
  set_content(intClickedContent);
 };

 // set content
 var set_content = function(contentNo){
  set_visibility(jQuery('#lightpop-data, #lightpop-details, #lightpop-details-caption, #lightpop-details-number, #lightpop-data-nav-close'), false);
  jQuery('#lightpop-nav, #lightpop-nav-prev, #lightpop-nav-next').hide();
  jQuery('#lightpop-content').hide().children().remove();
  jQuery('#lightpop-loading, #lightpop-box, #lightpop-box-content').show();
  set_box_css(false);
  if(!frameBorder && (jQuery.browser.safari || jQuery.browser.chrome)){jQuery('#lightpop-data-box').height('auto');}
  set_content_to_view(contentNo);
 };

 // set content to view
 var set_content_to_view = function(contentNo){
  (arrContent[contentNo].content
    ? function(n){jQuery('#lightpop-content').append(jQuery(arrContent[n].content)); setTimeout(function(){show_container(n);}, arrContent[n].later);}
    : (fileTypes[arrContent[contentNo].index].set ? fileTypes[arrContent[contentNo].index].set : (function(contentNo){set_video_info(contentNo); set_content_to_view(contentNo);}))
  )(contentNo);
 };

 // show container
 var show_container = function(contentNo){
  var intWidth  = arrContent[contentNo].width  + (settings.containerBorderSize + (frameBorder ? 0 : settings.boxBorderSize)) * 2;
  var intHeight = arrContent[contentNo].height + settings.containerBorderSize * 2;

  jQuery('#lightpop-box').animate({width:intWidth, height:intHeight}, settings.containerResizeSpeed, function(){
   var contentInfo = arrContent[contentNo];

   // resize content and data
   jQuery('#lightpop-box-content').css({width:(frameBorder ? contentInfo.width : intWidth), height:contentInfo.height});
   jQuery('#lightpop-content').css({width:contentInfo.width, height:contentInfo.height});
   jQuery('#lightpop-data-box').css({width:contentInfo.width});

   // set content css
   if (fileTypes[contentInfo.index].content_css) {jQuery('#lightpop-content').children().css(fileTypes[contentInfo.index].content_css);}

   // show content
   jQuery('#lightpop-loading').hide();
   if ((/<object.*>/i).test(contentInfo.content)) {jQuery('#lightpop-content').show();} else {jQuery('#lightpop-content').fadeIn();}
   set_box_css(true);

   // set content data
   jQuery('#lightpop-details-caption').html((settings.setLinkToTitle ? '<a href="' + contentInfo.href + '" title="' + contentInfo.title + '">' + contentInfo.title + '</a>' : contentInfo.title));
   jQuery('#lightpop-details-number').html((settings.txtImage ? settings.txtImage : contentInfo.type) + (arrContent.length > 1 ? ' ' + (contentNo + 1) + ' ' + settings.txtOf + ' ' + arrContent.length : ''));
   var data_box = set_visibility(jQuery('#lightpop-data, #lightpop-details, #lightpop-details-caption, #lightpop-details-number, #lightpop-data-nav-close'), false).show();
   jQuery('#lightpop-data-box').slideDown('fast', function(){
    var intDataboxHeight = (!jQuery.browser.safari ? jQuery(this).height() : this.scrollHeight);
    intDataboxHeight = (intDataboxHeight < 28 ? 28 : intDataboxHeight);
    intHeight += intDataboxHeight;
    (frameBorder
     ? (function(d){jQuery('#lightpop-box').animate({width:intWidth, height:intHeight}, 'fast', function(){set_visibility(d, true);});})
     : (function(d){if(jQuery.browser.safari || jQuery.browser.chrome){jQuery('#lightpop-data-box').height(intDataboxHeight);}set_visibility(d, true);})
    )(data_box);
   });

   // set navigation
   jQuery('#lightpop-nav').css({width:intWidth}).show(function(){
    jQuery('#lightpop-nav-prev, #lightpop-nav-next').css({height:Math.round(intHeight / 3), background:'transparent url(' + settings.imageBlank + ') no-repeat'}).unbind();
    if(contentNo != 0){
     jQuery('#lightpop-nav-prev').hover(
      function(){jQuery(this).css({background:'url(' + settings.imageBtnPrev + ') left 30% no-repeat'});},
      function(){jQuery(this).css({background:'transparent url(' + settings.imageBlank + ') no-repeat'});}
     ).show().click((function(i){return function(){set_content(i); return false;}})(contentNo - 1));
    }
    if(contentNo != (arrContent.length - 1)){
     jQuery('#lightpop-nav-next').hover(
      function(){jQuery(this).css({background:'url(' + settings.imageBtnNext + ') right 30% no-repeat'});},
      function(){jQuery(this).css({background:'transparent url(' + settings.imageBlank + ') no-repeat'});}
     ).show().click((function(i){return function(){set_content(i); return false;}})(contentNo + 1));
    }
    keyboard_navigation(true, contentNo);
   });

   // preload contents
   if((arrContent.length - 1) > contentNo){preload(contentNo + 1);}
   if(contentNo > 0){preload(contentNo - 1);}
  });
 };

 // preload
 var preload = function(contentNo){
  if(!arrContent[contentNo].content) {
   (fileTypes[arrContent[contentNo].index].preload
     ? fileTypes[arrContent[contentNo].index].preload
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
  if (numWidth == 0 || numHeight == 0) {
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
  var strUrl = contentInfo.href.replace(/&.*$/i,'');
  var arrSizes, arrParam = {}, arrUrlParam = [];
  var vid, strSrc = null, intLater = 1500;
  if ((/\?.*$/).test(contentInfo.href)) {
   var arrParams = contentInfo.href.replace(/^.*\?/,'').split("&");
   if (arrParams.length > 0) {
    jQuery.each(arrParams, function(){
     if (!(/^(width|height|v|i|docid|id)\=.*$/i).test(this)) arrUrlParam.push(this);
    });
   }
  }

  arrSizes = get_sizes_from_str(contentInfo.href, contentInfo.defaultSize);
  switch(contentInfo.type){
   case 'YouTube':
    vid    = strUrl.replace(/^.*\?v=(.*)$/i, '$1');
    strUrl = contentInfo.base + vid;
    arrUrlParam.push('hl=ja');
    if (settings.paramYouTube) arrUrlParam = jQuery.merge(arrUrlParam, settings.paramYouTube);
    jQuery.each(arrUrlParam, function(){strUrl += '&' + this;});
    strSrc = get_flash_src(strUrl, arrSizes[0], arrSizes[1], arrParam);
    break;
   case 'metacafe':
    vid    = strUrl.replace(/^.*\/watch\/([\d]+\/[^\/]*)\/?.*$/i, '$1');
    strUrl = contentInfo.base + vid + '.swf';
    if (settings.paramMetacafe) arrUrlParam = jQuery.merge(arrUrlParam, settings.paramMetacafe);
    strSrc = get_flash_src(strUrl, arrSizes[0], arrSizes[1], arrParam, arrUrlParam);
    break;
   case 'LiveLeak':
    vid    = strUrl.replace(/^.*\?i=(.*)$/i, '$1');
    strUrl = contentInfo.base + vid;
    arrParam = {quality:'high'};
    if (settings.paramLiveLeak) arrUrlParam = jQuery.merge(arrUrlParam, settings.paramLiveLeak);
    strSrc = get_flash_src(strUrl, arrSizes[0], arrSizes[1], arrParam, arrUrlParam);
    break;
   case 'GoogleVideo':
    vid    = strUrl.replace(/^.*\?docid=([a-z\d\-]+).*$/i, '$1');
    strUrl = contentInfo.base + vid;
    arrParam = {flashvars:''};
    arrUrlParam.push('hl=en');
    if (settings.paramGoogleVideo) arrUrlParam = jQuery.merge(arrUrlParam, settings.paramGoogleVideo);
    strSrc = get_flash_src(strUrl, arrSizes[0], arrSizes[1], arrParam, arrUrlParam);
    break;
   case 'IFILM':
    vid    = strUrl.replace(/\?.*$/, '').replace(/^.*\/video\/([^\/]*)[\/]?$/i, '$1');
    strUrl = contentInfo.base;
    arrParam = {flashvars:	'flvbaseclip=' + vid + '&'
               ,quality:	'high'
               ,bgcolor:	'000000'};
    if (settings.paramifilm) arrUrlParam = jQuery.merge(arrUrlParam, settings.paramifilm);
    strSrc = get_flash_src(strUrl, arrSizes[0], arrSizes[1], arrParam, arrUrlParam);
    break;
   case 'Dailymotion':
    vid    = strUrl.replace(/^.*\/video\/([^_]*).*$/i, '$1');
    strUrl = contentInfo.base + vid;
    arrParam = {allowFullScreen:	'true'
               ,allowScriptAccess:	'always'};
    if (settings.paramDailymotion) arrUrlParam = jQuery.merge(arrUrlParam, settings.paramDailymotion);
    strSrc = get_flash_src(strUrl, arrSizes[0], arrSizes[1], arrParam, arrUrlParam);
    break;
   case 'superdeluxe':
    vid    = strUrl.replace(/^.*\?id=(.*)$/i, '$1');
    strUrl = contentInfo.base;
    arrParam = {allowFullScreen:'true'
               ,FlashVars:	'id=' + vid};
    if (settings.paramsuperdeluxe) arrUrlParam = jQuery.merge(arrUrlParam, settings.paramsuperdeluxe);
    strSrc = get_flash_src(strUrl, arrSizes[0], arrSizes[1], arrParam, arrUrlParam);
    break;
   case 'nicovideo':
    vid    = strUrl.replace(/^.*\/watch\/(.*)$/i, '$1');
    strUrl = contentInfo.base + vid;
    strSrc = '<iframe width="' + arrSizes[0] + '" height="' + arrSizes[1] + '" src="http://www.nicovideo.jp/thumb/' + vid + '" scrolling="no" style="border:solid 1px #CCC;" frameborder="0"><a href="' + strUrl + '">' + contentInfo.tytle + '</iframe>';
    intLater = 500;
    break;
   case 'Video':
    strUrl = strUrl.replace(/\?.*$/, '');
    switch(strUrl.toLowerCase().match(/\.(flv|swf|rm|mov|3gp|mp4|asf|avi|mpg|wmv)$/i)[1]){
     case 'flv':
      arrSizes[1] += 22;
      strUrl = settings.flvplayer + '?file=' + strUrl;
     case 'swf':
      arrParam = jQuery.extend({quality:'high',bgcolor:'#000'}, arrParam);
      strSrc = get_flash_src(strUrl, arrSizes[0], arrSizes[1], arrParam, arrUrlParam);
      break;
     case 'rm':
      arrParam = {autostart:	'true'
                 ,controls:	'imagewindow,controlpanel'};
      strSrc = get_embed_src(strUrl, 'CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA', 'audio/x-pn-realaudio-plugin', arrSizes[0], arrSizes[1], arrParam, '');
      break;
     case 'mov':
     case 'mp4':
     case '3gp':
      arrSizes[1] += 20;
      arrParam = {href:		strUrl
                 ,controller:	'true'
                 ,pluginspage:	'http://www.apple.com/quicktime/download/'
                 ,autoplay:	'true'
                 ,bgcolor:	'000000'};
      strSrc = get_embed_src(strUrl, '02BF25D5-8C17-4B23-BC80-D3488ABDDC6B', 'video/quicktime', arrSizes[0], arrSizes[1], arrParam, ' codebase="http://www.apple.com/qtactivex/qtplugin.cab"');
      break;
     default:
      arrSizes[1] += 20;
      arrParam = {href:		strUrl
                 ,autostart:	'true'
                 ,uiMode:	'full'};
      strSrc = get_embed_src(strUrl, '6BF52A52-394A-11d3-B153-00C04F79FAA6', 'application/x-oleobject', arrSizes[0], arrSizes[1], arrParam, '');
      break;
    }
    intLater = 1000;
    break;
  }
  if (strSrc) {
   arrContent[contentNo] = jQuery.extend(contentInfo, {content:strSrc, width:arrSizes[0], height:arrSizes[1], later:intLater});
  }
 }

 var get_flash_src = function(url, width, height, param, url_param) {
  var strSrc = '';
  if (typeof url_param != 'undefined' && url_param.length > 0) {
   jQuery.each(url_param, function(){
    if (url.indexOf(this.replace(/^(.*\=).*$/, '$1')) < 0) url += (url.indexOf('?') < 0 ? '?' : '&amp;') + this;
   });
  }
  var wkParam = {movie: url, wmode: 'transparent'};
  param = (param ? jQuery.extend(wkParam, param) : wkParam);
  strSrc = '<object width="' + width + '" height="' + height + '"' + ' data="' + url + '" type="application/x-shockwave-flash" wmode="' + param.wmode + '">';
  jQuery.each(param, function(key){
   strSrc += '<param name="' + key + '" value="' + this + '" />';
  });
  strSrc += '</object>';
  return strSrc;
 };

 var get_embed_src = function(url, clsid, apl_type, width, height, param, url_param, obj_option) {
  var strSrc = '', strEmb = '';
  var wkParam = {src: url, width: width, height: height, type: apl_type};
  param = (param ? jQuery.extend(wkParam, param) : wkParam);
  strSrc += '<object width="' + width + '" height="' + height + '"' + (clsid != ' ' ? 'classid="clsid:' + clsid + '"' : '') + ' type="' + apl_type + '" ' + obj_option + '>';
  strEmb += '<embed width="' + width + '" height="' + height + '"';
  jQuery.each(param, function(key){
   strSrc += '<param name="' + key + '" value="' + this + '" />';
   strEmb += ' ' + key + '="' + this + '"';
  })
  strEmb += '><noembed><a href="' + url + '">' + url + '</a></noembed></embed>';
  strSrc += strEmb + '</object>';
  return strSrc;
 };

 // set box css
 var set_box_css = function(enable) {
  if (!frameBorder) {
   var jQueryObj = jQuery('#lightpop-box-hd, #lightpop-box-hdc, #lightpop-box-bd, #lightpop-box-bdc, #lightpop-box-ft, #lightpop-box-ftc');
   var bg_transparent = 'transparent', bg_content = settings.contentBgColor;
   if (enable) {
    jQuery('#lightpop-box').css({backgroundColor:bg_transparent});
    jQuery('#lightpop-box-hd').css({background:bg_transparent + ' url(' + settings.imageBox + ') left top no-repeat'});
    jQuery('#lightpop-box-hdc').css({background:bg_transparent + ' url(' + settings.imageBox + ') right top no-repeat'});
    jQuery('#lightpop-box-bd').css({background:bg_content + ' url(' + settings.imageBorderL + ') left top repeat-y'});
    jQuery('#lightpop-box-bdc').css({background:bg_transparent + ' url(' + settings.imageBorderR + ') right top repeat-y'});
    jQuery('#lightpop-box-ft').css({background:bg_transparent + ' url(' + settings.imageBox + ') left bottom no-repeat'});
    jQuery('#lightpop-box-ftc').css({background:bg_transparent + ' url(' + settings.imageBox + ') right bottom no-repeat'});
    jQueryObj.show();
   } else {
    jQuery('#lightpop-box').css({backgroundColor:bg_content});
    jQueryObj.css({background:bg_transparent});
   }
   return jQueryObj;
  }
 };

 var image_set = function(contentNo, setFlag){
  image_load(arrContent[contentNo].href, (function(n, f){return function(){
   var arrSizes = get_sizes_from_str(this.src, new Array(this.width, this.height), new Array(settings.imageMaxWidth, settings.imageMaxHeight));
   arrContent[n] = jQuery.extend(arrContent[n], {content:'<img src="' + this.src.replace(/\?.*$/, '') + '" width="' + arrSizes[0] + '" height="' + arrSizes[1] + '" />', width:arrSizes[0], height:arrSizes[1], later:(jQuery.browser.msie ? 250 : 100)});
   if (f) set_content_to_view(n);
  }})(contentNo, setFlag));
 }

 // image loader
 var image_load = function(src, callback){
  if (typeof objImageLoader[src] == 'undefined') {
   objImageLoader[src] = new Image();
   if (typeof callback == 'function') objImageLoader[src].onload = callback;
   objImageLoader[src].src = src;
  }
  return objImageLoader[src];
 }

 // set visibility
 var set_visibility = function(jQueryObj, enable){
  return jQueryObj.css({visibility:(enable ? 'visible' : 'hidden')});
 }

 // set keydown event
 var keyboard_navigation = function(enable, contentNo){
  jQuery(document).unbind('keydown');
  if (enable) {
   jQuery(document).keydown(function(objEvent){
    var key,keycode,escapeKey;
    if(!objEvent){
     keycode = event.keyCode;
     escapeKey = 27;
    } else {
     keycode = objEvent.keyCode;
     escapeKey = objEvent.DOM_VK_ESCAPE;
    }
    key = String.fromCharCode(keycode).toLowerCase();
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
  jQuery(window).unbind('resize');
  if (enable) {
   jQuery(window).resize(function(){
    var arrPageSizes  = get_page_sizes();
    var arrPageScroll = get_page_scroll();
    jQuery('#overlay').css({width:arrPageSizes[0], height:arrPageSizes[1]});
    jQuery('#lightpop').css({top:arrPageScroll[1] + Math.round(arrPageSizes[3] / 10), left:arrPageScroll[0]});
   });
  }
 };

 // get page sizes
 var get_page_sizes = function(){
  var xScroll,yScroll, windowWidth, windowHeight, pageHeight, pageWidth;
  if (window.innerHeight && window.scrollMaxY) {
   xScroll = window.innerWidth  + window.scrollMaxX;
   yScroll = window.innerHeight + window.scrollMaxY;
  } else if (document.body.scrollHeight > document.body.offsetHeight) {
   xScroll = document.body.scrollWidth;
   yScroll = document.body.scrollHeight;
  } else {
   xScroll = document.body.offsetWidth;
   yScroll = document.body.offsetHeight;
  }
  if (self.innerHeight) {
   if(document.documentElement.clientWidth){
    windowWidth = document.documentElement.clientWidth;
   } else {
    windowWidth = self.innerWidth;
   }
   windowHeight = self.innerHeight;
  } else if (document.documentElement && document.documentElement.clientHeight) {
   windowWidth  = document.documentElement.clientWidth;
   windowHeight = document.documentElement.clientHeight;
  } else if (document.body) {
   windowWidth  = document.body.clientWidth;
   windowHeight = document.body.clientHeight;
  }
  pageWidth  = (xScroll < windowWidth  ? xScroll : windowWidth);
  pageHeight = (yScroll < windowHeight ? windowHeight : yScroll);
  return new Array(pageWidth, pageHeight, windowWidth, windowHeight);
 };

 // get page scroll
 var get_page_scroll = function(){
  var xScroll, yScroll;
  if(self.pageYOffset){
   yScroll = self.pageYOffset;
   xScroll = self.pageXOffset;
  }else if(document.documentElement && document.documentElement.scrollTop){
   yScroll = document.documentElement.scrollTop;
   xScroll = document.documentElement.scrollLeft;
  }else if(document.body){
   yScroll = document.body.scrollTop;
   xScroll = document.body.scrollLeft;
  }
  return new Array(xScroll, yScroll);
 };

 // finish!
 var finish_lightpop = function(){
  set_visibility(jQuery('object',jQuery('#lightpop')), false).remove();
  jQuery('#lightpop').slideUp(function(){
   jQuery(this).remove();
   jQuery('#overlay').fadeOut(function(){
    jQuery(this).remove();
    // show embed, object, select element
    set_visibility(jQuery('embed, object, select'), true);
   });
  });
  keyboard_navigation(false);
  window_resize(false);
 };

 return initialize(this);
};})(jQuery);
