<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class UserController extends BaseController
{
    //获取用户金钱
    public function getUserMoney($uid)
    {
        $res=Redis::connection('UserInfo')->hget($uid,'money');

        if ($res)
        {
            //有数据
            return $res;

        }else
        {
            //没数据
            Redis::connection('UserInfo')->hset($uid,'money',Config::get('myDefine.InitMoney'));

            return Config::get('myDefine.InitMoney');
        }
    }

    //买卖结束后增加或减少用户金钱
    public function exprUserMoney($uid,$belongid,$money)
    {
        //买方扣款 被买方加款
        if ($belongid==0)
        {
            $res=Redis::connection('UserInfo')->hget($uid,'money');

            Redis::connection('UserInfo')->hset($uid,'money',$res - $money);

        }else
        {
            $res=Redis::connection('UserInfo')->hget($uid,'money');

            Redis::connection('UserInfo')->hset($uid,'money',$res - $money);

            $res=Redis::connection('UserInfo')->hget($belongid,'money');

            Redis::connection('UserInfo')->hset($belongid,'money',$res + $money);
        }

        return true;
    }

    //获取购地卡数量
    public function getBuyCardCount($uid)
    {
        $today=Carbon::now()->format('Ymd');

        $key='BuyCard_'.$today.'_'.$uid;

        if (Redis::connection('UserInfo')->get($key)===null)
        {
            //每天给5张
            Redis::connection('UserInfo')->set($key,Config::get('myDefine.InitBuyCard'));

            Redis::connection('UserInfo')->expire($key,86400);

            return Config::get('myDefine.InitBuyCard');
        }

        return Redis::connection('UserInfo')->get($key);
    }

    //减购地卡
    public function setBuyCardCount($uid,$num=1)
    {
        $today=Carbon::now()->format('Ymd');

        $key='BuyCard_'.$today.'_'.$uid;

        $count=Redis::connection('UserInfo')->get($key);

        Redis::connection('UserInfo')->set($key,$count - $num);

        Redis::connection('UserInfo')->expire($key,86400);

        return true;
    }

    //获取用户姓名和头像
    public function getUserNameAndAvatar($uid,$update=false)
    {
        //redis里没有就从tssj里拿
        $userinfo['name']=Redis::connection('UserInfo')->hget($uid,'name');
        $userinfo['avatar']=Redis::connection('UserInfo')->hget($uid,'avatar');

        if ($userinfo['name']===null && $uid!=0)
        {
            $res=DB::connection('tssj_old')->table('tssj_member')->where('userid',$uid)->first();

            Redis::connection('UserInfo')->hset($uid,'name',trim($res->username));
            Redis::connection('UserInfo')->hset($uid,'avatar',trim($res->avatar));

            $userinfo['name']=trim($res->username);
            $userinfo['avatar']=trim($res->avatar);
        }

        if ($uid==0)
        {
            $userinfo['name']='系统';
            $userinfo['avatar']='';
        }

        return $userinfo;
    }




}