<?php

namespace App\Http\Controllers\WoDeLu;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class TrackUserController extends Controller
{
    //这个uid是不是会员
    public function getVip($uid)
    {
        $res=(int)Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'Vip');

        //不是会员
        if (!$res) return false;

        $res=jsonDecode(Redis::connection('TrackUserInfo')->hget('Track_'.$uid,'VipInfo'));

        return $res;
    }








}
