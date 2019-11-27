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








});

