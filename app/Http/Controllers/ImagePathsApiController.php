<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ImagePaths;

class ImagePathsApiController extends Controller
{

  public function search($userId) {
    $paths = ImagePaths::where("userId", $userId)->get()->all();
    return $paths;
  }

}
