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
//Route::post('/image/queue/test', 'Image\QueueController@test')->name('image-queue-test');
//Route::post('/image/queue/test-migrate', 'Image\QueueController@testMigrate')->name('image-queue-test-migrate');
Route::post('/image/queue/test-import', 'Image\QueueController@testImport')->name('image-queue-test-import');

Route::group(['middleware' => 'auth.basic'], function () {
    Route::prefix('/image')->namespace('Image')->group(function () {
        // работа через очередь
        Route::prefix('/queue')->group( function () {
            // добавить в очередь загрузку изображения через интерфейс (высокий приоритет)
            Route::post('/load', 'QueueController@load')->name('image-queue-load');
            // добавить в очередь загрузку изображения через импорт (обычный приоритет)
            Route::post('/import', 'QueueController@import')->name('image-queue-import');
            // добавить в очередь задачу на обновление изображений
            Route::post('/import/update', 'QueueController@importUpdate')->name('image-queue-import-update');

            // добавить в очередь миграции (обычный приоритет)
            Route::post('/migrate', 'QueueController@migrate')->name('image-queue-migrate');

            Route::post('/test-result', 'QueueController@testSendServiceUrl')->name('image-queue-test-result');
        });

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

    Route::prefix('/record')->namespace('Calltracking')->group(function () {
        Route::post('/store', 'RecordController@store')->name('record-store');
    });
});
