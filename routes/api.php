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

//TODO Убрать тестовый роут
Route::post('/queue/image/test', 'Image\QueueController@test')->name('queue-image-test');

Route::group(['middleware' => 'auth.basic'], function () {
    // работа через очередь
    Route::prefix('/queue')->group( function () {
        // добавить в очередь загрузку изображения
        Route::post('/image/load', 'Image\QueueController@load')->name('queue-image-load');
    });

    Route::prefix('/image')->namespace('Image')->group(function () {
        //работа с изображениями из фотобанка
        Route::prefix('/photobank')->group(function () {
            $route = 'image-photobank';

            // загрузить изображение по ссылке в сервис
            Route::post('/load', 'PhotobankController@loadAction')->name($route . '-load-action');
            // прямая загрузка файла
            Route::post('/upload', 'PhotobankController@uploadAction')->name($route . '-upload-action');
            // удаление файла
            Route::post('/remove', 'PhotobankController@removeAction')->name($route . '-remove-action');
            // заблокировать изображение по ссылке
            Route::post('/block', 'PhotobankController@blockAction')->name($route . '-block-action');
            // получить список изображений по урлу (списку урлов)
            Route::post('/by-url', 'PhotobankController@byUrlView')->name($route . '-by-url-view');
            // получить список изображений по хешу (списку хешей)
            Route::post('/by-hash', 'PhotobankController@byHashView')->name($route . '-by-hash-view');
        });

        //работа с индивидуальными изображениями авто
        Route::prefix('/auto')->group(function () {
            $route = 'image-auto';

            // загрузить изображение в сервис
            Route::post('/load', 'AutoController@loadAction')->name($route . '-load-action');
            // прямая загрузка файла
            Route::post('/upload', 'AutoController@uploadAction')->name($route . '-upload-action');
            // удаление файла
            Route::post('/remove', 'AutoController@removeAction')->name($route . '-remove-action');
            // заблокировать изображение по ссылке
            Route::post('/block', 'AutoController@blockAction')->name($route . '-block-action');
            // получить список изображений по урлу (списку урлов)
            Route::post('/by-url', 'AutoController@byUrlView')->name($route . '-by-url-view');
            // получить список изображений по хешу (списку хешей)
            Route::post('/by-hash', 'AutoController@byHashView')->name($route . '-by-hash-view');
        });
    });
});
