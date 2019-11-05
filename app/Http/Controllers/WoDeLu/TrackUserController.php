<?php

namespace App\Http\Controllers\WoDeLu;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TrackUserController extends Controller
{
    //这个uid买了多少迷雾拓展包
    public function getFogPackage($uid)
    {
        return (int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');
    }

    //这个uid是不是会员，是什么种类的会员
    public function getVipInfo($uid)
    {
        $res=Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'VipInfo');

        //不是会员
        if (!$res) return [];

        //是会员，过期没
        $res=jsonDecode($res);

        //过期了
        if (Carbon::now()->timestamp > $res['expire'])
        {
            Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'VipInfo',null);

            return [];
        }

        return $res;
    }

    //现在服务器上有多少个迷雾点
    public function getFogNum($uid)
    {
        $obj=new TrackFogController();

        $suffix=$obj->getDatabaseNoOrTableNo($uid);

        try
        {
            $res=DB::connection('TrackFog'.$suffix['db'])->table('user_fog_'.$suffix['table'])->where('uid',$uid)->count();

        }catch (\Exception $e)
        {
            return 0;
        }

        return $res;
    }

    //用户信息
    public function getUserInfo(Request $request)
    {
        $uid=$request->uid;

        if ($uid <= 0) return response()->json(['resCode'=>601]);

        //会员信息
        $vipInfo=$this->getVipInfo($uid);

        //会员等级
        isset($vipInfo['level']) ? $level=$vipInfo['level'] : $level=0;

        //过期时间
        isset($vipInfo['expire']) ? $expire=$vipInfo['expire'] : $expire=0;
        if ($expire!==0) $expire=date('Y-m-d',$expire);

        //用户增加了多少迷雾点
        $fogPackage=$this->getFogPackage($uid);

        //服务器上有多少个点
        $fogNum=$this->getFogNum($uid);

        return response()->json([
            'resCode'=>Config::get('resCode.200'),
            'vipLevel'=>$level,
            'vipExpire'=>$expire,
            'fogPackage'=>$fogPackage+200,//迷雾拓展包
            'fog'=>(int)($fogNum*0.0079),//目前有多少面积在服务器中
        ]);
    }

    //修改会员状态，支付后，接收到异步通知调用
    public function modifyVipStatus($uid,$productId)
    {
        $subject=[
            '1'=>'一个月vip',
            '2'=>'三个月vip',
            '3'=>'一年vip',
            '4'=>'100km',
            '5'=>'200km',
            '6'=>'300km',
            '7'=>'500km',
            '8'=>'750km',
            '9'=>'1000km',

            '255'=>'测试',
        ];

        $productId=(int)$productId;

        switch ($productId)
        {
            case 1:

                $vipInfo=$this->getVipInfo($uid);

                $res['level']=$productId;

                $expire=Carbon::now()->addDays(31)->timestamp;

                $res['expire']=$expire;

                if ($vipInfo)
                {
                    //在当前时间上，再加一个月时间
                    $vipInfo=jsonDecode($vipInfo);

                    $res['expire']=Carbon::createFromTimestamp($vipInfo['expire'])->addDays(31)->timestamp;
                }

                Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'VipInfo',jsonEncode($res));

                break;

            case 2:

                $vipInfo=$this->getVipInfo($uid);

                $res['level']=$productId;

                $expire=Carbon::now()->addDays(93)->timestamp;

                $res['expire']=$expire;

                if ($vipInfo)
                {
                    //在当前时间上，再加一个月时间
                    $vipInfo=jsonDecode($vipInfo);

                    $res['expire']=Carbon::createFromTimestamp($vipInfo['expire'])->addDays(93)->timestamp;
                }

                Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'VipInfo',jsonEncode($res));

                break;

            case 3:

                $vipInfo=$this->getVipInfo($uid);

                $res['level']=$productId;

                $expire=Carbon::now()->addDays(365)->timestamp;

                $res['expire']=$expire;

                if ($vipInfo)
                {
                    //在当前时间上，再加一个月时间
                    $vipInfo=jsonDecode($vipInfo);

                    $res['expire']=Carbon::createFromTimestamp($vipInfo['expire'])->addDays(365)->timestamp;
                }

                Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'VipInfo',jsonEncode($res));

                break;

            case 4:

                //100
                $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                $res+=100;

                Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                break;

            case 5:

                //200
                $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                $res+=200;

                Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                break;

            case 6:

                //300
                $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                $res+=300;

                Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                break;

            case 7:

                //500
                $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                $res+=500;

                Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                break;

            case 8:

                //750
                $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                $res+=750;

                Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                break;

            case 9:

                //1000
                $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                $res+=1000;

                Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                break;

            default:

                break;
        }
    }







}
