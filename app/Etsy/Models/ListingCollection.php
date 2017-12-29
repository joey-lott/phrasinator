<?php

namespace App\Etsy\Models;

class ListingCollection extends EtsyModel {

  private $listings = [];

  public function count() {
    return count($this->listings);
  }

  public function add($listing) {
    array_push($this->listings, $listing);
  }

  public function first() {
    return $this->listings[0];
  }

  public function getAt($i) {
    return $this->listings[$i];
  }

  public function jsonSerialize() {
    return ["listings" => $this->listings];
  }

}
