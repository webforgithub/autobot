<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('chart', 'ChartController@index');
Route::get('get-chart/{symbol}', 'ChartController@getChart');

/* ================== Homepage + Admin Routes ================== */

require __DIR__.'/admin_routes.php';
