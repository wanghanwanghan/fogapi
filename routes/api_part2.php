<?php

Route::group(['middleware'=>['PVandUV']],function ()
{
    //迷雾上传
    Route::match(['get','post'],'FogUpload','TanSuoShiJie\\FogController@fogUpload');

    //迷雾下载
    Route::match(['get','post'],'FogDownload','TanSuoShiJie\\FogController@fogDownload');












});

