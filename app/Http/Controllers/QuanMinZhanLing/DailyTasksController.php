<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\DailyTasksModel;
use App\Model\UserTradeInfoModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class DailyTasksController extends BaseController
{
    //每日任务key
    public $DailyTasksKey='DailyTasks_';

    //从数据库取每日任务id的缓存key
    public $KeyForDB='DailyTasksCacheForDB';

    //获取redis中的key
    public function getRedisKey($uid)
    {
        $date=Carbon::now()->format('Ymd');

        return $this->DailyTasksKey."{$date}_".$uid;
    }

    //获取每日任务完成情况
    public function getDailyTasksForUser(Request $request)
    {
        $uid=$request->uid;

        //每日任务id数组
        $dailyTasksId=Cache::remember($this->KeyForDB.'_1',120,function()
        {
            return DailyTasksModel::pluck('id')->toArray();
        });

        if (!is_array($dailyTasksId)) return response()->json(['resCode' => Config::get('resCode.604')]);

        try
        {
            $res=Redis::connection('UserInfo')->hgetall($this->getRedisKey($uid));

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.630')]);
        }

        //处理数组
        $count=count($dailyTasksId);
        for ($i=0;$i<$count;$i++)
        {
            //看看这个key在redis hash里有没有
            $key=$dailyTasksId[$i];

            if (array_key_exists($key,$res))
            {
                $tmp[$key]=$res[$key];

            }else
            {
                $tmp[$key]=0;
            }
        }

        //id是4和5需要算一下
        $this->tid_4($uid,$tmp);
        $this->tid_5($uid,$tmp);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$tmp]);
    }

    //计算id是4
    public function tid_4($uid,&$tmp)
    {
        //购买任意格子

        //不存在4
        if (!isset($tmp['4'])) return true;

        //4已经完成
        if ($tmp['4']!=0) return true;

        $suffix=Carbon::now()->format('Ym');

        UserTradeInfoModel::suffix($suffix);

        //当天起始时间点
        $timestamp=Carbon::now()->startOfDay()->timestamp;

        $res=UserTradeInfoModel::where(['uid'=>$uid])->where('paytime','>=',$timestamp)->first();

        if ($res)
        {
            //完成了
            $tmp['4']=1;

            Redis::connection('UserInfo')->hset($this->getRedisKey($uid),4,1);

            Redis::connection('UserInfo')->expireat($this->getRedisKey($uid),Carbon::now()->endOfDay()->timestamp);
        }

        return true;
    }

    //计算id是5
    public function tid_5($uid,&$tmp)
    {
        //卖出任意格子

        //不存在5
        if (!isset($tmp['5'])) return true;

        //5已经完成
        if ($tmp['5']!=0) return true;

        $suffix=Carbon::now()->format('Ym');

        UserTradeInfoModel::suffix($suffix);

        //当天起始时间点
        $timestamp=Carbon::now()->startOfDay()->timestamp;

        $res=UserTradeInfoModel::where(['belong'=>$uid])->where('paytime','>=',$timestamp)->first();

        if ($res)
        {
            //完成了
            $tmp['5']=1;

            Redis::connection('UserInfo')->hset($this->getRedisKey($uid),5,1);

            Redis::connection('UserInfo')->expireat($this->getRedisKey($uid),Carbon::now()->endOfDay()->timestamp);
        }

        return true;
    }

    //设置每日任务完成情况
    public function setDailyTasksForUser(Request $request)
    {
        $uid=$request->uid;

        $key=$this->getRedisKey($uid);

        //每日任务id
        $dailyTasksId=trim($request->tid);

        //status
        $status=trim($request->status);

        try
        {
            $nowStatus=Redis::connection('UserInfo')->hget($key,$dailyTasksId);

            //2是已领取状态
            if ($nowStatus==2) return response()->json(['resCode'=>Config::get('resCode.200')]);

            Redis::connection('UserInfo')->hset($key,$dailyTasksId,$status);

            Redis::connection('UserInfo')->expire($key,86400);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.631')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //获取当天的每日任务
    public function getDailyTasks(Request $request)
    {
        //当天第一次请求时间
        $star=time();

        //当天结束时间
        $stop=Carbon::now()->endOfDay()->timestamp;

        //差多少分钟
        $m=($stop-$star)/60;

        $m=intval($m);

        $dailyTasks=Cache::remember($this->KeyForDB.'_2',$m,function()
        {
            $res=DailyTasksModel::all()->toArray();

            //随机5个
            return array_random($res,5);
        });

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$dailyTasks]);
    }

    //广告看完之后的回调
    public function callBackForAD(Request $request)
    {






    }








}
