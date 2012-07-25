var HOST;
var PATH;

function applyCookie(id, path, host) {
	HOST = host;
	PATH = path;
	var form = document.forms['commentsForm'];
	form.email.value = getCookie("mtcmtmail");
	form.author.value = getCookie("mtcmtauth");
	form.url.value = getCookie("mtcmthome");
	if (getCookie("mtcmtauth")) {
		form.bakecookie[0].checked = true;
	} else {
		form.bakecookie[1].checked = true;
	}
}

// Copyright (c) 1996-1997 Athenia Associates.
// http://www.webreference.com/js/
// License is granted if and only if this entire
// copyright notice is included. By Tomer Shiran.

function setCookie(name, value, expires, path, domain, secure) {
    var curCookie = name + "=" + escape(value) + ((expires) ? "; expires=" + expires.toGMTString() : "") + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + ((secure) ? "; secure" : "");
    document.cookie = curCookie;
}

function getCookie(name) {
    var prefix = name + '=';
    var c = document.cookie;
    var nullstring = '';
    var cookieStartIndex = c.indexOf(prefix);
    if (cookieStartIndex == -1)
        return nullstring;
    var cookieEndIndex = c.indexOf(";", cookieStartIndex + prefix.length);
    if (cookieEndIndex == -1)
        cookieEndIndex = c.length;
    return unescape(c.substring(cookieStartIndex + prefix.length, cookieEndIndex));
}

function deleteCookie(name, path, domain) {
    if (getCookie(name))
        document.cookie = name + "=" + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + "; expires=Thu, 01-Jan-70 00:00:01 GMT";
}

function fixDate (date) {
    var base = new Date(0);
    var skew = base.getTime();
    if (skew > 0)
        date.setTime(date.getTime() - skew);
}

function rememberMe(f) {
    var now = new Date();
    fixDate(now);
    now.setTime(now.getTime() + 365 * 24 * 60 * 60 * 1000);
    setCookie('mtcmtauth', f.author.value, now, PATH, HOST, '');
    setCookie('mtcmtmail', f.email.value, now, PATH, HOST, '');
    setCookie('mtcmthome', f.url.value, now, PATH, HOST, '');
}

function forgetMe(f) {
    deleteCookie('mtcmtmail', PATH, HOST);
    deleteCookie('mtcmthome', PATH, HOST);
    deleteCookie('mtcmtauth', PATH, HOST);
}
