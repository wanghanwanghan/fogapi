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

        $target=\App\Model\FoodMap\AuctionHouse::where('expireTime','<=',$time)->get();

        foreach ($target as $one)
        {
            //删除拍卖行数据
            \App\Model\FoodMap\AuctionHouse::find($one->id)->delete();

            $sql="update userPatch set num = num + {$one->num} where uid={$one->uid} and pid={$one->pid}";

            //把碎片返回给用户
            DB::connection('FoodMap')->update($sql);
        }

        return true;
    }
}
