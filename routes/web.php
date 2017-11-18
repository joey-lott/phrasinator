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
  var_dump(get_resources());
  dump("memory: ".memory_get_usage());
  gc_enable();
  gc_collect_cycles();
  dump("memory: ".memory_get_usage());
  dump("peak memory: ".memory_get_peak_usage());

  // $phrase = "señor 123 456:::7890 [[[color=FF0000]]]a:::[[[/color]]][[[color=00FF00]]]bc defg hijk[[[/color]]] lmno p[[[color=0000FF]]]qrs[[[/color]]]tu";
  // $t2m = new TextToMarkup($phrase);
  // dd($t2m->words);
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
