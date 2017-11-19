<?php

namespace App\RPL;
use App\RPL\MarkedUpPhrase;
use App\RPL\Color;

class TextToMarkup {

  public $phrases;
  public $characters;
  public $rawWordsNoMarkup;
  public $rawPhraseNoMarkup;
  public $colors;

  private $defaultTextColor;

  public function __construct($text, $defaultTextColor = null) {
    $this->phrases = $this->breakIntoPhrases($text);
    $this->breakIntoWords();
    $this->breakIntoCharacters();
    if(!isset($defaultTextColor)) $defaultTextColor = new Color("000000");
    $this->defaultTextColor = $defaultTextColor;
  }

  private function breakIntoWords() {

    // First, operate on the raw phrases to extract the raw words
    $phrases = [];
    foreach($this->phrases as $phrase) {
      $phrases[] = $phrase->phrase;
    }

    // This results in the phrase minus the markup (but keeps the line delimiters)
    $this->rawPhraseNoMarkup = implode("", $phrases);

    $phraseNoDelimiters = implode(" ", explode(":::", $this->rawPhraseNoMarkup));
    $this->rawWordsNoMarkup = explode(" ", $phraseNoDelimiters);
  }

  private function breakIntoCharacters() {
    $this->characters = [];
    foreach($this->phrases as $phrase) {
      $chars = $phrase->charactersNoDelimitersOrSpaces;
      foreach($chars as $char) {
        $this->characters[] = $char;
      }
    }
  }

  private function breakIntoPhrases($text) {
    $brokenUpByClosingMarkup = preg_split("/\[\[\[\/color\]]]/", $text);
    $phrases = [];
    $this->colors = [$this->defaultTextColor];
    foreach($brokenUpByClosingMarkup as $chunk) {
      $chunksForMarkUp = preg_split("/\[\[\[color=([0-9a-zA-Z]{6})]]]/", $chunk, -1, PREG_SPLIT_DELIM_CAPTURE);
      // The first element is always the default color
      $markUp = new MarkedUpPhrase($chunksForMarkUp[0], $this->defaultTextColor);
      $phrases[] = $markUp;
      // If there are more than one, that means the second element is the color
      // and the third is the phrase to apply the color to
      if(count($chunksForMarkUp) == 3) {
        $markUp = new MarkedUpPhrase($chunksForMarkUp[2], new Color($chunksForMarkUp[1]));
        $phrases[] = $markUp;
        $this->colors[] = new Color($chunksForMarkUp[1]);
      }
    }
    return $phrases;
  }

}
