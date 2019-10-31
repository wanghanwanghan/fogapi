<?php

Route::group(['middleware'=>['PVandUV']],function ()
{
    //我的路迷雾上传弹框
    Route::match(['get','post'],'TodayShowUploadFogBoxLimitForTrackFog','WoDeLu\TrackFogController@todayShowUploadFogBoxLimitForTrackFog');

    //我的路足迹上传弹框
    Route::match(['get','post'],'TodayShowUploadFogBoxLimitForTrackZuJi','WoDeLu\TrackFogController@todayShowUploadFogBoxLimitForTrackZuJi');

    //我的路迷雾上传
    Route::match(['get','post'],'TrackFogUpload','WoDeLu\TrackFogController@fogUpload');

    //我的路迷雾下载
    Route::match(['get','post'],'TrackFogDownload','WoDeLu\TrackFogController@fogDownload');

    //我的路足迹上传
    Route::match(['get','post'],'TrackZuJiUpload','WoDeLu\TrackFogController@zujiUpload');

    //我的路足迹下载
    Route::match(['get','post'],'TrackZuJiDownload','WoDeLu\TrackFogController@zujiDownload');



});

