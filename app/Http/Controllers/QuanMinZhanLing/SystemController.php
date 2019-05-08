<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\Admin\SystemMessageModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class SystemController extends BaseController
{
    //获取系统通知
    public function getSystemMessage(Request $request)
    {
        $request->type==''  ? $type=1   : $type=$request->type;
        $request->page==''  ? $page=1   : $page=$request->page;
        $request->limit=='' ? $limit=5 : $limit=$request->limit;

        if ((int)$type===1)
        {
            //分批查
            $offset=($page-1)*$limit;

            $res=Cache::remember('SystemMessage_type_1',2,function () use ($limit,$offset){

                return SystemMessageModel::where('exec',1)
                    ->orderBy('id','desc')
                    ->limit($limit)->offset($offset)->get(['id','myContent','execTime'])->toArray();

            });

        }else
        {
            $res=Cache::remember('SystemMessage_type_2',10,function (){

                return SystemMessageModel::where('exec',1)
                    ->orderBy('id','desc')->get(['id','myContent','execTime'])->toArray();

            });
        }

        foreach ($res as &$one)
        {
            $one['time']=formatDate($one['execTime']);
        }
        unset($one);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }

    //公告栏是否显示小红点
    public function showRedDot()
    {

    }
}