<?php
/******************************************************************************
 * WpKougabu Resize - Define
 *****************************************************************************/

define('KOUGABU_FRAME_NAME', 'nashi');
define('KEEP_ASPECT', true);

//for version 1.00 compatible
//define('KOUGABU_FRAME_NAME', 'maru');
//define('KEEP_ASPECT', false);

/******************************************************************************
 * WpKougabu Resize - WordPress Interface Define
 *****************************************************************************/

$path = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
require_once(
	file_exists($path . 'wp-load.php') ? $path . 'wp-load.php' : $path . 'wp-config.php');

if(!class_exists('WpKougabu')) return;

/******************************************************************************
 * WpKougabu Resize
 *
 * @author		hiromasa
 * @version		1.10
 *
 *****************************************************************************/
class WpKougabuRiseze {
	
	var $imageInfo;
	var $frameInfo;
	var $GGuid;
	
	var $guid;
	var $post_id;
	var $width;
	var $height;
	
	/**
	 * WpKougabuRiseze
	 *
	 * @return WpKougabuRiseze
	 */
	function WpKougabuRiseze() {
		
		$this->imageInfo = get_option('wp-kougabu');
		$this->frameSize = array();
		
		$this->guid    = $_GET['guid'];
		$this->post_id = $_GET['post_id'];
		$this->width   = $_GET['width'];
		$this->height  = $_GET['height'];
		
	}
	
	/**
	 * outputPhotoImage
	 *
	 */
	function outputPhotoImage() {
		
		global $wpKougabu;
		//TODO: static call better 
		$cacheFile =
			$wpKougabu->getCachePath($this->guid, $this->width, $this->height);
		$this->GGuid =
			$wpKougabu->getGGuid($this->guid, $this->post_id);
		
		$image = $this->resizeImage($cacheFile);
		header('Content-Type: image/jpg');
		imagejpeg($image, null, 100);
		imagedestroy($image);
		
	}
	
	/**
	 * resizeImage
	 * 
	 * @param $frame
	 */
	function resizeImage($cacheFile) {
		
		$img_width = 120;
		$img_height = 160;
		
		if($this->validateArgs()) {
			$img_width = $this->width;
			$img_height = $this->height;
		}
		
		$img_resized = 
			function_exists('imagecreatetruecolor')
			? @imagecreatetruecolor($img_width, $img_height)
			: @imagecreate($img_width, $img_height);
		if ( $img_resized === FALSE )
			$img_resized = @imagecreate($img_width, $img_height);
		$bgc = imagecolorallocate($img_resized, 255, 255, 255);
		imagefilledrectangle(
			$img_resized, 0, 0, $img_width, $img_height, $bgc);
		
		if(!$this->validateArgs()) return $img_resized;
		
		$frameImgPath =
			dirname(__FILE__)
			. '/images/'
			. KOUGABU_FRAME_NAME;
		$frameTopLeft           = imagecreatefrompng($frameImgPath . '_tl.png');
		$frameTopRight          = imagecreatefrompng($frameImgPath . '_tr.png');
		$frameBottomLeft        = imagecreatefrompng($frameImgPath . '_bl.png');
		$frameBottomRight       = imagecreatefrompng($frameImgPath . '_br.png');
		$frameVerticalLeftLine  = imagecreatefrompng($frameImgPath . '_vl.png');
		$frameVerticalRightLine = imagecreatefrompng($frameImgPath . '_vr.png');
		$frameWidthTopLine      = imagecreatefrompng($frameImgPath . '_wt.png');
		$frameWidthBottomLine   = imagecreatefrompng($frameImgPath . '_wb.png');
		
		$this->frameSize['top_left'][0]      = imagesx($frameTopLeft);
		$this->frameSize['top_left'][1]      = imagesy($frameTopLeft);
		$this->frameSize['top_right'][0]     = imagesx($frameTopRight);
		$this->frameSize['top_right'][1]     = imagesy($frameTopRight);
		$this->frameSize['bottom_left'][0]   = imagesx($frameBottomLeft);
		$this->frameSize['bottom_left'][1]   = imagesy($frameBottomLeft);
		$this->frameSize['bottom_right'][0]  = imagesx($frameBottomRight);
		$this->frameSize['bottom_right'][1]  = imagesy($frameBottomRight);
		$this->frameSize['v_line_left'][0]   = imagesx($frameVerticalLeftLine);
		$this->frameSize['v_line_left'][1]   = imagesy($frameVerticalLeftLine);
		$this->frameSize['v_line_right'][0]  = imagesx($frameVerticalRightLine);
		$this->frameSize['v_line_right'][1]  = imagesy($frameVerticalRightLine);
		$this->frameSize['w_line_top'][0]    = imagesx($frameWidthTopLine);
		$this->frameSize['w_line_top'][1]    = imagesy($frameWidthTopLine);
		$this->frameSize['w_line_bottom'][0] = imagesx($frameWidthBottomLine);
		$this->frameSize['w_line_bottom'][1] = imagesy($frameWidthBottomLine);
		
		imagecopy(
			$img_resized
			, $frameTopLeft
			, 0
			, 0
			, 0, 0
			, $this->frameSize['top_left'][0]
			, $this->frameSize['top_left'][1]);
		imagecopy(
			$img_resized
			, $frameTopRight
			, $img_width  - $this->frameSize['top_right'][0]
			, 0
			, 0, 0
			, $this->frameSize['top_right'][0]
			, $this->frameSize['top_right'][1]);
		imagecopy(
			$img_resized
			, $frameBottomLeft
			, 0
			, $img_height - $this->frameSize['bottom_left'][1]
			, 0, 0
			, $this->frameSize['bottom_left'][0]
			, $this->frameSize['bottom_left'][1]);
		imagecopy(
			$img_resized
			, $frameBottomRight
			, $img_width  - $this->frameSize['bottom_right'][0]
			, $img_height - $this->frameSize['bottom_right'][1]
			, 0, 0
			, $this->frameSize['bottom_right'][0]
			, $this->frameSize['bottom_right'][1]);
		
		imagedestroy($frameTopLeft);
		imagedestroy($frameTopRight);
		imagedestroy($frameBottomLeft);
		imagedestroy($frameBottomRight);
		
		for($y = $this->frameSize['top_left'][1];
			$y < $img_height - $this->frameSize['bottom_left'][1]; $y++) {
			imagecopy(
				$img_resized
				, $frameVerticalLeftLine
				, 0
				, $y
				, 0, 0
				, $this->frameSize['v_line_left'][0]
				, $this->frameSize['v_line_left'][1]);
			imagecopy(
				$img_resized
				, $frameVerticalRightLine
				, $img_width  - $this->frameSize['v_line_right'][0]
				, $y
				, 0, 0
				, $this->frameSize['v_line_right'][0]
				, $this->frameSize['v_line_right'][1]);
		}
		for($x = $this->frameSize['top_left'][0];
			$x < $img_width - $this->frameSize['bottom_right'][0]; $x++) {
			imagecopy(
				$img_resized
				, $frameWidthTopLine
				, $x
				, 0
				, 0, 0
				, $this->frameSize['w_line_top'][0]
				, $this->frameSize['w_line_top'][1]);
			imagecopy(
				$img_resized
				, $frameWidthBottomLine
				, $x
				, $img_height  - $this->frameSize['w_line_bottom'][1]
				, 0, 0
				, $this->frameSize['w_line_bottom'][0]
				, $this->frameSize['w_line_bottom'][1]);
		}
		
		imagedestroy($frameVerticalLeftLine);
		imagedestroy($frameVerticalRightLine);
		imagedestroy($frameWidthTopLine);
		imagedestroy($frameWidthBottomLine);
		
		$snoopy = new Snoopy;
		$snoopy->read_timeout = 30;
		$snoopy->timed_out = true;
		$snoopy->fetch($this->guid);
		$imgbin = $snoopy->results;
		$http_code = $snoopy->response_code;
		
		if(strpos($http_code, '200') === false)
			return $img_resized;

		$img = @imagecreatefromstring($imgbin);
		if($img === false)
			return $img_resized;
		
		$img_width_org = imagesx($img);
		$img_height_org = imagesy($img);
		
		$img_convert_width = 
			$img_width - $this->frameSize['bottom_right'][0] - $this->frameSize['top_left'][0];
		$img_convert_height = 
			$img_height- $this->frameSize['bottom_right'][1] - $this->frameSize['top_left'][1];
		$offset_x = 0;
		$offset_y = 0;
		
		if(KEEP_ASPECT) {
			$image_size = wp_constrain_dimensions(
				$img_width_org
				, $img_height_org
				, $img_convert_width
				, $img_convert_height);
			$offset_x = ($img_convert_width  - $image_size[0]) / 2;
			$offset_y = ($img_convert_height - $image_size[1]) / 2;
			$img_convert_width = $image_size[0];
			$img_convert_height = $image_size[1]; 
		}
		
		imagecopyresampled(
			$img_resized,
			$img,
			$this->frameSize['top_left'][0] + $offset_x,
			$this->frameSize['top_left'][1] + $offset_y,
			0,
			0,
			$img_convert_width,
			$img_convert_height,
			$img_width_org,
			$img_height_org);
		
		@imagejpeg($img_resized, $cacheFile, 100);
		
		return $img_resized;
	}
	
	/**
	 * validateArgs
	 *
	 * @return boolean
	 */
	function validateArgs() {
		
		$gguid = $this->GGuid;
		
		if($this->imageInfo[$gguid]['link_url'] == '') {
			return false;
		}
		if(!is_numeric($this->width) || !is_numeric($this->height)) {
			return false;
		}
		$sizes = $this->imageInfo[$gguid]['cached'][md5($this->width . $this->height)];
		list($width, $height) = $sizes;
		if(!($this->width == $width && $this->height == $height)) {
			return false;
		}
		
		return true;
		
	}
	
}

/******************************************************************************
 * WpKougabu Resize - Main
 *****************************************************************************/
require_once(ABSPATH . WPINC . '/class-snoopy.php');

$wpKougabuRisize = & new WpKougabuRiseze();
$wpKougabuRisize->outputPhotoImage();
?>
