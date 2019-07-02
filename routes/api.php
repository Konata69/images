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

Route::group(['middleware' => 'auth.basic'], function () {
    // загрузить изображение в фотобанк по ссылке в сервис
    Route::post('/image/load', 'ImageController@loadAction')->name('image-load-action');
    // загрузить индивидуальное изображение автомобиля в сервис
    Route::post('/image/load-auto', 'ImageController@loadAutoAction')->name('image-load-auto-action');
    // заблокировать изображение по ссылке
    Route::post('/image/block', 'ImageController@blockAction')->name('image-block-action');
    // получить список изображений по урлу (списку урлов)
    Route::post('/image/by-url', 'ImageController@byUrlView')->name('image-by-url-view');
    // получить список изображений по хешу (списку хешей)
    Route::post('/image/by-hash', 'ImageController@byHashView')->name('image-by-hash-view');
});
