<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Pinterest\PinAPI;

class PinAuthController extends Controller
{
    public function __construct()
    {
    }

    public function getToken(Request $request) {
      $api = new PinAPI();
      $api->getToken($request->code);
    }
}
