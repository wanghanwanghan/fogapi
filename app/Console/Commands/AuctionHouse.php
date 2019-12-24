<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuctionHouse extends Command
{
    protected $signature = 'FoodMap:AuctionHouse';

    protected $description = '每分钟刷拍卖行物品，过期的返回给用户';

    public function handle()
    {
        $time=Carbon::now()->timestamp;

        //取得正在卖的物品
        $target=\App\Model\FoodMap\AuctionHouse::where('expireTime','<=',$time)->where(['status'=>1])->get();

        foreach ($target as $one)
        {
            //刷回给用户
            $goods=\App\Model\FoodMap\AuctionHouse::find($one->id);
            $goods->status=4;
            $goods->save();

            $sql="update userPatch set num = num + {$one->num} where uid={$one->uid} and pid={$one->pid}";

            //把碎片返回给用户
            DB::connection('FoodMap')->update($sql);
        }

        return true;
    }
}
