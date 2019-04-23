<?php

namespace App\Console\Commands;

use App\Model\UserTradeInfoModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class Achievement extends Command
{
    protected $signature = 'Grid:Achievement';

    protected $description = '延时统计用户成就';

    public function __construct()
    {
        parent::__construct();
    }

    public function hashTableSuffix($uid)
    {
        return $uid%5;
    }

    public function getAchievementInRedis($uid)
    {
        $res=Redis::connection('UserInfo')->hget($uid,'Achievement');

        if ($res==null)
        {
            return [];
        }else
        {
            return json_decode($res,true);
        }
    }

    //=========================================================================================================================
    public function handle()
    {
        //取出待处理uid
        $allUid=Redis::connection('WriteLog')->smembers('Achievement');

        //删掉集合
        Redis::connection('WriteLog')->del('Achievement');

        //检查用户redis hash中的值
        foreach ($allUid as $uid)
        {
            //1xxx系列是首次
            $this->check1001($uid);
            $this->check1002($uid);
        }

        foreach ($allUid as $uid)
        {
            //2xxx系列是累计
            $this->check2xxx($uid);
        }

        //3xxx系列是同时

        //4xxx系列是同一格子
    }
    //=========================================================================================================================

    //首次购买无人格子
    public function check1001($uid)
    {
        $userAch=$this->getAchievementInRedis($uid);

        if (isset($userAch['1xxx']['1001']) && $userAch['1xxx']['1001']!=0)
        {
            //完成过就不执行了
            return true;
        }

        //只查询当月的用户交易表即可
        $suffix=Carbon::now()->format('Ym');

        //当月的用户交易模型
        UserTradeInfoModel::suffix($suffix);

        $res=UserTradeInfoModel::where(['uid'=>$uid,'belong'=>0])->first();

        if ($res)
        {
            //修改redis中的值
            $userAch=$this->getAchievementInRedis($uid);

            $userAch['1xxx']['1001']=1;

            Redis::connection('UserInfo')->hset($uid,'Achievement',json_encode($userAch));

            return true;
        }

        return true;
    }

    //首次购买别人格子
    public function check1002($uid)
    {
        $userAch=$this->getAchievementInRedis($uid);

        if (isset($userAch['1xxx']['1002']) && $userAch['1xxx']['1002']!=0)
        {
            //完成过就不执行了
            return true;
        }

        //只查询当月的用户交易表即可
        $suffix=Carbon::now()->format('Ym');

        //当月的用户交易模型
        UserTradeInfoModel::suffix($suffix);

        $res=UserTradeInfoModel::where('uid',$uid)->where('belong','<>',0)->first();

        if ($res)
        {
            //修改redis中的值
            $userAch=$this->getAchievementInRedis($uid);

            $userAch['1xxx']['1002']=1;

            Redis::connection('UserInfo')->hset($uid,'Achievement',json_encode($userAch));

            return true;
        }

        return true;
    }

    //累计购买/卖出系列
    public function check2xxx($uid)
    {
        //生成最近一年数组
        $i=12;
        while($i >= 1)
        {
            $m=$i-1;

            //只统计最近一年的表
            $yearArr[]=date('Ym',strtotime('-'.$m.'month'));

            $i--;
        }

        //循环统计最近一年的表
        $buy=0;
        $sale=0;
        for ($i=11;$i>=0;$i--)
        {
            if (!Schema::connection('masterDB')->hasTable('buy_sale_info_',$yearArr[$i])) break;

            $mbuy =DB::connection('masterDB')->table('buy_sale_info_',$yearArr[$i])->where('uid',$uid)->count();
            $msale=DB::connection('masterDB')->table('buy_sale_info_',$yearArr[$i])->where('belong',$uid)->count();

            $buy=$buy+$mbuy;
            $sale=$sale+$msale;
        }

        //修改redis中的值
        $userAch=$this->getAchievementInRedis($uid);

        $userAch['2xxx']['buyTotle']=$buy;
        $userAch['2xxx']['saleTotle']=$sale;

        Redis::connection('UserInfo')->hset($uid,'Achievement',json_encode($userAch));

        return true;
    }








}
