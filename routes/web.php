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


Auth::routes([
    'register' => false,
    'reset' => false,
    'verify' => false,
]);
Route::get('logout', 'Auth\LoginController@logout')->name('logout');

Route::any('{query}', function() {
    return redirect('/login');
})->where('query', '.*');
