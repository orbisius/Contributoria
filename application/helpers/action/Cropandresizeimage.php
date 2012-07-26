<?php

/**
 * 
 * Function to crop and resize and image
 * http://stackoverflow.com/questions/999250/php-gd-cropping-and-resizing-images
 * 
 * Example params
 * @param $path '/images/cat_lolz.jpg'
 * @param $image 'cat.jpg'
 * @param $crop '16:9'
 * @param $size '190x'
 */
class Helper_Action_Cropandresizeimage extends Zend_Controller_Action_Helper_Abstract {

    public function setparams($path, $image, $crop = null, $size = null, $maxsize = null) {

        $image = ImageCreateFromString(file_get_contents($image));

        if (is_resource($image) === true) {
            $x = 0;
            $y = 0;
            $width = imagesx($image);
            $height = imagesy($image);
            
            /*
              CROP (Aspect Ratio) Section
             */

            if (is_null($crop) === true) {
                $crop = array($width, $height);
            } else {
                $crop = array_filter(explode(':', $crop));

                if (empty($crop) === true) {
                    $crop = array($width, $height);
                } else {
                    if ((empty($crop[0]) === true) || (is_numeric($crop[0]) === false)) {
                        $crop[0] = $crop[1];
                    } else if ((empty($crop[1]) === true) || (is_numeric($crop[1]) === false)) {
                        $crop[1] = $crop[0];
                    }
                }

                $ratio = array(0 => $width / $height, 1 => $crop[0] / $crop[1]);

                if ($ratio[0] > $ratio[1]) {
                    $width = $height * $ratio[1];
                    $x = (imagesx($image) - $width) / 2;
                } else if ($ratio[0] < $ratio[1]) {
                    $height = $width / $ratio[1];
                    $y = (imagesy($image) - $height) / 2;
                }
            }
            
            /*
              Resize Section
             */

            if (is_null($size) === true) {
                /*
                Max Resize Section
                */
                if (is_null($maxsize) === true) {
                    $size = array(imagesx($image), imagesy($image));
                } else {
                    if($width > $maxsize || $height > $maxsize) {

                        $max_width = $width / ($height / $maxsize);
                        $max_height = $height / ($width / $maxsize);

                        // calculate the dest width and height
                        $dx = $width / $max_width;
                        $dy = $height / $max_height;

                        $d = max($dx,$dy);

                        $new_width = $width / $d;
                        $new_height = $height / $d;

                        // sanity check to make sure neither is zero
                        $size[0] = max(1,$new_width);
                        $size[1] = max(1,$new_height);
                    }
                }
            } else {
                $size = array_filter(explode('x', $size));
                if (empty($size) === true) {
                    $size = array(imagesx($image), imagesy($image));
                } else {
                    if ((empty($size[0]) === true) || (is_numeric($size[0]) === false)) {
                        $size[0] = round($size[1] * $width / $height);
                    } else if ((empty($size[1]) === true) || (is_numeric($size[1]) === false)) {
                        $size[1] = round($size[0] * $height / $width);
                    }
                }
            }
            
            $result = ImageCreateTrueColor($size[0], $size[1]);

            if (is_resource($result) === true) {
                ImageSaveAlpha($result, true);
                ImageAlphaBlending($result, true);
                ImageFill($result, 0, 0, ImageColorAllocate($result, 255, 255, 255));
                ImageCopyResampled($result, $image, 0, 0, $x, $y, $size[0], $size[1], $width, $height);

                ImageInterlace($result, true);
                ImageJPEG($result, $path, 75);
            }
        }

        return false;
    }

}
