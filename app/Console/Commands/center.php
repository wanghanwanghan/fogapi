<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Geohash\GeoHash;

class center extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wanghan:center';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从m0开始画中心点';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //m0 ['lat'=>'39.9104','lng'=>'116.397392']
        //     纬度0.025        经度0.035

        if (!Schema::connection('aliyun')->hasTable('grid'))
        {
            Schema::connection('aliyun')->create('grid', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->string('lat','15')->comment('纬度');
                $table->string('lng','15')->comment('经度');
                $table->string('geohash','15');
                $table->string('name','150')->comment('老康命名');
                $table->integer('price')->unsigned()->default(10)->comment('当前价格');
                $table->integer('hightPrice')->unsigned()->default(10)->comment('历史最高价格');
                $table->integer('belong')->unsigned()->default(0)->comment('当前所属');
                $table->integer('totle')->unsigned()->default(0)->comment('交易总数');//当天交易次数放到redis
                $table->char('showGrid','1')->default('1')->comment('格子是否开放');
                $table->timestamps();
                $table->index('geohash');
                $table->index('name');

            });
        }

        $geo=new GeoHash();

        //==========================================================================================================
        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //往西北 lat增加 lng减少 w西增加 n北增加
        $lat='39.9104';
        $lat+=0.025;

        //坐标轴和m0先不画
        for ($i=1;$i<=1300;$i++)
        {
            $lng='116.397392';
            $lng-=0.035;

            for ($j=1;$j<=1300;$j++)
            {
                $hash=$geo->encode($lat,$lng,12);

                DB::connection('aliyun')->table('grid')->insert([
                    'lat'=>number_format($lat,6),
                    'lng'=>number_format($lng,6),
                    'geohash'=>$hash,
                    'name'=>"w{$j}n{$i}",
                    'price'=>10,
                    'hightPrice'=>10,
                    'belong'=>0,
                    'totle'=>0,
                    'showGrid'=>'1'
                ]);

                $lng-=0.035;
            }

            $lat+=0.025;
        }
        //==========================================================================================================
        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //往西南 lat减少 lng减少 w西增加 s南增加
        $lat='39.9104';
        $lat-=0.025;

        //坐标轴和m0先不画
        for ($i=1;$i<=1300;$i++)
        {
            $lng='116.397392';
            $lng-=0.035;

            for ($j=1;$j<=1300;$j++)
            {
                $hash=$geo->encode($lat,$lng,12);

                DB::connection('aliyun')->table('grid')->insert([
                    'lat'=>number_format($lat,6),
                    'lng'=>number_format($lng,6),
                    'geohash'=>$hash,
                    'name'=>"w{$j}s{$i}",
                    'price'=>10,
                    'hightPrice'=>10,
                    'belong'=>0,
                    'totle'=>0,
                    'showGrid'=>'1'
                ]);

                $lng-=0.035;
            }

            $lat-=0.025;
        }
        //==========================================================================================================
        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //往东北 lat增加 lng增加 e东增加 n北增加
        $lat='39.9104';
        $lat+=0.025;

        //坐标轴和m0先不画
        for ($i=1;$i<=1300;$i++)
        {
            $lng='116.397392';
            $lng+=0.035;

            for ($j=1;$j<=1300;$j++)
            {
                $hash=$geo->encode($lat,$lng,12);

                DB::connection('aliyun')->table('grid')->insert([
                    'lat'=>number_format($lat,6),
                    'lng'=>number_format($lng,6),
                    'geohash'=>$hash,
                    'name'=>"e{$j}n{$i}",
                    'price'=>10,
                    'hightPrice'=>10,
                    'belong'=>0,
                    'totle'=>0,
                    'showGrid'=>'1'
                ]);

                $lng+=0.035;
            }

            $lat+=0.025;
        }
        //==========================================================================================================
        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //往东南 lat减少 lng增加 e东增加 s南增加
        $lat='39.9104';
        $lat-=0.025;

        //坐标轴和m0先不画
        for ($i=1;$i<=1300;$i++)
        {
            $lng='116.397392';
            $lng+=0.035;

            for ($j=1;$j<=1300;$j++)
            {
                $hash=$geo->encode($lat,$lng,12);

                DB::connection('aliyun')->table('grid')->insert([
                    'lat'=>number_format($lat,6),
                    'lng'=>number_format($lng,6),
                    'geohash'=>$hash,
                    'name'=>"e{$j}s{$i}",
                    'price'=>10,
                    'hightPrice'=>10,
                    'belong'=>0,
                    'totle'=>0,
                    'showGrid'=>'1'
                ]);

                $lng+=0.035;
            }

            $lat-=0.025;
        }
        //==========================================================================================================






    }
}
