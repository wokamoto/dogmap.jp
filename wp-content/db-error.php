<?php
header( 'HTTP/1.1 503 Service Unavailable' );
//header( 'Status: 500 Internal Server Error' );
header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
header( 'Pragma: no-cache' );
header( 'Content-Type: text/html; charset=utf-8' );
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>データベースエラー : dogmap.jp</title>
	<link rel="stylesheet" href="/error/style.css" type="text/css" />
</head>
<body id="error-page">
<h1>503 Service Unavailable - DB Error</h1>
<p>ただいまサーバのデータベースに接続できないようです。<br />
数分後に再度リロードしてみてください。</p>
<p>ご不便おかけしますが、よろしくお願いいたします。</p>
<p style="text-align: right;"><a href="http://dogmap.jp/">http://dogmap.jp/</a></p>
</body>
</html>
