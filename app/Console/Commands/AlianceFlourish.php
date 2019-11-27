<?php

namespace App\Console\Commands;

use App\Model\Aliance\AlianceGroupModel;
use App\Model\GridModel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AlianceFlourish extends Command
{
    protected $signature = 'Aliance:Flourish';

    protected $description = '计算联盟繁荣度';

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

                $res=GridModel::whereIn('belong',$userArr)
                    ->select(DB::connection('masterDB')->raw('sum(case when price / 30 > 0 then left(price / 30,1) + 1 else 1 end) as flourish'))
                    ->get()->toArray();

                //繁荣度
                $flourish=$res[0]['flourish'];
                //联盟成员总数
                $userTotal=count($userArr);
                //格子总数
                $gridTotal=GridModel::whereIn('belong',$userArr)->count();
                //格子总价
                $gridPriceTotal=GridModel::whereIn('belong',$userArr)
                    ->select(DB::connection('masterDB')->raw('sum(price) as gridPriceTotal'))
                    ->get()->toArray();
                $gridPriceTotal=$gridPriceTotal[0]['gridPriceTotal'];
                //格子均价
                $gridPriceAverageTotal=GridModel::whereIn('belong',$userArr)
                    ->select(DB::connection('masterDB')->raw('avg(price) as gridPriceAverageTotal'))
                    ->get()->toArray();
                $gridPriceAverageTotal=(int)$gridPriceAverageTotal[0]['gridPriceAverageTotal'];

            }catch (\Exception $e)
            {
                $sql="insert into flourish values ($day,$i,0,0,0,0,0)";

                DB::connection('Aliance')->insert($sql);

                continue;
            }

            $sql="insert into flourish values ($day,$i,$userTotal,$gridTotal,$gridPriceTotal,$gridPriceAverageTotal,$flourish)";

            DB::connection('Aliance')->insert($sql);
        }

        return true;
    }
}
