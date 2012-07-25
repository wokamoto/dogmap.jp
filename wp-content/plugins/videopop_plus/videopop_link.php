<?php
/*
VideoPop+ Plugin - Get Video Object
Version: 0.7.4

 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
*/
if (!class_exists('wokController') || !class_exists('wokScriptManager'))
	require(dirname(__FILE__).'/includes/common-controller.php');

define ('VIDEOPOP_PLAYER_AUTO_START', false);

function getVideoPopObject($vid='', $cap_flg=true, $flvplayer='swf/mediaplayer.swf', $lynk_err_note='Sorry, video not available.', $video_sizes='') {
	$upload_dir    = 'wp-content/videopop/';
	$plugin_dir    = 'wp-content/plugins/videopop_plus/';
	$video_data    = 'videopopdata.txt';

	if (!is_array($video_sizes)) {
		$video_sizes = array(
			 160 => array('width' => 160, 'height' => 120, 'note' => '')
			,320 => array('width' => 320, 'height' => 240, 'note' => '')
			,640 => array('width' => 640, 'height' => 480, 'note' => '')
			,719 => array('width' => 720, 'height' => 252, 'note' => '')
			,450 => array('width' => 450, 'height' => 120, 'note' => ' [16:9 - 1.78:1]')
			,480 => array('width' => 480, 'height' => 260, 'note' => '')
			,720 => array('width' => 720, 'height' => 405, 'note' => ' [16:9 - 1.78:1]')
			,721 => array('width' => 720, 'height' => 390, 'note' => ' [16:9 - 1.85:1]')
		);
	}

	$vide_autoplay = (defined('VIDEOPOP_PLAYER_AUTO_START') && VIDEOPOP_PLAYER_AUTO_START ? 'true' : 'false');
	$video_tmpl  = array(
		 'real' => "<object id=\"realplayer\" classid=\"clsid:cfcdaa03-8be4-11cf-b84b-0020afbbccfa\" width=\"%1\$s\" height=\"%2\$s\"><param name=\"src\" value=\"%3\$s\" /><param name=\"autostart\" value=\"$vide_autoplay\" /><param name=\"controls\" value=\"imagewindow,controlpanel\" /><embed src=\"%3\$s\" width=\"%1\$s\" height=\"%2\$s\" autostart=\"$vide_autoplay\" controls=\"imagewindow,controlpanel\" type=\"audio/x-pn-realaudio-plugin\"></embed></object>"
		,'mov'  => "<object classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\" width=\"%1\$s\" height=\"%2\$s\" codebase=\"http://www.apple.com/qtactivex/qtplugin.cab\"><param name=\"src\" value=\"%3\$s\" /><param name=\"href\" value=\"%3\$s\" /><param name=\"controller\" value=\"true\" /><param name=\"pluginspage\" value=\"http://www.apple.com/quicktime/download/\" /><param name=\"autoplay\" value=\"true\" /><param name=\"bgcolor\" value=\"000000\" /><embed src=\"%3\$s\" width=\"%1\$s\" height=\"%2\$s\" bgcolor=\"000000\" autoplay=\"true\" controller=\"true\" src=\"%3\$s\" type=\"video/quicktime\" pluginspage=\"http://www.apple.com/quicktime/download/\"></embed></object>"
		,'wmv'  => "<object id=\"winplayer\" classid=\"clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6\" width=\"%1\$s\" height=\"%2\$s\" type=\"application/x-oleobject\"><param name=\"URL\" value=\"%3\$s\" /><param name=\"AutoStart\" value=\"$vide_autoplay\" /><param name=\"uiMode\" value=\"full\" /><embed width=\"%1\$s\" height=\"%2\$s\" autostart=\"" . (defined('VIDEOPOP_PLAYER_AUTO_START') && VIDEOPOP_PLAYER_AUTO_START ? '1' : '0') . "\" autorewind=\"1\" showstatusbar=\"0\" showcontrols=\"1\" displaysize=\"1\" autosize=\"1\" allowchangedisplaysize=\"1\" pluginurl=\"http://www.microsoft.com/windows/windowsmedia\" type=\"video/x-ms-wmv-plugin\" name=\"winplayer\" src=\"%3\$s\"></embed></object>"
		,'mpg'  => "<object id=\"winplayer\" classid=\"clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6\" width=\"%1\$s\" height=\"%2\$s\" type=\"application/x-oleobject\"><param name=\"url\" value=\"%3\$s\" /><param name=\"autostart\" value=\"$vide_autoplay\" /><param name=\"uiMode\" value=\"full\" /><embed src=\"%3\$s\" width=\"%1\$s\" height=\"%2\$s\" autostart=\"" . (defined('VIDEOPOP_PLAYER_AUTO_START') && VIDEOPOP_PLAYER_AUTO_START ? '1' : '0') . "\" uiMode=\"full\" type=\"application/x-mplayer2\" pluginspage=\"http://www.microsoft.com/windows/mediaplayer/\"></embed></object>"
		,'swf'  => "<object data=\"%3\$s\" type=\"application/x-shockwave-flash\" width=\"%1\$s\" height=\"%2\$s\" wmode=\"transparent\"><param name=\"movie\" value=\"%3\$s\" /><param name=\"quality\" value=\"high\" /><param name=\"bgcolor\" value=\"#000000\" /><param name=\"wmode\" value=\"transparent\" /></object>"
		);

	if($vid != '') {
		$site_url = '';
		$php_url  = '';
		if (function_exists('get_settings')) {
			$site_url = get_settings('siteurl').'/';
			$php_url = $site_url.$plugin_dir;
		} else {
			$php_url = dirname($_SERVER['REQUEST_URI'])."/";
			if (!strstr($php_url,'http://')) {$php_url = 'http://'.$_SERVER['SERVER_NAME'].$php_url;}
			$site_url = substr($php_url,0,strpos($php_url,'wp-content/'));
		}
		$flvplayer = $php_url.$flvplayer;

		// Get Video Data
		$video_dir  = '';
		if (defined('ABSPATH')) {
			$video_dir = ABSPATH.$upload_dir;
		} else {
			$video_dir = realpath('./../../../'.$upload_dir)."/";
		}
		$video_data = $video_dir.$video_data;
		if(file_exists($video_data)) {$a_vids = unserialize(file_get_contents($video_data));}

		// Check for not deleted
		if(is_array($a_vids) && array_key_exists($vid,$a_vids)) {
			// Check file or url source
			$src = "";

			// Video URL
			if(!empty($a_vids[$vid]['lynkvp_url'])) {
				$src = 'http://'.$a_vids[$vid]['lynkvp_url'];
			} elseif(file_exists($video_dir.$a_vids[$vid]['lynkvp_id'].'.'.$a_vids[$vid]['lynkvp_type'])) {
				$src = $site_url.$upload_dir.$a_vids[$vid]['lynkvp_id'].'.'.$a_vids[$vid]['lynkvp_type'];
			}

			// Video Size
			if (isset($video_sizes[$a_vids[$vid]['lynkvp_size']])) {
				$width   = $video_sizes[$a_vids[$vid]['lynkvp_size']]['width'];
				$height  = $video_sizes[$a_vids[$vid]['lynkvp_size']]['height'];
			} else {
				$width   = (isset($a_vids[$vid]['lynkvp_width'])  ? $a_vids[$vid]['lynkvp_width']  : 640);
				$height  = (isset($a_vids[$vid]['lynkvp_height']) ? $a_vids[$vid]['lynkvp_height'] : 480);
			}

			// Video Caption
			$caption = stripslashes($a_vids[$vid]['lynkvp_caption']);

			// Video Thumbnail Image
			$image = (
				!empty($a_vids[$vid]['lynkvp_image'])
				? 'http://'.$a_vids[$vid]['lynkvp_image']
				: ''
				);

			// Video Type
			if ($src != "") {
				switch($a_vids[$vid]['lynkvp_type']) {
					case('rm'):
						$movie_type = 'real';
						break;
					case('mov'):
					case('3gp'):
					case('mp4'):
						$movie_type = 'mov';
						$height += 16;
						break;
					case('flv'):
						$movie_type = 'flv';
						$src = $flvplayer."?file=".$src;
						if ($image != '') {$src .= "&amp;image=".$image;}
						$height += 22;
					case('swf'):
						$movie_type = 'swf';
						break;
					case('wmv'):
						$movie_type = 'wmv';
						$height += 16;
						break;
					default:
						$movie_type = 'mpg';
						$height += 16;
						break;
				}

				// Set Return Value
				$out = (
					$cap_flg
					? sprintf($video_tmpl[$movie_type]."<br />%4\$s",$width,$height,$src,$caption)
					: sprintf($video_tmpl[$movie_type],$width,$height,$src)
					);

			} else {
				$out = $lynk_err_note;
			}
		}// if key exists
		unset($a_vids);

	} else {
		$out = $lynk_err_note;
	}

	unset($video_sizes);
	unset($video_tmpl);

	return $out;
}
?>
