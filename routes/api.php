<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

//防暴力请求中间件，请求中必须带uid
Route::group(['middleware'=>['PVandUV']],function ()
{
    //买格子
    Route::match(['post'],'BuyGrid','QuanMinZhanLing\\GridController@buyGrid');

    //修改格子的名称和上传图片
    Route::match(['post'],'RenameGrid','QuanMinZhanLing\\GridController@renameGrid');
    Route::match(['post'],'UploadPic/pic1','QuanMinZhanLing\\GridController@uploadPic');

    //格子详情
    Route::match(['post'],'GridDetails','QuanMinZhanLing\\GridController@gridDetails');

    //增加用户金钱，比如完成每日任务，签到等
    Route::match(['post'],'SetUserMoney',function (Request $request){

        $uid=$request->uid;
        $money=$request->money;

        $user=new \App\Http\Controllers\QuanMinZhanLing\UserController();

        $user->exprUserMoney($uid,0,$money,'+');

        return response()->json(['resCode'=>Config::get('resCode.200'),'money'=>$user->getUserMoney(trim($request->uid))]);

    });

    //每日签到
    Route::match(['post'],'SignIn','QuanMinZhanLing\\SignInController@signIn');

    //展示签到
    Route::match(['get'],'SignIn','QuanMinZhanLing\\SignInController@showSign');

    //获取用户金钱和购地卡数量
    Route::match(['post'],'GetUserInfo',function (Request $request){

        $user=new \App\Http\Controllers\QuanMinZhanLing\UserController();

        $money=$user->getUserMoney(trim($request->uid));

        $card=$user->getBuyCardCount(trim($request->uid));

        return response()->json(['resCode'=>Config::get('resCode.200'),'money'=>$money,'card'=>$card]);

    });

    //获取一个格子和周围格子的信息
    Route::match(['post'],'GetGridInfo','QuanMinZhanLing\\GridController@getGridInfo');

    //获取最近的交易信息
    Route::match(['post'],'GetRecentlyTradeInfo','QuanMinZhanLing\\UserController@getRecentlyTradeInfo');

    //获取用户每日任务
    Route::match(['post'],'GetDailyTasksForUser','QuanMinZhanLing\\DailyTasksController@getDailyTasksForUser');

    //设置用户每日任务
    Route::match(['post'],'SetDailyTasksForUser','QuanMinZhanLing\\DailyTasksController@setDailyTasksForUser');

    //获取每天随机的5个每日任务
    Route::match(['get'],'GetDailyTasks','QuanMinZhanLing\\DailyTasksController@getDailyTasks');

    //获取成就主表
    Route::match(['get'],'GetAchievement','QuanMinZhanLing\\AchievementController@getAchievement');



    //获取用户格子全部信息
    Route::match(['get'],'GetUserGridInfo','QuanMinZhanLing\\UserController@getUserGridInfo');

    //获取格子生涯概况
    Route::match(['get'],'GetGridCareer','QuanMinZhanLing\\UserController@getGridCareer');

    //内容审核
    Route::match(['post'],'ContentCheck','Server\\ContentCheckBase@check');

    //获取/统计用户成就
    Route::match(['get','post'],'GetAchievementForUser','QuanMinZhanLing\\AchievementController@getAchievementForUser');

    //获取排行榜信息
    Route::match(['get','post'],'GetRankList','QuanMinZhanLing\\RankListController@getRankList');

    //成就领取单机领取按钮，数据入库
    Route::match(['post'],'SetAchievementForUser','QuanMinZhanLing\\AchievementController@setAchievementForUser');

    //去tssj数据库更新用户头像
    Route::match(['get'],'ChangeAvatarAlready','QuanMinZhanLing\\UserController@changeAvatarAlready');

    //获取系统通知
    Route::match(['get'],'GetSystemMessage','QuanMinZhanLing\\SystemController@getSystemMessage');

    //获取系统通知详情
    Route::match(['get'],'GetSystemMessage/{id}','QuanMinZhanLing\\SystemController@getSystemMessageDetail');

    //是否显示小红点
    Route::match(['get'],'ShowRedDot','QuanMinZhanLing\\SystemController@showRedDot');
});

