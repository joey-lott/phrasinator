<?php

namespace App\Etsy\Models;

use App\Etsy\Models\ListingProduct;

// This class is a helper that allows me to define listing inventory data
// for a listing object before the listing ID is obtained. Hence, "staging"
// because this data is waiting for the listing ID to be obtained. Then,
// the listing object can submit inventory based on this staged data;
class ListingStagingData {

  public $products = [];

  public $stagingId;
  public $colors;
  public $sizes;
  public $primaryVariations;
  public $priceOnProperty;

  public function addProduct(ListingProduct $product) {
    array_push($this->products, $product);
  }

  public function getLowestPrice() {
    $lowest = 999999999999999999999999999;
    foreach($this->products as $product) {
      $productPrice = $product->getLowestPrice();
      if($productPrice < $lowest) {
            $lowest = $productPrice;
      }
    }
    return $lowest;
  }

  public function hasProducts() {
    return count($this->products) > 0;
  }

}
