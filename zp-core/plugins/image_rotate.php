<?php
function imageRotate($src_img, $angle) {

		$src_x = imagesx($src_img);
		$src_y = imagesy($src_img);
		if ($angle == 90 || $angle == -90) {
				$dest_x = $src_y;
				$dest_y = $src_x;
		} else {
				$dest_x = $src_x;
				$dest_y = $src_y;
		}

		$rotate=imagecreatetruecolor($dest_x,$dest_y);
		imagealphablending($rotate, false);

		switch ($angle) {
				case 90:
						for ($y = 0; $y < ($src_y); $y++) {
								for ($x = 0; $x < ($src_x); $x++) {
										$color = imagecolorat($src_img, $x, $y);
										imagesetpixel($rotate, $dest_x - $y - 1, $x, $color);
								}
						}
						break;
				case -90:
						for ($y = 0; $y < ($src_y); $y++) {
								for ($x = 0; $x < ($src_x); $x++) {
										$color = imagecolorat($src_img, $x, $y);
										imagesetpixel($rotate, $y, $dest_y - $x - 1, $color);
								}
						}
						break;
				case 180:
						for ($y = 0; $y < ($src_y); $y++) {
								for ($x = 0; $x < ($src_x); $x++) {
										$color = imagecolorat($src_img, $x, $y);
										imagesetpixel($rotate, $dest_x - $x - 1, $dest_y - $y - 1, $color);
								}
						}
						break;
				default: $rotate = $src_img;
		};
		return $rotate;
}
?>