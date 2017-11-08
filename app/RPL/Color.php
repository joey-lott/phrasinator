<?php

namespace App\RPL;

class Color {

  public $red;
  public $green;
  public $blue;

  public function __construct($hexString = null) {
    // If the parameter is set, use it. Otherwise, generate a random color.
    if(isset($hexString)) {
      $dec = hexdec($hexString);
      $this->red = 0xFF & ($dec >> 0x10);
      $this->green = 0xFF & ($dec >> 0x8);
      $this->blue = 0xFF & $dec;
    }
    else {
      // Random values should be between 1 and 254 in this case because the only
      // use case for this is in generating random colors that are not white
      // and not black.
      $this->red = rand(1, 254);
      $this->blue = rand(1, 254);
      $this->green = rand(1, 254);
    }
  }

  public function getInverse() {
    $inverse = new Color();
    $inverse->red = 255- $this->red;
    $inverse->green = 255- $this->green;
    $inverse->blue = 255- $this->blue;
  }

  public function isWhite() {
    return $this == new Color("FFFFFF");
  }

  public function isBlack() {
    return $this == new Color("000000");
  }

}
