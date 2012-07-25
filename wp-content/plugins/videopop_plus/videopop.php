<?php
/*
VideoPop+ Plugin - Player Window
Version: 0.7.3

 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

 this script based on
  VideoPop v.1.3.1(http://www.lynk.de/wordpress/videopop/)
  thanks to Marcus Grellert.
*/

error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(0);

if (!class_exists('VideoPopPlus'))
	require_once("./videopop_plus.php");

if (!function_exists('getVideoPopObject'))
	require_once("./videopop_link.php");

$vid = isset($_GET['vid'])
	? stripslashes($_GET['vid'])
	: '';

$cap = isset($_GET['cap'])
	? stripslashes((strtolower($_GET['cap'])) != 'false')
	: true;

$flv = file_exists('swf/mediaplayer.swf') 
	? 'swf/mediaplayer.swf' 
	: (file_exists('mediaplayer.swf') ? 'mediaplayer.swf' : 'flvplayer.swf');

unset($_GET);

$VideoPopPlus = new VideoPopPlus();
$video_tag = getVideoPopObject($vid, $cap, $flv, $VideoPopPlus->getText('Sorry, video not available.'));
unset($VideoPopPlus);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Video</title>
<style type="text/css" media="screen">/*<![CDATA[*/
* {margin:0; padding:0;}
body {width:100%; height:100%; margin:1em;}
/*]]>*/</style>
</head>
<body>
<div align="center">
<?php echo $video_tag; ?>
</div>
</body>
</html>
