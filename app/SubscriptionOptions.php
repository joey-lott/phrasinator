<?php

namespace App;

use Illuminate\Support\Facades\Storage;

class SubscriptionOptions {


  static public function getOptions() {
    return explode(",", trim(Storage::disk('local')->get('subscription-options.txt')));
  }

}
