<?php

namespace App\RPL;

use App\RPL\Color;

class MarkedUpPhrase {

  public $color;
  public $phrase;
  public $characters;
  public $charactersNoDelimitersOrSpaces;

  public function __construct($phrase, $color) {
    $this->phrase = $phrase;
    $this->color = $color;
    $this->breakIntoCharacters();
    $this->breakIntoCharactersNoDelimitersOrSpaces();
  }

  private function breakIntoCharacters() {
    $this->characters = [];
    $chars = preg_split("//u", $this->phrase, null, PREG_SPLIT_NO_EMPTY);
    foreach($chars as $char) {
      $markup = new MarkedUpCharacter($char, $this->color);
      $this->characters[] = $markup;
    }
  }

  private function breakIntoCharactersNoDelimitersOrSpaces() {
    $this->charactersNoDelimitersOrSpaces = [];
    $chars = str_replace(":::", "", $this->phrase);
    $chars = str_replace(" ", "", $chars);
    $chars = preg_split("//u", $chars, null, PREG_SPLIT_NO_EMPTY);
    foreach($chars as $char) {
      $markup = new MarkedUpCharacter($char, $this->color);
      $this->charactersNoDelimitersOrSpaces[] = $markup;
    }
  }

}
