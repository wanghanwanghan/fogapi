<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

//防暴力请求中间件，请求中必须带uid
Route::group(['middleware'=>['init']],function ()
{
    //每日签到
    Route::match(['post'],'SignIn','QuanMinZhanLing\\SignInController@signIn');

    //展示签到
    Route::match(['get'],'SignIn','QuanMinZhanLing\\SignInController@showSign');

    //买格子
    Route::match(['post'],'BuyGrid','QuanMinZhanLing\\GridController@buyGrid');

    //修改格子的名称和上传图片
    Route::match(['post'],'RenameGrid','QuanMinZhanLing\\GridController@renameGrid');
    Route::match(['post'],'UploadPic/pic1','QuanMinZhanLing\\GridController@uploadPic');

    //格子详情
    Route::match(['post'],'GridDetails','QuanMinZhanLing\\GridController@gridDetails');

    //成就领取单机领取按钮，数据入库
    Route::match(['post'],'AchievementComplete','QuanMinZhanLing\\AchievementController@achievementComplete');




});

Route::group(['middleware'=>[]],function ()
{
    //获取用户金钱和购地卡数量
    Route::match(['post'],'GetUserInfo',function (Request $request){

        $user=new \App\Http\Controllers\QuanMinZhanLing\UserController();

        $money=$user->getUserMoney(trim($request->uid));

        $card=$user->getBuyCardCount(trim($request->uid));

        return response()->json(['resCode'=>Config::get('resCode.200'),'money'=>$money,'card'=>$card]);

    });

    //获取一个格子和周围格子的信息
    Route::match(['post'],'GetGridInfo','QuanMinZhanLing\\GridController@getGridInfo');

    //内容审核
    Route::match(['post'],'ContentCheck','Server\\ContentCheckBase@check');






    //统计用户成就
    Route::match(['get','post'],'RequestAchievement','QuanMinZhanLing\\AchievementController@requestAchievement');
});

