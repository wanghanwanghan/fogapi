<?php

use Illuminate\Http\Request;
use Geohash\GeoHash;

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

Route::group(['middleware'=>['init']],function ()
{
    //每日签到
    Route::match(['get','post'],'wanghan','QuanMinZhanLing\\SignInController@signIn');



});



