<?php

namespace App\Http\Controllers\admin;

use App\Model\AvatarCheckModel;
use App\Model\PicCheckModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Intervention\Image\Facades\Image;

class AdminUserAvatarController extends AdminBaseController
{
    public function userAvatar()
    {
        return view('admin.avatar.avatar_index');
    }

    public function picInRedis1()
    {
        return view('admin.avatar.pic1_index');
    }

    public function userAjax(Request $request)
    {
        switch ($request->type)
        {
            case 'get_img_size':

                $imgUrl=$request->imgUrl;

                $imgArr=getimagesize(public_path($imgUrl));

                if ($imgArr[0]>500)
                {
                    $width =$imgArr[0];
                    $height=$imgArr[1];

                }else
                {
                    $width =$imgArr[0]*3;
                    $height=$imgArr[1]*3;
                }

                return ['width'=>$width,'height'=>$height];

                break;

            case 'get_user_img':

                //拿10个
                $res=DB::connection('masterDB')->table('avatar_check')
                    ->where('isCheck',0)
                    ->orderby('updated_at')->limit(10)->get()->toArray();

                //总共多少个
                $count=DB::connection('masterDB')->table('avatar_check')
                    ->where('isCheck',0)
                    ->orderby('updated_at')->count();

                return ['data'=>$res,'count'=>$count];

                break;

            case 'get_user_pic1':

                //拿10个
                $res=DB::connection('masterDB')->table('pic_check')
                    ->where('pic','redisPic1')
                    ->where('isCheck',0)
                    ->orderby('updated_at')->limit(10)->get()->toArray();

                //总共多少个
                $count=DB::connection('masterDB')->table('pic_check')
                    ->where('pic','redisPic1')
                    ->where('isCheck',0)
                    ->orderby('updated_at')->count();

                return ['data'=>$res,'count'=>$count];

                break;

            case 'picPass_picInRedis1':

                $stringId=$request->stringId;

                $arr=explode(',',$stringId);

                $res=PicCheckModel::find($arr[0]);

                $res->isCheck=1;

                $res->save();

                $picUrl=str_replace('readyToCheck','',$res->picUrl);

                $img=Image::make(public_path().$res->picUrl);

                $img->save(public_path().$picUrl);

                Redis::connection('UserInfo')->hset($arr[1],'PicInRedis1',$picUrl);

                return ['200'];

                break;

            case 'picNoPass_picInRedis1':

                $stringId=$request->stringId;

                $arr=explode(',',$stringId);

                $res=PicCheckModel::find($arr[0]);

                $res->picUrl=null;
                $res->isCheck=null;

                $res->save();

                return ['200'];

                break;

            case 'picPass':

                $stringId=$request->stringId;

                $arr=explode(',',$stringId);

                $res=AvatarCheckModel::find($arr[0]);

                $res->isCheck=1;

                $res->save();

                $avatarUrl=str_replace('readyToCheck','',$res->avatarUrl);

                $img=Image::make(public_path().$res->avatarUrl);

                $img->save(public_path().$avatarUrl);

                Redis::connection('UserInfo')->hset($arr[1],'avatar',$avatarUrl);

                return ['200'];

                break;

            case 'picNoPass':

                $stringId=$request->stringId;

                $arr=explode(',',$stringId);

                $res=AvatarCheckModel::find($arr[0]);

                $res->avatarUrl=null;
                $res->isCheck=null;

                $res->save();

                return ['200'];

                break;

            default:

                break;
        }

        return true;
    }

}