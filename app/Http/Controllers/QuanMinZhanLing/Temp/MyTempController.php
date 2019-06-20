<?php

namespace App\Http\Controllers\QuanMinZhanLing\Temp;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use Illuminate\Support\Facades\Redis;

class MyTempController extends BaseController
{
    public function test()
    {
        $Geo=new \Geohash\GeoHash();

        $lng=\sprintf("%.4f",'108.6548');
        $lat=\sprintf("%.4f",'40.4503');

        $geohash=$Geo->encode($lat,$lng,'8');


        $res=Redis::connection('tssjOldToNew')->set('wanghan','duan');
        $res=Redis::connection('tssjOldToNew')->get('wanghan');


        dd($res);
    }








}
