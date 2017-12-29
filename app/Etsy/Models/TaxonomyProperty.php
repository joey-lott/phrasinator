<?php

namespace App\Etsy\Models;

use App\Etsy\Models\TaxonomyPropertyScale;
use App\Etsy\Models\TaxonomyPropertyPossibleValue;

class TaxonomyProperty {

  public $property_id;
  public $name;
  public $display_name;
  public $is_required;
  public $supports_attributes;
  public $supports_variations;
  public $is_multivalued;
  public $scales = [];
  public $possible_values = [];
  public $selected_values = [];

  static public function createFromAPIResponse($response) {
    $tp = new TaxonomyProperty;

    // This should probably be corrected at some point. I'm casting
    // to array for now to avoid having to re-write the following code.
    // This broke on refactoring at some point because the value
    // switched form array to object.
    $response = (array) $response;

    $tp->property_id = $response["property_id"];
    $tp->name = $response["name"];
    $tp->display_name = $response["display_name"];
    $tp->is_required = $response["is_required"];
    $tp->supports_attributes = $response["supports_attributes"];
    $tp->supports_variations = $response["supports_variations"];
    $tp->is_multivalued = $response["is_multivalued"];
    foreach($response["scales"] as $scale) {
      $tps = TaxonomyPropertyScale::createFromAPIResponse($scale);
      array_push($tp->scales, $tps);
    }
    foreach($response["possible_values"] as $pv) {
      $tppv = TaxonomyPropertyPossibleValue::createFromAPIResponse($pv);
      array_push($tp->possible_values, $tppv);
    }
    $tp->selected_values = $response["selected_values"];
    return $tp;
  }

  public function getScaleByName($name) {
    foreach($this->scales as $scale) {
      if($scale->display_name == $name) {
        return $scale;
      }
    }
    // if there is no scale found, return a new TaxonomyPropertyScale object with a null ID.
    return new TaxonomyPropertyScale();
  }

  public function getPossibleValueByName($name) {
    foreach($this->possible_values as $pv) {
      if($pv->name == $name) {
//        echo "possible value: ".$pv->name."\n";
        return $pv;
      }
    }
    return null;
  }

}
