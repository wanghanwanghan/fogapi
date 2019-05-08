<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class SecurityController extends BaseController
{
    //uv的rediskey前缀
    public $uvKey='AccessUV_';

    //pv的rediskey前缀
    public $pvKey='AccessPV_';

    //统计pv，访问量
    public function recodePV()
    {
        $day=Carbon::now()->format('Ymd');

        $key=$this->pvKey.$day;

        Redis::connection('SignIn')->incr($key);

        return true;
    }

    //统计uv，独立访客
    public function recodeUV(Request $request)
    {
        $day=Carbon::now()->format('Ymd');

        $key=$this->uvKey.$day;

        $ip=trim($request->getClientIp());

        if ($ip!='')
        {
            Redis::connection('SignIn')->sadd($key,$ip);
        }

        return true;
    }
}