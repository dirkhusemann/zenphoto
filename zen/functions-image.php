<?php

// functions-image.php - HEADERS NOT SENT YET!

/*

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

*/

function unsharp_mask($img, $amount, $radius, $threshold) {
  // Awaiting permission from the author to release under GPL.
  return $img;
}



?>
