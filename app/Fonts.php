<?php

namespace App;

use Illuminate\Support\Facades\Storage;

class Fonts {


  static public function getFonts($subscriptionName = null) {
    $fonts = json_decode(trim(Storage::disk('local')->get('fonts.txt')));
    if($subscriptionName == null) return $fonts;

    $isBasicAccount = strpos($subscriptionName, "basic") !== false;
    $isPlusAccount = strpos($subscriptionName, "plus") !== false;
    $isSuperAccount = strpos($subscriptionName, "super") !== false;

    if($isPlusAccount) {
      $fonts = self::filterFonts($fonts, "plus");
    }
    else if($isSuperAccount) {
      $fonts = $fonts;
    }
    else {
      $fonts = self::filterFonts($fonts, "basic");
    }
    $sorter = new FontSorter($fonts);
    $fonts = $sorter->sortFonts();
    return $fonts;
  }

  static public function filterFonts($fonts, $filter) {
    $filteredFonts = [];
    foreach($fonts as $font) {
      if(!isset($font->level)) {
        $filteredFonts[] = $font;
        continue;
      }
      if($filter == "plus" && $font->level == "plus") {
        $filteredFonts[] = $font;
      }
    }
    return $filteredFonts;
  }

}

class FontSorter {

  private $fonts;

  public function __construct($fonts) {
    $this->fonts = $fonts;
  }

  public function compare($a, $b) {
    return strcmp($a->label, $b->label);
  }

  public function sortFonts() {
    usort($this->fonts, [$this, "compare"]);
    return $this->fonts;
  }


}
