<?php

namespace App\Http\Controllers\admin;

use App\Model\GridInfoModel;
use App\Model\PicCheckModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminGridController extends AdminBaseController
{
    //首页
    public function index(Request $request)
    {
        return true;
    }

    //ajax
    public function gridAjax(Request $request)
    {
        switch ($request->type)
        {
            case 'get_grid_img':

                //拿10个
                $res=DB::connection('masterDB')->table('pic_check')
                    ->leftJoin('grid','grid.id','=','pic_check.gid')
                    ->where('pic','pic1')
                    ->where('isCheck',0)
                    ->orderby('pic_check.updated_at','asc')->limit(10)->get()->toArray();

                //总共多少个
                $count=DB::connection('masterDB')->table('pic_check')
                    ->leftJoin('grid','grid.id','=','pic_check.gid')
                    ->where('pic','pic1')
                    ->where('isCheck',0)
                    ->orderby('pic_check.updated_at','asc')->count();

                return ['data'=>$res,'count'=>$count];

                break;

            case 'get_grid_img_2':

                //拿10个
                $res=DB::connection('masterDB')->table('grid_info')
                    ->leftJoin('grid','grid.id','=','grid_info.gid')
                    ->where('pic2','<>',null)
                    ->where('showPic2','<>',null)
                    ->where('showPic2','0')
                    ->orderby('grid_info.updated_at','asc')->limit(10)->get()->toArray();

                //总共多少个
                $count=DB::connection('masterDB')->table('grid_info')
                    ->leftJoin('grid','grid.id','=','grid_info.gid')
                    ->where('pic2','<>',null)
                    ->where('showPic2','<>',null)
                    ->where('showPic2','0')
                    ->orderby('grid_info.updated_at','asc')->count();

                return ['data'=>$res,'count'=>$count];

                break;

            case 'picPass':

                $stringId=$request->stringId;

                $whitchPic=$request->whitchPic;

                $arr=explode(',',$stringId);

                $suffix=$arr[0]%5;

                $path=public_path(DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix);

                //arr[0]是uid，arr[1]是gid
                $gridInfo=GridInfoModel::firstOrNew(['uid'=>$arr[0],'gid'=>$arr[1]]);

                if ($whitchPic==1)
                {
                    //arr[0]是uid，arr[1]是gid
                    $info=PicCheckModel::where(['uid'=>$arr[0],'gid'=>$arr[1],'pic'=>'pic1'])->first();

                    //过审的图片
                    $gridInfo->pic1=str_replace('readyToCheck','',$info->picUrl);
                    $gridInfo->showPic1=1;

                    @rename($path.$info->picUrl,$path.$gridInfo->pic1);
                }

                if ($whitchPic==2)
                {
                    //arr[0]是uid，arr[1]是gid
                    $info=PicCheckModel::where(['uid'=>$arr[0],'gid'=>$arr[1],'pic'=>'pic2'])->first();

                    $gridInfo->pic2=str_replace('readyToCheck','',$info->picUrl);
                    $gridInfo->showPic2=1;

                    @rename($path.$info->picUrl,$path.$gridInfo->pic2);
                }

                $gridInfo->save();

                return true;

                break;

            case 'picNoPass':

                $stringId=$request->stringId;

                $whitchPic=$request->whitchPic;

                $arr=explode(',',$stringId);

                //arr[0]是uid，arr[1]是gid
                $info=GridInfoModel::where(['uid'=>$arr[0],'gid'=>$arr[1]])->first();

                if ($whitchPic==1)
                {
                    $info->pic1=null;
                    $info->showPic1=null;
                }

                if ($whitchPic==2)
                {
                    $info->pic2=null;
                    $info->showPic2=null;
                }

                $info->save();

                return true;

                break;
        }
    }

    //审核格子图片页面
    public function gridImg(Request $request)
    {
        return view('admin.grid.grid_img');
    }

    //审核格子排行榜背景图片页面
    public function gridImg2(Request $request)
    {
        return view('admin.grid.grid_img_2');
    }


}