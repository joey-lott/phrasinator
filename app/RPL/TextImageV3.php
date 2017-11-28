<?php
namespace App\RPL;

use App\RPL\MarkedUp;
use App\RPL\TextToMarkup;
use App\RPL\TextImageLayoutV2;

class TextImageV3 {

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
  private $basePath;

  public function __construct($text, $font, $width = null, $height = null, $defaultTextColor = null, $lineSpacing = 0.1, $textJustification = "center", $basePath = "") {
    $this->basePath = $basePath;

    $this->text = $text;
    $this->font = $font;
    $this->textToMarkup = new TextToMarkup($text, $defaultTextColor);
    $this->layout = new TextImageLayoutV2($this->textToMarkup, base_path()."/fonts/".$this->font);
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
    return substr(implode("-", $raw), 0, 50);
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

  public function getMetricsForString($string) {
    $image = new \Imagick();
    $draw = new \ImagickDraw();
    $draw->setFont($this->getFullFont());
    $draw->setFontSize( $this->fontSize );
    $metrics = $image->queryFontMetrics($draw, $string);
    return $metrics;
  }

  public function adjustFontToFillSpace() {

    $layout = $this->layout;

    $lines = $layout->getLines();

    $longest = $layout->getLongestLine();
    $string = $this->convertCharsToString($longest);
    $metrics = $this->getMetricsForString($string);

    $width = $metrics["textWidth"];
    // Regardless of the width, adjust up or down to make a best
    // fit horizontally.
    $multiplier = ($this->imageWidth * .9) / $width;

    $this->fontSize *= $multiplier;
    $this->fontSize = round($this->fontSize);

    // But now, the text may overrun vertically. So test the height.
    // Calculate the line height of text containing maximum range
    // This will probably break if text is all uppercase
    $tallest = $this->generateLineWithAllCharacters();

    $string = $this->convertCharsToString($tallest);

    $metrics = $this->getMetricsForString($string);

    $height = $metrics["textHeight"];

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

    $tallest = $this->generateLineWithAllCharacters();
    $string = $this->convertCharsToString($tallest);
    $metrics = $this->getMetricsForString($string);

    // Now that the font is set, calculate the line height
    // The lineHeight is the height including line spacing
    $this->lineHeight = $metrics["textHeight"] * (1 + $this->verticalSpaceMultiplier);
    // The lineTextHeight is the height of the actual text
    $this->lineTextHeight = $metrics["textHeight"];

    return $this->fontSize;

  }

  public function convertCharsToString($chars) {
    $string = "";
    foreach($chars as $char) {
      $string .= $char->character;
    }
    return $string;
  }

  public function getTotalHeight() {
    // The height is the lineHeight (includes spacing) times one less than
    // the total number of lines, plus the height of one line of text
    // because the last line shouldn't include spacing below it
    $height = $this->lineHeight * (count($this->layout->getLines()) - 1) + $this->lineTextHeight;
    return $height;
  }

  public function getFullFont() {
    return base_path()."/fonts/".$this->font;
  }

  public function generateImageResource() {

    // Adjust the height of the image to match the total height of the text
    $this->imageHeight = $this->getTotalHeight();

    $image = new \Imagick();
    $image->newImage($this->imageWidth, $this->imageHeight, "none");
    $draw = new \ImagickDraw();
    $draw->setFont($this->getFullFont());
    $draw->setFontSize( $this->fontSize );

    // Get the width of the longest line of text to calculate the positions
    // of text when right or left justified.
    $longestLine = $this->layout->getLongestLine();
    $string = $this->convertCharsToString($longestLine);
    $metrics = $this->getMetricsForString($string);
    $longestLineWidth = $metrics["textWidth"];

    $lines = $this->layout->getLines();
    $y = 0;
    for($i = 0; $i < count($lines); $i++) {
      $line = $lines[$i];
      $string = $this->convertCharsToString($line);
      $metrics = $this->getMetricsForString($string);
      $lineWidth = $metrics["originX"];

      switch($this->textJustification) {
        case "left":
          $x = ($this->imageWidth - $longestLineWidth) / 2;
          break;
        case "right";
          $x = $this->imageWidth - (($this->imageWidth - $longestLineWidth) / 2) - $lineWidth;
          break;
        default:
          $x = ($this->imageWidth - $lineWidth) / 2;
      }

      foreach($line as $char) {
        $c = $char->character;
        $metrics = $image->queryFontMetrics($draw, $c);

        $draw->setFillColor("#".$char->color->getHexString());
        $image->annotateImage($draw, $x, $y + $metrics["ascender"], 0, $c);
        $x += $metrics["originX"];
      }
      $y += $this->lineHeight;

    }
    dblog(($image->getResource(\imagick::RESOURCETYPE_MEMORY))/1000000, "text image memory");
    $this->imageResource = $image;
    return $image;
  }

  public function saveToDisk($name) {

    $fileName = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);

    $image = $this->imageResource;

    $name = $fileName.".png";
    dblog("{$this->basePath}{$name}", "text image temp file (attempt)");
    $this->imageResource->writeImage($this->basePath.$name);
    dblog("{$this->basePath}{$name}", "text image write to disk");
    return ["name" => $name, "height" => $image->getImageHeight(), "width" => $image->getImageWidth()];
  }

  public function destroy() {
    $image = $this->imageResource;
    $image->clear();
    dblog(($image->getResource(\imagick::RESOURCETYPE_MEMORY))/1000000, "text image memory after being destroyed");

  }

}
