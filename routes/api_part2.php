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

    //全部上传迷雾弹窗限流
    Route::match(['get','post'],'TodayShowUploadFogBoxLimit',function (\Illuminate\Http\Request $request){

        $uid=(int)$request->uid;

        if ($uid <= 0) return response()->json(['resCode'=>601]);

        //当天最大人数，做成动态的吧
        $limit=1000;

        //当天已经上传的人数
        $todayPeople='TodayPeople_'.\Carbon\Carbon::now()->format('Ymd');

        //当天的成员
        $todaySismember='TodaySismember_'.\Carbon\Carbon::now()->format('Ymd');

        //===========================================================================================================
        if ($request->isMethod('get'))
        {
            $sismember=\Illuminate\Support\Facades\Redis::connection('TssjFog')->sismember($todaySismember,$uid);

            //在当天的成员里，说明传过了
            if ((int)$sismember) return response()->json(['resCode'=>200,'allow'=>0]);

            $count=\Illuminate\Support\Facades\Redis::connection('TssjFog')->get($todayPeople);

            //当天上传人数到达限制
            if ((int)$count >= $limit) return response()->json(['resCode'=>200,'allow'=>0]);

            //控制量
            $num=0;

            for ($i=0;$i<=9;$i++)
            {
                $num += (int)\Illuminate\Support\Facades\Redis::connection('TssjFog')->llen('FogUploadList_'.$i);
            }

            //当前要处理的迷雾点太多了，不能上传了
            if ($num * 5000 > 50000000) return response()->json(['resCode'=>200,'allow'=>0]);

            return response()->json(['resCode'=>200,'allow'=>1]);
        }
        //===========================================================================================================
        if ($request->isMethod('post'))
        {
            //把uid添加到集合成员
            \Illuminate\Support\Facades\Redis::connection('TssjFog')->sadd($todaySismember,$uid);
            //设置过期时间
            \Illuminate\Support\Facades\Redis::connection('TssjFog')->expire($todaySismember,86400);

            //当天上传limit加1
            \Illuminate\Support\Facades\Redis::connection('TssjFog')->incr($todayPeople);
            //设置过期时间
            \Illuminate\Support\Facades\Redis::connection('TssjFog')->expire($todayPeople,86400);

            return response()->json(['resCode'=>200]);
        }
        //===========================================================================================================

        return false;
    });

    //迷雾上传
    Route::match(['get','post'],'FogUpload','TanSuoShiJie\\FogController@fogUpload');

    //迷雾下载
    Route::match(['get','post'],'FogDownload','TanSuoShiJie\\FogController@fogDownload');

    //打开某格子的印象板
    Route::match(['get','post'],'OpenOneGridCommunity','QuanMinZhanLing\\CommunityController@openOneGridCommunity');









});

