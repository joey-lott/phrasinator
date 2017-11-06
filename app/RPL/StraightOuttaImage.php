<?php

namespace App\RPL;

class StraightOuttaImage {

  private $imageWidth = 2800;
  private $imageHeight = 3000;
  private $text;

  public function __construct($text) {
    $this->text = $text;
  }

  // $moveLeft is how many pixels to move the text to the left to
  // accommodate the font weirdness. Not all letters align correctly,
  // so it is sometimes necessary to move the text to the left a bit.
  public function render($moveLeft = 0) {

    // Does the directory exist? If not, create it.
    if(!is_dir(base_path()."/public/images/straightoutta")) {
      mkdir(base_path()."/public/images/straightoutta");
    }

    // delete all images in the images folder
    $files = glob(base_path()."/public/images/straightoutta/*");
    foreach($files as $file) {
      if(is_file($file)) {
        unlink($file);
      }
    }

    $image = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
    $imageWhite = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    $red = imagecolorallocate($image, 255, 0, 0);
    $blue = imagecolorallocate($image, 0, 0, 255);

    // Draw the outline.
    // First for the black image
    imagefilledrectangle($image, 0, 0, $this->imageWidth, $this->imageHeight, $black);
    imagefilledrectangle($image, 20, 20, $this->imageWidth - 20, $this->imageHeight - 20, $red);
    imagecolortransparent($image, $red);

    // Next, for the white image
    imagefilledrectangle($imageWhite, 0, 0, $this->imageWidth, $this->imageHeight, $white);
    imagefilledrectangle($imageWhite, 20, 20, $this->imageWidth - 20, $this->imageHeight - 20, $red);
    imagecolortransparent($imageWhite, $red);

    // Draw the upper and lower rectangles.
    // Only for the black image - the white image is transparent in the upper and lower rectangles
    imagefilledrectangle($image, 0, 0, $this->imageWidth, $this->imageHeight * .28, $black);
    imagefilledrectangle($image, 0, $this->imageHeight * .72, $this->imageWidth, $this->imageHeight, $black);

    // Then fill the middle rectangle for the white images
    imagefilledrectangle($imageWhite, 0, $this->imageHeight * .28, $this->imageWidth, $this->imageHeight * .72, $white);

    // The height of the rectangles for the upper and lower portions.
    $rectangleHeight = ($this->imageHeight) * .28;
    $printWidth = $this->imageWidth - 200;

    $fontPath = base_path()."/fonts/knockout.ttf";

    $text = "STRAIGHT";
    $printVals = $this->scaleTextToFit($text, $printWidth, $rectangleHeight - 150, $fontPath);
    $height = $printVals["height"];
    $width = $printVals["width"];
    $fontSize = $printVals["fontSize"];
    imagettftext($image, $fontSize, 0, ($this->imageWidth - $width) / 2, $height + ($rectangleHeight - $height) / 2, $red, $fontPath, $text);
    imagettftext($imageWhite, $fontSize, 0, ($this->imageWidth - $width) / 2, $height + ($rectangleHeight - $height) / 2, $white, $fontPath, $text);

    $text = "OUTTA";
    $middleHeight = ($this->imageHeight - 40) * .44;
    $printVals = $this->scaleTextToFit($text, $printWidth, $middleHeight , $fontPath);
    $height = $printVals["height"];
    $width = $printVals["width"];
    $fontSize = $printVals["fontSize"];
    $x1 = ($this->imageWidth - $width) / 2;
    // This font alignment is inconsistent across letters. Because of this,
    // move "OUTTA" to the left a little bit.
    $x1 -= $width * .015;
    $y1 = $height + $rectangleHeight + ($middleHeight - $height) / 2;
//    imagefilledrectangle($image, $x1, $y1 - $height, $x1 + $width, $y1 + $height, $blue);
    imagettftext($image, $fontSize, 0, $x1, $y1, $black, $fontPath, $text);
    imagettftext($imageWhite, $fontSize, 0, $x1, $y1, $red, $fontPath, $text);

    $text = $this->text;
    $printVals = $this->scaleTextToFit($text, $printWidth, $rectangleHeight - 200, $fontPath);
    $height = $printVals["height"];
    $width = $printVals["width"];
    $fontSize = $printVals["fontSize"];
    imagettftext($image, $fontSize, 0, ($this->imageWidth - $width) / 2 - $moveLeft, $this->imageHeight - ($rectangleHeight - $height) / 2 - 20, $red, $fontPath, $text);
    imagettftext($imageWhite, $fontSize, 0, ($this->imageWidth - $width) / 2 - $moveLeft, $this->imageHeight - ($rectangleHeight - $height) / 2 - 20, $white, $fontPath, $text);

    // imagefilledrectangle($imageWhite, 0, 0, $this->imageWidth, $this->imageHeight, $red);
    // imagecolortransparent($imageWhite, $red);

    //imagefilledrectangle($image, 0, 0, $this->imageWidth, $this->imageHeight, $red);

    imagepng($image, base_path()."/public/images/straightoutta/straight-outta-".$this->text.".png");
    imagepng($imageWhite, base_path()."/public/images/straightoutta/white_straight-outta-".$this->text.".png");

    imagedestroy($image);
    imagedestroy($imageWhite);
    return ["black" =>"/images/straightoutta/straight-outta-".$this->text.".png", "white" => "/images/straightoutta/white_straight-outta-".$this->text.".png"];
  }

  private function scaleTextToFit($text, $printWidth, $rectangleHeight, $fontPath) {
    $fontSize = 400;
    $box = imagettfbbox($fontSize, 0, $fontPath, $text);
    $width = $box[2] - $box[0];
    $height = $box[1] - $box[5];

    // Scale to best fit.
    // Which direction will be the limiter?
    $wRatio = $printWidth / $width;
    $hRatio = $rectangleHeight / $height;

    $multiplier = ($wRatio < $hRatio) ? $wRatio : $hRatio;

    $fontSize *= $multiplier;

    // Get the new height and width with the new font size.
    $box = imagettfbbox($fontSize, 0, $fontPath, $text);
    $width = $box[2] - $box[0];
    $height = $box[1] - $box[5];

    return ["fontSize" => $fontSize, "width" => $width, "height" => $height];
  }

}
