<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\Aliance\AlianceGroupModel;
use App\Model\DailyTasksModel;
use App\Model\UserTradeInfoModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $dailyTasksId=DailyTasksModel::pluck('id')->toArray();

        if (!is_array($dailyTasksId)) return response()->json(['resCode' => Config::get('resCode.604')]);

        $res=Redis::connection('UserInfo')->hgetall($this->getRedisKey($uid));

        $count=count($dailyTasksId);

        //处理数组
        if ((int)trim($request->isNew))
        {
            for ($i=0;$i<$count;$i++)
            {
                //看看这个key在redis hash里有没有
                $key=$dailyTasksId[$i];

                if (array_key_exists($key,$res))
                {
                    if ($key==7)
                    {
                        $tmp[$key]['status']=$res[$key];
                        $tmp[$key]['num']=$this->wishPoolForFreeByLookAd($uid);

                    }else
                    {
                        $tmp[$key]=$res[$key];
                    }

                }else
                {
                    if ($key==7)
                    {
                        $tmp[$key]['status']=0;
                        $tmp[$key]['num']=$this->wishPoolForFreeByLookAd($uid);

                    }else
                    {
                        $tmp[$key]=0;
                    }
                }
            }

            $this->tid_7($uid,$tmp);

        }else
        {
            for ($i=0;$i<5;$i++)
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

    //计算id是7
    public function tid_7($uid,&$tmp)
    {
        //获得许愿次数
        //7只有0和2两个状态，没有1

        if (!isset($tmp['7'])) return true;

        //7已经完成
        if ($tmp['7']['status']!=0) return true;

        //判断完成几次了
        $count=$tmp['7']['num'];

        $DBcount=DailyTasksModel::find(7);

        if ($count >= $DBcount->scheduleTotle)
        {
            //完成了
            $tmp['7']['status']=2;

            Redis::connection('UserInfo')->hset($this->getRedisKey($uid),7,2);

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

        $nowStatus=Redis::connection('UserInfo')->hget($key,$dailyTasksId);

        //2是已领取状态
        if ($nowStatus==2) return response()->json(['resCode'=>Config::get('resCode.200')]);

        //id是7的要特殊处理
        if ($dailyTasksId==7)
        {
            $this->addWishPoolCount($uid);
        }else
        {
            Redis::connection('UserInfo')->hset($key,$dailyTasksId,$status);
        }

        Redis::connection('UserInfo')->expire($key,86400);

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //获取当天的每日任务
    public function getDailyTasks(Request $request)
    {
        $uid=$request->uid;

        if ((int)trim($request->isNew))
        {
            $dailyTasks=DailyTasksModel::all()->toArray();
        }else
        {
            $dailyTasks=DailyTasksModel::limit(5)->get()->toArray();
        }

        if (is_numeric($uid) && $uid >= 1)
        {
            if (AlianceGroupModel::where(['uid'=>$uid,'alianceNum'=>3])->first() != null)
            {
                foreach ($dailyTasks as &$one)
                {
                    $one['price']=$one['price'] * 2;
                }
                unset($one);
            }
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$dailyTasks]);
    }

    //看广告得免费许愿次数，完成了几次了
    private function wishPoolForFreeByLookAd($uid,$expr=null)
    {
        $date=Carbon::now()->format('Ymd');

        $key=$this->DailyTasksKey."{$uid}_{$date}_tid7";

        if ($expr!=null)
        {
            $count=(int)Redis::connection('UserInfo')->get($key);

            $count++;

            Redis::connection('UserInfo')->set($key,$count);

            return Redis::connection('UserInfo')->expireat($key,Carbon::now()->endOfDay()->timestamp);
        }else
        {
            return (int)Redis::connection('UserInfo')->get($key);
        }
    }

    //许愿次数加1，tid7完成次数加1
    private function addWishPoolCount($uid)
    {
        //完成几次了
        $fCount=$this->wishPoolForFreeByLookAd($uid);

        //可以完成几次
        $DBcount=DailyTasksModel::find(7)->scheduleTotle;

        if ($fCount >= $DBcount) return true;

        //许愿次数加1
        //今日免费次数
        $time=Carbon::now()->format('Ymd');
        $key="WishPoolForFree_{$time}_{$uid}";
        $wishPoolForFree=Redis::connection('UserInfo')->get($key);

        if ($wishPoolForFree===null)
        {
            $wishPoolForFree=4;

        }else
        {
            $wishPoolForFree++;
        }

        Redis::connection('UserInfo')->set($key,$wishPoolForFree);

        //完成次数加1
        $this->wishPoolForFreeByLookAd($uid,'+1');

        return true;
    }





}
