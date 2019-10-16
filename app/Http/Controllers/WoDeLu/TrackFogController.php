<?php

namespace App\Http\Controllers\WoDeLu;

use App\Console\Commands\TrackFogUpload0;
use App\Http\Controllers\Controller;
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

    //分库分表规则
    public function getDatabaseNoOrTableNo($uid)
    {
        //根据uid
        if (!is_numeric($uid) || $uid <= 0) return false;

        //先%10，得到数据库后缀
        $db=$uid%10;

        //再%800，得到表后缀
        $table=$uid%800;

        return ['db'=>$db,'table'=>$table];
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








}
