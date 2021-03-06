<?php


namespace App\RPL;

use App\RPL\TextToMarkup;

class TextImageLayoutV2 {

  private $t2m;
  private $font;
  private $lines;
  private $fontSize = 400;
  private $verticalSpaceMultiplier = 0.1;

  public function __construct($t2m, $font) {
    $this->t2m = $t2m;
    $this->font = $font;
    $this->breakIntoLines();
  }

  public function getLines() {
    return $this->lines;
  }

  public function getLongestLine() {
    $longestWidth = 0;
    $longest;
    foreach($this->lines as $line) {
      $lineText = "";
      foreach($line as $char) {
        $lineText .= $char->character;
      }
      $width = $this->getTotalDimensionsOfTextInOneLine($lineText)[0];
      if($width > $longestWidth) {
        $longestWidth = $width;
        $longest = $line;
      }
    }
    return $longest;
  }


  public function getTallestLine() {
    $max = 0;
    $tallest;
    foreach($this->lines as $line) {
      $lineText = "";
      foreach($line as $char) {
        $lineText .= $char->character;
      }
      $height = $this->getTotalDimensionsOfTextInOneLine($lineText)[1];
      if($height > $max) {
        $max = $height;
        $tallest = $line;
      }
    }
    return $tallest;
  }

  private function breakIntoLines() {
    if(strpos($this->t2m->rawPhraseNoMarkup, ":::") === false) {
      $this->breakIntoLinesBestFit();
    }
    else {
      $this->breakIntoLinesDelimiter();
    }
  }

  private function breakIntoLinesDelimiter() {
    $lines = [];
    $rawLines = explode(":::", $this->t2m->rawPhraseNoMarkup);
    $index = 0;
    foreach($rawLines as $rawLine) {
      $line = [];
      $chars = preg_split("//u", $rawLine, null, PREG_SPLIT_NO_EMPTY);
      foreach($chars as $char) {
        if($char != " ") {
          $line[] = $this->t2m->characters[$index];
          $index++;
        }
        else {
          $line[] = new MarkedUpCharacter(" ", null);
        }
      }
      if(count($line) > 0) $lines[] = $line;
    }
    $this->lines = $lines;
  }

  private function breakIntoLinesBestFit() {
    $dimensions = $this->getTotalDimensionsOfTextInOneLine($this->t2m->rawPhraseNoMarkup);

    $width = $dimensions[0];
    $height = $dimensions[1];

    $height += $height * $this->verticalSpaceMultiplier;

    // The total number of "units" (i.e. square chunks) is the width divided by the height
    $numberOfUnits = $width / $height;

    // The total number of units per line. In other words, how many square chunks should fit
    // on one line of text. I'm assuming this makes sense - the square root will return
    // how many units if the whole thing is on a square grid.
    $unitsPerLine = sqrt($numberOfUnits);


    // The width per line is the maximum width per line of text. Each line
    // of text may be less than this, of course. And in some cases, if there is
    // a very long word, text can overrun this. But this value gives the guideline
    // for how wide a line of text should be.
    $widthPerLine = ($width / $numberOfUnits) * $unitsPerLine;

    // The lines of text.
    $lines = [];

    // A single line of text.
    $line = [];

    // Start the width of the line at 0. Add each word's width to this to keep a running total per line.
    $lineWidth = 0;
    // Start the height of the line at 0. For each word, we'll determine if it is the max height of the line.
    $lineHeight = 0;

    $words = $this->t2m->rawWordsNoMarkup;

    // The index for moving through the t2m characters
    $index = 0;

    for($i = 0; $i < count($words); $i++) {

      $word = $words[$i];

      $wordDimensions = $this->getTotalDimensionsOfTextInOneLine($word);
      $wordWidth = $wordDimensions[0];
      $wordHeight = $wordDimensions[1];

      $lineWidth += $wordWidth;

      // Is this word the max height of the line?
      if($wordHeight > $lineHeight) {
        $lineHeight = $wordHeight;
      }

      // If the line width is greater than the allowable width per line,
      // we've overrun the max width. So start a new line.
      if($lineWidth > $widthPerLine) {

        if($lineWidth == $wordWidth) {

          $chars = preg_split("//u", $word, null, PREG_SPLIT_NO_EMPTY);
          foreach($chars as $char) {
            if($char != " ") {
              $line[] = $this->t2m->characters[$index];
              $index++;
            }
          }

        }
        else {
          // Otherwise, we've overrun the max width of a line, so roll back one
          // Don't add the latest word to the current line.
          $i--;
          $lineWidth -= $wordWidth;
        }

        array_push($lines, $line);

        // Start a new line.
        $line = [];
        $lineWidth = 0;
        $lineHeight = 0;
        continue;
      }
      else {
        // Otherwise, tack on a space because it's the end of a word, but not the end of a line.
        // Just make sure it's not the start of a line, either.
        if(count($line) > 0) $line[] = new MarkedUpCharacter(" ", null);
      }

      $chars = preg_split("//u", $word, null, PREG_SPLIT_NO_EMPTY);
      foreach($chars as $char) {
        if($char != " ") {
          $line[] = $this->t2m->characters[$index];
          $index++;
        }
      }

      // It's the last line and last word, so push the line to the array.
      // Otherwise, most often, the last line would not get added.
      if($i == count($words) - 1) {
        array_push($lines, $line);
      }
    }

    $this->lines = $lines;
  }

  public function getTotalDimensionsOfTextInOneLine($text) {
    $image = new \Imagick();
    $draw = new \ImagickDraw();
    $draw->setFont($this->font);
    $draw->setFontSize( $this->fontSize );
    $metrics = $image->queryFontMetrics($draw, $text);
//    dump($metrics);
    $height = $metrics["boundingBox"]["y2"] - $metrics["boundingBox"]["y1"];
    return [$metrics["textWidth"], $height];//$metrics["textHeight"]];
  }

}
