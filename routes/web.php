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

use App\RPL\StraightOuttaImage;

Route::get("/", function() { return redirect("/home");});

Route::post("/generate", "GeneratorController@generate");
//Route::get('/authorize', "PinAuthController@getToken");

Route::get('/generate', "GeneratorController@form");


Route::get('/straightoutta', function () {
  return "<html><body><form action='/straightoutta' method='post'>".csrf_field()."outta what? <input type='text' name='outta'><br>move it left? <input type='text' name='moveLeft' value='0'><br><button>MAKE IT</button></body></html>";
});
Route::post('/straightoutta', function (Request $request) {
  $image = new StraightOuttaImage($request->outta);
  $uris = $image->render($request->moveLeft);
  return "<html><body><img src='".$uris["black"]."' width='280' height='300'><img src='".$uris["white"]."' width='280' height='300' style='background-color:black'></body></html>";
});

use App\RPL\TextToMarkup;
Route::get('/test', function() {

  $font = "GILLUBCD.ttf";
  $fontSize = 150;
  $image = imagecreatetruecolor(500, 500);
  $black = imagecolorallocate($image, 0, 0, 0);
  $white = imagecolorallocate($image, 255, 255, 255);
  $red = imagecolorallocate($image, 255, 0, 0);
  $green = imagecolorallocate($image, 0, 255, 0);
  imagefilledrectangle($image, 0, 0, 500, 500, $white);


  $phrase = "PSYCHOTIC TEACHER";
  $chars = preg_split("//u", $phrase, null, PREG_SPLIT_NO_EMPTY);

  $x = 0;
  $y = 200;
  $kerning = 0;
  foreach($chars as $char) {
    $charResponse = imagettftext($image, $fontSize, 0, $x, $y, $black, "../fonts/".$font, $char);
    $characterBox = imagettfbbox($fontSize, 0, "../fonts/".$font, $char);
    var_dump($characterBox);
    var_dump($charResponse);
    //$characterWidth = $characterBox[2] - $characterBox[0];
    $x = $charResponse[2] + $kerning;
    //var_dump($charResponse[4]);
  }


  imagedestroy($image);

  // $phrase = "señor 123 456:::7890 [[[color=FF0000]]]a:::[[[/color]]][[[color=00FF00]]]bc defg hijk[[[/color]]] lmno p[[[color=0000FF]]]qrs[[[/color]]]tu";
  // $t2m = new TextToMarkup($phrase);
  // dd($t2m->words);
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
