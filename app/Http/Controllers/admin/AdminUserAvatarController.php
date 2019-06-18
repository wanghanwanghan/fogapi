<?php

namespace App\Http\Controllers\admin;

use App\Model\AvatarCheckModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AdminUserAvatarController extends AdminBaseController
{
    public function userAvatar()
    {
        return view('admin.avatar.avatar_index');
    }

    public function userAjax(Request $request)
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

            case 'picPass':

                $stringId=$request->stringId;

                $arr=explode(',',$stringId);

                $res=AvatarCheckModel::find($arr[0]);

                $res->isCheck=1;

                $res->save();

                Redis::connection('UserInfo')->hset($arr[1],'avatar',$res->avatarUrl);

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