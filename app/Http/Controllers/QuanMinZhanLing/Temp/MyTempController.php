<?php

namespace App\Http\Controllers\QuanMinZhanLing\Temp;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\QuanMinZhanLing\UserController;
use App\Http\Controllers\Server\SendSmsBase;
use App\Http\Controllers\Server\TokenBase;
use App\Http\Controllers\TanSuoShiJie\FogController;
use App\Model\RankListModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class MyTempController extends BaseController
{
    public function test()
    {



        return view('inMap');
    }








}
