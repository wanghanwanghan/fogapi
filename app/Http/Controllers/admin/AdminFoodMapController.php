<?php

namespace App\Http\Controllers\admin;

use App\Model\FoodMap\AuctionHouse;
use App\Model\FoodMap\UserPatch;
use App\Model\FoodMap\UserSuccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AdminFoodMapController extends AdminBaseController
{
    //充值数据
    public function foodMapData1()
    {
        //后台可以看到，充值金额、钻石数、交易数、许愿钻石数、宝物、碎片

        $date=Carbon::now()->year;

        for ($i=$date;$i>=2019;$i--)
        {
            //我的路充值
            $sql="select count(distinct uid) as 'buyPeople',sum(price) as 'priceTotal',productSubject as 'subject',plant as 'plant' from wodelu{$i} where status=1 group by productSubject,plant order by productSubject,plant";

            $tmp["wodelu{$i}"]=DB::connection('userOrder')->select($sql);
        }

        $wodelu=$tmp;
        $tmp=[];

        for ($i=$date;$i>2019;$i--)
        {
            //我的路充值
            $sql="select count(distinct uid) as 'buyPeople',sum(price) as 'priceTotal',productSubject as 'subject',plant as 'plant' from tssj{$i} where status=1 group by productSubject,plant order by productSubject,plant";

            $tmp["tssj{$i}"]=DB::connection('userOrder')->select($sql);
        }

        $tssj=$tmp;

        return view('admin.showdata.show_foodmap_data_1')->with(['wodelu'=>$wodelu,'tssj'=>$tssj]);
    }

    //寻宝数据
    public function foodMapData2()
    {
        if (Redis::connection('default')->get('FoodMapUserData'))
        {
            $targetUid=jsonDecode(Redis::connection('default')->get('FoodMapUserData'));
            return view('admin.showdata.show_foodmap_data_2')->with(['res'=>$targetUid]);
        }

        //宝物数
        $succ=UserSuccess::groupBy('uid')->select(DB::connection('FoodMap')->raw('uid,count(1) as num'))->get()->toArray();
        foreach ($succ as $one)
        {
            $userSucc[$one['uid']]=$one['num'];
        }

        //碎片数
        $targetUid=UserPatch::groupBy('uid')->select(DB::connection('FoodMap')->raw('uid,sum(num) as userPatch'))->get()->toArray();

        //交易数
        $buyNum=AuctionHouse::where('status',2)->groupBy('uid')->select(DB::connection('FoodMap')->raw('uid,count(1) as buyNum'))->get()->toArray();
        $saleNum=AuctionHouse::where('bid','>',0)->groupBy('bid')->select(DB::connection('FoodMap')->raw('bid,count(1) as saleNum'))->get()->toArray();

        //今年的充值金额
        $RMB=DB::connection('userOrder')
            ->table("tssj2020")
            ->where('status',1)
            ->groupBy('uid')
            ->select(DB::connection('userOrder')->raw('uid,sum(price) as price'))->get()->toArray();

        $RMB=jsonDecode(jsonEncode($RMB));

        foreach ($RMB as $one)
        {
            $wanghan[$one['uid']]=$one['price'];
        }

        //转换成uid => num
        foreach ($buyNum as $one)
        {
            $buyTmp[$one['uid']]=$one['buyNum'];
        }
        foreach ($saleNum as $one)
        {
            $saleTmp[$one['bid']]=$one['saleNum'];
        }
        //合并两个数组，交易次数
        foreach ($buyTmp as $key=>$val)
        {
            if (!isset($saleTmp[$key])) continue;

            $buyTmp[$key]+=$saleTmp[$key];
        }

        foreach ($targetUid as &$one)
        {
            //用户名头像钱
            $one['name']=trim(Redis::connection('UserInfo')->hget($one['uid'],'name'));
            $one['avatar']=trim(Redis::connection('UserInfo')->hget($one['uid'],'avatar'));
            $one['money']=(int)Redis::connection('UserInfo')->hget($one['uid'],'money');

            //钻石数
            $one['diamond']=(int)Redis::connection('UserInfo')->hget($one['uid'],'Diamond');

            //许愿钻石数
            $one['wishNum']=(int)Redis::connection('UserInfo')->hget($one['uid'],'wishForDiamond');

            //宝物数
            $one['userSucc']=isset($userSucc[$one['uid']]) ? $userSucc[$one['uid']] : 0;

            //交易数
            $one['buySale']=isset($buyTmp[$one['uid']]) ? $buyTmp[$one['uid']] : 0;

            //充值金额
            isset($wanghan[$one['uid']]) ? $one['RMB']=$wanghan[$one['uid']] : $one['RMB']=0;
        }
        unset($one);

        $arr=[
            18426,
            137545,
            33586,
            22357,
            186454,
            180381,
            198023,
            191662,
            97105,
            211033,
            104563,
            30209,
            211540,
            26074,
            26078,
            26079,
        ];

        foreach ($targetUid as $key=>$value)
        {
            if (in_array($value['uid'],$arr)) unset($targetUid[$key]);
        }

        Redis::connection('default')->set('FoodMapUserData',jsonEncode($targetUid));
        Redis::connection('default')->expire('FoodMapUserData',300);

        return view('admin.showdata.show_foodmap_data_2')->with(['res'=>$targetUid]);
    }

    //充值详情
    public function moneyDetail($string)
    {
        //先看哪年的
        $year=null;
        for ($i=strlen($string)-4;$i<=strlen($string);$i++)
        {
            if (strlen($year)>=4) break;

            $year.=$string[$i];
        }

        //再看是探索世界还是我的路
        if (strtolower($string[0])=='t')
        {
            //tssj
            $table="tssj{$year}";
        }else
        {
            //wodelu
            $table="wodelu{$year}";
        }

        try
        {
            $tmp=DB::connection('userOrder')
                ->table($table)
                ->where('status',1)
                ->groupBy('mouth')
                ->select(DB::connection('userOrder')->raw('sum(price) as price,left(created_at,7) as mouth'))
                ->get();

        }catch (\Exception $e)
        {
            $tmp=[];
        }

        $allMoney=0;
        foreach ($tmp as $one)
        {
            $allMoney+=$one->price;
        }

        $res=[
            'target'=>trim($string),
            'money'=>$tmp,
            'allMoney'=>$allMoney
        ];

        return view('admin.showdata.show_money_detail')->with(['res'=>$res]);
    }

    //ajax
    public function foodMapAjax(Request $request)
    {

    }
}