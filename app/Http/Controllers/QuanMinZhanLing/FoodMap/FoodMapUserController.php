<?php

namespace App\Http\Controllers\QuanMinZhanLing\FoodMap;

use App\Http\Controllers\Server\ModifyPinyin;
use App\Http\Traits\Singleton;
use App\Model\FoodMap\AuctionHouse;
use App\Model\FoodMap\Patch;
use App\Model\FoodMap\UserGetPatchByWay;
use App\Model\FoodMap\UserPatch;
use App\Model\FoodMap\UserSuccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Overtrue\Pinyin\Pinyin;

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
        $patchId=Patch::where('subject','like',"{$substr}_")->pluck('id')->toArray();

        //看看用户是否拥有这些pid的碎片，并且碎片个数大于0
        $count=UserPatch::where('uid',$uid)->whereIn('pid',$patchId)->where('num','>',0)->count();

        //没有足够的碎片用来合成，或者已经合成过了，不再合成新的
        if (UserSuccess::where(['uid'=>$uid,'subject'=>$substr])->first() || $count < 4) return [];

        //合成
        $id=implode(',',$patchId);
        $sql="update userPatch set num=num-1 where uid={$uid} and pid in ({$id})";

        //减碎片
        DB::connection(self::$db)->update($sql);

        //加宝物
        $res=UserSuccess::create(['uid'=>$uid,'subject'=>$substr,'belongType'=>$pid->belongType]);

        //合成奖励
        //白：500地球币
        //绿：1000地球币
        //蓝：500钻石
        //紫：1000钻石
        //橙：3000钻石
        $this->composeReward($uid,$substr);

        $res=Patch::where('subject',$res->subject.'A')->first();

        return [
            'subject'=>mb_substr($res->subject,0,-1),
            'quality'=>$res->quality,
            'belongType'=>$res->belongType,
            'belongCity'=>$res->belongCity,
        ];
    }

    //合成奖励
    private function composeReward($uid,$subject)
    {
        //subject是不带字母的名字

        $quality=Patch::where('subject',$subject.'A')->first()->quality;

        //合成奖励
        //白：500地球币
        //绿：1000地球币
        //蓝：500钻石
        //紫：1000钻石
        //橙：3000钻石

        switch ($quality)
        {
            case '白':
                Redis::connection('UserInfo')->hincrby($uid,'money',500);
                break;

            case '绿':
                Redis::connection('UserInfo')->hincrby($uid,'money',1000);
                break;

            case '蓝':
                Redis::connection('UserInfo')->hincrby($uid,'Diamond',500);
                break;

            case '紫':
                Redis::connection('UserInfo')->hincrby($uid,'Diamond',1000);
                break;

            case '橙':
                Redis::connection('UserInfo')->hincrby($uid,'Diamond',3000);
                break;
        }

        return true;
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

        return UserPatch::with('patch')->where('uid',$uid)->where('num','>',0)->whereIn('belongType',$belongType)->get();
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
    public function getAuctionHouseSale($uid,$type,$seachKeyWord,$page)
    {
        $res=[];
        $limit=20;
        $offset=($page-1)*$limit;

        if ($type==1)
        {
            //出售页面
            $key=trim($seachKeyWord);

            if ($key)
            {
                $res=AuctionHouse::with(['patch'=>function ($query) use ($key) {
                    $query->where('subject','like',"%{$key}%");
                }])->where('uid','<>',$uid)
                    ->where('status',1)
                    ->orderBy('created_at','desc')
                    ->get()->toArray();

                foreach ($res as $key=>$val)
                {
                    if (empty($val['patch'])) unset($res[$key]);
                }

                if (!empty($res)) $res=paginateByMyself(arraySort1($res,['asc','diamond']),$page,$limit);

            }else
            {
                $res=AuctionHouse::with('patch')->where('uid','<>',$uid)
                    ->where('status',1)
                    ->orderBy('created_at','desc')
                    ->limit($limit)
                    ->offset($offset)
                    ->get()->toArray();
            }

            //显示这个碎片我有多少个？？？
            foreach ($res as &$one)
            {
                $howMuch=UserPatch::where(['uid'=>$uid,'pid'=>$one['pid']])->first();

                if ($howMuch)
                {
                    //存在
                    $one['iHave']=(int)$howMuch->num;
                }else
                {
                    //不存在
                    $one['iHave']=0;
                }
            }
            unset($one);

        }elseif ($type==2)
        {
            //我的出售页面
            //1是正在出售，2是被人买走，3是下架，4是刷回

            //我正在出售的
            $res=AuctionHouse::with('patch')
                ->where(['uid'=>$uid,'status'=>1])
                ->get()->toArray();

            $res=arraySort1($res,['desc','created_at']);

            //我卖出的碎片
            $mySale=AuctionHouse::with('patch')
                ->where(['uid'=>$uid,'status'=>2])
                ->orderBy('updated_at','desc')
                ->limit(1000)
                ->get()->toArray();

            //我购买的碎片
            $myBuy=AuctionHouse::with('patch')
                ->where(['bid'=>$uid,'status'=>2])
                ->orderBy('updated_at','desc')
                ->limit(1000)
                ->get()->toArray();

            //组合数据并排序
            $buySale=array_merge($myBuy,$mySale);
            $buySale=arraySort1($buySale,['desc','updated_at']);

            $tmp=array_merge($res,$buySale);

            if (!empty($tmp)) $res=paginateByMyself($tmp,$page,$limit);

        }elseif ($type==3)
        {
            //我的全部碎片
            //$res=UserPatch::with(['patch'=>function ($query) use ($seachKeyWord){
            //    $query->where('subject','like',"%{$seachKeyWord}%");
            //}])->where('uid',$uid)
            //    ->orderBy('num','desc')
            //    ->limit($limit)
            //    ->offset($offset)
            //    ->get()->toArray();

            $sql=<<<Eof
select uid,pid,num,up.belongType,id,subject,place,quality,belongCity from userPatch as up left join patch as p on up.pid = p.id where up.uid = {$uid} and up.num > 0 and p.subject like '%{$seachKeyWord}%' order by num desc limit {$offset},{$limit}
Eof;

            $res=DB::connection(self::$db)->select($sql);

            if (!empty($res))
            {
                $pinyin=new Pinyin();

                //整理数组
                foreach ($res as &$one)
                {
                    $tmp=$pinyin->convert(mb_substr($one->subject,0,-1));

                    if (empty($tmp))
                    {
                        $one->pinyin=mb_substr($one->subject,0,-1);

                    }else
                    {
                        $tmp=ModifyPinyin::getInstance()->modifyArray($tmp);
                        $tmp=implode('',$tmp);
                        $one->pinyin=$tmp;
                    }
                }
                unset($one);
            }

        }else
        {
            return $res;
        }

        $pinyin=new Pinyin();

        foreach ($res as &$one)
        {
            if (is_object($one)) continue;

            //整理拼音
            $py=$pinyin->convert(mb_substr($one['patch']['subject'],0,-1));

            if (empty($py))
            {
                $one['patch']['pinyin']=mb_substr($one['patch']['subject'],0,-1);
            }else
            {
                $py=ModifyPinyin::getInstance()->modifyArray($py);
                $tmp=implode('',$py);
                $one['patch']['pinyin']=$tmp;
            }

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
            'uid'=>(int)$request->uid,
            'pid'=>(int)$patchInfo->id,
            'expireTime'=>(int)$expireTime,
            'money'=>(int)$request->money,
            'diamond'=>(int)$request->diamond,
            'num'=>(int)$request->num,
            'status'=>1,
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

        switch ($patchInfo->quality)
        {
            case '白':
                $money=50;
                break;
            case '绿':
                $money=100;
                break;
            case '蓝':
                $money=200;
                break;
            case '紫':
                $money=300;
                break;
            case '橙':
                $money=500;
                break;
            default:
                $money=150;
                break;
        }

        Redis::connection('UserInfo')->hincrby($request->uid,'money',$money*$request->num);

        return true;
    }

    //购买碎片
    public function buyPatch($buyUid,$ahInfo)
    {
        $patchInfo=Patch::find($ahInfo->pid);

        $res=$this->composeTreasure($buyUid,$patchInfo->subject);

        return empty($res) ? [] : $res;
    }

    //下架碎片
    public function cancelPatch($ahInfo)
    {
        $sql="update userPatch set num = num + {$ahInfo->num} where uid={$ahInfo->uid} and pid={$ahInfo->pid}";

        //把碎片返回给用户
        DB::connection(self::$db)->update($sql);

        //修改拍卖行物品状态
        $ahInfo->status=3;
        $ahInfo->save();

        return true;
    }

    //每天获取的碎片记录到mysql表中
    public function userGetPatchByWay($uid,$way,$ymd,$num=1)
    {
        $res=UserGetPatchByWay::where(['uid'=>$uid,'way'=>$way,'date'=>$ymd])->first();

        if ($res===null)
        {
            try
            {
                UserGetPatchByWay::create(['uid'=>$uid,'way'=>$way,'date'=>$ymd,'num'=>$num]);

            }catch (\Exception $e)
            {
                return true;
            }

        }else
        {
            $sql="update userGetPatchByWay set num=num+{$num} where uid={$uid} and way={$way} and `date`={$ymd}";

            try
            {
                DB::connection(self::$db)->update($sql);

            }catch (\Exception $e)
            {
                return true;
            }
        }

        return true;
    }

    //用户得到哪个碎片
    public function choseOnePatchGiveUser($uid,$patchBelong,$thisTimeQuality=[])
    {
        $patchName=null;
        $treasureType=$this->getTreasureType();

        //用户的前4个碎片，必定得到一个宝物
        $res=UserPatch::with('patch')->where('uid',$uid)->limit(5)->get()->toArray();

        if (empty($res))
        {
            //第一次获得碎片
            //通过传进来的$patchBelong，随机一个碎片给
            $patchArr=Patch::where('belongCity','like',$patchBelong.'%')
                ->whereNotIn('quality',['蓝','紫','橙'])
                ->whereIn('belongType',$treasureType)
                ->get()->toArray();

            $patchArr=array_random($patchArr);

            //UserPatch::create([
            //    'uid'=>$uid,
            //    'pid'=>$patchArr['id'],
            //    'num'=>1,
            //    'belongType'=>$patchArr['belongType'],
            //]);

            $patchName=$patchArr['subject'];

        }elseif (count($res) < 4)
        {
            //第一个宝物进行中
            //拿到已有宝物碎片的pid，和宝物名称
            $pid=[];
            $subject=null;
            foreach ($res as $one)
            {
                if (empty($pid)) $subject=mb_substr($one['patch']['subject'],0,-1);

                $pid[]=$one['pid'];
            }

            //然后取出宝物的所有碎片，id不在pid中的
            $patch=Patch::where('subject','like',$subject.'%')->whereNotIn('id',$pid)->first();

            //UserPatch::create([
            //    'uid'=>$uid,
            //    'pid'=>$patch->id,
            //    'num'=>1,
            //    'belongType'=>$patch->belongType,
            //]);

            $patchName=$patch->subject;

        }else
        {
            //随意了
            for ($i=1;$i<=95;$i++)
            {
                $tmp[]='绿';
            }

            for ($i=1;$i<=5;$i++)
            {
                $tmp[]='蓝';
            }

            shuffle($tmp);

            if (empty($thisTimeQuality))
            {
                $quality=[array_random($tmp)];
            }else
            {
                $quality=$thisTimeQuality;
            }

            if (time() % 10 === 0)
            {
                //本地碎片
                $patchArr=Patch::whereIn('quality',$quality)
                    ->where('belongCity','like',$patchBelong.'%')
                    ->whereIn('belongType',$treasureType)
                    ->get()->toArray();
            }else
            {
                //全部碎片
                $patchArr=Patch::whereIn('quality',$quality)
                    ->whereIn('belongType',$treasureType)
                    ->get()->toArray();
            }

            if (!empty($patchArr))
            {
                $patchArr=array_random($patchArr);

                //$tmp=UserPatch::where(['uid'=>$uid,'pid'=>$patchArr['id']])->first();

                //if ($tmp==null)
                //{
                    //UserPatch::create([
                    //    'uid'=>$uid,
                    //    'pid'=>$patchArr['id'],
                    //    'num'=>1,
                    //    'belongType'=>$patchArr['belongType'],
                    //]);

                //}else
                //{
                    //$tmp->num++;
                    //$tmp->save();
                //}

                $patchName=$patchArr['subject'];
            }
        }

        return $patchName;
    }



}
