<?php

namespace App\Http\Controllers\QuanMinZhanLing\Temp;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\QuanMinZhanLing\UserController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint;

class MyTempController extends BaseController
{
    public function test()
    {
        $res='';

//        if (!Schema::connection('TssjFogMongoDB0')->hasTable('users'))
//        {
//            Schema::connection('TssjFogMongoDB0')->create('users', function (Blueprint $table)
//            {
//                $table->integer('uid');
//                $table->string('geo');
//                $table->integer('unixTime');
//                $table->unique(['uid','geo']);
//            });
//        }


//        for ($i=1;$i<=30;$i++)
//        {
//            $res=DB::connection('TssjFogMongoDB0')->collection('users')->insert([
//
//                'uid'=>random_int(1,99999),
//                'geo'=>str_random(),
//                'unixTime'=>time(),
//
//            ]);
//        }


//        $res=DB::connection('TssjFogMongoDB0')->collection('users')->createIndex([
//
//            'uid'=>1,
//            'geo'=>1,
//
//        ],[
//
//            'background'=>true,
//            'unique'=>true,
//
//        ]);

        //$res=DB::connection('TssjFogMongoDB0')->statement('db.myTest.createIndex({"uid":1,"geo":1},{"background":true,"unique":true})');




//        $res=DB::connection('TssjFogMongoDB0')->collection('users')->insert([
//
//            'uid'=>95135,
//            'geo'=>'o7Bu8am8QB4JKJfd',
//            'unixTime'=>time(),
//
//        ]);

//        $res=DB::connection('TssjFogMongoDB0')->collection('users')->where(['uid'=>111,'geo'=>'PornHub1'])->update(['unixTime'=>time()],['upsert'=>true,'multiple'=>false]);





        dd($res,'oye');



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
