<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('product', 'App\Http\Controllers\ApiController@getDataProduct');
Route::get('customer', 'App\Http\Controllers\ApiController@getDataCustomer');
Route::post('transaction', 'App\Http\Controllers\ApiController@postTransaction');
Route::get('transaction', 'App\Http\Controllers\ApiController@getTransaction');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
