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

    $this->setLineHeightForMetrics($metrics);
    $totalHeight = $this->getTotalHeight();
    $printHeight = $this->imageHeight;
    if($totalHeight > $printHeight) {
      $this->fontSize *= $this->imageHeight / $totalHeight;
      $this->fontSize = $this->fontSize;
    }

    $tallest = $this->generateLineWithAllCharacters();
    $string = $this->convertCharsToString($tallest);
    $metrics = $this->getMetricsForString($string);
    $this->setLineHeightForMetrics($metrics);
    // Now that the font is set, calculate the line height
//    $height = $this->lineHeight * (count($this->layout->getLines()) - 1) + $this->lineTextHeight;
    return $this->fontSize;

  }

  public function setLineHeightForMetrics($metrics) {
    // The lineHeight is the height including line spacing
    $this->lineHeight = $metrics["textHeight"] * (1 + $this->verticalSpaceMultiplier);
    // The lineTextHeight is the height of the actual text
    $this->lineTextHeight = $metrics["textHeight"];

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

  public function saveToDisk() {

    $image = $this->imageResource;
    $tmpPath = storage_path("temp_text.png");
    $this->imageResource->writeImage($tmpPath);

    return ["name" => $tmpPath, "height" => $image->getImageHeight(), "width" => $image->getImageWidth()];
  }

  public function destroy() {
    $image = $this->imageResource;
    $image->clear();
    dblog(($image->getResource(\imagick::RESOURCETYPE_MEMORY))/1000000, "text image memory after being destroyed");

  }

}
