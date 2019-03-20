<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wanghan:test';

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
        $Geo=new \Geohash\GeoHash();

        DB::connection('tssj_old')->table('tssj_fog')->orderBy('fogid')->chunk(5000,function ($data) use ($Geo)
        {
            foreach ($data as $one)
            {
                if (!is_numeric($one->longitude) || !is_numeric($one->latitude)) continue;

                $lng=\sprintf("%.4f",$one->longitude);
                $lat=\sprintf("%.4f",$one->latitude);

                $res=amapSelect($lng,$lat);

                //返回true说明插入成功，返回false是未知问题
                $res=insertGeohash($Geo,$lng,$lat,$res);

                if (!$res)
                {
                    $geohash=$Geo->encode($lat,$lng,'9');

                    $tmp=DB::connection('tssj_new_2019')->table('Unknown_geohash')->where('geohash',$geohash)->first();

                    if ($tmp==null)
                    {
                        $arr['geohash']=$geohash;
                        $arr['lng']=empty($lng)?'':$lng;
                        $arr['lat']=empty($lat)?'':$lat;

                        DB::connection('tssj_new_2019')->table('Unknown_geohash')->insert($arr);
                    }
                }
            }
        });























    }
}
