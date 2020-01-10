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

        for ($i=$date;$i>=2019;$i--)
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
        //宝物数
        $succ=UserSuccess::groupBy('uid')->select(DB::connection('FoodMap')->raw('uid,count(1) as num'))->get()->toArray();

        //碎片数
        $targetUid=UserPatch::groupBy('uid')->select(DB::connection('FoodMap')->raw('uid,sum(num) as userPatch'))->get()->toArray();

        //交易数
        $buyNum=AuctionHouse::where('status',2)->groupBy('uid')->select(DB::connection('FoodMap')->raw('uid,count(1) as buyNum'))->get()->toArray();
        $saleNum=AuctionHouse::where('bid','>',0)->groupBy('bid')->select(DB::connection('FoodMap')->raw('bid,count(1) as saleNum'))->get()->toArray();

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
            foreach ($succ as $userSucc)
            {
                if ($one['uid']==$userSucc['uid'])
                {
                    $one['userSucc']=$userSucc['num'];
                }
            }

            //交易数
            $one['buySale']=isset($buyTmp[$one['uid']]) ? $buyTmp[$one['uid']] : 0;
        }
        unset($one);

        return view('admin.showdata.show_foodmap_data_2')->with(['res'=>$targetUid]);
    }

    //ajax
    public function foodMapAjax(Request $request)
    {

    }
}