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

        foreach ($allUid as $uid)
        {
            //3xxx系列是同时
            $this->check3xxx($uid);
        }

        foreach ($allUid as $uid)
        {
            //4xxx系列是同一格子
            $this->check4xxx($uid);
        }
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
        //获取用户成就数组
        $userAch=$this->getAchievementInRedis($uid);

        $res='pass';
        if (!(isset($userAch['2xxx']['2001']) && $userAch['2xxx']['2001']!=0)) $res='noPass';
        if (!(isset($userAch['2xxx']['2002']) && $userAch['2xxx']['2002']!=0)) $res='noPass';
        if (!(isset($userAch['2xxx']['2003']) && $userAch['2xxx']['2003']!=0)) $res='noPass';
        if (!(isset($userAch['2xxx']['2004']) && $userAch['2xxx']['2004']!=0)) $res='noPass';
        if (!(isset($userAch['2xxx']['2005']) && $userAch['2xxx']['2005']!=0)) $res='noPass';
        if (!(isset($userAch['2xxx']['2006']) && $userAch['2xxx']['2006']!=0)) $res='noPass';
        if (!(isset($userAch['2xxx']['2007']) && $userAch['2xxx']['2007']!=0)) $res='noPass';
        if (!(isset($userAch['2xxx']['2008']) && $userAch['2xxx']['2008']!=0)) $res='noPass';

        //全都完成了，不统计这人了
        if ($res=='pass') return true;

        //生成最近一年的数组
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
            if (!Schema::connection('masterDB')->hasTable('buy_sale_info_'.$yearArr[$i])) break;

            $mbuy =DB::connection('masterDB')->table('buy_sale_info_'.$yearArr[$i])->where('uid',$uid)->count();
            $msale=DB::connection('masterDB')->table('buy_sale_info_'.$yearArr[$i])->where('belong',$uid)->count();

            $buy=$buy+$mbuy;
            $sale=$sale+$msale;
        }

        //获取2xxx成就系列
        $achAll=DB::connection('masterDB')->table('achievement')->where('id','like','2%')->get(['id','scheduleTotle'])->toArray();

        foreach ($achAll as $oneAch)
        {
            if (!isset($userAch['2xxx'][$oneAch->id]))
            {
                //没完成某个2xxx系列
                $buyArr =[2001,2002,2003,2004];
                $saleArr=[2005,2006,2007,2008];

                if (in_array($oneAch->id,$buyArr))
                {
                    //当前id是购买
                    if ($buy >= $oneAch->scheduleTotle)
                    {
                        //实际购买次数，大于当前成就需求次数
                        $userAch['2xxx'][$oneAch->id]=1;
                    }

                }elseif (in_array($oneAch->id,$saleArr))
                {
                    //当前id是卖出
                    if ($sale >= $oneAch->scheduleTotle)
                    {
                        //实际卖出次数，大于当前成就需求次数
                        $userAch['2xxx'][$oneAch->id]=1;
                    }

                }else
                {}
            }
        }

        //修改redis中的值
        $userAch['2xxx']['buyTotle']=$buy;
        $userAch['2xxx']['saleTotle']=$sale;

        Redis::connection('UserInfo')->hset($uid,'Achievement',json_encode($userAch));

        return true;
    }

    //同时拥有几个格子系列
    public function check3xxx($uid)
    {
        //bbk说不做了
    }

    //同一格子累计交易系列
    public function check4xxx($uid)
    {

    }







}