<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class SignInController extends BaseController
{
    //用户签到
    public function signIn(Request $request)
    {
        $key=Carbon::now()->format('Ymd');
        $userid=$request->uid;

        //判断是否已经签到
        try
        {
            $res=Redis::connection('SignIn')->getbit($key,$userid);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.602')]);
        }

        if ($res) return response()->json(['resCode'=>Config::get('resCode.603')]);

        //签到写入redis
        try
        {
            Redis::connection('SignIn')->setbit($key,$userid,1);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.602')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //用户当周签到情况
    public function showSign(Request $request)
    {
        //一周开始的第一天
        //$star=Carbon::now()->startOfWeek()->format('Ymd');

        //一周结束的最后一天
        //$stop=Carbon::now()->endOfWeek()->format('Ymd');

        //for ($i=$star;$i<=$stop;)
        //{
        //    try
        //    {
        //        $sign=Redis::connection('SignIn')->getbit($i,$request->uid);

        //    }catch (\Exception $e)
        //    {
        //        return response()->json(['resCode'=>Config::get('resCode.602')]);
        //    }

        //   $res[$i]=$sign;

        //    //下一天
        //    $i=Carbon::parse($i.' +1 days')->format('Ymd');
        //}

        //return response()->json(['resCode'=>Config::get('resCode.200'),'resData'=>$res]);

        $star=Carbon::now()->format('Ymd');
        $stop=Carbon::parse($star.' -7 days')->format('Ymd');
        $uid=trim($request->uid);

        //返回总共8天的签到情况
        for ($i=$star;$i>=$stop;)
        {
            try
            {
                $sign=Redis::connection('SignIn')->getbit($i,$uid);

            }catch (\Exception $e)
            {
                return response()->json(['resCode'=>Config::get('resCode.602')]);
            }

           $res[$i]=$sign;

            //上一天
            $i=Carbon::parse($i.' -1 days')->format('Ymd');
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'resData'=>$res]);
    }
}