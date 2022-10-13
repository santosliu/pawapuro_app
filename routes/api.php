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

Route::post('/line/receive','LinebotController@msgReceive');

Route::post('/royal/receive','RoyalBotController@msgReceive');
Route::get('/royal/receive','RoyalBotController@msgReceive');

Route::post('/record/receive','RecordBotController@msgReceive');
Route::get('/record/receive','RecordBotController@msgReceive');

Route::post('/monica/receive','MonicaBotController@msgReceive');
Route::get('/monica/receive','MonicaBotController@msgReceive');

// Route::get('/line/send','LinebotController@msgSend');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
