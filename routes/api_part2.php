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
        $limit=\Illuminate\Support\Facades\Config::get('myDefine.PeopleLimt');

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
            if ($num * 5000 > \Illuminate\Support\Facades\Config::get('myDefine.FogLimit')) return response()->json(['resCode'=>200,'allow'=>0]);

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


    //发布一条印象
    Route::match(['get','post'],'CreateArticle','QuanMinZhanLing\\Community\\CommunityController@createArticle');

    //返回所有官方标签
    Route::match(['get','post'],'GetTssjLabel','QuanMinZhanLing\\Community\\CommunityController@getTssjLabel');

    //查找标签
    Route::match(['get','post'],'SelectLabel','QuanMinZhanLing\\Community\\CommunityController@selectLabel');

    //创建新标签
    Route::match(['get','post'],'CreateLabel','QuanMinZhanLing\\Community\\CommunityController@createLabel');

    //查看一个格子下的印象
    Route::match(['get','post'],'GetArticleByGridName','QuanMinZhanLing\\Community\\CommunityController@getArticleByGridName');

    //返回40个热门标签
    Route::match(['get','post'],'GetHotLabelsLimit40','QuanMinZhanLing\\Community\\CommunityController@getHotLabelsLimit40');

    //给印象点赞
    Route::match(['get','post'],'LikeAndDontLike','QuanMinZhanLing\\Community\\CommunityController@likeAndDontLike');

    //获取印象的所有点赞人
    Route::match(['get','post'],'GetArticleAllLike','QuanMinZhanLing\\Community\\CommunityController@getArticleAllLike');

    //获取印象的所有评论
    Route::match(['get','post'],'GetArticleAllComment','QuanMinZhanLing\\Community\\CommunityController@getArticleAllComment');

    //发表评论
    Route::match(['get','post'],'CreateComment','QuanMinZhanLing\\Community\\CommunityController@createComment');

    //举报
    Route::match(['get','post'],'Rubbish','QuanMinZhanLing\\Community\\CommunityController@rubbish');

    //查看用户页面
    Route::match(['get','post'],'GetUserPage','QuanMinZhanLing\\Community\\CommunityController@getUserPage');

    //写对人的印象
    Route::match(['get','post'],'SetUserLabel','QuanMinZhanLing\\Community\\CommunityController@setUserLabel');

    //选择对人的印象
    Route::match(['get','post'],'SelectUserLabel','QuanMinZhanLing\\Community\\CommunityController@selectUserLabel');

    //查看所有对人的印象
    Route::match(['get','post'],'GetUserLabel','QuanMinZhanLing\\Community\\CommunityController@getUserLabel');

    //关注和取消关注
    Route::match(['get','post'],'FollowerAndUnfollower','QuanMinZhanLing\\Community\\CommunityController@followerAndUnfollower');

    //删除印象
    Route::match(['get','post'],'DeleteArticle','QuanMinZhanLing\\Community\\CommunityController@deleteArticle');

    //发送私信
    Route::match(['get','post'],'SetPrivateMail','QuanMinZhanLing\\Community\\CommunityController@setPrivateMail');

    //查看私信
    Route::match(['get','post'],'GetPrivateMail','QuanMinZhanLing\\Community\\CommunityController@getPrivateMail');

    //查看用户消息（点赞，评论）
    Route::match(['get','post'],'GetUserMessage','QuanMinZhanLing\\Community\\CommunityController@getUserMessage');





});

