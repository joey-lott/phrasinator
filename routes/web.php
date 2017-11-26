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

use App\Fonts;

Route::get("subscribeStatus", function() {
//  $options = SubscriptionOptions::getOptions();
//dump(auth()->user()->subscribed("phrasinator-basic-monthly"));
  dump(auth()->user()->getCurrentSubscription());
  // dump(auth()->user()->hasCardOnFile());
  //dump(auth()->user()->onTrial("phrasinator-basic-monthly"));
  // dump(auth()->user()->upcomingInvoice());
  // dump(auth()->user()->defaultCard());
//  dump(auth()->user()->onGracePeriodDefaultSubscription());
});

Route::post(
    'stripe/webhook',
    '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook'
);
