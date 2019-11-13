<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use Illuminate\Support\Facades\Redis;

class WriteLogController extends BaseController
{
    //买格子产生的log
    public function writeGridTradeLog($arr)
    {
        //$arr['gid']=$gridInfo->id;
        //$arr['gName']=$gridInfo->name;
        //$arr['uid']=$uid;
        //$arr['uName']='';
        //$arr['belong']=$gridInfo->belong;
        //$arr['belongName']='';
        //$arr['payMoney']=$payMoney;
        //$arr['payCount']=$gridInfo->totle + 1;
        //$arr['payTime']=time();

        $arr=jsonEncode($arr);

        Redis::connection('WriteLog')->lpush('tradeInfo',$arr);

        return true;
    }




}