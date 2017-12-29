<?php

namespace App\Etsy\Models;

use App\Etsy\Models\EtsyModel;

class PropertyValue extends EtsyModel {

  public $property_id;
  public $property_name;
  public $scale_id;
  public $scale_name;
  public $value_ids = [];
  public $values = [];

  public function __construct($id, $sid, $vs, $vids = []) {
    $this->property_id = $id;
    $this->scale_id = $sid;
    $this->values = $vs;
    $this->value_ids = $vids;
  }

  public function jsonSerialize() {
    if(!isset($this->values)) {
        return ["property_id" => $this->property_id,
                "scale_id" => $this->scale_id,
                "value_ids" => $this->value_ids];
    }
    return ["property_id" => $this->property_id,
            "scale_id" => $this->scale_id,
            "values" => $this->values];
  }

}
