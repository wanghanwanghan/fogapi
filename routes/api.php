<?php

use Illuminate\Http\Request;
use Geohash\GeoHash;

//防暴力请求中间件，请求中必须带uid
Route::group(['middleware'=>['init']],function ()
{
    //每日签到
    Route::match(['post'],'wanghan','QuanMinZhanLing\\SignInController@signIn');

    //展示签到
    Route::match(['get'],'wanghan','QuanMinZhanLing\\SignInController@showSign');

    //买格子
    Route::match(['post'],'BuyGrid','QuanMinZhanLing\\GridController@buyGrid');






});

Route::group(['middleware'=>[]],function ()
{
    //获取一个格子和周围格子的信息
    Route::match(['post'],'GetGridInfo','QuanMinZhanLing\\GridController@getGridInfo');

    //内容审核
    Route::match(['post'],'test','Server\\ContentCheckBase@check');







});

