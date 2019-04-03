<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/image/load', 'ImageController@loadAction')->name('image-load-action');
Route::post('/image/block', 'ImageController@blockAction')->name('image-block-action');
Route::post('/image/by-url', 'ImageController@byUrlView')->name('image-by-url-view');
Route::post('/image/by-hash', 'ImageController@byHashView')->name('image-by-hash-view');
