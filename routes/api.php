<?php

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




Route::group(['middleware' => ['HasAccess']], function () {

    /**
     * user
     */

    Route::group(['prefix' => 'user'], function () {
        Route::get('', "UserController@auth")->middleware('auth:api');
        Route::get('login', "UserController@login");
        Route::get('find', "UserController@find");
        Route::get('logout', "UserController@logout")->middleware('auth:api');
        Route::post('', "UserController@signup");
        Route::post('update', "UserController@update")->middleware('auth:api');
        Route::delete('', "UserController@destroy")->middleware('auth:api');
    });

});