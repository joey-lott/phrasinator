<?php

namespace App\RPL;

use App\RPL\Color;

class MarkedUp {

  public $color;
  public $word;

  public function __construct($text) {
      preg_match('/\[\[\[.*\]\]\]/', $text, $matches);
      if(isset($matches[0])) {
        $this->word = str_replace($matches[0], "", $text);
        $markup = explode("=", str_replace(["[", "]"], "", $matches[0]));
        $this->color = new Color($markup[1]);
      }
      else {
        $this->word = $text;
        $this->color = new Color("000000");
      }
  }

}
