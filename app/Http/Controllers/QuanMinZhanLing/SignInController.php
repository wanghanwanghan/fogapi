<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class SignInController extends BaseController
{
    public $key='ContinuousSignIn_';

    //用户签到
    public function signIn(Request $request)
    {
        $key=Carbon::now()->format('Ymd');

        $userid=$request->uid;

        $continuousSignIn=$this->key.$userid;

        //判断是否已经签到
        try
        {
            $res=Redis::connection('SignIn')->getbit($key,$userid);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.602')]);
        }

        //已经签到
        if ($res) return response()->json(['resCode'=>Config::get('resCode.603')]);

        //bbk需求
        try
        {
            $res=Redis::connection('SignIn')->get($continuousSignIn);

            $res=jsonDecode($res);

            //是不是连续签到
            if ($res['nextSignIn']!='' && $res['nextSignIn']==$key)
            {
                $res['continuation']=isset($res['continuation']) ? $res['continuation'] + 1 : 1;

                if ($res['continuation']==8) $res['continuation']=1;

            }else
            {
                $res['continuation']=1;
            }

            $res['nextSignIn']=Carbon::now()->addDay()->format('Ymd');

            Redis::connection('SignIn')->set($continuousSignIn,jsonEncode($res));

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.602')]);
        }

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

        //bbk需求
        $continuousSignIn=$this->key.$uid;

        try
        {
            $res=Redis::connection('SignIn')->get($continuousSignIn);

            $res=jsonDecode($res);

            if ($res!=null && $res['nextSignIn']==$star && $res['continuation']==7)
            {
                //第8天的时候
                $res['continuation']=0;

            }elseif (isset($res['nextSignIn']) && $star - (int)$res['nextSignIn'] >= 1)
            {
                //签到间隔大于1天
                $res['continuation']=0;

            }else
            {
                $res['continuation']=isset($res['continuation']) ? $res['continuation'] : 0;
            }

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.602')]);
        }

        $today=Redis::connection('SignIn')->getbit($star,$uid);

        $res['today']=(int)$today;

        return response()->json(['resCode'=>Config::get('resCode.200'),'resData'=>$res]);
    }
}