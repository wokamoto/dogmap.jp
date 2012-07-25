function topsyCallback(json) {
    var res, html, tweet, total, thumb, author_id;
    res = json.response;
    if ( !res.total ) {
        return false;
    }
	total = 0;
    html = '<ul style="list-style:none;margin:0 0 5px 0;padding:0;">';
    for ( var i=0; i<res.list.length; i++ ) {
        tweet     = res.list[i];
        thumb     = tweet.author.photo_url.replace(/(normal)\.([a-z]{3,4})$/i,'mini.$2');
        author_id = tweet.author.url.replace('http://twitter.com/','');
		if ( author_id != 'dogmap_jp' ) {
	        html
	            += '<li style="margin:0;padding:1px;font:11px/16px sans-serif;color:#333;white-space:pre;overflow:hidden;">'
	            +  '<a href="'+tweet.author.url+'" target="_blank">'
	            +  '<img src="'+thumb+'" alt="'+tweet.author.name+'" style="border:0;vertical-align:middle;width:24px;height:24px;" />'
	            +  '</a> '
	            +  '<a href="'+tweet.author.url+'" target="_blank" style="color:#0084B4;">'
	            +  author_id
	            +  '</a> '
	            +  tweet.content.replace(/(\r\n|\r|\n)/g,'')
	            +  '</li>';
			total++;
		}
    }
    html += '</ul>';
    if ( res.total > 10 ) {
        html
            += '<div>'
            +  '<a href="'+res.topsy_trackback_url+'" target="_blank" style="display:inline-block;margin:0;padding:5px;font:14px/16px sans-serif;color:#0084B4;text-decoration:none;border:1px solid #CCC;background:#EEE;-moz-border-radius:5px;-webkit-border-radius:5px;\">'
            +  'もっと読む'
            +  '</a>'
            +  '</div>';
    }
    if ( total > 0 ) {
	    if ( document.getElementById('topsy_counter') ) {
	        document.getElementById('topsy_counter').innerHTML = '（' + res.total + ' tweets）';
	    }
	    if ( document.getElementById('topsy_trackbacks') ) {
	        document.getElementById('topsy_trackbacks').innerHTML = html;
	    }
    }
}

