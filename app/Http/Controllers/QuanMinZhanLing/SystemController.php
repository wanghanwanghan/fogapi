<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\Admin\SystemMessageModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SystemController extends BaseController
{
    //获取系统通知
    public function getSystemMessage(Request $request)
    {
        $res=SystemMessageModel::where('exec',1)->orderBy('id','desc')->get(['id','myContent','execTime'])->toArray();

        foreach ($res as &$one)
        {
            $one['time']=formatDate($one['execTime']);
        }
        unset($one);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }
}