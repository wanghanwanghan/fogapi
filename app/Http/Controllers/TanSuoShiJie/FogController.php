<?php

namespace App\Http\Controllers\TanSuoShiJie;

use App\Console\Commands\FogUpload0;
use App\Http\Controllers\Controller;
use App\Model\Tssj\FogModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class FogController extends Controller
{
    //要完成的目标
    //10万用户，每人100万个点，一共1000亿个点的数据存储
    //分10个库，每个库200个表，每个表存5000万个点，一共可存1000亿个点

    //打开几个工作队列 1-10
    public $workList=5;

    //分库分表规则
    public function getDatabaseNoOrTableNo($uid)
    {
        //根据uid
        if (!is_numeric($uid) || $uid <= 0) return false;

        //先%10，得到数据库后缀
        $db=$uid%10;

        //再%200，得到表后缀
        $table=$uid%200;

        return ['db'=>$db,'table'=>$table];
    }

    //迷雾上传
    public function fogUpload(Request $request)
    {
        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0 || $request->data=='')
        {
            return response()->json(['resCode'=>Config::get('resCode.604')]);
        }

        $data=jsonDecode($request->data);

        $readyToHandle['uid']=$uid;
        $readyToHandle['data']=$data;

        //通过uid分成10个队列处理数据
        $suffix=$uid%$this->workList;

        try
        {
            //左进
            Redis::connection('TssjFog')->lpush('FogUploadList_'.$suffix,jsonEncode($readyToHandle));

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

        (new FogUpload0())->createTable($suffix);

        FogModel::databaseSuffix($suffix['db']);
        FogModel::tableSuffix($suffix['table']);

        $page=trim($request->page);
        $limit=5000;
        $offset=($page-1)*$limit;

        try
        {
            $res=FogModel::where('uid',$uid)->orderBy('id')->limit($limit)->offset($offset)->get(['id','lat as latitude','lng as longitude','geo as geohash','unixTime as timestamp'])->toArray();

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.624')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'thisTotle'=>count($res),'data'=>$res]);
    }








}
