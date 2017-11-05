<?php

namespace App\Pinterest;

use GuzzleHttp\Client;

class PinAPI {

  protected $appId;
  protected $appSecret;
  protected $redirectUrl;

  public function __construct() {
    $this->appId = env("PIN_APP_ID");
    $this->appSecret = env("PIN_APP_SECRET");
    $this->redirectUrl = env("PIN_APP_REDIRECT_URL");
  }

  public function getAuthorizationUrl() {
    $url = "https://api.pinterest.com/oauth/?response_type=code&redirect_uri={$this->redirectUrl}&client_id={$this->appId}&scope=read_public&state=768uyFys";
    return $url;
  }

  public function getToken($code) {
    $url = "https://api.pinterest.com/v1/oauth/token?grant_type=authorization_code&code={$code}&client_id={$this->appId}&client_secret={$this->appSecret}";
//    $url = "https://api.pinterest.com/v3/oauth?grant_type=authorization_code&code={$code}&client_id={$this->appId}&client_secret={$this->appSecret}";
//    $params = ["grant_type" => "authorization_code", "code" => $code, "client_id" => $this->appId, "client_secret" => $this->appSecret];
//    dd($params["grant_type"]);
    // $client = new Client();
    // $response = $client->post($url);//, ["data" => $params]);
    // $headers = $response->getHeaders();
    // $token = $headers["X-Pinterest-RID"][0];
    // dd($response->getBody());
    $oauth = new \OAuth($this->appId, $this->appSecret);
    $response = $oauth->getAccessToken($url);
    dd($response);
  }

//  public function

}
