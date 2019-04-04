<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class SignInController extends BaseController
{
    //用户签到
    public function signIn(Request $request)
    {
        $key=Carbon::now()->format('Ymd');
        $userid=$request->userid;

        try
        {
            Redis::connection('SignIn')->setbit($key,$userid,1);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.602')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }
}