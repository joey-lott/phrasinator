<?php

namespace App;

class AppSecrets {

  private $secrets;

  public function getSecrets() {
    $file = file_get_contents(env("APP_SECRETS"));
    $this->secrets = json_decode($file, true);
  }

  public function getSecret($key) {
    return $this->secrets["CUSTOM"][$key];
  }

  static public function get($key) {
    $as = new AppSecrets();
    $as->getSecrets();
    return $as->getSecret($key);
  }


}
