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








}
