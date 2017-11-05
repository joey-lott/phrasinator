<?php

namespace App\GearBubble;

use GuzzleHttp\Client as GClient;
use Goutte\Client;

class Browser {


  public function getPage($url) {
    $client = new Client();
    $crawler = $client->request("GET", $url);
    return $crawler->getResponse()->getStatusCode();
  }

}
