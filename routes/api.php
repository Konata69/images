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
    Route::prefix('/image')->namespace('Image')->group(function () {
        //работа с изображениями из фотобанка
        Route::prefix('/photobank')->group(function () {
            // загрузить изображение по ссылке в сервис
            Route::post('/load', 'PhotobankController@loadAction')->name('image-load-action');
            // заблокировать изображение по ссылке
            Route::post('/block', 'PhotobankController@blockAction')->name('image-block-action');
            // получить список изображений по урлу (списку урлов)
            Route::post('/by-url', 'PhotobankController@byUrlView')->name('image-by-url-view');
            // получить список изображений по хешу (списку хешей)
            Route::post('/by-hash', 'PhotobankController@byHashView')->name('image-by-hash-view');
        });

        //работа с индивидуальными изображениями авто
        Route::prefix('/auto')->group(function () {
            // загрузить изображение в сервис
            Route::post('/load', 'AutoController@loadAutoAction')->name('image-auto-load-action');
            // заблокировать изображение по ссылке
            Route::post('/block', 'AutoController@blockAction')->name('image-block-action');
            // получить список изображений по урлу (списку урлов)
            Route::post('/by-url', 'AutoController@byUrlView')->name('image-by-url-view');
            // получить список изображений по хешу (списку хешей)
            Route::post('/by-hash', 'AutoController@byHashView')->name('image-by-hash-view');
        });
    });
});
