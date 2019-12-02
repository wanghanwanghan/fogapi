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
    Route::match(['post'],'UploadPic/pic1','QuanMinZhanLing\\GridController@uploadPic');//格子背景图
    Route::match(['post'],'UploadPic/pic2','QuanMinZhanLing\\GridController@uploadPic');//格子第一背景图
    Route::match(['post'],'UploadPic/picInRedis1','QuanMinZhanLing\\GridController@uploadPic');//什么鸡巴玩意

    //格子详情
    Route::match(['post'],'GridDetails','QuanMinZhanLing\\GridController@gridDetails');

    //增加用户金钱，比如完成每日任务，签到等
    Route::match(['post'],'SetUserMoney',function (Request $request){

        $uid=$request->uid;
        $money=$request->money;
        $moneyFrom=$request->moneyFrom;

        $user=new \App\Http\Controllers\QuanMinZhanLing\UserController();

        $user->exprUserMoney($uid,0,$money,'+',['moneyFrom'=>$moneyFrom]);

        return response()->json(['resCode'=>Config::get('resCode.200'),'money'=>$user->getUserMoney(trim($request->uid))]);

    });

    //每日签到给多少钱
    Route::match(['get','post'],'SignMoney','QuanMinZhanLing\\SignInController@signMoney');

    //每日签到
    Route::match(['post'],'SignIn','QuanMinZhanLing\\SignInController@signIn');

    //展示签到
    Route::match(['get'],'SignIn','QuanMinZhanLing\\SignInController@showSign');

    //获取用户金钱和购地卡数量和钻石
    Route::match(['post'],'GetUserInfo',function (Request $request){

        $user=new \App\Http\Controllers\QuanMinZhanLing\UserController();

        $money=$user->getUserMoney(trim($request->uid));

        $card=$user->getBuyCardCount(trim($request->uid));

        $diamond=$user->getUserDiamond(trim($request->uid));

        return response()->json(['resCode'=>Config::get('resCode.200'),'money'=>$money,'card'=>$card]);

    });

    //获取用户通过金币还能购买购地卡的数量
    Route::match(['get','post'],'GetBuyCardStatus','QuanMinZhanLing\\UserController@getBuyCardStatus');

    //设置用户通过金币还能购买购地卡的数量
    Route::match(['get','post'],'SetBuyCardStatus','QuanMinZhanLing\\UserController@setBuyCardStatus');

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
    Route::match(['get'],'GetSystemMessage/{id}','QuanMinZhanLing\\SystemController@getSystemMessageDetail')->where('id','[0-9]+');

    //领取系统通知中的钱或道具
    Route::match(['post'],'GetGoodsOrMoney','QuanMinZhanLing\\SystemController@getGoodsOrMoney');

    //是否显示小红点
    Route::match(['get'],'ShowRedDot','QuanMinZhanLing\\SystemController@showRedDot');

    //分享图片
    Route::match(['get'],'SharePicture','QuanMinZhanLing\\UserController@sharePicture');

    //查看钱袋，领取钱袋
    Route::match(['get','post'],'UserWallet','QuanMinZhanLing\\UserController@userWallet');

    //获取app版本号
    Route::match(['get'],'GetAppVersion',function (){

        //安卓版本号
        $androidVer=(string)\Illuminate\Support\Facades\Redis::connection('default')->hget('tssjAndroidAppVersion','ver');

        //安卓下载链接
        $androidUrl=(string)\Illuminate\Support\Facades\Redis::connection('default')->hget('tssjAndroidAppVersion','url');

        //apple版本号
        $appleVer  =(string)\Illuminate\Support\Facades\Redis::connection('default')->hget('tssjAppleAppVersion','ver');

        //apple下载链接
        $appleUrl  =(string)\Illuminate\Support\Facades\Redis::connection('default')->hget('tssjAppleAppVersion','url');

        if ($androidVer=='') $androidVer=$androidUrl='0';
        if ($appleVer=='')   $appleVer=$appleUrl='0';

        $android=['ver'=>$androidVer,'url'=>$androidUrl];
        $apple=['ver'=>$appleVer,'url'=>$appleUrl];

        return response()->json(['resCode'=>Config::get('resCode.200'),'android'=>$android,'apple'=>$apple,'msg'=>'您当前使用的不是最新版本，请更新到最新版本。']);

    });
    Route::match(['get','post'],'GetAppVersionNew',function(){

        //安卓版本号
        $androidVer=(string)\Illuminate\Support\Facades\Redis::connection('default')->hget('tssjAndroidAppVersion','ver');

        //安卓下载链接
        $androidUrl=(string)\Illuminate\Support\Facades\Redis::connection('default')->hget('tssjAndroidAppVersion','url');

        //apple版本号
        $appleVer  =(string)\Illuminate\Support\Facades\Redis::connection('default')->hget('tssjAppleAppVersion','ver');

        //apple下载链接
        $appleUrl  =(string)\Illuminate\Support\Facades\Redis::connection('default')->hget('tssjAppleAppVersion','url');

        if ($androidVer=='') $androidVer=$androidUrl='0';
        if ($appleVer=='')   $appleVer=$appleUrl='0';

        $android=['ver'=>$androidVer,'url'=>$androidUrl];
        $apple=['newVer'=>$appleVer,'oldVer'=>$appleUrl];

        return response()->json(['resCode'=>Config::get('resCode.200'),'android'=>$android,'apple'=>$apple,'msg'=>'您当前使用的不是最新版本，请更新到最新版本。']);

    });

    //用户反馈意见
    Route::match(['get','post'],'UserFeedback','QuanMinZhanLing\FeedbackController@feedbackHandler');

    //勋章
    Route::match(['get','post'],'GetTssjGridMedal','QuanMinZhanLing\UserController@getTssjGridMedal');











});

