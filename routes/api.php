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
    Route::prefix('/image')->group(function () {
        Route::prefix('/photobank')->group(function () {
            // загрузить изображение в фотобанк по ссылке в сервис
            Route::post('/load', 'ImageController@loadAction')->name('image-load-action');
            // заблокировать изображение по ссылке
            Route::post('/block', 'ImageController@blockAction')->name('image-block-action');
            // получить список изображений по урлу (списку урлов)
            Route::post('/by-url', 'ImageController@byUrlView')->name('image-by-url-view');
            // получить список изображений по хешу (списку хешей)
            Route::post('/by-hash', 'ImageController@byHashView')->name('image-by-hash-view');
        });

        //работа с индивидуальными изображениями авто
        Route::prefix('/auto')->group(function () {
            // загрузить индивидуальное изображение автомобиля в сервис
            Route::post('/load', 'ImageController@loadAutoAction')->name('image-auto-load-action');
        });
    });
});
