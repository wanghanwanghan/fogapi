<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\Admin\SystemMessageModel;
use App\Model\GetGoodsBySysMsgModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class SystemController extends BaseController
{
    //获取系统通知
    public function getSystemMessage(Request $request)
    {
        $uid=$request->uid;
        $request->type==''  ? $type=1   : $type=$request->type;
        $request->page==''  ? $page=1   : $page=$request->page;
        $request->limit=='' ? $limit=5  : $limit=$request->limit;

        if ((int)$type===1)
        {
            //分批查
            $offset=($page-1)*$limit;

            $res=Cache::remember('SystemMessage_type_1',1,function () use ($limit,$offset){

                return SystemMessageModel::orderBy('id','desc')->limit($limit)->offset($offset)->get()->toArray();

            });

        }else
        {
            $res=Cache::remember('SystemMessage_type_2',1,function (){

                return SystemMessageModel::orderBy('id','desc')->get()->toArray();

            });
        }

        $suffix=$uid%3;
        GetGoodsBySysMsgModel::suffix($suffix);

        foreach ($res as &$one)
        {
            if ($one['myObj']==2)
            {
                //大于0说明数据库里有数据，是已经领取的状态
                GetGoodsBySysMsgModel::where(['uid'=>$uid,'sid'=>$one['id']])->count()>0 ? $one['canIOpen']=0 : $one['canIOpen']=1;
            }

            $one['time']=formatDate($one['execTime']);

            if (strpos($one['time'],'-')!==false) $one['time']=formatDate(strtotime($one['created_at']));
        }
        unset($one);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }

    //获取系统通知详情
    public function getSystemMessageDetail($id)
    {
        if ($id!='' && $id!=null && $id!=0 && is_numeric($id))
        {
            $res=SystemMessageModel::find($id);

            if ($res==null) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>null]);

            return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res->toArray()]);

        }else
        {
            return response()->json(['resCode' => Config::get('resCode.604')]);
        }
    }

    //公告栏是否显示小红点
    public function showRedDot(Request $request)
    {
        //做法是请求两个接口，一个是交易信息，要给是系统信息
        //返回结果md5发给前端

        $uid=$request->uid;

        if ($uid=='') return response()->json(['resCode' => Config::get('resCode.601')]);

        //交易信息
        $userObj=new UserController();

        $tradeInfo=$userObj->getRecentlyTradeInfo($request)->getData();

        foreach ($tradeInfo->data as &$one)
        {
            //paytime必须要弄成0，因为paytime经过format后，会有“多少分钟之前”这样的变量
            $one->paytime=0;
        }
        unset($one);

        $tradeInfo_md5=md5(json_encode($tradeInfo));

        //系统信息
        $systemInfo=$this->getSystemMessage($request)->getData();

        $systemInfo_md5=md5(json_encode($systemInfo));

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>md5($tradeInfo_md5.$systemInfo_md5)]);
    }

    //领取物品
    public function getGoodsOrMoney(Request $request)
    {
        //公告信息中可能给用户发钱或者发道具
        $sid=$request->sid;//公告主键
        $uid=$request->uid;//用户主键
        $type=(int)$request->type;//1是领钱，2是领物品
        //$gid=json_decode($request->gid,true);//物品主键

        if (!is_numeric($sid) && $sid==0) return response()->json(['resCode' => Config::get('resCode.604')]);

        if (!is_numeric($uid) && $uid==0) return response()->json(['resCode' => Config::get('resCode.604')]);

        if ($type!==1 && $type!==2) return response()->json(['resCode' => Config::get('resCode.604')]);

        //通过uid分三张表中
        $suffix=$uid%3;

        GetGoodsBySysMsgModel::suffix($suffix);

        if (GetGoodsBySysMsgModel::where(['uid'=>$uid,'sid'=>$sid])->count()!=0)
        {
            //说明已经领取过了
            return response()->json(['resCode' => Config::get('resCode.631')]);

        }else
        {
            GetGoodsBySysMsgModel::create(['uid'=>$uid,'sid'=>$sid]);
        }

        return response()->json(['resCode' => Config::get('resCode.200')]);
    }











}