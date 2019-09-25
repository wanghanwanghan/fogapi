<?php

namespace App\Http\Controllers\QuanMinZhanLing\Temp;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\QuanMinZhanLing\UserController;
use App\Http\Controllers\Server\SendSmsBase;
use App\Http\Controllers\TanSuoShiJie\FogController;
use App\Model\RankListModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class MyTempController extends BaseController
{
    public function test()
    {
        $obj=new SendSmsBase();



        //dd(string2Number('s344e132')%50);


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
