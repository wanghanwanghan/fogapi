<?php

namespace App\Http\Controllers\TanSuoShiJie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class FogController extends Controller
{
    //要完成的目标
    //10万用户，每人100万个点，一共1000亿个点的数据存储
    //分10个库，每个库200个表，每个表存5000万个点，一共可存1000亿个点

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

        try
        {
            //左进
            Redis::connection('TssjFog')->lpush('FogUploadList',jsonEncode($readyToHandle));

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.631')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }









}
