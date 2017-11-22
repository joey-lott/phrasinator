<?php

use Illuminate\Http\Request;

Route::get('/search-pixabay', "PixabayApiController@search");
Route::get('/get-image-paths/{userId}', "ImagePathsApiController@search");
