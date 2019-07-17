<?php

Route::group(['middleware'=>['PVandUV']],function ()
{
    //根据uid上传手机经纬度
    Route::match(['get','post'],'AccordingToUidUploadLatLng',function (\Illuminate\Http\Request $request){

        if ($request->isMethod('get'))
        {
            //获取需要监控的uid

            //uid => 秒
            $uidArray=[
                '18426'=>'10',
                '22357'=>'10',
                '61727'=>'10',
            ];

            return response()->json(['resCode'=>200,'target'=>$uidArray]);
        }

        if ($request->isMethod('post'))
        {
            if ((int)$request->uid <= 0) return false;

            $uid=(int)$request->uid;

            $data=[
                'lat'=>trim($request->lat),
                'lng'=>trim($request->lng),
                'time'=>trim($request->time),
            ];

            \Illuminate\Support\Facades\Redis::connection('default')->set('AccordingToUidUploadLatLng_'.$uid,jsonEncode($data));

            return response()->json(['resCode'=>200]);
        }

        return false;
    });

    //迷雾上传
    Route::match(['get','post'],'FogUpload','TanSuoShiJie\\FogController@fogUpload');

    //迷雾下载
    Route::match(['get','post'],'FogDownload','TanSuoShiJie\\FogController@fogDownload');

    //打开某格子的印象板
    Route::match(['get','post'],'OpenOneGridCommunity','QuanMinZhanLing\\CommunityController@openOneGridCommunity');









});

