// jQuery.jsonp.js
(function(A){A.extend({_jsonp:{scripts:{},charset:"utf-8",counter:1,head:document.getElementsByTagName("head")[0],name:function(callback){var name="_jsonp_"+(new Date).getTime()+"_"+this.counter;this.counter++;var cb=function(json){eval("delete "+name);callback(json);A._jsonp.head.removeChild(A._jsonp.scripts[name]);delete A._jsonp.scripts[name]};eval(name+" = cb");return name},load:function(url,name){var script=document.createElement("script");script.type="text/javascript";script.charset=this.charset;script.src=url;this.head.appendChild(script);this.scripts[name]=script}},getJSONP:function(url,callback){var name=A._jsonp.name(callback);var url=url.replace(/{callback}/,name);A._jsonp.load(url,name);return this}})})(jQuery);

jQuery(function(){
 var ld_user = 'wokamoto_1973';
 jQuery.getJSONP(
  'http://api.clip.livedoor.com/json/clips?livedoor_id=' + ld_user + '&limit=5&callback={callback}',
  function(d) {
   var ul = jQuery('<ul></ul>');
   jQuery(d.clips).each(function(){ul.append(jQuery('<li><a href="' + this.link + '" title="' + (this.notes=='' ? '' : this.notes + ' -- ') + this.title + '">' + this.title + '</a></li>'));});
   jQuery('#livedoor-clips').empty().append(ul).append(jQuery('<div style="margin-top:0.5em;text-align:right;"><p><a href="http://clip.livedoor.com/clips/' + ld_user + '" title="livedoor clips">...more</a></p></div>')).append(jQuery('<div style="margin-top:0.5em;text-align:right;"><p>Powered by <strong><a href="http://clip.livedoor.com/">Livedoor Clips!</a></strong></p></div>'));
  }
 );
});
