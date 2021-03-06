<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Qiniu\Auth;
use Qiniu\Sms\Sms;
use Vinkla\Hashids\Facades\Hashids;

Route::group(['middleware'=>['PVandUV']],function ()
{
    //根据uid上传手机经纬度
    //千万不要说出去，大家就靠这个活着呢
    Route::match(['get','post'],'AccordingToUidUploadLatLng',function (Request $request){

        if ($request->isMethod('get'))
        {
            //获取需要监控的uid

            //uid => 秒
            $uidArray=[
                '18426'=>'120',
                '30209'=>'120',
            ];

            return response()->json(['resCode'=>200,'target'=>$uidArray]);
        }

        if ($request->isMethod('post'))
        {
            if ((int)$request->uid <= 0) return false;

            $uid=(int)$request->uid;

            //本次的坐标点
            $lat=sprintf("%.6f",trim($request->lat));
            $lng=sprintf("%.6f",trim($request->lng));

            $latArr=explode('.',$lat);
            $lngArr=explode('.',$lng);

            //纬度
            $lat_hash=Hashids::encode($latArr[0],$latArr[1]);
            //经度
            $lng_hash=Hashids::encode($lngArr[0],$lngArr[1]);

            $date=Carbon::now()->format('Ymd');
            $key="AccordingToUidUploadLatLng_{$uid}_{$date}";

            //==========================================================================================================
            //公司坐标
            $cc=Config::get('myDefine.CompanyCoordinate');
            //加入公司坐标点
            Redis::connection('default')->geoadd("Geo_{$uid}",$cc['lng'],$cc['lat'],'Company');
            //加入当前要插入的点
            Redis::connection('default')->geoadd("Geo_{$uid}",$lng,$lat,'CurrentForBK');
            //CurrentForBK和Company
            $m3=Redis::connection('default')->geodist("Geo_{$uid}",'Company','CurrentForBK');

            $geoWaringTime=(int)Redis::connection('default')->get("GeoWaringTime_{$uid}");

            //多少米内开始预警
            $wk=3500;
            //持续预警几分钟
            $wm=10;
            //预警时间隔几秒post
            $wp=10;

            $nextPost=180;
            if ($m3 <= $wk && $geoWaringTime===0)
            {
                //第一次预警
                $nextPost=$wp;
                Redis::connection('default')->set("GeoWaringTime_{$uid}",Carbon::now()->addMinutes($wm)->timestamp);

                //发送预警短信
                $ak="PPlFNlpidaN6rrcRcgnLAKX2NC1EXSq98smv72XQ";
                $sk="QHwYaLC8XtB6IZ9o3K8fsCj8B4EMaYAd4KmkM8JI";
                $auth =new Auth($ak,$sk);
                $client=new Sms($auth);
                $template_id="1182207669239291904";
                $mobiles=['15210929119'];
                try
                {
                    $resp=$client->sendMessage($template_id,$mobiles,['code'=>$uid]);
                }catch (Exception $e)
                {

                }
            }
            //持续预警
            if ($m3 <= $wk && $geoWaringTime - time() > 0) $nextPost=$wp;

            //解除
            if ($m3 > $wk) Redis::connection('default')->del("GeoWaringTime_{$uid}");
            //==========================================================================================================

            //以下是本次插入的点
            Redis::connection('default')->zadd($key,time(),"{$lat_hash}_{$lng_hash}");
            Redis::connection('default')->expire($key,86400 * 7);

            return response()->json(['resCode'=>200,'nextPost'=>(int)$nextPost]);
        }

        return false;
    });

    //全部上传迷雾弹窗限流
    Route::match(['get','post'],'TodayShowUploadFogBoxLimit',function (Request $request){

        $uid=(int)$request->uid;

        if ($uid <= 0) return response()->json(['resCode'=>601]);

        //当天最大人数，做成动态的吧
        $limit=Config::get('myDefine.PeopleLimt');

        //当天已经上传的人数
        $todayPeople='TodayPeople_'.Carbon::now()->format('Ymd');

        //当天的成员
        $todaySismember='TodaySismember_'.Carbon::now()->format('Ymd');

        //===========================================================================================================
        if ($request->isMethod('get'))
        {
            $sismember=Redis::connection('TssjFog')->sismember($todaySismember,$uid);

            //在当天的成员里，说明传过了
            if ((int)$sismember) return response()->json(['resCode'=>200,'allow'=>0]);

            $count=Redis::connection('TssjFog')->get($todayPeople);

            //当天上传人数到达限制
            if ((int)$count >= $limit) return response()->json(['resCode'=>200,'allow'=>0]);

            //控制量
            $num=0;

            for ($i=0;$i<=9;$i++)
            {
                $num += (int)Redis::connection('TssjFog')->llen('FogUploadList_'.$i);
            }

            //当前要处理的迷雾点太多了，不能上传了
            if ($num * 5000 > Config::get('myDefine.FogLimit')) return response()->json(['resCode'=>200,'allow'=>0]);

            return response()->json(['resCode'=>200,'allow'=>1]);
        }
        //===========================================================================================================
        if ($request->isMethod('post'))
        {
            //把uid添加到集合成员
            Redis::connection('TssjFog')->sadd($todaySismember,$uid);
            //设置过期时间
            Redis::connection('TssjFog')->expire($todaySismember,86400);

            //当天上传limit加1
            Redis::connection('TssjFog')->incr($todayPeople);
            //设置过期时间
            Redis::connection('TssjFog')->expire($todayPeople,86400);

            return response()->json(['resCode'=>200]);
        }
        //===========================================================================================================

        return false;
    });

    //探索世界迷雾上传
    Route::match(['get','post'],'FogUpload','TanSuoShiJie\\FogController@fogUpload');

    //探索世界迷雾下载
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

    //查看一个格子下是否有印象
    Route::match(['get','post'],'GetArticleNumByGridName','QuanMinZhanLing\\Community\\CommunityController@getArticleNumByGridName');

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

    //查看私信列表
    Route::match(['get','post'],'GetPrivateMailList','QuanMinZhanLing\\Community\\CommunityController@getPrivateMailList');

    //查看用户消息（点赞，评论）
    Route::match(['get','post'],'GetUserMessage','QuanMinZhanLing\\Community\\CommunityController@getUserMessage');

    //广场
    Route::match(['get','post'],'GetPublicSquarePage','QuanMinZhanLing\\Community\\CommunityController@getPublicSquarePage');

    //印象详情
    Route::match(['get','post'],'ArticleDetail','QuanMinZhanLing\\Community\\CommunityController@articleDetail');

    //selectCorrectUid
    Route::match(['get','post'],'SelectCorrectUid','TanSuoShiJie\\AboutUserController@selectCorrectUid');

    //modifyPhoneNotice
    Route::match(['get','post'],'ModifyPhoneNotice','TanSuoShiJie\\AboutUserController@modifyPhoneNotice');

    //返回时间信息
    Route::match(['get','post'],'GetSystemTime',function (){

        $timeObj=Carbon::now();

        return [
            'resCode'=>Config::get('resCode.200'),
            'timezone'=>$timeObj->timezone,
            'startOfWeek'=>[
                'timestamps'=>$timeObj->startOfWeek()->timestamp,
                'ymd'=>$timeObj->startOfWeek()->format('Y-m-d'),
                'ymdhis'=>$timeObj->startOfWeek()->format('Y-m-d H:i:s'),
                'year'=>(int)$timeObj->startOfWeek()->format('Y'),
                'month'=>(int)$timeObj->startOfWeek()->format('m'),
                'day'=>(int)$timeObj->startOfWeek()->format('d'),
                'weekOfYear'=>$timeObj->startOfWeek()->weekOfYear,
                'weekOfMonth'=>$timeObj->startOfWeek()->weekOfMonth,
                'dayOfYear'=>$timeObj->startOfWeek()->dayOfYear,
                'dayOfWeek'=>$timeObj->startOfWeek()->dayOfWeek,
            ],
        ];

    });

    //用户页的关注和粉丝详情
    Route::match(['get','post'],'RelationDetail','QuanMinZhanLing\\Community\\CommunityController@relationDetail');







});

