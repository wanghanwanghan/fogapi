<?php

namespace App\Http\Controllers\QuanMinZhanLing\FoodMap;

use App\Http\Controllers\QuanMinZhanLing\UserController;
use App\Model\FoodMap\AuctionHouse;
use App\Model\FoodMap\Patch;
use App\Model\FoodMap\UserPatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class FoodMapController extends FoodMapBaseController
{
    public function init()
    {
        $this->createTable('patch');
        $this->createTable('userPatch');
        $this->createTable('userSuccess');
        $this->createTable('auctionHouse');

        return true;
    }

    //许愿池
    public function wishPool(Request $request)
    {
        $uid=$request->uid;
        $num=$request->num;

        //今日免费次数
        $time=Carbon::now()->format('Ymd');
        $key="WishPoolForFree_{$time}_{$uid}";
        $wishPoolForFree=Redis::connection('UserInfo')->get($key);

        if ($wishPoolForFree===null) $wishPoolForFree=3;

        //钻石个数
        $diamond=(new UserController())->getUserDiamond($uid);

        if ($num==5 && $diamond >= 300)
        {
            $res=$this->setUid($uid)->getWish($num);

            (new UserController())->exprUserDiamond($uid,300,'-');

        }elseif ($wishPoolForFree || $num==1 && $diamond >= 66)
        {
            $res=$this->setUid($uid)->getWish($num);

            if ($wishPoolForFree > 0)
            {
                $wishPoolForFree--;
                Redis::connection('UserInfo')->set($key,$wishPoolForFree);
                Redis::connection('UserInfo')->expire($key,86400);

            }else
            {
                (new UserController())->exprUserDiamond($uid,66,'-');
            }

        }else
        {
            return response()->json(['resCode'=>Config::get('resCode.700')]);
        }

        //处理结果，加钱，加券，加碎片
        $new=$this->handleWishPool($uid,$res);

        return response()->json([
            'resCode'=>Config::get('resCode.200'),
            'wishPoolForFree'=>(int)$wishPoolForFree,
            'diamondNum'=>Redis::connection('UserInfo')->hget($uid,'Diamond'),
            'data'=>$res,
            'new'=>$new
        ]);
    }

    //许愿池处理结果
    public function handleWishPool($uid,$res)
    {
        $new=[];

        foreach ($res as $one)
        {
            $arr=explode('_',$one);

            //购地卡
            if ($arr[0]=='buyCard')
            {
                $time=Carbon::now()->format('Ymd');
                $key="BuyCard_{$time}_{$uid}";
                $num=Redis::connection('UserInfo')->get($key);
                Redis::connection('UserInfo')->set($key,$num+$arr[1]);
                continue;
            }
            //地球币
            if ($arr[0]=='money')
            {
                Redis::connection('UserInfo')->hincrby($uid,'money',$arr[1]);
                continue;
            }
            //钻石
            if ($arr[0]=='diamond')
            {
                Redis::connection('UserInfo')->hincrby($uid,'Diamond',$arr[1]);
                continue;
            }
            //普通碎片
            if ($arr[0]=='commonPatch' || $arr[0]=='epicPatch')
            {
                $new[]=FoodMapUserController::getInstance()->composeTreasure($uid,$arr[1]);
                continue;
            }
        }

        return array_filter($new);
    }

    //获取用户已经收集到宝物个数
    public function getUserTreasureNum(Request $request)
    {
        $uid=$request->uid;

        $type=$request->type;

        $res=FoodMapUserController::getInstance()->getUserAllTreasureNum($uid,$type);

        $type=$this->getTreasureType();

        return response()->json(['resCode'=>Config::get('resCode.200'),'type'=>$type,'data'=>$res]);
    }

    //获取用户宝物页
    public function getUserTreasure(Request $request)
    {
        $uid=$request->uid;

        $type=$request->type;

        //用户有的碎片
        $res=FoodMapUserController::getInstance()->getUserAllPatch($uid,$type);

        //用户已经合成的
        $success=FoodMapUserController::getInstance()->getUserAllTreasure($uid,$type);

        $successName=[];

        foreach ($success as $one)
        {
            $successName[]=$one->subject;
        }

        foreach ($res as $key=>$one)
        {
            if (in_array(substr($one->patch->subject,0,-1),$successName)) unset($res[$key]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'my'=>$res,'all'=>$success]);
    }

    //拍卖行
    public function auctionHouse(Request $request)
    {
        $uid=$request->uid;
        $type=$request->type;
        $page=(int)$request->page;
        if ($page < 1) $page=1;

        $res=FoodMapUserController::getInstance()->getAuctionHouseSale($uid,$type,$page);

        //我的剩余钻石
        $diamond=(int)Redis::connection('UserInfo')->hget($uid,'Diamond');

        //不含有数据
        if (empty($res)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res,'diamond'=>$diamond]);

        foreach ($res as &$one)
        {
            if (!isset($one['expireTime']))
            {
                $sort=1;
                continue;
            }

            $one['expireDate']=formatDate($one['expireTime'],'date');
        }
        unset($one);

        if (!isset($sort)) $res=arraySort1($res,['asc','expireTime']);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res,'diamond'=>$diamond]);
    }

    //出售碎片
    public function saleUserPatch(Request $request)
    {
        $uid=(int)$request->uid;
        $patchName=trim($request->patchName);
        $type=(int)$request->type;
        $money=(int)$request->money;
        $diamond=(int)$request->diamond;
        $num=(int)$request->num < 1 ? 1 : (int)$request->num;
        $expire=(int)$request->expire;

        //取得碎片信息
        $patchInfo=Patch::where('subject',$patchName)->first();

        //查看用户是否还有剩余该碎片
        $check=UserPatch::where(['uid'=>$uid,'pid'=>$patchInfo->id])->where('num','>=',$num)->first();

        if (empty($check)) return response()->json(['resCode'=>Config::get('resCode.701')]);

        //加锁
        if (redisLock("saleUserPatch_{$uid}",3)===null) return response()->json(['resCode'=>Config::get('resCode.600')]);

        if ($type==1)
        {
            FoodMapUserController::getInstance()->saleToAuctionHouse($request,$patchInfo,$check);
        }else
        {
            FoodMapUserController::getInstance()->saleToSystem($request,$patchInfo,$check);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //购买或者下架
    public function buyPatchOrCancel(Request $request)
    {
        $buyUid=$request->uid;

        $type=$request->type;

        $ahId=$request->ah;

        $ahInfo=AuctionHouse::find($ahId);

        //加锁
        if (redisLock("buyPatchOrCancel_$buyUid",3)===null) return response()->json(['resCode'=>Config::get('resCode.600')]);

        if ($type==2)
        {
            //下架
            FoodMapUserController::getInstance()->cancelPatch($ahInfo);

            return response()->json(['resCode'=>Config::get('resCode.200')]);
        }

        //不能买自己的碎片
        if ($buyUid==$ahInfo->uid) return response()->json(['resCode'=>Config::get('resCode.702')]);

        //判断钻石够不够，我的剩余钻石
        $diamond=(int)Redis::connection('UserInfo')->hget($buyUid,'Diamond');

        if ($diamond < $ahInfo->diamond) return response()->json(['resCode'=>Config::get('resCode.700')]);

        //购买碎片
        $res=FoodMapUserController::getInstance()->buyPatch($buyUid,$ahInfo);

        //扣钻石
        Redis::connection('UserInfo')->hincrby($buyUid,'Diamond',-$ahInfo->diamond);

        //删除ah记录
        $ahInfo->delete();

        return response()->json(['resCode'=>Config::get('resCode.200'),'new'=>$res]);
    }










    public function randomPatch()
    {
        $sql="select * from patch order by rand() limit 1";

        $res=DB::connection($this->db)->select($sql);

        $res=current($res);

        $sql="select * from userPatch where uid=22357 and pid={$res->id}";

        $ddd=DB::connection($this->db)->select($sql);

        if (empty($ddd))
        {
            DB::connection($this->db)->table('userPatch')->insert(['uid'=>22357,'pid'=>$res->id,'num'=>1,'belongType'=>$res->belongType]);

        }else
        {
            DB::connection($this->db)->table('userPatch')->where(['uid'=>22357,'pid'=>$res->id])->increment('num');
        }

        return $res;
    }
}
