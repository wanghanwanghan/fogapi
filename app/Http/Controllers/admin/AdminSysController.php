<?php

namespace App\Http\Controllers\admin;

use App\Model\GridInfoModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSysController extends AdminBaseController
{
    //首页
    public function index(Request $request)
    {
        return true;
    }

    //ajax
    public function sysAjax(Request $request)
    {
        switch ($request->type)
        {
            case 'get_grid_img':

                //拿10个
                $res=DB::connection('masterDB')->table('grid_info')
                    ->leftJoin('grid','grid.id','=','grid_info.gid')
                    ->where('pic1','<>',null)
                    ->where('showPic1','<>',null)
                    ->where('showPic1','0')
                    ->orderby('grid_info.updated_at','asc')->limit(10)->get()->toArray();

                //总共多少个
                $count=DB::connection('masterDB')->table('grid_info')
                    ->leftJoin('grid','grid.id','=','grid_info.gid')
                    ->where('pic1','<>',null)
                    ->where('showPic1','<>',null)
                    ->where('showPic1','0')
                    ->orderby('grid_info.updated_at','asc')->count();

                return ['data'=>$res,'count'=>$count];

                break;

            case 'picPass':

                $stringId=$request->stringId;

                $arr=explode(',',$stringId);

                //arr[0]是uid，arr[1]是gid
                $info=GridInfoModel::where(['uid'=>$arr[0],'gid'=>$arr[1]])->first();

                $info->showPic1=1;
                $info->save();

                return true;

                break;

            case 'picNoPass':

                $stringId=$request->stringId;

                $arr=explode(',',$stringId);

                //arr[0]是uid，arr[1]是gid
                $info=GridInfoModel::where(['uid'=>$arr[0],'gid'=>$arr[1]])->first();

                $info->pic1=null;
                $info->showPic1=null;
                $info->save();

                return true;

                break;
        }
    }

    //创建一个公告
    public function sysCreate(Request $request)
    {
        return view('admin.sys.sys_create');
    }












}