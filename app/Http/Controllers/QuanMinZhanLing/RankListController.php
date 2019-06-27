<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\GridInfoModel;
use App\Model\GridModel;
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

        switch ($type)
        {
            //获取个人资产
            case '1':

                return response()->json($this->getUserAssets($uid));

                break;

            //格子排行
            case '2':

                return response()->json($this->getGridAssets($uid));

                break;

            //格子总价榜
            case '3':

                return response()->json($this->getGridTotlePrice($uid));

                break;

            //格子数量榜
            case '4':

                return response()->json($this->getGridTotle($uid));

                break;

            //购买格子纳税榜
            case '5':

                return response()->json($this->getGridTax($uid));

                break;

            default:

                return response()->json(['resCode'=>Config::get('resCode.604')]);

                break;
        }
    }

    //个人资产
    public function getUserAssets($uid)
    {
        $all=RankListModel::orderBy('now')->limit(200)->get()->toArray();

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

        if (empty($all))
        {
            $all=null;
        }else
        {
            $all=arraySort1($all,['asc','now']);
        }

        return ['resCode'=>Config::get('resCode.200'),'all'=>$all,'usr'=>$usr];
    }

    //格子资产
    public function getGridAssets($uid)
    {
        $res=Redis::connection('WriteLog')->get('GridRankList');

        if ($res==null) return ['resCode'=>Config::get('resCode.604')];

        $res=jsonDecode($res);

        $res=arraySort1($res,['asc','row']);
        $res=changeArrKey($res,['row'=>'now']);

        $pic2=null;

        //格子排行榜第一的图片随时更新
        if (isset($res[0]['uid']) && $res[0]['uid']!='' && $res[0]['uid']!=0 && $res[0]['gridName']!='')
        {
            $info=GridModel::where('name',$res[0]['gridName'])->first();

            if ($info)
            {
                $info=GridInfoModel::where(['uid'=>$res[0]['uid'],'gid'=>$info->id,'showPic2'=>1])->first();

                if ($info) $pic2=$info->pic2;
            }
        }

        $res[0]['pic2']=$pic2;

        return ['resCode'=>Config::get('resCode.200'),'data'=>$res];
    }

    //格子总价榜
    public function getGridTotlePrice($uid)
    {
        $key='GridTotlePriceRank';

        $res=jsonDecode(Redis::connection('WriteLog')->get($key));

        $col=current(collect($res)->where('uid',$uid)->all());

        if ($col==false) $col=null;

        return ['resCode'=>Config::get('resCode.200'),'usr'=>$col,'all'=>collect($res)->slice(0,200)->all()];
    }

    //格子数量榜
    public function getGridTotle($uid)
    {
        $key='GridTotleRank';

        $res=jsonDecode(Redis::connection('WriteLog')->get($key));

        $col=current(collect($res)->where('uid',$uid)->all());

        if ($col==false) $col=null;

        return ['resCode'=>Config::get('resCode.200'),'usr'=>$col,'all'=>collect($res)->slice(0,200)->all()];
    }

    //购买格子纳税榜
    public function getGridTax($uid)
    {
        $key='GridTaxRank';

        $res=jsonDecode(Redis::connection('WriteLog')->get($key));

        if ($res==null || empty($res) || $res==false)
        {
            return ['resCode'=>Config::get('resCode.200'),'usr'=>null,'all'=>[]];
        }

        $col=current(collect($res)->where('uid',$uid)->all());

        if ($col==false) $col=null;

        return ['resCode'=>Config::get('resCode.200'),'usr'=>$col,'all'=>collect($res)->slice(0,200)->all()];
    }



}