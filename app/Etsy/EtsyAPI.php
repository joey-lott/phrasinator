<?php

namespace App\Etsy;

use GuzzleHttp\Client;
use \OAuth;
use App\Etsy\Models\ListingProduct;
use App\Etsy\Models\ListingOffering;
use App\Etsy\Models\ListingInventory;
use App\ApiCalls;
use Illuminate\Support\Facades\Storage;
use App\ListingThrottle;

class EtsyAPI
{
    private $apiKey;
    private $secret;

    public static $ALL_LISTINGS = "all_listings";
    public static $PAGE_LISTINGS = "page_listings";

    public function __construct($apiKey, $secret) {
      $this->apiKey = $apiKey;
      $this->secret = $secret;
    }

    public function setName($name) {
      $this->name = $name;
    }

    public function getListings() {
      return $this->listings;
    }

    public function getNextPage() {
      return $this->nextPage;
    }

    // Get the shop with $this->name as the ID/name from Etsy. Return it.
    // Can return either the shop data as an object or "404" as a string.
    // Consider refactoring to throw exception if no shop found.
    public function fetchShop($id) {
      $this->recordCall("fetchShop");
      $client = new Client;
      try {
        $results = $this->callGet("shops/".$id)->results;
        // $response = $client->get("https://openapi.etsy.com/v2/shops/".$this->name."?api_key=".$this->apiKey);
        // $results = json_decode($response->getBody())->results;
        if(count($results)) {
          // Maybe should convert to Shop object before returning.
          return $results[0];
        }
        else {
          return "404";
        }
      }
      catch(\GuzzleHttp\Exception\ClientException $error) {
        return "404";
      }
    }

    public function fetchShippingTemplates($userId) {
      $this->recordCall("fetchShippingTemplates");

      $formData = [
        "user_id" => $userId,
        "limit" => "100",
        ];
      $response = $this->callOAuth("users/".$userId."/shipping/templates", $formData, OAUTH_HTTP_METHOD_GET);

      $response = json_encode($response["results"]);

      return $response;
    }

    public function fetchListing($id) {
      $this->recordCall("fetchListing");
        $listing = $this->callGet("listings/".$id);
        dd($listing);
    }

    public function fetchListings($id, $page=1, $draftsBool = false) {
      $this->recordCall("fetchListings");
      $page = 1;
      $p = $this->fetchListingsPage($id, $page, $draftsBool);
      $results = $p->results;
      $this->listings = $results;
      return $results;
    }

    private function fetchListingsPage($id, $page, $draftsBool) {
      $this->recordCall("fetchListingsPage");
      $endpoint = "shops/".$id."/listings/";
      if($draftsBool) {
        $endpoint = $endpoint."draft";
      }
      else {
        $endpoint = $endpoint."active";
      }
      $params = "&page=".$page."&limit=100";
      if($draftsBool){
        $params = [];
        //dd($endpoint);
        return $this->callOAuth($endpoint, $params, OAUTH_HTTP_METHOD_GET);
      }
      else {
        return $this->callGet($endpoint, $params);
      }
    }

    public function fetchCountries() {
      $this->recordCall("fetchCountries");
      return $this->callGet("countries")->results;
    }

    public function fetchRegions() {
      $this->recordCall("fetchRegions");
      return $this->callGet("regions")->results;
    }

    public function fetchShippingTemplateEntries($templateId) {
      $this->recordCall("fetchShippingTemplateEntries");
      $formData = [
        "shipping_template_id" => $templateId,
        "limit" => "100"
        ];
        $response = $this->callOAuth("/shipping/templates/".$templateId."/entries", $formData, OAUTH_HTTP_METHOD_GET);
        return $response["results"];
    }

    public function fetchCountryByID($countryId) {
      $this->recordCall("fetchCountryByID");
      $response = $this->callGet("countries/".$countryId);
      dd($response);
    }

    public function finalizeAuthorization($secret, $token, $verifier) {
      $this->recordCall("fetchFinalizeAuthorization");
      $oauth = new \OAuth($this->apiKey, $this->secret);
      $oauth->setToken($token, $secret);
      try {
        $response = $oauth->getAccessToken("https://openapi.etsy.com/v2/oauth/access_token", null, $verifier);
        $user = auth()->user();

        $user->oauthToken = $response["oauth_token"];
        $user->oauthTokenSecret = $response["oauth_token_secret"];
        $user->save();
        return true;
      }
      catch(\OAuthException $exception) {
        return false;
      }

    }

    public function getEtsyAuthorizeLink() {
      $this->recordCall("getEtsyAuthorizeLink");
      $a = $this->apiKey;
      $b = $this->secret;

      $oauth = new \OAuth($this->apiKey, $this->secret);
      try {
        $response = $oauth->getRequestToken("https://openapi.etsy.com/v2/oauth/request_token?scope=listings_w%20listings_r", route("completeAuthorization"));
        setcookie("token_secret", $response["oauth_token_secret"]);
        return $response["login_url"];
      }
      catch (\OAuthException $e) {
        dd($e);
      }
    }

    public function fetchShippingTemplateById($id) {
      $this->recordCall("fetchShippingTemplateById");
      $template = $this->callOAuth("shipping/templates/".$id, null, OAUTH_HTTP_METHOD_GET);
      return (object) $template["results"][0];
    }

    public function createShippingTemplate($request) {
      $this->recordCall("createShippingTemplate");
      // create the shipping template that has both U.S> origin and destination.
      // Primary and secondary cost are equal (no shipping breaks for multiple items).
      $formData = [
        "title" => $request->title,
        "origin_country_id" => $request->origin_country_id,
        "primary_cost" => $request->ww_cost,
        "secondary_cost" => $request->ww_cost,
        "min_processing_days" => $request->min_processing_days,
        "max_processing_days" => $request->max_processing_days,
        ];
        // Etsy is returning an error when supplying these values. But should
        // include these.
        $response = $this->callOAuth("shipping/templates", $formData);
        $templateId = $response["results"][0]["shipping_template_id"];
        // Create a template entry for Canada
        $countryId = $this->fetchCountryIDByISO("CA");
        $data = ["destination_country_id" => $countryId, "cost" => $request->ca_cost];
        $this->createShippingTemplateEntry($templateId, $data);
        // Create a template entry for US
        $countryId = $this->fetchCountryIDByISO("US");
        $data = ["destination_country_id" => $countryId, "cost" => $request->us_cost];
        $template = $this->createShippingTemplateEntry($templateId, $data);
        return $template;
    }

    public function updateShippingTemplate($id, $request) {
      $this->recordCall("updateShippingTemplate");
      // create the shipping template that has both U.S> origin and destination.
      // Primary and secondary cost are equal (no shipping breaks for multiple items).
      $formData = [
        "title" => $request->title,
        "origin_country_id" => $request->origin_country_id,
        "min_processing_days" => $request->min_processing_days,
        "max_processing_days" => $request->max_processing_days,
        "shipping_template_id" => $id
        ];
        // Etsy is returning an error when supplying these values. But should
        // include these.
        $response = $this->callOAuth("shipping/templates/".$id, $formData, OAUTH_HTTP_METHOD_PUT);

        // Create a template entry for Canada
        $countryId = $this->fetchCountryIDByISO("CA");
        $data = ["destination_country_id" => $countryId, "cost" => $request->ca_cost];
        $this->updateShippingTemplateEntry($request->ca_entry_id, $id, $data);
        // Create a template entry for US
        $countryId = $this->fetchCountryIDByISO("US");
        $data = ["destination_country_id" => $countryId, "cost" => $request->us_cost];
        $this->updateShippingTemplateEntry($request->us_entry_id, $id, $data);

    }

    public function fetchCountryIDByISO($iso) {
      $this->recordCall("fetchCountryIDByISO");
      $response = $this->callGet("countries/iso/".$iso);
      return $response->results[0]->country_id;
    }

    public function createShippingTemplateEntry($templateId, $request) {
      $this->recordCall("createShippingTemplateEntry");
      // create the shipping template that has both U.S> origin and destination.
      // Primary and secondary cost are equal (no shipping breaks for multiple items).
      $formData = [
        "shipping_template_id" => $templateId,
        "destination_country_id" => $request["destination_country_id"],
        "primary_cost" => $request["cost"],
        "secondary_cost" => $request["cost"],
        ];
        $response = $this->callOAuth("shipping/templates/entries", $formData);
        return $response;
    }

    public function updateShippingTemplateEntry($entryId, $templateId, $request) {
      $this->recordCall("updateShippingTemplateEntry");
      // create the shipping template that has both U.S> origin and destination.
      // Primary and secondary cost are equal (no shipping breaks for multiple items).
      $formData = [
        "shipping_template_entry_id" => $entryId,
        "destination_country_id" => $request["destination_country_id"],
        "primary_cost" => $request["cost"],
        "secondary_cost" => $request["cost"],
        "shipping_template_id" => $templateId
        ];

        $response = $this->callOAuth("shipping/templates/entries/".$entryId, $formData, OAUTH_HTTP_METHOD_PUT);
        return $response;
    }

    public function fetchSellerTaxonomy() {
      $this->recordCall("fetchSellerTaxonomy");
      $response = $this->callGet("taxonomy/seller/get");
      return $response->results;
    }

    // listing ID 550543222
    // image URL https://gearbubble-assets.s3.amazonaws.com/5/1699738/43/235/front.png
    public function uploadImage($listingId, $imageUrl) {
        $this->recordCall("uploadImage");
        // Get the path to this app /temp/img.
        $path = dirname(realpath(__FILE__))."/temp/img";

        // If the /temp/img dir doesn't exist, create it.
        if(!is_dir($path)){
          mkdir($path, 0700, true);
        }

        // Get the image data from the remote file
        $imgData = file_get_contents($imageUrl);

        // The path to the temp image to create
        $imgPath = $path."/".$listingId;

        // Put the remote data in the temp file
        file_put_contents($imgPath, $imgData);

        // Provide the local path to the temp file so that oauth can upload it
        $formData = [
          "@image" => "@".$imgPath.";type=image/jpeg"
        ];

        $response = $this->callOAuth("/listings/".$listingId."/images", $formData, OAUTH_HTTP_METHOD_POST, true);

        // remote the file
        unlink($imgPath);

        // The response has a count property that is 1 if the file uploaded successfully. Otherwise, there will be an error.
        return $response["count"] == 1;
    }

    public function uploadDigitalListingPdf($listingId, $fileUrl) {
        // Get the path to this app /temp/img.
        $path = dirname(realpath(__FILE__))."/temp/file";

        // If the /temp/img dir doesn't exist, create it.
        if(!is_dir($path)){
          mkdir($path, 0700, true);
        }

        // Get the data from the remote file
        $data = file_get_contents($fileUrl);

        // The path to the temp image to create
        $filePath = $path."/".$listingId;

        // Put the remote data in the temp file
        file_put_contents($filePath, $data);

        // Provide the local path to the temp file so that oauth can upload it
        $formData = [
          "@file" => "@".$filePath.";type=application/pdf",
          "name" => "your_printable_file.pdf"
        ];

        $response = $this->callOAuth("/listings/".$listingId."/files", $formData, OAUTH_HTTP_METHOD_POST, true);

        // remote the file
        unlink($filePath);

        // The response has a count property that is 1 if the file uploaded successfully. Otherwise, there will be an error.
        return $response["count"] == 1;
    }


    public function fetchShopCurrentUser() {
      $this->recordCall("fetchShopCurrentUser");

      // Use the __SELF__ token that Etsy supports to retrieve the shop for the current user.
      // This requires OAuth to work even though otherwise the endpoint does not require OAuth.
      $response = $this->callOAuth("users/__SELF__/shops", null, OAUTH_HTTP_METHOD_GET);
      if(isset($response["error"])) {
        dump("There was an unhandled error. Please send the following to joeylott@gmail.com so I can know about this problem.");
        dump($response);
      }
      if(count($response["results"]) == 0) {
        dump("It appears that your Etsy shop is new and does not have any listings. In order to use the app, you must create at least one listing manually through your Etsy shop manager. If you believe that you have received this message in error or if you are having problems, send an email to joeylott@gmail.com with the subject line 'Lightning Lister - no shop error' and include the following in your message");
        dd($response);
      }
      return $response["results"][0];
    }

    public function fetchInventory($listingId) {
      $this->recordCall("fetchInventory");
      $inventory = $this->callOAuth("listings/".$listingId."/inventory", null, OAUTH_HTTP_METHOD_GET);
      return $inventory;
    }

    public function fetchTaxonomyProperties($taxonomyId) {
      $this->recordCall("fetchTaxonomyProperties");
      $properties = $this->callOAuth("taxonomy/seller/".$taxonomyId."/properties", null, OAUTH_HTTP_METHOD_GET);
      return $properties["results"];
    }

    public function createListing($listing) {
      $this->recordCall("createListing");

      $formData = [
        "quantity" => $listing->quantity,
        "title" => $listing->title,
        "description" => $listing->description,
        "price" => $listing->price,
        "taxonomy_id" => $listing->taxonomy_id,
        "tags" => $listing->tags,
        "who_made" => $listing->who_made,
        "when_made" => $listing->when_made,
        "state" => $listing->state,
        "is_supply" => $listing->is_supply,
        "shipping_template_id" => $listing->shipping_template_id,
        ];

      $response = $this->callOAuth("listings", $formData);

      // Return the error and handle it upstream
      if(isset($response["error"])) return $response;

      $listingRecord = $response["results"][0];
      $listingId = $listingRecord["listing_id"];

      if(count($listing->imagesToAddFromUrl)) {
        foreach($listing->imagesToAddFromUrlReversed() as $imageUrl) {
          $this->uploadImage($listingId, $imageUrl);
        }
      }

      $this->trackListingCreation();

      return $listingRecord;
    }

    // Keep track of each listing a user makes.
    private function trackListingCreation() {
      // $lt = new ListingThrottle();
      // $lt->user_id = auth()->user()->id;
      // $lt->save();
    }

    public function updateInventory($listingId, $inventory, $priceOnProperty) {
      $this->recordCall("updateInventory");
      $formData = ["products" => $inventory, "price_on_property" => $priceOnProperty];
      return $this->callOAuth("listings/".$listingId."/inventory", $formData, OAUTH_HTTP_METHOD_PUT);
    }


    public function fetchAllDrafts($shopId) {
      $formData = [];//["limit" => 25];
      return $this->callOAuth("shops/".$shopId."/listings/draft", $formData, OAUTH_HTTP_METHOD_GET);
    }

    public function updateWhoMadeOnDraft($listingId, $whoMade = "someone_else") {
      $formData = ["who_made" => $whoMade];
      return $this->callOAuth("listings/".$listingId, $formData, OAUTH_HTTP_METHOD_PUT);
    }

    public function fetchUser($login) {
      $this->recordCall("fetchUser");
      return $this->callGet("users/".$login);
    }

    public function addProvisionalUser($login) {
      $this->recordCall("addProvisionalUser");
      return $this->callPostV3("application/provisional-users/".$login);
    }

    private function callGet($endpoint, $params="") {
      $client = new Client;
      $response = $client->request("GET", "https://openapi.etsy.com/v2/".$endpoint."?api_key=".$this->apiKey."".$params);
      return json_decode($response->getBody());
    }

    private function callPostV3($endpoint) {
      $client = new Client;
      $params = ["headers" => ["x-api-key" => $this->apiKey]];
      $response = $client->request("POST", "https://openapi.etsy.com/v3/".$endpoint, $params);
      return json_decode($response->getBody());
    }

    private function callOAuth($endpoint, $params, $method=OAUTH_HTTP_METHOD_POST, $requestEngineCurl = false, $returnJson = false) {
      $oauth = new OAuth($this->apiKey, $this->secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
      $user = auth()->user();
      $oauth->setToken($user->oauthToken, $user->oauthTokenSecret);
      if($requestEngineCurl) {
        $oauth->setRequestEngine(OAUTH_REQENGINE_CURL);
      }
      $url = "https://openapi.etsy.com/v2/".$endpoint;
      try{
        if(count($params) == 0) {$params = null;}
        $response = $oauth->fetch($url, $params, $method);
        $json = $oauth->getLastResponse();
        if($returnJson) return $json;
        $obj = json_decode($json, true);
        return $obj;
      }
      catch(\OAuthException $e) {
        return ["params" => $params, "url" => $url, "error" => $e];
        dump("Your request produced an unhandled error. Please copy the following and send it in an email to joeylott@gmail.com with a subject line of 'GB Lightning Lister Bug Report'. Thank you.");
        dump($url);
        dump($params);
        dd($e);
      }
    }

    // Record the API call in the database
    private function recordCall($name) {
      // Commented out for now because I don't want to record this information
      // and take up database space at the moment. But this does work, and
      // I can uncomment it later if I want to record this information.
        // $ac = new APICalls();
        // $ac->name = $name;
        // $ac->save();
    }

}
