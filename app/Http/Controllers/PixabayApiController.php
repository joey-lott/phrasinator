<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Pixabay\PixabayClient;

class PixabayApiController extends Controller
{

  public function search(Request $request) {
    $apiKey = env("PIXABAY_API_KEY");
    $page = isset($request->page) ? $request->page : 1;
    $imageType = isset($request->vectorOnly) ? "vector" : "all";
    $client = new PixabayClient(["key" => $apiKey]);
    return $client->get(["q" => $request->keyword, "page" => $page, "image_type" => $imageType], true);
  }

}
