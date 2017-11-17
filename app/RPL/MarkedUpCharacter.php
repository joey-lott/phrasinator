<?php

namespace App\RPL;

class MarkedUpCharacter {

  public $color;
  public $character;

  public function __construct($character, $color) {
    $this->character = $character;
    $this->color = $color;
  }

}
