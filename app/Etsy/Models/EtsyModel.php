<?php

namespace App\Etsy\Models;


abstract class EtsyModel implements \JsonSerializable {

  abstract public function jsonSerialize();




}
