<?php

namespace App\Http\Controllers\admin;

use Illuminate\Support\Facades\Redis;

class AdminUserController extends AdminBaseController
{
    public function userData1()
    {
        $res=jsonDecode(Redis::connection('default')->get('userData1'));

        return view('admin.showdata.show_user_data_1')->with(['res'=>$res]);
    }





}