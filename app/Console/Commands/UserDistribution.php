<?php

namespace App\Console\Commands;

use App\Http\Controllers\QuanMinZhanLing\SecurityController;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class UserDistribution extends Command
{
    protected $signature = 'Admin:UserDistribution';

    protected $description = '后台admin的控制面板，统计用户分布情况';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $yestday=Carbon::now()->subDay()->format('Ymd');

        $obj=new SecurityController();

        $redisKey=$obj->uvKey.$yestday;

        //取所有
        $res=Redis::connection('SignIn')->zrevrange($redisKey,0,-1,'withscores');

        if (empty($res)) return true;

        foreach ($res as $arrKey=>$val)
        {
            //如果不是ip，继续下一个
            if (!preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#',$arrKey)) continue;

            try
            {
                $res=addressForIP($arrKey);

            }catch (\Exception $e)
            {
                sleep(10);

                try
                {
                    $res=addressForIP($arrKey);

                }catch (\Exception $e)
                {
                    continue;
                }
            }

            if (!isset($res['result']['area'])) continue;

            //存在area
            Redis::connection('SignIn')->zincrby($obj->userDistribution,$val,$res['result']['area']);
        }
    }
}
