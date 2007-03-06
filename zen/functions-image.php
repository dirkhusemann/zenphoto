<?php

// functions-image.php - HEADERS NOT SENT YET!

/**
 * Show an error image if an image was requested, and 
 */
function imageError($errormessage, $errorimg='err-imagegeneral.gif') {
  global $newfilename, $album, $image;
  $debug = isset($_GET['debug']);
  if ($debug) {
    echo('<strong>Zenphoto Image Processing Error:</strong> ' . $errormessage
      . '<br /><br />Request URI: [ <code>' . sanitize($_SERVER['REQUEST_URI'], true) . '</code> ]'
      . '<br />PHP_SELF:    [ <code>' . sanitize($_SERVER['PHP_SELF'], true) . '</code> ]'
      . (empty($newfilename) ? '' : '<br />Cache: [<code> /cache'  . sanitize($newfilename, true) . '</code>]')
      . (empty($image) || empty($album) ? '' : ' <br />Image: [<code> '.sanitize($album.'/'.$image, true).' </code>]<br />'));
  } else {
    header('Location: ' . FULLWEBPATH . '/zen/images/' . $errorimg);
    exit();
  }
}

function imageDebug($args) {
  list($size, $width, $height, $cw, $ch, $cx, $cy, $quality, $thumb, $crop) = $args;
  echo "<strong>DEBUG <code>i.php</code> | Arguments:</strong><br />\n\n"
    .  "<ul><li>size =    <strong>" . sanitize($size, true)     . "</strong></li>\n"
    .  "<li>width =   <strong>" . sanitize($width, true)    . "</strong></li>\n"
    .  "<li>height =  <strong>" . sanitize($height, true)   . "</strong></li>\n"
    .  "<li>cw =      <strong>" . sanitize($cw, true)       . "</strong></li>\n"
    .  "<li>ch =      <strong>" . sanitize($ch, true)       . "</strong></li>\n"
    .  "<li>cx =      <strong>" . sanitize($cx, true)       . "</strong></li>\n"
    .  "<li>cy =      <strong>" . sanitize($cy, true)       . "</strong></li>\n"
    .  "<li>quality = <strong>" . sanitize($quality, true)  . "</strong></li>\n"
    .  "<li>thumb =   <strong>" . sanitize($thumb, true)    . "</strong></li>\n"
    .  "<li>crop =    <strong>" . sanitize($crop, true)     . "</strong></li></ul>\n";
    
}



/**

WARNING! Due to a known bug in PHP 4.3.2 this script is not working well in this
version. The sharpened images get too dark. The bug is fixed in version 4.3.3.

From version 2 (July 17 2006) the script uses the imageconvolution function in
PHP version >= 5.1, which improves the performance considerably.

Unsharp masking is a traditional darkroom technique that has proven very
suitable for digital imaging. The principle of unsharp masking is to create a
blurred copy of the image and compare it to the underlying original. The
difference in colour values between the two images is greatest for the pixels
near sharp edges. When this difference is subtracted from the original image,
the edges will be accentuated.

The Amount parameter simply says how much of the effect you want. 100 is
'normal'. Radius is the radius of the blurring circle of the mask. 'Threshold'
is the least difference in colour values that is allowed between the original
and the mask. In practice this means that low-contrast areas of the picture are
left unrendered whereas edges are treated normally. This is good for pictures of
e.g. skin or blue skies.

Any suggenstions for improvement of the algorithm, expecially regarding the
speed and the roundoff errors in the Gaussian blur process, are welcome.

Permission to license this code under the GPL was granted by the author on 2/12/2007.

*/
function unsharp_mask($img, $amount, $radius, $threshold) {
  /*
  Unsharp Mask for PHP - version 2.0
  Unsharp mask algorithm by Torstein Hønsi 2003-06.
  Please leave this notice.
  */

  // $img is an image that is already created within php using
  // imgcreatetruecolor. No url! $img must be a truecolor image.

  // Attempt to calibrate the parameters to Photoshop:
  if ($amount > 500)    $amount = 500;
  $amount = $amount * 0.016;
  if ($radius > 50)    $radius = 50;
  $radius = $radius * 2;
  if ($threshold > 255)    $threshold = 255;
  
  $radius = abs(round($radius));     // Only integers make sense.
  if ($radius == 0) return $img;
  $w = imagesx($img); $h = imagesy($img);
  $imgCanvas = imagecreatetruecolor($w, $h);
  $imgCanvas2 = imagecreatetruecolor($w, $h);
  $imgBlur = imagecreatetruecolor($w, $h);
  $imgBlur2 = imagecreatetruecolor($w, $h);
  imagecopy ($imgCanvas, $img, 0, 0, 0, 0, $w, $h);
  imagecopy ($imgCanvas2, $img, 0, 0, 0, 0, $w, $h);
  

  // Gaussian blur matrix:
  //    1    2    1
  //    2    4    2
  //    1    2    1
  //////////////////////////////////////////////////
  
  imagecopy($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h); // background    
  
  for ($i = 0; $i < $radius; $i++)    {
    if (function_exists('imageconvolution')) { // PHP >= 5.1
      $matrix = array(
        array( 1, 2, 1 ),
        array( 2, 4, 2 ),
        array( 1, 2, 1 )
      );
      imageconvolution($imgCanvas, $matrix, 16, 0);
    } else {
            
      // Move copies of the image around one pixel at the time and merge them with weight
      // according to the matrix. The same matrix is simply repeated for higher radii.
              
      imagecopy      ($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
      imagecopymerge ($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
      imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
      imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
      imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
      imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
      imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20 ); // up
      imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down
      imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
      imagecopy      ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

      // During the loop above the blurred copy darkens, possibly due to a roundoff
      // error. Therefore the sharp picture has to go through the same loop to
      // produce a similar image for comparison. This is not a good thing, as processing
      // time increases heavily.
      imagecopy      ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h);
      imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
      imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
      imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
      imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
      imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
      imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 20 );
      imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 16.666667);
      imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
      imagecopy      ($imgCanvas2, $imgBlur2, 0, 0, 0, 0, $w, $h);
    }
  }

  // Calculate the difference between the blurred pixels and the original
  // and set the pixels
  for ($x = 0; $x < $w; $x++)    { // each row
    for ($y = 0; $y < $h; $y++)    { // each pixel
          
      $rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
      $rOrig = (($rgbOrig >> 16) & 0xFF);
      $gOrig = (($rgbOrig >> 8) & 0xFF);
      $bOrig = ($rgbOrig & 0xFF);
      
      $rgbBlur = ImageColorAt($imgCanvas, $x, $y);
      
      $rBlur = (($rgbBlur >> 16) & 0xFF);
      $gBlur = (($rgbBlur >> 8) & 0xFF);
      $bBlur = ($rgbBlur & 0xFF);
      
      // When the masked pixels differ less from the original
      // than the threshold specifies, they are set to their original value.
      $rNew = (abs($rOrig - $rBlur) >= $threshold)
          ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
          : $rOrig;
      $gNew = (abs($gOrig - $gBlur) >= $threshold)
          ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
          : $gOrig;
      $bNew = (abs($bOrig - $bBlur) >= $threshold)
          ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
          : $bOrig;
                  
      if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
        $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
        ImageSetPixel($img, $x, $y, $pixCol);
      }
    }
  }
  return $img;
}



?>
