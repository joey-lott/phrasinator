<?php

namespace App;

class AppSecrets {

  private $secrets;

  public function getSecrets() {
    $file = file_get_contents(env("APP_SECRETS"));
    $this->secrets = json_decode($file, true);
  }

  public function getSecret($key) {
    if(isset($this->secrets["CUSTOM"][$key])) {
      return $this->secrets["CUSTOM"][$key];
    }
    else {      
       return false;
    }
  }

  static public function get($key) {
    $as = new AppSecrets();
    $as->getSecrets();
    $secret = $as->getSecret($key);
    if(!$secret) $secret = env($key);
    return $secret;
  }


}
