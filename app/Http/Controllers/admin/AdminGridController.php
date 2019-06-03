<?php

namespace App\Http\Controllers\admin;

use App\Model\GridInfoModel;
use App\Model\PicCheckModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

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
            case 'get_img_size':

                $imgUrl=$request->imgUrl;

                $imgArr=getimagesize(public_path($imgUrl));

                $width =$imgArr[0]*3;
                $height=$imgArr[1]*3;

                return ['width'=>$width,'height'=>$height];

                break;

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
                $res=DB::connection('masterDB')->table('pic_check')
                    ->leftJoin('grid','grid.id','=','pic_check.gid')
                    ->where('pic','pic2')
                    ->where('isCheck',0)
                    ->orderby('pic_check.updated_at','asc')->limit(10)->get()->toArray();

                //总共多少个
                $count=DB::connection('masterDB')->table('pic_check')
                    ->leftJoin('grid','grid.id','=','pic_check.gid')
                    ->where('pic','pic2')
                    ->where('isCheck',0)
                    ->orderby('pic_check.updated_at','asc')->count();

                return ['data'=>$res,'count'=>$count];

                break;

            case 'picPass':

                $stringId=$request->stringId;

                $whitchPic=$request->whitchPic;

                $arr=explode(',',$stringId);

                //arr[0]是uid，arr[1]是gid
                $gridInfo=GridInfoModel::firstOrNew(['uid'=>$arr[0],'gid'=>$arr[1]]);

                if ($whitchPic==1)
                {
                    //arr[0]是uid，arr[1]是gid
                    $picCheckInfo=PicCheckModel::where(['uid'=>$arr[0],'gid'=>$arr[1],'pic'=>'pic1'])->first();

                    //过审的图片
                    $gridInfo->pic1=str_replace('readyToCheck','',$picCheckInfo->picUrl);
                    $gridInfo->showPic1=1;

                    @unlink(public_path().$gridInfo->pic1);

                    $img=Image::make(public_path().$picCheckInfo->picUrl);
                    $img->save(public_path().$gridInfo->pic1);
                }

                if ($whitchPic==2)
                {
                    //arr[0]是uid，arr[1]是gid
                    $picCheckInfo=PicCheckModel::where(['uid'=>$arr[0],'gid'=>$arr[1],'pic'=>'pic2'])->first();

                    $gridInfo->pic2=str_replace('readyToCheck','',$picCheckInfo->picUrl);
                    $gridInfo->showPic2=1;

                    @unlink(public_path().$gridInfo->pic2);

                    $img=Image::make(public_path().$picCheckInfo->picUrl);
                    $img->save(public_path().$gridInfo->pic2);
                }

                $picCheckInfo->isCheck=1;
                $picCheckInfo->save();

                $gridInfo->save();

                return true;

                break;

            case 'picNoPass':

                $stringId=$request->stringId;

                $whitchPic=$request->whitchPic;

                $arr=explode(',',$stringId);

                if ($whitchPic==1)
                {
                    //arr[0]是uid，arr[1]是gid
                    $info=PicCheckModel::where(['uid'=>$arr[0],'gid'=>$arr[1],'pic'=>'pic1'])->first();
                }

                if ($whitchPic==2)
                {
                    //arr[0]是uid，arr[1]是gid
                    $info=PicCheckModel::where(['uid'=>$arr[0],'gid'=>$arr[1],'pic'=>'pic2'])->first();
                }

                $info->pic=null;
                $info->isCheck=null;
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