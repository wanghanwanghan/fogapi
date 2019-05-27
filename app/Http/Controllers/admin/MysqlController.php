<?php

namespace App\Http\Controllers\admin;

use Illuminate\Support\Facades\DB;

class MysqlController extends AdminBaseController
{
    public function slowSelect()
    {
        $res=DB::connection('masterDB')->table('slow_sql')->orderBy('execTime','desc')->paginate(3);

        return view('admin.mysql.mysql_slow_select')->with(['res'=>$res]);
    }
}