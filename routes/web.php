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

Route::get('/', function () {
    return view('index');
});

// Route::get('/test','LinebotController@test');

//依照學校以及編號，需要兩種顯示方式
Route::get('/deck/no/{deck_id}', 'DeckController@byDeck');
Route::get('/school/{school_id}', 'DeckController@bySchool');

