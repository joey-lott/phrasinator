<?php
namespace App\RPL;

use App\RPL\MarkedUp;
use App\RPL\TextToMarkup;

class TextImage {

  private $font;
  public $text;
  public $defaultFontSize = 400;
  private $fontSize = 400;
  private $imageWidth = 3000;
  private $imageHeight = 3000;
  private $verticalSpaceMultiplier = 0.1;
  private $textToPrint; // replace this with $textLines
  private $textLines;
  private $hasCalculatedFontSize = false;
  private $forcingFont = false;
  private $containsSpecialCharacters = false;
  private $wordDimensions;
  public $imageResources;
  public $transparent;
  private $textToMarkup;

  public function __construct($text, $font, $width = null, $height = null) {
    $this->text = $text;
    $this->textToMarkup = new TextToMarkup($text);
    $this->font = $font;
    if(isset($width)) $this->imageWidth = $width;
    if(isset($height)) $this->imageHeight = $height;
    $this->containsSpecialCharacters = (boolean) strpos($text, ":::");
  }

  // public function getMarkedUpWords() {
  //   $words = explode(" ", $this->text);
  //   $markedUp = [];
  //   foreach($words as $word) {
  //     $markedUp[] = new MarkedUp($word);
  //   }
  //   return $markedUp;
  // }

  public function longestWord() {
    $phrase = $this->textToMarkup->rawPhraseNoMarkup;
    $words = explode(" ", $phrase);

    // Create an associative array in which each word is a key
    // and the values are the lengths of the words
    $map = array_combine($words, array_map("strlen", $words));

    // Get the key (word) with the longest value
    $longest = array_keys($map, max($map))[0];

    return $longest;
  }

  // Get just the words, not the markup
  public function getWords() {
    return $phrase = $this->textToMarkup->rawWordsNoMarkup;
  }

  public function getWordsDimensions() {
    $words = $this->getWords();

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
    ////dump("arrangeWordsToLines");
    if(strpos($this->text, ":::")) {
      return $this->arrangeWordsToLinesSpecialCharacters();
    }
    else {
      return $this->arrangeWordsToLinesGrid();
    }
  }

  public function arrangeWordsToLinesSpecialCharacters() {
    ////dump("arrangeWordsToLinesSpecialCharacters");

    // Why doing this here? Comment for now...
    // If the word dimensions haven't yet been calculated, do so first.
    // if(!isset($this->wordDimensions)) $this->getWordsDimensions();
    // $wordDimensions = $this->wordDimensions;

    $lines = explode(":::", $this->textToMarkup->rawPhraseNoMarkup);

    // if the last line is blank, remove it
    if($lines[count($lines) - 1] == "") array_pop($lines);

    for($i = 0; $i < count($lines); $i++) {
      $line = $lines[$i];

      // Split the line into words
      $words = explode(" ", $line);

      $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $line);

      $width = $box[2] - $box[0];
      $height = $box[1] - $box[5];

      $lines[$i] = ["width" => $width, "height" => $height, "words" => $words];
    }
    ////dump("setting textLines");
    ////dump($lines);
    $this->textLines = $lines;
    return $lines;
  }

  public function arrangeWordsToLinesGrid() {
    ////dump("arrangeWordsToLinesGrid");
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

    $words = $this->textToMarkup->rawWordsNoMarkup;

    // If the word dimensions haven't yet been calculated, do so first.
    if(!isset($this->wordDimensions)) $this->getWordsDimensions();
    $this->getWordsDimensions();
    $wordDimensions = $this->wordDimensions;

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
        $lineHeight = 0;
        continue;
      }
      array_push($line, $words[$i]);

      // It's the last line and last word, so push the line to the array.
      // Otherwise, most often, the last line would not get added.
      if($i == count($words) - 1) {
        array_push($lines, ["width" => $lineWidth, "height" => $lineHeight, "words" => $line]);
      }
    }
    ////dump("setting textLines");
    ////dump($lines);
    $this->textLines = $lines;
    return $lines;
  }

  public function adjustFontToFillSpace() {
    ////dump("adjustFontToFillSpace");
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

//    ////dump("adjusted font size: ".$this->fontSize);
    // Adjust the word dimensions based on new font size.
    // This may break something.
    $this->getWordsDimensions();
    $this->arrangeWordsToLines();

    return $this->fontSize;

  }

  public function longestLine() {
    ////dump("longestLine");
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
//    $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "lg");
//    $lineHeight = $box[1] - $box[5];
    $gNBox = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "gN");
    $gNHeight = $gNBox[1] - $gNBox[5];
    $height = 0;
    foreach($this->textLines as $line) {
      $height += $gNHeight + ($gNHeight * $this->verticalSpaceMultiplier);
    }
    return $height;
  }

  public function generateImageResources() {
    ////dump("generateImageResources");
    // First, delete all images in the images folder
    $files = glob(base_path()."/public/images/*");
    foreach($files as $file) {
      if(is_file($file)) {
        unlink($file);
      }
    }

    // Adjust the height of the image to match the total height of the text
    $this->imageHeight = $this->getTotalHeight();

    // Create the images and three colors used: black and white for text, red for transparency
    $image = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
    $imageWhite = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);

    $transparencyColor = $this->generateNewTransparencyColor();
    $transparent = imagecolorallocate($image, $transparencyColor->red, $transparencyColor->green, $transparencyColor->blue);

    // Fill the image with red and set red to transparent
    imagefilledrectangle($image, 0, 0, $this->imageWidth, $this->imageHeight, $transparent);
    imagecolortransparent($image, $transparent);

    // Draw upper and lower bounding lines to see the boundaries
    // imagefilledrectangle($image, 0, 0, $this->imageWidth, 10, $black);
    // imagefilledrectangle($image, 0, $this->imageHeight - 10, $this->imageWidth, $this->imageHeight, $black);

    imagefilledrectangle($imageWhite, 0, 0, $this->imageWidth, $this->imageHeight, $transparent);
    imagecolortransparent($imageWhite, $transparent);

    // Write each line of text to the image, centering each
    $currentY = 0;

    $markedUpCharacterIndex = 0;

    // $nBox = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "n");
    // $NBox = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "N");
    // $gBox = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "g");
    // $gNBox = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, "gN");
    // $nHeight = $nBox[1] - $nBox[5];
    // $NHeight = $NBox[1] - $NBox[5];
    // $gHeight = $gBox[1] - $gBox[5];
    // $gNHeight = $gNBox[1] - $gNBox[5];

    foreach($this->textLines as $line) {
      // Join the words of the line together
      $lineText = implode(" ", $line["words"]);

      // Get the bounding box of the line of text
      $box = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $lineText);

      $lineWidth = $box[2] - $box[0];
      $lineHeight = $box[1] - $box[5];

      // Get the average width per character.
      $widthPerCharacter = $lineWidth / count(preg_split("//u", $lineText, null, PREG_SPLIT_NO_EMPTY));

      // If first line, calculate y value to vertically center text
      if($currentY == 0) {
        // Commented because no longer vertically centering...adjusting image height instead
        // $totalHeight = ($lineHeight + ($lineHeight * $this->verticalSpaceMultiplier)) * count($this->textLines);
        // $currentY = $lineHeight + (($this->imageHeight - $totalHeight) / 2) - ($lineHeight * $this->verticalSpaceMultiplier * 1.5);
        $currentY = -$box[5];
      }
      else {
        $eachLineHeight = $this->imageHeight / count($this->textLines);
        $currentY += $eachLineHeight;
      }

      // This is the x position of the first word in the line
      $x = ($this->imageWidth - $lineWidth) / 2;

      // Get the words
      $lineWords = $line["words"];

      // Now, add each individual mark up word
      for($i = 0; $i < count($lineWords); $i++) {
        $word = $lineWords[$i];
        $wordChars = preg_split("//u", $word, null, PREG_SPLIT_NO_EMPTY);
        for($j = 0; $j < count($wordChars); $j++) {
          $markup = $this->textToMarkup->characters[$markedUpCharacterIndex];
          $color = $markup->color;

          $imgColor = imagecolorallocate($image, $color->red, $color->green, $color->blue);
          $imgColorWhite = $color->isBlack() ? imagecolorallocate($image, 255, 255, 255) : $imgColor;
          $printText = $markup->character;

          // Figure out the width of the character
          $characterBox = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $printText);
          $characterWidth = $characterBox[2] - $characterBox[0];

          if(($i == 0 && $j == 0)) {
            $x -= $characterBox[0];
          }

          $bBox = imagettftext($image, $this->fontSize, 0, $x, $currentY, $imgColor, base_path()."/fonts/".$this->font, $printText);
          imagettftext($imageWhite, $this->fontSize, 0, $x, $currentY, $imgColorWhite, base_path()."/fonts/".$this->font, $printText);

          dump($printText);
          dump($characterBox);
          dump($bBox);

          $characterSpacing = 0;//$characterBox[0];
          $x = $bBox[2] + ($characterBox[0] * 1) + $characterSpacing;
          $markedUpCharacterIndex++;
        }

        // If it's not the last word in the line, add a space after the word
        if($i < count($lineWords) - 1) {
          $printText = " ";

          imagettftext($image, $this->fontSize, 0, $x, $currentY, $imgColor, base_path()."/fonts/".$this->font, $printText);
          imagettftext($imageWhite, $this->fontSize, 0, $x, $currentY, $imgColorWhite, base_path()."/fonts/".$this->font, $printText);

          // Figure out the width of the character
          $characterBox = imagettfbbox($this->fontSize, 0, base_path()."/fonts/".$this->font, $printText);
          $x += $characterBox[2] - $characterBox[0];
        }
      }
      // imagettftext($image, $this->fontSize, 0, $x, $currentY, $black, base_path()."/fonts/".$this->font, $lineText);
      // imagettftext($imageWhite, $this->fontSize, 0, $x, $currentY, $white, base_path()."/fonts/".$this->font, $lineText);
    }

    $this->imageResources = ["dark" => $image, "light" => $imageWhite];

    return $this->imageResources;
  }

  public function saveImages($name) {

    $fileName = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);

    $image = $this->imageResources["dark"];
    $imageWhite = $this->imageResources["light"];

    $name = $fileName.".png";
    imagepng($image, base_path()."/public/images/".$name);

    // $name and $nameWhite used to be prepended with base_path().
    $nameWhite = "white_".$fileName.".png";
    imagepng($imageWhite, base_path()."/public/images/".$nameWhite);

    $this->destroyResources();


    return [$name, $nameWhite];
  }

  public function destroyResources() {
    $image = $this->imageResources["dark"];
    $imageWhite = $this->imageResources["light"];

    //dump("destroying text image resources");
    //dump($image);
    //dump($imageWhite);
    imagedestroy($image);
    imagedestroy($imageWhite);
    //dump("destroyed");
    //dump($image);
    //dump($imageWhite);
  }

}
