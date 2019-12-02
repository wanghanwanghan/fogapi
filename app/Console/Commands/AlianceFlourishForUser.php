<?php

namespace App\Console\Commands;

use App\Model\Aliance\AlianceGroupModel;
use App\Model\GridModel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AlianceFlourishForUser extends Command
{
    protected $signature = 'Aliance:FlourishForUser';

    protected $description = '计算联盟繁荣度 用户的';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //每个格子基础是1，价格每增加30，再加1

        $day=Carbon::now()->subDay()->format('Ymd');

        //一个一个联盟计算
        for ($i=1;$i<=4;$i++)
        {
            $tmp=AlianceGroupModel::where('alianceNum',$i)->get(['uid']);

            $userArr=[];
            foreach ($tmp as $one)
            {
                $userArr[]=$one->uid;
            }

            try
            {
                if (empty($userArr)) throw new \Exception('123');

                foreach ($userArr as $one)
                {
                    $res=GridModel::where('belong',$one)
                        ->select(DB::connection('masterDB')->raw('sum(case when price / 30 > 0 then left(price / 30,1) + 1 else 1 end) as flourish'))
                        ->get()->toArray();

                    //繁荣度
                    $flourish=$res[0]['flourish'];
                    //格子总数
                    $gridTotal=GridModel::where('belong',$one)->count();
                    //格子总价
                    $gridPriceTotal=GridModel::where('belong',$one)
                        ->select(DB::connection('masterDB')->raw('sum(price) as gridPriceTotal'))
                        ->get()->toArray();
                    $gridPriceTotal=$gridPriceTotal[0]['gridPriceTotal'];
                    //格子均价
                    $gridPriceAverageTotal=GridModel::where('belong',$one)
                        ->select(DB::connection('masterDB')->raw('avg(price) as gridPriceAverageTotal'))
                        ->get()->toArray();
                    $gridPriceAverageTotal=(int)$gridPriceAverageTotal[0]['gridPriceAverageTotal'];

                    $sql="insert into flourishForUser values ($day,$one,$i,$gridTotal,$gridPriceTotal,$gridPriceAverageTotal,$flourish)";

                    DB::connection('Aliance')->insert($sql);
                }

            }catch (\Exception $e)
            {
                continue;
            }
        }

        return true;
    }
}
