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

        if (!Schema::connection('aliyun')->hasTable('test'))
        {
            Schema::connection('aliyun')->create('test', function (Blueprint $table)
            {
                $table->increments('id')->unsigned();
                $table->string('lng','15');
                $table->string('lat','15');
                $table->string('geohash','15');
                $table->string('name','150');
                $table->timestamps();
            });
        }

        $geo=new GeoHash();

        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //往西北 lat增加 lng减少 w增加 n增加
        $lat='39.9104';

        for ($i=1;$i<=1000;$i++)
        {
            $lng='116.397392';

            $lng-=0.025;

            for ($j=1;$j<=22;$j++)
            {
                $hash=$geo->encode($lat,$lng,12);

                DB::connection('aliyun')->table('test')->insert(['lng'=>$lng,'lat'=>$lat,'geohash'=>$hash]);
            }
        }







    }
}
