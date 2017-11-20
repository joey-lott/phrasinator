<?php
namespace App\RPL;

use App\RPL\MarkedUp;
use App\RPL\TextToMarkup;

class TextImageV2 {

  private $font;
  public $text;
  public $defaultFontSize = 400;
  private $fontSize = 40;
  private $imageWidth = 3000;
  private $imageHeight = 3000;
  private $verticalSpaceMultiplier = 0.25;
  public $imageResource;
  public $transparent;
  private $textToMarkup;
  private $layout;
  // Store the height of one line of text including line spacing. This gets calculated once. Used many times.
  private $lineHeight;
  // Store the height of one line of text - text only, no line spacing
  private $lineTextHeight;
  private $textJustification;

  public function __construct($text, $font, $width = null, $height = null, $defaultTextColor = null, $lineSpacing = 0.1, $textJustification = "center") {
    $this->text = $text;
    $this->font = $font;
    $this->textToMarkup = new TextToMarkup($text, $defaultTextColor);
    $this->layout = new TextImageLayout($this->textToMarkup, base_path()."/fonts/".$this->font);
    if(isset($width)) $this->imageWidth = $width;
    if(isset($height)) $this->imageHeight = $height;
    $this->containsSpecialCharacters = (boolean) strpos($text, ":::");
    $this->verticalSpaceMultiplier = $lineSpacing;
    $this->textJustification = $textJustification;
  }

  public function getFileName() {
    $raw = $this->textToMarkup->rawWordsNoMarkup;
    for($i = 0; $i < count($raw); $i++) {
      $raw[$i] = implode("", preg_split("/[^a-zA-Z0-9\_]/", $raw[$i]));
    }
    return implode("-", $raw);
  }


  public function createImageResourceForLineOfMarkup($lineOfMarkup, $height, $transparent, $baselineToTopOfText, $fontSizeMultiplier = 1) {

    $image = imagecreatetruecolor($this->imageWidth, $height);
    imagecolortransparent($image, $transparent);
    imagefilledrectangle($image, 0, 0, $this->imageWidth, $height, $transparent);

    // $red = imagecolorallocate($image, 254, 1, 1);
    // imagefilledrectangle($image, 0, 0, $this->imageWidth, 10, $red);
    // imagefilledrectangle($image, 0, $height - 10, $this->imageWidth, $height, $red);


    $x = 0;
    $height = 0;

    for($i = 0; $i < count($lineOfMarkup); $i++) {
      $markup = $lineOfMarkup[$i];
      $color = $markup->color;

      $imgColor = imagecolorallocate($image, $color->red, $color->green, $color->blue);

      $printText = $markup->character;

      // Use the negative of the color to turn off anti-aliasing. Otherwise,
      // there can be some weird artifacts as a result of transparency
      $bBox = imagettftext($image, $this->fontSize * $fontSizeMultiplier, 0, $x, $baselineToTopOfText, -$imgColor, base_path()."/fonts/".$this->font, $printText);

      $x = $bBox[2];
      // Get the character height
      $charHeight = $bBox[1] - $bBox[5];
      // If this is the max height, keep track
      if($charHeight > $height) $height = $charHeight;
    }
    return ["image" => $image, "width" => $bBox[2], "height" => $height];
  }

  private function generateLineWithAllCharacters() {
    $line = [];
    $charsString = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!?,'Ñ";
    $chars = preg_split("//u", $charsString, null, PREG_SPLIT_NO_EMPTY);
    foreach($chars as $char) {
      $line[] = new MarkedUpCharacter($char, null);
    }
    return $line;
  }

  private function calculateLineHeight() {
    $tallest = $this->generateLineWithAllCharacters();
    $lineImage = $this->createImageResourceForLineOfMarkup($tallest, 1, null, 1);
    imagedestroy($lineImage["image"]);

    // The lineHeight is the height including line spacing
    $this->lineHeight = $lineImage["height"] * (1 + $this->verticalSpaceMultiplier);
    // The lineTextHeight is the height of the actual text
    $this->lineTextHeight = $lineImage["height"];
  }

  public function adjustFontToFillSpace() {

    $layout = $this->layout;

    $lines = $layout->getLines();

    $longest = $layout->getLongestLine();
    $lineImage = $this->createImageResourceForLineOfMarkup($longest, 1, null, 1);
    imagedestroy($lineImage["image"]);


    $width = $lineImage["width"];

    // Regardless of the width, adjust up or down to make a best
    // fit horizontally.
    $multiplier = ($this->imageWidth * .9) / $width;

    $this->fontSize *= $multiplier;
    $this->fontSize = round($this->fontSize);

    // But now, the text may overrun vertically. So test the height.
    // Calculate the line height of text containing maximum range
    // This will probably break if text is all uppercase
    $tallest = $this->generateLineWithAllCharacters();
    $lineImage = $this->createImageResourceForLineOfMarkup($tallest, 1, null, 1);
    imagedestroy($lineImage["image"]);

    $height = $lineImage["height"];
    $linesCount = count($layout->getLines());
    $totalHeight = ($height * (1 + ($this->verticalSpaceMultiplier))) * $linesCount;//($height * (1 + abs($this->verticalSpaceMultiplier))) * $linesCount;
    // The print height should be less than the entire image height
    // to prevent text getting cut off. This is a hack because some
    // fonts report incorrect height.
    $printHeight = $this->imageHeight;
    if($totalHeight > $printHeight) {
      $this->fontSize *= $this->imageHeight / $totalHeight;
      $this->fontSize = round($this->fontSize);
    }

    // Now that the font is set, calculate the line height
    $this->calculateLineHeight();

    return $this->fontSize;

  }

  public function generateNewTransparencyColor() {
    // generate a random color;
    $transparent = new Color();

    // verify that the color is not in any of the markup.
    foreach($this->textToMarkup->colors as $color) {

      // If the color is already being used in markup,
      // call this method recursively to get a new color.
      if($color == $transparent) {
        $transparent = $this->generateNewTransparencyColor();
      }
    }
    $this->transparent = $transparent;
    return $transparent;
  }

  public function getTotalHeight() {
    $height = $this->lineHeight;
    $multiplier = $this->verticalSpaceMultiplier > 0 ? $this->verticalSpaceMultiplier : $this->verticalSpaceMultiplier / 2;
    //$height = $height * count($this->layout->getLines());//($height * (1 + $multiplier)) * count($this->layout->getLines());
    $height = ($height * (1 + $multiplier)) * (count($this->layout->getLines()) - 1) + $height;
    return $height;
  }

  public function generateImageResource() {

    // Adjust the height of the image to match the total height of the text
    $this->imageHeight = $this->getTotalHeight();

    // Create the images and three colors used: black and white for text, red for transparency
    $image = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);

    $transparencyColor = $this->generateNewTransparencyColor();
    $transparent = imagecolorallocate($image, $transparencyColor->red, $transparencyColor->green, $transparencyColor->blue);

    // Fill the image with transparent color and set transparent
    imagefilledrectangle($image, 0, 0, $this->imageWidth, $this->imageHeight, $transparent);
    imagecolortransparent($image, $transparent);

    // Write each line of text to the image, centering each
    $currentY = 0;

    $markedUpCharacterIndex = 0;

    // Get the width of the longest line of text to calculate the positions
    // of text when right or left justified.
    $longestLine = $this->layout->getLongestLine();
    $lineImage = $this->createImageResourceForLineOfMarkup($longestLine, 1, $transparent, 1);
    imagedestroy($lineImage["image"]);
    $longestLineWidth = $lineImage["width"];

    $lines = $this->layout->getLines();

    for($i = 0; $i < count($lines); $i++) {
      $line = $lines[$i];
      // Join the characters of the line together
      $lineText = "";
      foreach($line as $char) {
        $lineText .= $char->character;
      }

      // Get the bounding box of the line of text
//      $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $lineText);

      $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "W");
      $diff = $this->lineHeight - ($box[1] - $box[5]);

      $heightAboveBaseline = -$box[5];// - ($diff / 2);

      $eachLineHeight = $this->lineTextHeight;

      // Create the line of text using 0.85 as a font size multipler.
      // The 0.85 is a hack to reduce the size of the printed font
      // relative to the calculated space because the fonts can report
      // some strange values that result in a few pixels getting shaved
      // off otherwise.
      $thisLineHeight = $i < count($lines) - 1 ? $eachLineHeight : $eachLineHeight;
      $lineImage = $this->createImageResourceForLineOfMarkup($line, $eachLineHeight, $transparent, $heightAboveBaseline, 0.85);

      // This is the x position of the first word in the line
      switch($this->textJustification) {
        case "left":
          $x = ($this->imageWidth - $longestLineWidth) / 2;
          break;
        case "right";
          $x = $this->imageWidth - (($this->imageWidth - $longestLineWidth) / 2) - $lineImage["width"];
          break;
        default:
          $x = ($this->imageWidth - $lineImage["width"]) / 2;
      }

      imagecopy($image, $lineImage["image"], $x, $currentY, 0, 0, $lineImage["width"], $eachLineHeight);
      imagedestroy($lineImage["image"]);

      $currentY += $this->lineHeight + ($this->lineHeight * $this->verticalSpaceMultiplier);

    }

    $this->imageResource = $image;
    return $image;
  }

  public function saveImage($name) {

    $fileName = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);

    $image = $this->imageResource;

    $name = $fileName.".png";
    imagepng($image, base_path()."/public/images/".$name);

    $this->destroyResources();

    return $name;
  }

  public function destroyResources() {
    $image = $this->imageResource;
    imagedestroy($image);
  }

}
