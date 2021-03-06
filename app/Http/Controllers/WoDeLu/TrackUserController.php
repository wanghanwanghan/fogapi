<?php

namespace App\Http\Controllers\WoDeLu;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Intervention\Image\Facades\Image;

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
        $res=Cache::remember('TrackGetFogNum_'.$uid,1,function () use ($uid)
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
        });

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
        if (isset($vipInfo['expire']))
        {
            $expire=$vipInfo['expire'];

            $expireLimit=Carbon::createFromTimestamp($expire)->diffInDays();

        }else
        {
            $expire=0;

            $expireLimit=0;
        }

        //用户增加了多少迷雾点
        $fogPackage=$this->getFogPackage($uid);

        //服务器上有多少个点
        $fogNum=$this->getFogNum($uid);

        return response()->json([
            'resCode'=>Config::get('resCode.200'),
            'vipLevel'=>$level,
            'vipExpireDateTime'=>$expire ? date('Y-m-d',$expire) : 0,//到期时间Ymd
            'vipExpireLimitDays'=>$expireLimit,//还有多少天到期
            'fogPackage'=>$fogPackage+200,//迷雾拓展包
            'fog'=>(int)($fogNum*0.0079),//目前有多少面积在服务器中
        ]);
    }

    //修改会员状态，支付后，接收到异步通知调用
    public function modifyVipStatus($uid,$productId)
    {
        if (!is_numeric($productId))
        {
            //苹果的逻辑
            switch ($productId)
            {
                case 'wodeluapp.zujiyigeyuehuiyuan':

                    $vipInfo=$this->getVipInfo($uid);

                    $res['level']=1;

                    $expire=Carbon::now()->addDays(31)->timestamp;

                    $res['expire']=$expire;

                    if ($vipInfo)
                    {
                        $vipInfo['level'] < $res['level'] ?: $res['level']=$vipInfo['level'];

                        $res['expire']=Carbon::createFromTimestamp($vipInfo['expire'])->addDays(31)->timestamp;
                    }

                    Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'VipInfo',jsonEncode($res));

                    break;

                case 'wodeluapp.zujisangeyuehuiyuan':

                    $vipInfo=$this->getVipInfo($uid);

                    $res['level']=2;

                    $expire=Carbon::now()->addDays(93)->timestamp;

                    $res['expire']=$expire;

                    if ($vipInfo)
                    {
                        $vipInfo['level'] < $res['level'] ?: $res['level']=$vipInfo['level'];

                        $res['expire']=Carbon::createFromTimestamp($vipInfo['expire'])->addDays(93)->timestamp;
                    }

                    Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'VipInfo',jsonEncode($res));

                    break;

                case 'wodeluapp.zujinianhuiyuan':

                    $vipInfo=$this->getVipInfo($uid);

                    $res['level']=3;

                    $expire=Carbon::now()->addDays(365)->timestamp;

                    $res['expire']=$expire;

                    if ($vipInfo)
                    {
                        $vipInfo['level'] < $res['level'] ?: $res['level']=$vipInfo['level'];

                        $res['expire']=Carbon::createFromTimestamp($vipInfo['expire'])->addDays(365)->timestamp;
                    }

                    Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'VipInfo',jsonEncode($res));

                    break;

                case 'wodeluapp.zuji100km':

                    //100
                    $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                    $res+=100;

                    Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                    break;

                case 'wodeluapp.zuji200km':

                    //200
                    $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                    $res+=200;

                    Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                    break;

                case 'wodeluapp.zuji300km':

                    //300
                    $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                    $res+=300;

                    Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                    break;

                case 'wodeluapp.zuji550km':

                    //550
                    $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                    $res+=550;

                    Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                    break;

                case 'wodeluapp.zuji750km':

                    //750
                    $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                    $res+=750;

                    Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                    break;

                case 'wodeluapp.zuji1000km':

                    //1000
                    $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                    $res+=1000;

                    Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'FogPackage',$res);

                    break;

                default:

                    break;
            }

            return true;
        }

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
                    $vipInfo['level'] < $res['level'] ?: $res['level']=$vipInfo['level'];

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
                    $vipInfo['level'] < $res['level'] ?: $res['level']=$vipInfo['level'];

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
                    $vipInfo['level'] < $res['level'] ?: $res['level']=$vipInfo['level'];

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

                //550
                $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'FogPackage');

                $res+=550;

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

        return true;
    }

    //获取用户姓名和头像
    public function getUserNameAndAvatar($uid)
    {
        //redis里没有就从track里拿
        $userinfo['name']  =trim(Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'name'));
        $userinfo['avatar']=trim(Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'avatar'));

        if (empty($userinfo['name']))
        {
            $res=DB::connection('track_old')->table('track_member')->where('userid',$uid)->first();

            if (empty($res))
            {
                $userinfo['name']=randomUserName();
            }else
            {
                $userinfo['name']=trim($res->username);
            }

            Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'name',$userinfo['name']);
        }

        if (empty($userinfo['avatar']))
        {
            $res=DB::connection('track_old')->table('track_member')->where('userid',$uid)->first();

            $userinfo['avatar']='/imgModel/systemAvtar.png';

            if (!empty($res))
            {
                $avatar='http://www.wodeluapp.com/attachment/'.trim($res->avatar);

                $check=checkFileExists($avatar);

                //检查是否可以取得
                if ($check)
                {
                    $img=file_get_contents($avatar);

                    $url=$this->storeAvatar($img,$uid);

                    if ($url) $userinfo['avatar']=$url;
                }
            }

            Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'avatar',$userinfo['avatar']);
        }

        return $userinfo;
    }

    //图片贮存到服务器
    private function storeAvatar($content,$uid)
    {
        $suffix=$uid%5;

        $width =200;
        $height=200;

        $path=public_path(DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR);

        $pathStoreInDB=DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR;

        if (!is_dir($path)) mkdir($path,0777,true);

        $filename="track_avatar_{$uid}.jpg";

        try
        {
            Image::make($content)->resize($width,$height)->save($path.$filename);

            return $pathStoreInDB.$filename;

        }catch (\Exception $e)
        {
            sleep(1);

            try
            {
                Image::make($content)->resize($width,$height)->save($path.$filename);

                return $pathStoreInDB.$filename;

            }catch (\Exception $w)
            {
                return '';
            }
        }
    }
}
