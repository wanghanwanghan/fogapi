<?php

namespace App\Http\Controllers\QuanMinZhanLing\FoodMap;

use App\Http\Traits\Singleton;
use App\Model\FoodMap\AuctionHouse;
use App\Model\FoodMap\Patch;
use App\Model\FoodMap\UserPatch;
use App\Model\FoodMap\UserSuccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class FoodMapUserController
{
    use Singleton;

    private static $db='FoodMap';

    //开放哪些类别
    private function getTreasureType()
    {
        return (new FoodMapController())->getTreasureType();
    }

    //合成宝物
    public function composeTreasure($uid,$patchSubject)
    {
        //先把这个碎片加到用户碎片表
        $pid=Patch::where('subject',$patchSubject)->first();

        //加入用户碎片表
        $res=UserPatch::where(['uid'=>$uid,'pid'=>$pid->id])->first();

        if (empty($res))
        {
            //第一次获得这个碎片
            UserPatch::create(['uid'=>$uid,'pid'=>$pid->id,'num'=>1,'belongType'=>$pid->belongType]);

        }else
        {
            //获得很多次了
            $res->num++;
            $res->save();
        }

        //碎片添加完了，下面开始合成
        //菜名带字母
        $substr=substr($patchSubject,0,-1);

        //需要id
        $patchId=Patch::where('subject','like',"{$substr}%")->pluck('id')->toArray();

        //看看用户是否拥有这些pid的碎片，并且碎片个数大于0
        $count=UserPatch::where('uid',$uid)->whereIn('pid',$patchId)->where('num','>',0)->count();

        //没有足够的碎片用来合成，或者已经合成过了，不再合成新的
        if (UserSuccess::where(['uid'=>$uid,'subject'=>$substr])->first() || $count < 4) return '';

        //合成
        $id=implode(',',$patchId);
        $sql="update userPatch set num=num-1 where uid={$uid} and pid in ({$id})";

        //减碎片
        DB::connection(self::$db)->update($sql);

        //加宝物
        $res=UserSuccess::create(['uid'=>$uid,'subject'=>$substr,'belongType'=>$pid->belongType]);

        //加地球币
        Redis::connection('UserInfo')->hincrby($uid,'money',1000);

        return $res->subject;
    }

    //获取用户所有碎片
    public function getUserAllPatch($uid,$belongType='')
    {
        if ($belongType=='')
        {
            $belongType=$this->getTreasureType();
        }else
        {
            $belongType=[$belongType];
        }

        return UserPatch::with('patch')->where('uid',$uid)->whereIn('belongType',$belongType)->get();
    }

    //获取用户所有宝物
    public function getUserAllTreasure($uid,$belongType='')
    {
        if ($belongType=='')
        {
            $belongType=$this->getTreasureType();
        }else
        {
            $belongType=[$belongType];
        }

        return UserSuccess::where('uid',$uid)->whereIn('belongType',$belongType)->get();
    }

    //获取用户所有宝物个数
    public function getUserAllTreasureNum($uid,$belongType='')
    {
        if ($belongType=='')
        {
            $belongType=$this->getTreasureType();
        }else
        {
            $belongType=[$belongType];
        }

        return UserSuccess::where('uid',$uid)
            ->whereIn('belongType',$belongType)
            ->groupBy('belongType')
            ->select(DB::connection(self::$db)->raw('belongType,count(1) as num'))
            ->get();
    }

    //拍卖行出售页面
    public function getAuctionHouseSale($uid,$type,$page)
    {
        $res=[];
        $limit=10;
        $offset=($page-1)*$limit;

        if ($type==1)
        {
            //出售页面
            $res=AuctionHouse::with('patch')->where('uid','<>',$uid)
                ->orderBy('created_at','desc')
                ->limit($limit)
                ->offset($offset)
                ->get()->toArray();

        }elseif ($type==2)
        {
            //我的出售页面
            $res=AuctionHouse::with('patch')->where('uid',$uid)
                ->orderBy('created_at','desc')
                ->limit($limit)
                ->offset($offset)
                ->get()->toArray();

        }elseif ($type==3)
        {
            //我的全部碎片
            $res=UserPatch::with('patch')->where('uid',$uid)
                ->orderBy('num','desc')
                ->limit($limit)
                ->offset($offset)
                ->get()->toArray();

        }else
        {
            return $res;
        }

        foreach ($res as &$one)
        {
            $one['userInfo']['name']=Redis::connection('UserInfo')->hget($one['uid'],'name');
            $one['userInfo']['avatar']=Redis::connection('UserInfo')->hget($one['uid'],'avatar');
        }
        unset($one);

        return $res;
    }

    //卖给拍卖行
    public function saleToAuctionHouse($request,$patchInfo,$userPatchInfo)
    {
        $expireTime=Carbon::now()->addDays($request->expire)->timestamp;

        //拍卖行中创建一条记录
        $arr=[
            'uid'=>$request->uid,
            'pid'=>$patchInfo->id,
            'expireTime'=>$expireTime,
            'money'=>$request->money,
            'diamond'=>$request->diamond,
            'num'=>$request->num,
        ];

        AuctionHouse::create($arr);

        //减用户碎片表数量
        $userPatchInfo->num -= $request->num;
        $userPatchInfo->save();

        return true;
    }

    //卖给系统
    public function saleToSystem($request,$patchInfo,$userPatchInfo)
    {
        $userPatchInfo->num -= $request->num;

        $userPatchInfo->save();

        Redis::connection('UserInfo')->hincrby($request->uid,'money',150*$request->num);

        return true;
    }

    //购买碎片
    public function buyPatch($buyUid,$ahInfo)
    {
        $patchInfo=Patch::find($ahInfo->pid)->first();

        $res=$this->composeTreasure($buyUid,$patchInfo->subject);

        return $res=='' ? [] : [$res];
    }

    //下架碎片
    public function cancelPatch($ahInfo)
    {
        $sql="update userPatch set num = num + {$ahInfo->num} where uid={$ahInfo->uid} and pid={$ahInfo->pid}";

        //把碎片返回给用户
        DB::connection(self::$db)->update($sql);

        //删除拍卖行数据
        $ahInfo->delete();

        return true;
    }






}
