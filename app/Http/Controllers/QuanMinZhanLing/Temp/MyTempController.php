<?php

namespace App\Http\Controllers\QuanMinZhanLing\Temp;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\QuanMinZhanLing\UserController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class MyTempController extends BaseController
{
    public function test()
    {

        return view('inMap');
    }

    //随机返回一句鸡汤
    public function oneSaid()
    {
        $url='http://api.guaqb.cn/v1/onesaid/';

        try
        {
            $oneSaid=file_get_contents($url);



        }catch (\Exception $e)
        {
            //从mysql中取
            $oneSaid='';
        }



        dd($oneSaid);

    }








}
