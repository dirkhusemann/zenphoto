<?php
$exif = exif_read_data($filename);
$ort = $exif['IFD0']['Orientation'];
		switch($ort)
		{
				case 1: // nothing
				break;

				case 2: // horizontal flip
						$image->flipImage($public,1);
				break;
 															
				case 3: // 180 rotate left
						$image->rotateImage($public,180);
				break;
 									
				case 4: // vertical flip
						$image->flipImage($public,2);
				break;
 							
				case 5: // vertical flip + 90 rotate right
						$image->flipImage($public, 2);
								$image->rotateImage($public, -90);
				break;
 							
				case 6: // 90 rotate right
						$image->rotateImage($public, -90);
				break;
 							
				case 7: // horizontal flip + 90 rotate right
						$image->flipImage($public,1);   
						$image->rotateImage($public, -90);
				break;
 							
				case 8:    // 90 rotate left
						$image->rotateImage($public, 90);
				break;
		}

?>