<?php

namespace App\Etsy\Models;

use App\Etsy\Models\EtsyModel;

class ListingOffering extends EtsyModel {

  public $offering_id;
  public $price;
  public $quantity;
  public $is_enabled;
  public $is_deleted;

  public function __construct($p, $q = 999, $e = 1) {
    $this->price = $p;
    $this->quantity = $q;
    $this->is_enabled = $e;
  }

  public function jsonSerialize() {
    return ['price' => $this->price,
           'quantity' => $this->quantity,
           'is_enabled' => $this->is_enabled
           ];
  }

}
