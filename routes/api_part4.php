<?php

Route::group(['middleware'=>['PVandUV']],function ()
{
    //入盟请帖
    Route::match(['get','post'],'GetUserInviteList','QuanMinZhanLing\Aliance\AlianceController@getUserInviteList');

    //加入联盟
    Route::match(['get','post'],'JoinAliance','QuanMinZhanLing\Aliance\AlianceController@joinAliance');

    //退出联盟
    Route::match(['get','post'],'ExitAliance','QuanMinZhanLing\Aliance\AlianceController@exitAliance');

    //获取联盟信息
    Route::match(['get','post'],'GetAlianceInfoByAlianceId','QuanMinZhanLing\Aliance\AlianceController@getAlianceInfoByAlianceId');

    //获取联盟成员
    Route::match(['get','post'],'GetAlianceMember','QuanMinZhanLing\Aliance\AlianceController@getAlianceMember');

    //获取联盟相关用户信息
    Route::match(['get','post'],'GetUserInfoForAliance','QuanMinZhanLing\Aliance\AlianceController@getUserInfoForAliance');

    //关注和取消关注
    Route::match(['get','post'],'Follower','QuanMinZhanLing\Aliance\AlianceController@follower');

    //战绩情况
    Route::match(['get','post'],'GetMilitaryExploits','QuanMinZhanLing\Aliance\AlianceController@getMilitaryExploits');

    //占领概况
    Route::match(['get','post'],'GetChartInfo','QuanMinZhanLing\Aliance\AlianceController@getChartInfo');

    //领取alianceNum=2的联盟88地球币奖励
    Route::match(['get','post'],'GetAlianceReward','QuanMinZhanLing\Aliance\AlianceController@getAlianceReward');








});

