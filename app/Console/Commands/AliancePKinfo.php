<?php

namespace App\Console\Commands;

use App\Http\Controllers\QuanMinZhanLing\Aliance\AlianceBaseController;
use App\Model\Aliance\AlianceGroupModel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AliancePKinfo extends Command
{
    protected $signature = 'Aliance:PKinfo';

    protected $description = '每月第一天计算上月联盟战绩';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //建表
        (new AlianceBaseController())->createTable('aliancePK');

        //执行当天的前一天，也就是上个月
        $star=Carbon::now()->subDay()->startOfMonth()->format('Ymd');
        $stop=Carbon::now()->subDay()->endOfMonth()->format('Ymd');

        for ($i=1;$i<=4;$i++)
        {
            $flourish=DB::connection('Aliance')->table('flourish')
                ->whereBetween('date',[$star,$stop])
                ->where('alianceNum',$i)
                ->select(DB::connection('Aliance')->raw('sum(flourish) as flourish'))->get();

            //该联盟当月的繁荣度
            $flourish=(int)current($flourish)[0]->flourish;

            $tmp[]=['date'=>$star,'alianceNum'=>$i,'flourish'=>$flourish];
        }

        $tmp=arraySort1($tmp,['desc','flourish']);

        $i=1;
        foreach ($tmp as &$one)
        {
            $i > 1 ? $one['winOrLose']=0 : $one['winOrLose']=1;

            if (Carbon::now()->format('Ymd') > 20201212)
            {
                $res=AlianceGroupModel::where('alianceNum',$one['alianceNum'])->get();

                foreach ($res as $two)
                {
                    if ($i===1)
                    {
                        $num=(int)Redis::connection('UserInfo')->hget($two->uid,"AlianceWinOrLose{$one['alianceNum']}");

                        //所在联盟夺冠次数+1
                        Redis::connection('UserInfo')->hset($two->uid,"AlianceWinOrLose{$one['alianceNum']}",$num+1);

                        //记录赢没赢
                        $winOrLose=jsonDecode(Redis::connection('UserInfo')->hget($two->uid,"AlianceWinOrLose"));

                        $mouth=Carbon::now()->subDay()->startOfMonth()->format('Ym');

                        if (!is_array($winOrLose)) $winOrLose=[];

                        $winOrLose[]=['mouth'=>$mouth,'alianceNum'=>$one['alianceNum'],'winOrLose'=>1];

                        Redis::connection('UserInfo')->hset($two->uid,"AlianceWinOrLose",jsonEncode($winOrLose));

                    }else
                    {
                        //记录赢没赢
                        $winOrLose=jsonDecode(Redis::connection('UserInfo')->hget($two->uid,"AlianceWinOrLose"));

                        $mouth=Carbon::now()->subDay()->startOfMonth()->format('Ym');

                        if (!is_array($winOrLose)) $winOrLose=[];

                        $winOrLose[]=['mouth'=>$mouth,'alianceNum'=>$one['alianceNum'],'winOrLose'=>0];

                        Redis::connection('UserInfo')->hset($two->uid,"AlianceWinOrLose",jsonEncode($winOrLose));
                    }
                }
            }

            $i++;
        }
        unset($one);

        DB::connection('Aliance')->table('aliancePK')->insert($tmp);

        return true;
    }
}
