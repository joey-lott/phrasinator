<?php

namespace App\Etsy\Models;

use App\Etsy\Models\ListingStagingData;
use App\Etsy\Models\ListingInventory;

class Listing {

  public $quantity = 999;
  public $title;
  public $description;
  public $price;
  public $taxonomy_id;
  public $tags;
  public $who_made = "i_did";
  public $when_made = "made_to_order";
  public $state = "draft";
  public $is_supply = "false";
  public $processing_min = 7;
  public $processing_max = 14;
  public $imagesToAddFromUrl = [];
  public $shipping_template_id;

  public $inventory;
  public $staging;
  public $priceVariationPropertyId;
  public $digitalFileUrls;

  public function __construct($t, $d, $p, $tid, $tags, $stid, $urls) {
    $this->title = $t;
    $this->description = $d;
    $this->price = $p;
    $this->taxonomy_id = $tid;
    $this->tags = $tags;
    $this->imagesToAddFromUrl = $urls;
    $this->staging = new ListingStagingData();
    $this->shipping_template_id = $stid;
    $this->digitalFileUrls = [];
  }

  public function addDigitalFileUrl($url) {
    $this->digitalFileUrls[] = $url;
  }

  public function addImageUrl($url) {
    array_push($this->imagesToAddFromUrl, $url);
  }

  public function saveToEtsy() {
    if(!isset($this->price)) {
      $this->price = $this->staging->getLowestPrice();
    }
    $api = resolve("\App\Etsy\EtsyAPI");
    $listing = $api->createListing($this);

    // If "error" is set in the response, it means there was an error
    // thrown by Etsy. In which case, $listing is an array with error
    // information. Return it now and don't try to add inventory.
    if(isset($listing["error"])) return $listing;

    if(count($this->digitalFileUrls) > 0) {
      foreach($this->digitalFileUrls as $url) {
        $api->uploadDigitalListingPdf($listing["listing_id"], $url);
      }
    }

    return $listing;
  }

  public function createListingInventory($listingId) {
    return new ListingInventory($listingId, $this->staging->products, $this->priceVariationPropertyId);
  }

  public function imagesToAddFromUrlReversed() {
    return array_reverse($this->imagesToAddFromUrl);
  }

}
