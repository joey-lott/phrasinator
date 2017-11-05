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

Route::get("/", function() { return redirect("/home");});
Route::get('/generate', function () {
  return view("welcome");
});
Route::post("/generate", "GeneratorController@generate");
//Route::get('/authorize', "PinAuthController@getToken");

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
