<?php

namespace App\Etsy\Models;

class TaxonomyPropertyPossibleValue {

  public $value_id;
  public $name;
  public $order;
  public $scale_id;
  public $equal_to;

  static public function createFromAPIResponse($response) {
    $tp = new TaxonomyPropertyPossibleValue;

    // This is due to a "bug" in which the data returned by Etsy
    $response = (array) $response;

    $tp->value_id = $response["value_id"];
    $tp->name = $response["name"];
    $tp->order = $response["order"];
    $tp->scale_id = $response["scale_id"];
    return $tp;
  }

}
