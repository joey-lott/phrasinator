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

Route::get('/authorize', "UserController@etsyAuthorize");
Route::get('/authorize/complete', "UserController@completeAuthorization")->name('completeAuthorization');


// Uncomment to use non-queued version
//Route::post("/generate", "GeneratorController@generate");

// This is the queued version. Comment this if useing the non-queued version
Route::post("/generate", "GeneratorController@generateQueuedImageJob");

Route::get('/generate', "GeneratorController@form");

Route::get('/generate/print', "PrintGeneratorController@form");
Route::post("/generate/print", "PrintGeneratorController@generateQueuedImageJob");

Route::get('/image/upload', "UploadImageController@form");
Route::post('/image/upload', "UploadImageController@upload");


Route::get('/list', "ProductController@showForm");
Route::post('/list', "ProductController@submit");

Route::get('/straightoutta', function () {
  return "<html><body><form action='/straightoutta' method='post'>".csrf_field()."outta what? <input type='text' name='outta'><br>move it left? <input type='text' name='moveLeft' value='0'><br><button>MAKE IT</button></body></html>";
});
Route::post('/straightoutta', function (Request $request) {
  $image = new StraightOuttaImage($request->outta);
  $uris = $image->render($request->moveLeft);
  return "<html><body><img src='".$uris["black"]."' width='280' height='300'><img src='".$uris["white"]."' width='280' height='300' style='background-color:black'></body></html>";
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

use App\ImagePaths;
use App\Jobs\GenerateCompositeImage;

Route::get("subscribe", "SubscribeController@showPaymentForm");

Route::post("subscribe", "SubscribeController@subscribe");

Route::get('/account', 'AccountController@myAccount');
Route::get('/account/welcome', 'AccountController@welcome');
Route::get('/account/cancel', 'AccountController@cancelForm');
Route::post('/account/cancel', 'SubscribeController@cancel');
Route::post('/account/resume', 'SubscribeController@resume');
Route::get('/account/change', 'SubscribeController@showChangePlanForm');
Route::post('/account/change', 'SubscribeController@changePlan');

use App\AppSecrets;
use App\DbLog;

Route::get("/log-view", function() {
  $logs = DbLog::orderBy("created_at", "desc")->get()->all();
  echo "<ul>";
  foreach($logs as $log) {
    echo "<li>{$log->context}, {$log->message}, {$log->created_at}</li>";
  }
  echo "</ul>";
});


use App\RPL\TextImageV3;
use App\RPL\Color;
use App\RPL\CompositeImageV2;
use App\Jobs\TestJob;

Route::get("/dispatch-test", function() {
    TestJob::dispatch()->onConnection("database");
});

Route::get("/test", function() {
  $phrase = "the good quick [[[color=ff2929]]]brown[[[/color]]] fox jumped over the lazy dog";
  $fontName = "shadowsintolight.ttf";
  $width = 3000;
  $height = 3000;
  $heightRemaining = 1000;
  $color = new Color("000000");
  $lineSpacing = "0.17";
  $imageLocation = "above";
  $pixabayImage= "https://cdn.pixabay.com/photo/2017/11/22/22/53/nuts-2971675_960_720.jpg";
  $textJustification = "center";
  Storage::disk("local")->makeDirectory("temp");
  $tempPath = Storage::disk("local")->url("temp");
  dump($tempPath);
  $imagePath = base_path()."/storage/app/temp/";
  dump($imagePath);
  $g = new GenerateCompositeImage(1, $width, $height, $phrase, $fontName,
                              $imageLocation, $pixabayImage, $lineSpacing,
                              $textJustification, $imagePath);
  $url = $g->handle();
  dd($url);
  $image = new TextImageV3($phrase, $fontName, $width, $heightRemaining, $color, $lineSpacing, $textJustification);
  $image->adjustFontToFillSpace();
  $resource = $image->generateImageResource();
// $image->saveImage("test2");
// return "<img src='images/test2.png'>";
 $composite = new CompositeImageV2(3000, 3000);

  $composite->fetchFromUrl("https://cdn.pixabay.com/photo/2017/11/22/22/53/nuts-2971675_960_720.jpg");
  $heightRemaining = $composite->fetchHeightRemaining();
  $composite->addBelow($resource);
  $url = $composite->saveToDisk("test.png", "test");
  return "<img src='{$url}'>";
  // $font = "comingsoon.ttf";
  $font = base_path()."/fonts/".$fontName;
  $img = new \Imagick();
  $img->newImage(1000, 1000, "none");
  $draw = new ImagickDraw();
  $draw->setFont($font);
  $draw->setFillColor('black');
  $draw->setFontSize( 100 );
  $printText = "g";
  $metrics = $img->queryFontMetrics($draw, $printText);
  $x = $metrics["originX"];
  dump($metrics);
  $img->annotateImage($draw, 0, $metrics["ascender"], 0, $printText);
  $printText = "H";

  $metrics = $img->queryFontMetrics($draw, $printText);
  $totalWidth = $x + $metrics["originX"];

  dump($metrics);
  $img->annotateImage($draw, $x, $metrics["ascender"], 0, $printText);
  $img->writeImage(base_path()."/public/images/test.png");
  dump($totalWidth);
  $metrics = $img->queryFontMetrics($draw, "gH");
  dump($metrics);
  $width = $metrics["textWidth"];
  return "<img src='images/test.png'>";
  // $red = imagecolorallocate($image, 254, 1, 1);

});

Route::post(
    'stripe/webhook',
    '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook'
);
