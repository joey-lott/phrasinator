<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\RPL\TextImage;

class GeneratorController extends Controller
{

    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function generate(Request $request) {
      //ini_set('max_execution_time', 60);
      $quotes = explode("\r\n", $request->quotes);
      $output = "<html><head><title>phrasinator</title></head><body>";
      dd($output);
      foreach($quotes as $quote) {
        $image = new TextImage($quote, $request->fontName);
        $image->adjustFontToFillSpace();
        $paths = $image->saveImage("{$quote}", $request->fontSize);
        $path1 = "/images/".($paths[0]);
        $path2 = "/images/".($paths[1]);
        $output .= "<img src='{$path1}' width='200' height='200' border='1'><img src='{$path2}' width='200' height='200' border='1' style='background-color:black'>";
      }
      $output .= "</body></html>";
      return $output;
    }

}
