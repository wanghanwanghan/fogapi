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

    //用户分布rediskey
    public $userDistribution='UserDistribution';

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
            Redis::connection('SignIn')->zincrby($key,1,$ip);
        }

        return true;
    }

    //ajax
    public function ajax(Request $request)
    {
        switch ($request->type)
        {
            case 'get_uv':

                //拿出当月所有uv，当天往后减，直到后两位是01，等于减到了当月第一天
                $res=[];

                $day=Carbon::now()->format('Ymd');

                for ($i=1;$i<=33;$i++)
                {
                    $key=$this->uvKey.$day;

                    $arrKey=substr($day,-2);

                    $res[(int)$arrKey]=Redis::connection('SignIn')->zcard($key);

                    if ($arrKey=='01') break;

                    $day--;
                }

                return $res;

                break;

            case 'get_pv':

                //拿出当月所有uv，当天往后减，直到后两位是01，等于减到了当月第一天
                $res=[];

                $day=Carbon::now()->format('Ymd');

                for ($i=1;$i<=33;$i++)
                {
                    $key=$this->pvKey.$day;

                    $arrKey=substr($day,-2);

                    $res[(int)$arrKey]=(int)Redis::connection('SignIn')->get($key);

                    if ($arrKey=='01') break;

                    $day--;
                }

                return $res;

                break;

            case 'get_user_distribution':

                //还是要通过定时任务，把昨天的uvKey通过请求接口加过来
                //这里只要取得redis值就行，不计算

                return Redis::connection('SignIn')->zrevrange($this->userDistribution,0,4,'withscores');

                break;

            default:

                break;
        }
    }




}