<?php

namespace App\Http\Controllers\QuanMinZhanLing\FoodMap;

use App\Http\Controllers\QuanMinZhanLing\UserController;
use App\Http\Controllers\Server\ModifyPinyin;
use App\Model\FoodMap\AuctionHouse;
use App\Model\FoodMap\Patch;
use App\Model\FoodMap\UserGetPatchByWay;
use App\Model\FoodMap\UserPatch;
use App\Model\GridModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Overtrue\Pinyin\Pinyin;
use function GuzzleHttp\Psr7\str;

class FoodMapController extends FoodMapBaseController
{
    //通过经纬度，判断用户得到哪里的碎片
    public function getOnePatch(Request $request)
    {
        $uid=$request->uid;
        $way=(int)trim($request->type);
        $lng=$request->lng;
        $lat=$request->lat;

        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $res=FoodMapPatchController::getInstance()->getOnePatchBelong($way,$lng,$lat);

        //得到坐标转后的地理位置，比如 北京上海
        if ($res===null) return response()->json(['resCode'=>Config::get('resCode.200'),'patch'=>new \stdClass(),'new'=>[]]);

        $patchBelong=$res;

        // '进app'=>1,//只送三次
        // '签到'=>2,//每天一次
        // '每日任务'=>3,//？？？
        // '领钱袋'=>4,//每天一次
        // '进入寻宝首页'=>5,//每天一次
        // '买格子'=>6,//概率给
        // '许愿池'=>7,//只是记录一下
        // '交易所'=>8,//只是记录一下

        //判断以上状态，用户本次能不能得到碎片，能得到哪个碎片
        $patchName=null;
        $date=Carbon::now()->format('Ymd');
        switch ($way)
        {
            case 1:

                //进入app，只送三次
                $res=UserGetPatchByWay::where(['uid'=>$uid,'way'=>$way])->first();

                //已经有三个了
                if ($res!=null && $res->num >= 3) break;

                //今天已经给过了
                if ($res!=null && $res->date == $date) break;

                //选择一个碎片给用户
                $patchName=FoodMapUserController::getInstance()->choseOnePatchGiveUser($uid,$patchBelong);

                if ($patchName==null) break;

                if ($res==null)
                {
                    UserGetPatchByWay::create(['uid'=>$uid,'way'=>$way,'date'=>$date,'num'=>1]);
                }else
                {
                    $res->num++;
                    $res->save();
                }

                break;

            case 2:

                //签到，每天一次
                $res=UserGetPatchByWay::where(['uid'=>$uid,'way'=>$way,'date'=>$date])->first();

                //已经有了
                if ($res!=null) break;

                //选择一个碎片给用户
                $patchName=FoodMapUserController::getInstance()->choseOnePatchGiveUser($uid,$patchBelong);

                if ($patchName==null) break;

                UserGetPatchByWay::create(['uid'=>$uid,'way'=>$way,'date'=>$date,'num'=>1]);

                break;

            case 3:

                //每日任务，？？？
                $res=UserGetPatchByWay::where(['uid'=>$uid,'way'=>$way,'date'=>$date])->first();

                //已经有了
                if ($res!=null) break;

                //选择一个碎片给用户
                $patchName=FoodMapUserController::getInstance()->choseOnePatchGiveUser($uid,$patchBelong);

                if ($patchName==null) break;

                UserGetPatchByWay::create(['uid'=>$uid,'way'=>$way,'date'=>$date,'num'=>1]);

                break;

            case 4:

                //领钱袋，每天一次
                $res=UserGetPatchByWay::where(['uid'=>$uid,'way'=>$way,'date'=>$date])->first();

                //已经有了
                if ($res!=null) break;

                //选择一个碎片给用户
                $patchName=FoodMapUserController::getInstance()->choseOnePatchGiveUser($uid,$patchBelong);

                if ($patchName==null) break;

                UserGetPatchByWay::create(['uid'=>$uid,'way'=>$way,'date'=>$date,'num'=>1]);

                break;

            case 5:

                //进入寻宝首页，每天一次
                $res=UserGetPatchByWay::where(['uid'=>$uid,'way'=>$way,'date'=>$date])->first();

                //已经有了
                if ($res!=null) break;

                //选择一个碎片给用户
                $patchName=FoodMapUserController::getInstance()->choseOnePatchGiveUser($uid,$patchBelong);

                if ($patchName==null) break;

                UserGetPatchByWay::create(['uid'=>$uid,'way'=>$way,'date'=>$date,'num'=>1]);

                break;

            case 6:

                //买格子，有几率给
                //选择一个碎片给用户
                $gName=$request->gName;

                //格子价格
                $price=GridModel::where('name',$gName)->first()->price;

                //5001以上100%
                if ($price > 5000) $havePatch=1;
                //1001-5000区间80%
                if ($price > 1000 && $price <= 5000) $havePatch=random_int(1,100) < 80 ? 1 : 0;
                //501-1000区间60%
                if ($price > 500 && $price <= 1000) $havePatch=random_int(1,100) < 60 ? 1 : 0;
                //101-500区间40%
                if ($price > 100 && $price <= 500) $havePatch=random_int(1,100) < 40 ? 1 : 0;
                //100以下20%
                if ($price <= 100) $havePatch=random_int(1,100) < 20 ? 1 : 0;

                if (!$havePatch) break;

                //给碎片
                $patchName=FoodMapUserController::getInstance()->choseOnePatchGiveUser($uid,$patchBelong);

                if ($patchName==null) break;

                $res=UserGetPatchByWay::where(['uid'=>$uid,'way'=>$way,'date'=>$date])->first();

                if ($res==null)
                {
                    UserGetPatchByWay::create(['uid'=>$uid,'way'=>$way,'date'=>$date,'num'=>1]);
                }else
                {
                    $res->num++;
                    $res->save();
                }

                break;

            case 7:
                //这里什么都不做，调用userGetPatchByWay记录
                break;

            case 8:
                //这里记录一下就行，调用userGetPatchByWay
                break;
        }

        //判断是否得到碎片
        if ($patchName==null) return response()->json(['resCode'=>Config::get('resCode.200'),'patch'=>new \stdClass(),'new'=>[]]);

        //合成
        $new=FoodMapUserController::getInstance()->composeTreasure($uid,$patchName);

        $patch=Patch::where('subject',$patchName)->get()->toArray();

        $patch=current($patch);

        $pinyinContent=(new Pinyin())->convert(substr($patch['subject'],0,-1));

        if (empty($pinyinContent))
        {
            $patch['pinyin']=substr($patch['subject'],0,-1);

        }else
        {
            $pinyinContent=ModifyPinyin::getInstance()->modifyArray($pinyinContent);
            $patch['pinyin']=implode('',$pinyinContent);
        }

        empty($new) ? $new=[] : $new=[$new];

        return response()->json(['resCode'=>Config::get('resCode.200'),'patch'=>$patch,'new'=>$new]);
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

        if ($wishPoolForFree===null) $wishPoolForFree=0;

        //钻石个数
        $diamond=(new UserController())->getUserDiamond($uid);


        //获取今日概率提高的是哪个碎片
        if (!is_numeric($num) || $num=='' || empty($num))
        {
            return response()->json([
                'resCode'=>Config::get('resCode.200'),
                'wishPoolForFree'=>(int)$wishPoolForFree,
                'luckNum'=>(new FoodMapBaseController())->setUid($uid)->getLuckNum(),
                'diamondNum'=>(int)Redis::connection('UserInfo')->hget($uid,'Diamond'),
                'epicPatch'=>(new FoodMapBaseController())->choseEpicPatch(),
            ]);
        }


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
            'luckNum'=>(new FoodMapBaseController())->setUid($uid)->getLuckNum(),
            'diamondNum'=>(int)Redis::connection('UserInfo')->hget($uid,'Diamond'),
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
                $wanghan=FoodMapUserController::getInstance()->composeTreasure($uid,$arr[1]);

                if (!empty($wanghan)) $new[]=$wanghan;

                $way=7;
                $ymd=Carbon::now()->format('Ymd');
                $num=1;

                FoodMapUserController::getInstance()->userGetPatchByWay($uid,$way,$ymd,$num);

                continue;
            }
        }

        return $new;
    }

    //获取用户已经收集到宝物个数，这个接口改了，现在是显示哪个类别开放了已经剩余多少天关闭
    public function getUserTreasureNum(Request $request)
    {
        //求剩余多少天
        $lastMonthStart=Carbon::now()->addMonth()->startOfMonth();
        $time=(new Carbon)->diffInDays($lastMonthStart,true);
        $expire="剩余 {$time} 天";

        //得到所有类别
        $allType=(new FoodMapBaseController())->treasureType;
        //当前开放类别
        $type=$this->getTreasureType();

        foreach ($allType as &$one)
        {
            unset($one['openMonth']);

            if (!in_array($one['typeName'],$type)) continue;

            $one['expire']=$expire;
        }
        unset($one);

        return response()->json(['resCode'=>Config::get('resCode.200'),'type'=>$allType]);
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

        $my=[];

        $pinyin=new Pinyin();

        foreach ($res as $key=>$one)
        {
            if (in_array(substr($one->patch->subject,0,-1),$successName)) continue;

            $pinyinContent=$pinyin->convert(substr($one->patch->subject,0,-1));

            if (empty($pinyinContent))
            {
                $tmp=$one->toArray();
                $tmp['patch']['pinyin']=substr($one->patch->subject,0,-1);
                $my[]=$tmp;

            }else
            {
                $pinyinContent=ModifyPinyin::getInstance()->modifyArray($pinyinContent);
                $tmp=$one->toArray();
                $tmp['patch']['pinyin']=implode('',$pinyinContent);
                $my[]=$tmp;
            }
        }

        //把$success里的宝物，多余的碎片添加进来
        foreach ($success as &$one)
        {
            $pinyinContent=$pinyin->convert($one->subject);

            if (empty($pinyinContent))
            {
                $one->pinyin=$one->subject;

            }else
            {
                $pinyinContent=ModifyPinyin::getInstance()->modifyArray($pinyinContent);
                $one->pinyin=implode('',$pinyinContent);
            }

            $pidArr=Patch::where('subject','like',$one->subject.'_')->pluck('id')->toArray();

            $one->patch=UserPatch::with('patch')->where('uid',$uid)->whereIn('pid',$pidArr)->get()->toArray();
        }
        unset($one);

        return response()->json(['resCode'=>Config::get('resCode.200'),'my'=>$my,'all'=>$success]);
    }

    //拍卖行
    public function auctionHouse(Request $request)
    {
        $uid=$request->uid;
        $type=$request->type;
        $seachKeyWord=trim($request->keyword);
        $page=(int)$request->page;
        if ($page < 1) $page=1;

        $res=FoodMapUserController::getInstance()->getAuctionHouseSale($uid,$type,$seachKeyWord,$page);

        //我的剩余钻石
        $diamond=(int)Redis::connection('UserInfo')->hget($uid,'Diamond');

        //不含有数据
        if (empty($res)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res,'diamond'=>$diamond]);

        foreach ($res as &$one)
        {
            //type=3过来的数据，都是obj
            if (is_object($one))
            {
                $sort=1;
                continue;
            }

            if (!isset($one['expireTime']))
            {
                $sort=1;
                continue;
            }

            $one['expireDate']=formatDate($one['expireTime'],'date');
        }
        unset($one);

        if (!isset($sort)) $res=arraySort1($res,['asc','expireTime']);

        if ($type==2)
        {
            //正在出售的方上面，已经卖出的方下面？？？？
            $onSale=$saled=[];

            foreach ($res as $one)
            {
                if ($one['bid'] > 0)
                {
                    //已经卖出去了
                    $one['buyUserInfo']=[
                        'name'=>trim(Redis::connection('UserInfo')->hget($one['bid'],'name')),
                        'avatar'=>trim(Redis::connection('UserInfo')->hget($one['bid'],'avatar')),
                    ];
                    $saled[]=$one;

                }else
                {
                    $one['buyUserInfo']=[
                        'name'=>'',
                        'avatar'=>'',
                    ];
                    $onSale[]=$one;
                }
            }

            $res=array_merge($onSale,$saled);
        }

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
            //每个人只能卖10个？？？
            $count=AuctionHouse::where(['uid'=>$uid,'status'=>1])->count();

            if ($count >= 10) return response()->json(['resCode'=>Config::get('resCode.704')]);

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

        //加锁
        $key="buyPatchOrCancel_{$ahId}";
        if (redisLock($key,10)===null) return response()->json(['resCode'=>Config::get('resCode.600')]);

        try
        {
            $ahInfo=AuctionHouse::find($ahId);

        }catch (ModelNotFoundException $e)
        {
            //解锁
            redisUnlock($key);

            return response()->json(['resCode'=>Config::get('resCode.703')]);
        }

        if ($type==2)
        {
            //下架
            FoodMapUserController::getInstance()->cancelPatch($ahInfo);

            //解锁
            redisUnlock($key);

            return response()->json(['resCode'=>Config::get('resCode.200')]);
        }

        //不能买自己的碎片
        if ($buyUid==$ahInfo->uid) return response()->json(['resCode'=>Config::get('resCode.702')]);

        //判断钻石够不够，我的剩余钻石
        $diamond=(int)Redis::connection('UserInfo')->hget($buyUid,'Diamond');

        if ($diamond < $ahInfo->diamond)
        {
            //解锁
            redisUnlock($key);

            return response()->json(['resCode'=>Config::get('resCode.700')]);
        }

        //购买碎片
        $res=FoodMapUserController::getInstance()->buyPatch($buyUid,$ahInfo);

        empty($res) ? $new=[] : $new=[$res];

        //扣钻石
        Redis::connection('UserInfo')->hincrby($buyUid,'Diamond',-$ahInfo->diamond);

        //给对方加钻石
        Redis::connection('UserInfo')->hincrby($ahInfo->uid,'Diamond',$ahInfo->diamond);

        //修改状态
        $ahInfo->status=2;
        $ahInfo->bid=$buyUid;
        $ahInfo->save();

        //解锁
        redisUnlock($key);

        $way=8;
        $ymd=Carbon::now()->format('Ymd');
        $num=$ahInfo->num;

        FoodMapUserController::getInstance()->userGetPatchByWay($buyUid,$way,$ymd,$num);

        return response()->json(['resCode'=>Config::get('resCode.200'),'new'=>$new]);
    }

    //根据碎片中文名称换取碎片详细信息
    public function getPatchInfoByPatchName(Request $request)
    {
        $patchName=trim($request->patchName);

        $res=FoodMapPatchController::getInstance()->getPatchInfo($patchName);

        if (!$res) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>[]]);

        $res=$res->toArray();

        $pinyin=(new Pinyin())->convert(substr($res['subject'],0,-1));

        if (empty($pinyin))
        {
            $res['pinyin']=substr($res['subject'],0,-1);
        }else
        {
            $pinyin=ModifyPinyin::getInstance()->modifyArray($pinyin);
            $res['pinyin']=implode('',$pinyin);
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }

    //充值页面
    public function getPayPage(Request $request)
    {
        $uid=$request->uid;

        $money=(int)Redis::connection('UserInfo')->hget($uid,'money');

        $diamond=(int)Redis::connection('UserInfo')->hget($uid,'Diamond');

        $unixTime=(int)Redis::connection('UserInfo')->hget($uid,'DiamondUntil');

        if (!$unixTime) $unixTime=time();

        if ($unixTime <= time())
        {
            //没有月卡或者月卡失效
            return response()->json([
                'resCode'=>Config::get('resCode.200'),
                'money'=>$money,
                'diamond'=>$diamond,
                'expireDate'=>''
            ]);

        }else
        {
            $expireDate=formatDate($unixTime,'date');

            return response()->json([
                'resCode'=>Config::get('resCode.200'),
                'money'=>$money,
                'diamond'=>$diamond,
                'expireDate'=>'月卡生效中 '.$expireDate
            ]);
        }
    }

    //每天领取钻石
    public function getDiamondEveryday(Request $request)
    {
        $uid=$request->uid;

        //如果没领，就自动领了

        $unixTime=(int)Redis::connection('UserInfo')->hget($uid,'DiamondUntil');

        if (!$unixTime)
        {
            //不含有时间，压根不是月卡会员
            $status=0;

        }else
        {
            //是会员，先判断到没到期
            if ($unixTime < time())
            {
                //过期了
                $status=0;

            }else
            {
                //看看今天领没领
                $date=Carbon::now()->format('Ymd');

                $key="getDiamondEveryday_{$date}_{$uid}";

                $check=(int)Redis::connection('UserInfo')->get($key);

                if ($check===1)
                {
                    //已经领取了
                    $status=0;
                }else
                {
                    //今天还没领取
                    (new UserController())->exprUserDiamond($uid,80,'+');

                    Redis::connection('UserInfo')->set($key,1);
                    Redis::connection('UserInfo')->expire($key,86400);

                    $status=1;
                }
            }
        }

        return response()->json([
            'resCode'=>Config::get('resCode.200'),
            'data'=>$status
        ]);
    }





}
