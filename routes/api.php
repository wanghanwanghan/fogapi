<?php

use Illuminate\Http\Request;
use Geohash\GeoHash;

Route::group(['middleware'=>['init']],function ()
{
    //每日签到
    Route::match(['post'],'wanghan','QuanMinZhanLing\\SignInController@signIn');
    //展示签到
    Route::match(['get'],'wanghan','QuanMinZhanLing\\SignInController@showSign');







});

Route::group(['middleware'=>[]],function ()
{







});

