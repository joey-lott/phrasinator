<?php
namespace App\RPL;

class TextImage {

  private $font;
  public $text;
  public $defaultFontSize = 400;
  private $fontSize = 400;
  private $imageWidth = 500;
  private $imageHeight = 500;
  private $verticalSpaceMultiplier = 0.1;
  private $textToPrint; // replace this with $textLines
  private $textLines;
  private $hasCalculatedFontSize = false;
  private $forcingFont = false;
  private $containsSpecialCharacters = false;
  private $wordDimensions;

  public function __construct($text, $font) {
    $this->text = $text;
    $this->font = $font;
    $this->containsSpecialCharacters = (boolean) strpos($text, ":::");
  }

  public function longestWord() {
    $words = explode(" ", $this->text);

    // Create an associative array in which each word is a key
    // and the values are the lengths of the words
    $map = array_combine($words, array_map("strlen", $words));

    // Get the key (word) with the longest value
    $longest = array_keys($map, max($map))[0];

    return $longest;
  }

  public function getWordsDimensions() {
    $words = explode(" ", $this->text);

    $dimensions = [];

    foreach($words as $word) {
      $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $word);
      $width = $box[2] - $box[0];
      $height = $box[1] - $box[5];
      array_push($dimensions, ["width" => $width, "height" => $height]);
    }
    $this->wordDimensions = $dimensions;
    return $dimensions;
  }

  public function getTotalDimensionsOfTextInOneLine() {

    // If the word dimensions haven't yet been calculated, do so first.
    if(!isset($this->wordDimensions)) $this->getWordsDimensions();

    $widthSum = 0;
    $height = 0;
    foreach($this->wordDimensions as $dimensions) {
      $widthSum += $dimensions["width"];
      if($dimensions["height"] > $height) $height = $dimensions["height"];
    }
    return ["width" => $widthSum, "height" => $height];
  }

  public function arrangeWordsToLines() {
    if(strpos($this->text, ":::")) {
      return $this->arrangeWordsToLinesSpecialCharacters();
    }
    else {
      return $this->arrangeWordsToLinesGrid();
    }
  }

  public function arrangeWordsToLinesSpecialCharacters() {
    $lines = explode(":::", $this->text);
    for($i = 0; $i < count($lines); $i++) {
      $line = $lines[$i];
      $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $line);
      $width = $box[2] - $box[0];
      $height = $box[1] - $box[5];
      $lines[$i] = ["width" => $width, "height" => $height, "words" => [$line]];
    }
    $this->textLines = $lines;
    return $lines;
  }

  public function arrangeWordsToLinesGrid() {
    // Get the dimensions of the text in line long line.
    $dimensions = $this->getTotalDimensionsOfTextInOneLine();

    $width = $dimensions["width"];
    $height = $dimensions["height"];
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

    $words = explode(" ", $this->text);
    $wordDimensions = $this->getWordsDimensions();

    for($i = 0; $i < count($words); $i++) {

      $wordWidth = $wordDimensions[$i]["width"];
      $wordHeight = $wordDimensions[$i]["height"];

      $lineWidth += $wordWidth;

      // Is this word the max height of the line?
      if($wordHeight > $lineHeight) {
        $lineHeight = $wordHeight;
      }

      // If the line width is greater than the allowable width per line,
      // we've overrun the max width. So start a new line.
      if($lineWidth > $widthPerLine) {

        if($lineWidth == $wordWidth) {

          // This is a long word on one line - we'll just have to accept that.
          array_push($line, $words[$i]);
        }
        else {
          // Otherwise, we've overrun the max width of a line, so roll back one
          // Don't add the latest word to the current line.
          $i--;
          $lineWidth -= $wordWidth;
        }

        array_push($lines, ["width" => $lineWidth, "height" => $lineHeight, "words" => $line]);

        // Start a new line.
        $line = [];
        $lineWidth = 0;
        continue;
      }
      array_push($line, $words[$i]);

      // It's the last line and last word, so push the line to the array.
      // Otherwise, most often, the last line would not get added.
      if($i == count($words) - 1) {
        array_push($lines, ["width" => $lineWidth, "height" => $lineHeight, "words" => $line]);
      }
    }
    $this->textLines = $lines;
    return $lines;
  }

  public function adjustFontToFillSpace() {
    if(!isset($this->textLines)) {
      $this->arrangeWordsToLines();
    }

    $longest = $this->longestLine();

    $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $longest);
    $width = $box[2] - $box[0];

    // Regardless of the width, adjust up or down to make a best
    // fit horizontally.
    $multiplier = ($this->imageWidth * .9) / $width;
    $this->fontSize *= $multiplier;
    $this->fontSize = round($this->fontSize);

    // But now, the text may overrun vertically. So test the height.
    // Calculate the line height of text containing maximum range
    // This will probably break if text is all uppercase
    $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "lg");
    $lineHeight = $box[1] - $box[5];

    $lines = $this->textLines;
    $totalHeight = (($lineHeight * $this->verticalSpaceMultiplier) + $lineHeight) * count($lines);

    if($totalHeight > $this->imageHeight) {
      $this->fontSize *= $this->imageHeight / $totalHeight;
      $this->fontSize = round($this->fontSize);
    }


    return $this->fontSize;

  }

/*  public function fontSize() {

    // This is an expensive method. So don't run it if not necessary.
    if($this->hasCalculatedFontSize || $this->forcingFont) return;

    $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $this->longestLine());
    $width = $box[2] - $box[0];

    // Regardless of the width, adjust up or down to make a best
    // fit horizontally.
    $multiplier = $this->imageWidth / $width;
    $this->fontSize *= $multiplier;
    $this->fontSize = round($this->fontSize);

    // But now, the text may overrun vertically. So test the height.
    // Calculate the line height of text containing maximum range
    // This will probably break if text is all uppercase
    $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "lg");
    $lineHeight = $box[1] - $box[5];

    // Split the text to lines.
    $lines = $this->splitToLines();

    $totalHeight = (($lineHeight + $this->verticalSpaceMultiplier) + $lineHeight) * count($lines);
    if($totalHeight > $this->imageHeight) {
      dump("font size too big vertically: ".$this->imageHeight." ".$totalHeight);
      $this->fontSize *= $this->imageHeight / $totalHeight;
      $this->fontSize = round($this->fontSize);
    }


    $this->hasCalculatedFontSize = true;
    dump("final font size: ".$this->fontSize);
    return $this->fontSize;
  }
*/
  public function longestLine() {
    if(!isset($this->textLines)) {
      $this->arrangeWordsToLines();
    }
    $longest = 0;
    $longestLine = "";
    foreach($this->textLines as $line) {
      $lineWidth = $line["width"];
      if($lineWidth > $longest) {
        $longest = $lineWidth;
        $longestLine = implode(" ", $line["words"]);
      }
    }
    return $longestLine;
  }

  /*public function splitToLines() {
dump("split to lines");
    // Don't run this method if it has already been run (i.e. textToPrint is set)
    if(isset($this->textToPrint)) return;

    // Does the input text contain special characters? If so, process
    // it according to special character rules. Otherwise, process it normally.
    if($this->containsSpecialCharacters) {

      $printText = explode(":::", $this->text);

    }
    else {
      $words = explode(" ", $this->text);

      $printText = [];

      // Start with the first word.
      $currentLine = $words[0];
      $test = $currentLine;

      // Test each word
      for($i = 0; $i < count($words); $i++) {
        $appendValue = ($i + 1< count($words)) ? $words[$i + 1] : "";
        $test .= " ".$appendValue;

        // Create the bounding box for the test
        $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $test);
        $width = $box[2] - $box[0];

        // If the width is greater than the image width, reset the current
        // line and test.
        if($width > $this->imageWidth) {

          // Add the current line to the print text.
          array_push($printText, $currentLine);

          // Reset the current line and test
          $currentLine = $appendValue;

          $test = $currentLine;
        }
        else {
          // If last word, add current line to print text.
          if($i == count($words) - 1) {
            array_push($printText, $currentLine);
          }
          else {
            $currentLine = $test;
          }
        }
      }
      if($printText == []) $printText = [$this->text];

    }
    $this->textToPrint = $printText;
    return $printText;
  }
*/
  public function saveImage($name, $forceFontSize = -1) {

    if($forceFontSize != -1) {
      $this->fontSize = $forceFontSize;
      $this->forcingFont = true;
    }

    // First, delte all images in the images folder
    $files = glob(base_path()."/public/images/*");
    foreach($files as $file) {
      if(is_file($file)) {
        unlink($file);
      }
    }

    // Set the font size
//    $this->fontSize();


    // Create the images and three colors used: black and white for text, red for transparency
    $image = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
    $imageWhite = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    $red = imagecolorallocate($image, 255, 0, 0);

    // Fill the image with red and set red to transparent
    imagefilledrectangle($image, 0, 0, $this->imageWidth, $this->imageHeight, $red);
    imagecolortransparent($image, $red);

    imagefilledrectangle($imageWhite, 0, 0, $this->imageWidth, $this->imageHeight, $red);
    imagecolortransparent($imageWhite, $red);

    // Write each line of text to the image, centering each
    $currentY = 0;

    // Calculate the line height of text containing maximum range
    // This will probably break if text is all uppercase
    $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "lg");
    $lineHeight = $box[1] - $box[5];

    foreach($this->textLines as $line) {
      // Join the words of the line together
      $lineText = implode(" ", $line["words"]);
      // Get the bounding box of the first line of text
      $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $lineText);

      $lineWidth = $box[2] - $box[0];

      // If first line, calculate y value to vertically center text
      if($currentY == 0) {
        $totalHeight = ($lineHeight + ($lineHeight * $this->verticalSpaceMultiplier)) * count($this->textLines);
        $currentY = $lineHeight + (($this->imageHeight - $totalHeight) / 2) - ($lineHeight * $this->verticalSpaceMultiplier * 1.5);
      }
      else {
        $currentY += $lineHeight + $lineHeight * $this->verticalSpaceMultiplier;
      }

      $x = ($this->imageWidth - $lineWidth) / 2;
      imagettftext($image, $this->fontSize, 0, $x, $currentY, $black, base_path()."/fonts/".$this->font, $lineText);
      imagettftext($imageWhite, $this->fontSize, 0, $x, $currentY, $white, base_path()."/fonts/".$this->font, $lineText);
    }

    $fileName = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);


    $name = $fileName.".png";
    imagepng($image, base_path()."/public/images/".$name);
    imagedestroy($image);
    // $name and $nameWhite used to be prepended with base_path().
    $nameWhite = "white_".$fileName.".png";
    imagepng($imageWhite, base_path()."/public/images/".$nameWhite);
    imagedestroy($imageWhite);

    return [$name, $nameWhite];
  }

}
