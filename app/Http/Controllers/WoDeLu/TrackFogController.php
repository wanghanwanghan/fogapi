<?php

namespace App\Http\Controllers\WoDeLu;

use App\Console\Commands\TrackFogUpload0;
use App\Console\Commands\TrackFogUploadForZUJI0;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Filesystem\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TrackFogController extends Controller
{
    //打开几个工作队列 1-10
    public $workList=10;

    //是否开启处理迷雾点任务 1开启，0不开启
    public $runWork=1;

    //分库分表规则 迷雾的
    public function getDatabaseNoOrTableNo($uid)
    {
        //根据uid
        if (!is_numeric($uid) || $uid <= 0) return false;

        //先%10，得到数据库后缀
        $db=$uid%10;

        //再%900，得到表后缀
        $table=$uid%900;

        return ['db'=>$db,'table'=>$table];
    }

    //有了会员规则加个判读
    public function iCanUpload($uid)
    {
        //月底再加这个限制
        if (Carbon::now()->format('Ymd') <= 20191130) return true;

        //如果是会员，不做任何限制
        $vipInfo=(new TrackUserController())->getVipInfo($uid);

        //是会员都可以传
        if (!empty($vipInfo)) return true;

        //不是会员就看看空间是不是满了
        //满了就不让上传

        //先看有多少迷雾拓展包，加200
        $fogPackage=(new TrackUserController())->getFogPackage($uid);
        $fogPackage=$fogPackage+200;

        //看看服务器上有多少面积
        $fogNum=(new TrackUserController())->getFogNum($uid);
        $fogNum=(int)($fogNum*0.0079);

        //如果服务器上有的面积，超过了，拓展包中的面积，就不让传了，需要购买拓展包，或者买会员
        if ($fogNum >= $fogPackage) return false;

        return true;
    }

    //迷雾上传限流
    public function todayShowUploadFogBoxLimitForTrackFog(Request $request)
    {
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
            $sismember=Redis::connection('TrackFog')->sismember($todaySismember,$uid);

            //在当天的成员里，说明传过了
            if ((int)$sismember) return response()->json(['resCode'=>200,'allow'=>0]);

            $count=Redis::connection('TrackFog')->get($todayPeople);

            //当天上传人数到达限制
            if ((int)$count >= $limit) return response()->json(['resCode'=>200,'allow'=>0]);

            //控制量
            $num=0;

            for ($i=0;$i<=9;$i++)
            {
                $num += (int)Redis::connection('TrackFog')->llen('FogUploadList_'.$i);
            }

            //当前要处理的迷雾点太多了，不能上传了
            if ($num * 5000 > Config::get('myDefine.FogLimit') / 10) return response()->json(['resCode'=>200,'allow'=>0]);

            return response()->json(['resCode'=>200,'allow'=>1]);
        }
        //===========================================================================================================
        if ($request->isMethod('post'))
        {
            //把uid添加到集合成员
            Redis::connection('TrackFog')->sadd($todaySismember,$uid);
            //设置过期时间
            Redis::connection('TrackFog')->expire($todaySismember,86400);

            //当天上传limit加1
            Redis::connection('TrackFog')->incr($todayPeople);
            //设置过期时间
            Redis::connection('TrackFog')->expire($todayPeople,86400);

            return response()->json(['resCode'=>200]);
        }
        //===========================================================================================================

        return false;
    }

    //分库分表规则 足迹的
    public function getDatabaseNoOrTableNoForZUJI($unixTimeOrYmd='')
    {
        //228万数据，440兆空间，7字段，2索引

        //395面积，5万个点，1万人，5亿数据

        if ($unixTimeOrYmd=='') $unixTimeOrYmd=time();

        //库后缀，一年一个库
        //再%5，得到表后缀，一天一个表，一年365 * 5个表
        //20190330 2019-03-30 1571991904
        try
        {
            $db=Carbon::parse($unixTimeOrYmd)->format('Y');

            $table=Carbon::parse($unixTimeOrYmd)->format('Ymd');

        }catch (\Exception $e)
        {
            $db=date('Y',$unixTimeOrYmd);

            $table=date('Ymd',$unixTimeOrYmd);
        }

        //db=>2019 table=>20190101
        return ['db'=>$db,'table'=>$table];
    }

    //足迹上传限流
    public function todayShowUploadFogBoxLimitForTrackZuJi(Request $request)
    {
        $uid=(int)$request->uid;

        if ($uid <= 0) return response()->json(['resCode'=>601]);

        //当天最大人数，做成动态的吧
        $limit=Config::get('myDefine.ZuJiPeopleLimt');

        //当天已经上传的人数
        $todayPeople='ZuJiTodayPeople_'.Carbon::now()->format('Ymd');

        //当天的成员
        $todaySismember='ZuJiTodaySismember_'.Carbon::now()->format('Ymd');

        //===========================================================================================================
        if ($request->isMethod('get'))
        {
            $sismember=Redis::connection('TrackFog')->sismember($todaySismember,$uid);

            //在当天的成员里，说明传过了
            if ((int)$sismember) return response()->json(['resCode'=>200,'allow'=>0]);

            $count=Redis::connection('TrackFog')->get($todayPeople);

            //当天上传人数到达限制
            if ((int)$count >= $limit) return response()->json(['resCode'=>200,'allow'=>0]);

            //控制量
            $num=0;

            for ($i=0;$i<=9;$i++)
            {
                $num += (int)Redis::connection('TrackFog')->llen('FogUploadZuJiList_'.$i);
            }

            //当前要处理的足迹天数太多了，不能上传了
            if ($num > Config::get('myDefine.ZuJiDayLimit')) return response()->json(['resCode'=>200,'allow'=>0]);

            return response()->json(['resCode'=>200,'allow'=>1]);
        }
        //===========================================================================================================
        if ($request->isMethod('post'))
        {
            //把uid添加到集合成员
            Redis::connection('TrackFog')->sadd($todaySismember,$uid);
            //设置过期时间
            Redis::connection('TrackFog')->expire($todaySismember,86400);

            //当天上传limit加1
            Redis::connection('TrackFog')->incr($todayPeople);
            //设置过期时间
            Redis::connection('TrackFog')->expire($todayPeople,86400);

            return response()->json(['resCode'=>200]);
        }
        //===========================================================================================================

        return false;
    }

    //迷雾上传
    public function fogUpload(Request $request)
    {
        if (!$this->runWork) return response()->json(['resCode'=>Config::get('resCode.200')]);

        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0 || $request->data=='')
        {
            return response()->json(['resCode'=>Config::get('resCode.604')]);
        }

        //是不是会员，空间满没满
        if (!$this->iCanUpload($uid)) return response()->json(['resCode'=>Config::get('resCode.631')]);

        $data=jsonDecode($request->data);

        $readyToHandle['uid']=$uid;
        $readyToHandle['data']=$data;

        //通过uid分成最多10个队列处理数据
        $suffix=$uid%$this->workList;

        try
        {
            //左进
            Redis::connection('TrackFog')->lpush('FogUploadList_'.$suffix,jsonEncode($readyToHandle));

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.631')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //迷雾下载
    public function fogDownload(Request $request)
    {
        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $suffix=$this->getDatabaseNoOrTableNo($uid);

        (new TrackFogUpload0())->createTable($suffix);

        $page=trim($request->page);
        $limit=5000;
        $offset=($page-1)*$limit;

        try
        {
            $res=DB::connection("TrackFog{$suffix['db']}")->table("user_fog_{$suffix['table']}")
                ->where('uid',$uid)
                ->orderBy('unixTime')
                ->limit($limit)->offset($offset)
                ->get(['lat as latitude','lng as longitude','geo as geohash','unixTime as timestamp']);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.624')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'thisTotle'=>count($res),'data'=>$res]);
    }

    //足迹上传
    public function zujiUpload(Request $request)
    {
        if (!$this->runWork) return response()->json(['resCode'=>Config::get('resCode.200')]);

        //[
        //  'uid' =>23357,
        //  'date'=>20191031,
        //  'data'=>要处理的足迹json,
        //]

        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0 || $request->data=='') return response()->json(['resCode'=>Config::get('resCode.604')]);

        //是不是会员，空间满没满
        if (!$this->iCanUpload($uid)) return response()->json(['resCode'=>Config::get('resCode.631')]);

        $date=trim($request->date);

        if (!is_numeric($date) || $date <= 0) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $data=jsonDecode($request->data);

        $readyToHandle['uid']=$uid;
        $readyToHandle['date']=$date;
        $readyToHandle['data']=$data;

        //通过uid分成最多10个队列处理数据
        $suffix=$uid%$this->workList;

        try
        {
            //左进
            Redis::connection('TrackFog')->lpush('FogUploadZuJiList_'.$suffix,jsonEncode($readyToHandle));

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.631')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //足迹下载
    public function zujiDownload(Request $request)
    {
        $uid=$request->uid;

        $date=$request->date;

        $suffix=$this->getDatabaseNoOrTableNoForZUJI($date);

        $obj=new TrackFogUploadForZUJI0();

        $obj->createTable($suffix);

        //判断有没有数据
        $todayAll=DB::connection('TrackFogForZUJI'.$suffix['db'])->table('user_zuji_index')->where(['uid'=>$uid,'date'=>$date])->get()->toArray();

        //如果数据是空
        if (empty($todayAll)) return response()->json(['resCode'=>Config::get('resCode.625'),'data'=>[]]);

        //如果有数据
        foreach ($todayAll as $one)
        {
            $locations=DB::connection('TrackFogForZUJI'.$suffix['db'])
                ->table("user_zuji_{$suffix['table']}")
                ->where(['uid'=>$uid,'randomUUID'=>$one->randomUUID])
                ->get(['timestamp','lat as latitude','lng as longitude']);

            $res[]=[
                'status'=>$one->status,
                'distance'=>$one->distance,
                'stopLocationStr'=>$one->stopLocationStr,
                'startTimestamp'=>$one->startTimestamp,
                'startlocationStr'=>$one->startLocationStr,
                'endTimestamp'=>$one->endTimestamp,
                'interval'=>$one->interval,
                'locations'=>$locations,
            ];
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }




}
