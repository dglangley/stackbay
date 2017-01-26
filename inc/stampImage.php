<?php
	$opacity = .5;
	$transparency = 1 - $opacity;
	// Load the stamp and the photo to apply the watermark to
	$STAMP = imagecreatefrompng($_SERVER["ROOT_DIR"].'/img/logo.png');
	imagealphablending($STAMP, false); // imagesavealpha can only be used by doing this for some reason
	imagesavealpha($STAMP, true); // this one helps you keep the alpha. 
	imagefilter($STAMP, IMG_FILTER_COLORIZE, 0,0,0,127*$transparency); // the fourth parameter is alpha
	$STAMP_LG = imagecreatefrompng($_SERVER["ROOT_DIR"].'/img/ventel-watermark-logo.png');
	imagealphablending($STAMP_LG, false); // imagesavealpha can only be used by doing this for some reason
	imagesavealpha($STAMP_LG, true); // this one helps you keep the alpha. 
	imagefilter($STAMP_LG, IMG_FILTER_COLORIZE, 0,0,0,127*$transparency); // the fourth parameter is alpha
	$IMAGE_TMP = sys_get_temp_dir();
	if (substr($IMAGE_TMP,strlen($IMAGE_TMP)-1,1)<>'/') { $IMAGE_TMP .= '/'; }

	function stampImage($image_filename) {
		global $STAMP,$STAMP_LG,$IMAGE_TMP;

		$stamp = $STAMP;
		$dst_im = imagecreatefromjpeg($image_filename);
//		$dst_im = imagecreatefrompng('img/TIMESOURCE-3500.png');

		$im_sx = imagesx($dst_im);
		$im_sy = imagesy($dst_im);

		// width; example: in a 640x480 image, and the watermark is 300x240, the $dst_x is .5 * 640-300 = 170 from left/right
		$sx = imagesx($stamp);
		$sx_lg = imagesx($STAMP_LG);
		// if watermark image is wider than picture, insert stamp at position 0
		if ($im_sx<$sx) {
			$dst_x = 0;
		} else {
			// if the image can contain our larger watermark stamp, use it instead of the smaller
			if ($sx_lg<$im_sx) {
				$stamp =  $STAMP_LG;
				$sx = imagesx($stamp);
			}
			$dst_x = ($im_sx - $sx) * .5;
		}

		// height
		$sy = imagesy($stamp);
		// if watermark image is taller than picture, insert stamp at position 0
		if ($im_sy<$sy) {
			$dst_y = 0;
		} else {
			$dst_y = (($im_sy - $sy) * .5) - 30;
		}

		// Copy the stamp image onto our photo using the margin offsets and the photo 
		// width to calculate positioning of the stamp. 
		imagecopy($dst_im, $stamp, $dst_x, $dst_y, 0, 0, $sx, $sy);
/*
		imagecopy($dst_im, $stamp, 50, 50, 0, 0, $sx, $sy);
		imagecopy($dst_im, $stamp, ($im_sx-($sx-50)), 50, 0, 0, $sx, $sy);
		imagecopy($dst_im, $stamp, ($im_sx-($sx-50)), ($im_sy-($sy-50)), 0, 0, $sx, $sy);
		imagecopy($dst_im, $stamp, 100, 200, 0, 0, $sx, $sy);
*/

		// Output and free memory
/*
		$handle = fopen($attachment, "w");
		// add contents from file
		fwrite($handle, $report);
		fclose($handle);

		header('Content-type: image/png');
		imagepng($dst_im);

*/
		//remove leading dir paths
		$filename_parts = explode('/',$image_filename);
		$new_filename = preg_replace('/([.](png|jpg|jpeg))?$/i','-vttn$1',$filename_parts[(count($filename_parts)-1)]);
		$stamped_filename = $IMAGE_TMP.$new_filename;
		//$stamped_filename = '/devimgs/'.$new_filename;
		imagejpeg($dst_im,$stamped_filename);
		imagedestroy($dst_im);

		return ($stamped_filename);
	}
?>
