<?php

namespace App\Etsy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class ShippingTemplate
{

  static public function createFromAPI($json) {
    $sts = json_decode($json);
    $a = [];
    foreach($sts as $stObj) {
      $st = new ShippingTemplate();
      $st->title = $stObj->title;
      $st->user_id = $stObj->user_id;
      $st->shipping_template_id = $stObj->shipping_template_id;
      $st->min_processing_days = $stObj->min_processing_days;
      $st->max_processing_days = $stObj->max_processing_days;
      array_push($a, $st);
    }
    return collect($a);
  }

  static public function getAllShippingTemplatesForUser($id) {

    // First, look for the templates in a saved file.
    if(Storage::exists("shipping_templates_{$id}.json")) {
      $json = Storage::get("shipping_templates_{$id}.json");
    }
    else {
      $api = resolve("\App\Etsy\EtsyAPI");
      $json = $api->fetchShippingTemplates($id);

      // Write the results to file for use in tests and to cache on the server
      // to reduce the number of calls to Etsy.
      Storage::put("shipping_templates_{$id}.json", $json);
    }

    $sts = ShippingTemplate::createFromAPI($json);

    return $sts;
  }

  static public function deleteCachedTemplateFileForUser($id) {
    if(Storage::exists("shipping_templates_{$id}.json")) {
      Storage::delete("shipping_templates_{$id}.json");
    }
  }
}
