<?php

namespace App\RPL;

use App\RPL\Color;

class MarkedUpCharacter {

  public $color;
  public $character;

  public function __construct($character, $color) {
    if($color == null) {
      $color = new Color(0, 0, 0);
    }
    $this->character = $character;
    $this->color = $color;
  }

}
