<?php

namespace App\Http\Controllers\QuanMinZhanLing\Temp;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\QuanMinZhanLing\UserController;
use App\Http\Controllers\TanSuoShiJie\FogController;
use App\Model\Tssj\FogModel;
use Illuminate\Support\Facades\Redis;
use Geohash\GeoHash as myGeo;

class MyTempController extends BaseController
{
    public function test()
    {





        return view('inMap');
    }








}
