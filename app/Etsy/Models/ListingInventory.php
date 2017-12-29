<?php

namespace App\Etsy\Models;

class ListingInventory {

  public $listing_id;
  public $products = [];
  public $priceVariationPropertyId;

  public function __construct($id, $products = [], $priceVariationPropertyId = "") {
    $this->listing_id = $id;
    $this->products = $products;
    $this->priceVariationPropertyId = $priceVariationPropertyId;
  }

  public function addProduct($product) {
    array_push($this->products, $product);
  }

  public function jsonEncodeProducts() {
    return json_encode($this->products);
  }

  public function saveToEtsy() {
    $api = resolve("\App\Etsy\EtsyAPI");
    $response = $api->updateInventory($this->listing_id, $this->jsonEncodeProducts(), $this->priceVariationPropertyId);
  }

}
