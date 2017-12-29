<?php

namespace App\Etsy\Models;

class TaxonomyPropertyScale {

  public $scale_id;
  public $display_name;
  public $description;

  static public function createFromAPIResponse($response) {
    // This is due to a "bug" in which the data returned by Etsy
    $response = (array) $response;

    $tps = new TaxonomyPropertyScale($response["scale_id"], $response["display_name"], $response["description"]);
    return $tps;
  }

  public function __construct($id = null, $name = null, $d = null) {
    $this->scale_id = $id;
    $this->display_name = $name;
    $this->description = $d;
  }

}
