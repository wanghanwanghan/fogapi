<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class paihangbang extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wanghan:paihangbang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $connection='tssj_old';

        //算出每个用户有多少个点 做排行榜
        if (!Schema::connection($connection)->hasTable('paihangbang'))
        {
            Schema::connection($connection)->create('paihangbang', function (Blueprint $table)
            {
                $table->integer('userid')->unsigned();
                $table->integer('total')->unsigned();
                $table->unique(['userid','total']);
                $table->engine='InnoDB';
            });
        }

        $table=[
            'tssj_fog','tssj_fog_2015','tssj_fog_2016',
            'tssj_fog_2017','tssj_fog_201801','tssj_fog_201802',
            'tssj_fog_201803','tssj_fog_201804','tssj_fog_201805',
            'tssj_fog_201806','tssj_fog_201807','tssj_fog_201808',
            'tssj_fog_201809','tssj_fog_201810','tssj_fog_201811',
            'tssj_fog_201812','tssj_fog_201901','tssj_fog_201902',
        ];

        foreach ($table as $one)
        {
            $res=DB::connection($connection)->select("select userid,count(1) as total from {$one} group by userid");

            foreach ($res as $two)
            {
                $data=DB::connection($connection)->table($one)->where('userid',$two->userid)->first();

                if ($data!=null)
                {
                    DB::connection($connection)->table($one)->update(['userid'=>$two,'total'=>$two->total+$data->total]);
                }else
                {
                    DB::connection($connection)->table($one)->insert(['userid'=>$two,'total'=>$two->total]);
                }
            }

            Redis::set($one,time());
        }
    }
}
