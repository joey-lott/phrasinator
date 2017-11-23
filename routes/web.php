<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Http\Request;
use App\Pinterest\PinAPI;
use App\RPL\CompositeImage;
use App\RPL\StraightOuttaImage;

Route::get("/", function() { return redirect("/home");});

//Route::get('/authorize', "PinAuthController@getToken");

// Uncomment to use non-queued version
//Route::post("/generate", "GeneratorController@generate");

// This is the queued version. Comment this if useing the non-queued version
Route::post("/generate", "GeneratorController@generateQueuedImageJob");

Route::get('/generate', "GeneratorController@form");

Route::get('/straightoutta', function () {
  return "<html><body><form action='/straightoutta' method='post'>".csrf_field()."outta what? <input type='text' name='outta'><br>move it left? <input type='text' name='moveLeft' value='0'><br><button>MAKE IT</button></body></html>";
});
Route::post('/straightoutta', function (Request $request) {
  $image = new StraightOuttaImage($request->outta);
  $uris = $image->render($request->moveLeft);
  return "<html><body><img src='".$uris["black"]."' width='280' height='300'><img src='".$uris["white"]."' width='280' height='300' style='background-color:black'></body></html>";
});

use App\RPL\TextImageV2;
Route::get('/test', function(Request $request) {
  $composite = new CompositeImage(1000, 1000);
  $fontName = isset($request->fontName) ? $request->fontName : "knockout.ttf";
  $phrase = isset($request->phrase) ? $request->phrase : "tesT [[[color=ff0000]]]ph[[[/color]]]rasE";
  $image = new TextImageV2($phrase, $fontName, 1000, 1000);
  $image->adjustFontToFillSpace();
  $resource = $image->generateImageResource();
  $composite->addAbove($resource);
  $composite->setTransparent($image->transparent);
  $path = $composite->saveToDisk($image->getFileName(), auth()->user()->id);
  dd($path);
//  return "<html><body><img src='images/{$path}'></body></html>";
});

use App\RPL\TextImageLayout;
use App\RPL\TextToMarkup;
Route::get('/test2', function(Request $request) {
  $t2m = new TextToMarkup("this [[[color=ff0000]]]is[[[/color]]] a phrase");
  $til = new TextImageLayout($t2m, base_path()."/fonts/knockout.ttf");
  $image = imagecreatetruecolor(1,1);
  $imgColor = imagecolorallocate($image, 0, 0, 0);
  $x = 0;
  $printText = "a";
  $bBox = imagettftext($image, 100, 0, $x, 0, $imgColor, base_path()."/fonts/knockout.ttf", $printText);
  dump(($bBox[1] - $bBox[5]));
  $printText = "b";
  $bBox = imagettftext($image, 100, 0, $bBox[2], 0, $imgColor, base_path()."/fonts/knockout.ttf", $printText);
//  dump($bBox);
  dump(($bBox[1] - $bBox[5]));
  $printText = "g";
  $bBox = imagettftext($image, 200, 0, $bBox[2], 0, $imgColor, base_path()."/fonts/knockout.ttf", $printText);
  dump(($bBox[1] - $bBox[5]));

});

use Illuminate\Support\Facades\Storage;
Route::get("/tests3", function() {
  Storage::makeDirectory("test");
  $directories = Storage::directories("/");
  dd($directories);
  $files = Storage::files("/");
  dd($files);
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

use App\ImagePaths;
use App\Jobs\GenerateCompositeImage;

Route::get('imageJob', function() {
//   $user = auth()->user();
// //  dump($user);
//   $userId = $user->id;
//   ImagePaths::where("userId", $userId)->delete();
//   GenerateCompositeImage::dispatch(auth()->user()->id, 3000, 3000, "this is a test", "knockout.ttf", "none", null, 0, "center", base_path()."/public/images/".$userId."/");
});
