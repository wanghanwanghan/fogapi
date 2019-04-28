<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\GridInfoModel;
use App\Model\RankListModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class RankListController extends BaseController
{
    public function getRankList(Request $request)
    {
        $uid=$request->uid;
        $type=$request->type;

        //获取个人资产
        switch ($type)
        {
            case '1':

                $this->getUserAssets($uid);

                break;

            case '2':

                $this->getGridAssets($uid);

                break;

            default:

                return response()->json(['resCode'=>Config::get('resCode.604')]);

                break;
        }
    }

    //个人资产
    public function getUserAssets($uid)
    {
        $all=RankListModel::orderBy('now','desc')->get()->toArray();
        $usr=RankListModel::where('uid',$uid)->get()->toArray();

        $userController=new UserController();

        //添加头像
        foreach ($all as &$oneUser)
        {
            $userInfo=$userController->getUserNameAndAvatar($oneUser['uid']);

            $oneUser['avatar']=$userInfo['avatar'];
            $oneUser['name']=$userInfo['name'];

            unset($oneUser['id']);
            unset($oneUser['last']);
            unset($oneUser['gridPrice']);
            unset($oneUser['money']);
        }
        unset($oneUser);

        //个人排行
        if (empty($usr))
        {
            $usr=null;

        }else
        {
            $usr=current($usr);

            $userInfo=$userController->getUserNameAndAvatar($usr['uid']);

            $usr['avatar']=$userInfo['avatar'];
            $usr['name']=$userInfo['name'];

            unset($usr['id']);
            unset($usr['gridPrice']);
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'all'=>$all,'usr'=>$usr]);
    }

    //格子资产
    public function getGridAssets($uid)
    {
        $res=Redis::connection('WriteLog')->get('GridRankList');

        if ($res==null) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $res=json_decode($res,true);

        //取得格子第一名的图片
        foreach ($res as &$oneUser)
        {
            if ($oneUser['row']!=1) continue;

            $pic2=GridInfoModel::where(['uid'=>$oneUser['uid'],'showPic2'=>1])->first();

            if ($pic2==null)
            {
                $oneUser['pic2']=null;

            }else
            {
                $oneUser['pic2']=$pic2->pic2;
            }
        }
        unset($oneUser);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }
}