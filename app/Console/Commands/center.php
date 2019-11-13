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
    protected $signature = 'wanghan:center';

    protected $description = '从c0开始画中心点';

    protected $myTableName = 'grid_test';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //c0 ['lat'=>'39.9104','lng'=>'116.397392']
        //纬度0.025   经度0.035

        if (!Schema::connection('masterDB')->hasTable($this->myTableName))
        {
            Schema::connection('masterDB')->create($this->myTableName, function (Blueprint $table) {

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
                $table->primary(['id','belong']);
                $table->index('name');
                $table->index('belong');

                //Alter table grid partition by linear key(belong) partitions 128;
            });
        }

        $geo=new GeoHash();

        //==========================================================================================================
        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //向上550   向下880   向左1250   向右550
        //向上2000  向下5000  向左8400   向右1800

        //往西北 lat增加 lng减少 w西增加 n北增加
        $lat='39.9104';
        $lat+=0.025;

        //坐标轴和c0先不画 1680w
        for ($i=1;$i<=2000;$i++)
        {
            $lng='116.397392';
            $lng-=0.035;

            for ($j=1;$j<=8400;$j++)
            {
                $hash=$geo->encode($lat,$lng,12);

                try
                {
                    $myCheck=DB::connection('masterDB')->table($this->myTableName)->where('name',"n{$i}w{$j}")->first();

                    if ($myCheck)
                    {
                        Redis::connection('default')->incr('inserttotle');

                    }else
                    {
                        DB::connection('masterDB')->table($this->myTableName)->insert([
                            'lat'=>number_format($lat,6),
                            'lng'=>number_format($lng,6),
                            'geohash'=>$hash,
                            'name'=>"n{$i}w{$j}",
                            'price'=>10,
                            'hightPrice'=>10,
                            'belong'=>0,
                            'totle'=>0,
                            'showGrid'=>'1'
                        ]);
                    }

                }catch (\Exception $e)
                {
                    Redis::connection('default')->incr('inserttotle_error');
                }

                $lng-=0.035;
            }

            $lat+=0.025;
        }
        //==========================================================================================================
        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //向上550   向下880   向左1250   向右550
        //向上2000  向下5000  向左8400   向右1800

        //往西南 lat减少 lng减少 w西增加 s南增加
        $lat='39.9104';
        $lat-=0.025;

        //坐标轴和c0先不画 4200w
        for ($i=1;$i<=5000;$i++)
        {
            $lng='116.397392';
            $lng-=0.035;

            for ($j=1;$j<=8400;$j++)
            {
                $hash=$geo->encode($lat,$lng,12);

                try
                {
                    $myCheck=DB::connection('masterDB')->table($this->myTableName)->where('name',"s{$i}w{$j}")->first();

                    if ($myCheck)
                    {
                        Redis::connection('default')->incr('inserttotle');

                    }else
                    {
                        DB::connection('masterDB')->table($this->myTableName)->insert([
                            'lat'=>number_format($lat,6),
                            'lng'=>number_format($lng,6),
                            'geohash'=>$hash,
                            'name'=>"s{$i}w{$j}",
                            'price'=>10,
                            'hightPrice'=>10,
                            'belong'=>0,
                            'totle'=>0,
                            'showGrid'=>'1'
                        ]);
                    }

                }catch (\Exception $e)
                {
                    Redis::connection('default')->incr('inserttotle_error');
                }

                $lng-=0.035;
            }

            $lat-=0.025;
        }
        //==========================================================================================================
        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //向上550   向下880   向左1250   向右550
        //向上2000  向下5000  向左8400   向右1800

        //往东北 lat增加 lng增加 e东增加 n北增加
        $lat='39.9104';
        $lat+=0.025;

        //坐标轴和c0先不画 360w
        for ($i=1;$i<=2000;$i++)
        {
            $lng='116.397392';
            $lng+=0.035;

            for ($j=1;$j<=1800;$j++)
            {
                $hash=$geo->encode($lat,$lng,12);

                try
                {
                    $myCheck=DB::connection('masterDB')->table($this->myTableName)->where('name',"n{$i}e{$j}")->first();

                    if ($myCheck)
                    {
                        Redis::connection('default')->incr('inserttotle');

                    }else
                    {
                        DB::connection('masterDB')->table($this->myTableName)->insert([
                            'lat'=>number_format($lat,6),
                            'lng'=>number_format($lng,6),
                            'geohash'=>$hash,
                            'name'=>"n{$i}e{$j}",
                            'price'=>10,
                            'hightPrice'=>10,
                            'belong'=>0,
                            'totle'=>0,
                            'showGrid'=>'1'
                        ]);
                    }

                }catch (\Exception $e)
                {
                    Redis::connection('default')->incr('inserttotle_error');
                }

                $lng+=0.035;
            }

            $lat+=0.025;
        }
        //==========================================================================================================
        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //向上550   向下880   向左1250   向右550
        //向上2000  向下5000  向左8400   向右1800

        //往东南 lat减少 lng增加 e东增加 s南增加
        $lat='39.9104';
        $lat-=0.025;

        //坐标轴和c0先不画 900w
        for ($i=1;$i<=5000;$i++)
        {
            $lng='116.397392';
            $lng+=0.035;

            for ($j=1;$j<=1800;$j++)
            {
                $hash=$geo->encode($lat,$lng,12);

                try
                {
                    $myCheck=DB::connection('masterDB')->table($this->myTableName)->where('name',"s{$i}e{$j}")->first();

                    if ($myCheck)
                    {
                        Redis::connection('default')->incr('inserttotle');

                    }else
                    {
                        DB::connection('masterDB')->table($this->myTableName)->insert([
                            'lat'=>number_format($lat,6),
                            'lng'=>number_format($lng,6),
                            'geohash'=>$hash,
                            'name'=>"s{$i}e{$j}",
                            'price'=>10,
                            'hightPrice'=>10,
                            'belong'=>0,
                            'totle'=>0,
                            'showGrid'=>'1'
                        ]);
                    }

                }catch (\Exception $e)
                {
                    Redis::connection('default')->incr('inserttotle_error');
                }

                $lng+=0.035;
            }

            $lat-=0.025;
        }
        //==========================================================================================================
        $n=1;//北
        $s=1;//南
        $w=1;//西
        $e=1;//东

        //向上550   向下880   向左1250   向右550
        //向上2000  向下5000  向左8400   向右1800

        //画4个坐标轴和c0
        $lat='39.9104';
        $lng='116.397392';

        //西
        for ($i=1;$i<=8400;$i++)
        {
            $lng-=0.035;

            $hash=$geo->encode($lat,$lng,12);

            try
            {
                $myCheck=DB::connection('masterDB')->table($this->myTableName)->where('name',"w{$i}")->first();

                if ($myCheck)
                {
                    Redis::connection('default')->incr('inserttotle');

                }else
                {
                    DB::connection('masterDB')->table($this->myTableName)->insert([
                        'lat'=>number_format($lat,6),
                        'lng'=>number_format($lng,6),
                        'geohash'=>$hash,
                        'name'=>"w{$i}",
                        'price'=>10,
                        'hightPrice'=>10,
                        'belong'=>0,
                        'totle'=>0,
                        'showGrid'=>'1'
                    ]);
                }

            }catch (\Exception $e)
            {
                Redis::connection('default')->incr('inserttotle_error');
            }
        }

        $lat='39.9104';
        $lng='116.397392';

        //南
        for ($i=1;$i<=5000;$i++)
        {
            $lat-=0.025;

            $hash=$geo->encode($lat,$lng,12);

            try
            {
                $myCheck=DB::connection('masterDB')->table($this->myTableName)->where('name',"s{$i}")->first();

                if ($myCheck)
                {
                    Redis::connection('default')->incr('inserttotle');

                }else
                {
                    DB::connection('masterDB')->table($this->myTableName)->insert([
                        'lat'=>number_format($lat,6),
                        'lng'=>number_format($lng,6),
                        'geohash'=>$hash,
                        'name'=>"s{$i}",
                        'price'=>10,
                        'hightPrice'=>10,
                        'belong'=>0,
                        'totle'=>0,
                        'showGrid'=>'1'
                    ]);
                }

            }catch (\Exception $e)
            {
                Redis::connection('default')->incr('inserttotle_error');
            }
        }

        $lat='39.9104';
        $lng='116.397392';

        //东
        for ($i=1;$i<=1800;$i++)
        {
            $lng+=0.035;

            $hash=$geo->encode($lat,$lng,12);

            try
            {
                $myCheck=DB::connection('masterDB')->table($this->myTableName)->where('name',"e{$i}")->first();

                if ($myCheck)
                {
                    Redis::connection('default')->incr('inserttotle');

                }else
                {
                    DB::connection('masterDB')->table($this->myTableName)->insert([
                        'lat'=>number_format($lat,6),
                        'lng'=>number_format($lng,6),
                        'geohash'=>$hash,
                        'name'=>"e{$i}",
                        'price'=>10,
                        'hightPrice'=>10,
                        'belong'=>0,
                        'totle'=>0,
                        'showGrid'=>'1'
                    ]);
                }

            }catch (\Exception $e)
            {
                Redis::connection('default')->incr('inserttotle_error');
            }
        }

        $lat='39.9104';
        $lng='116.397392';

        //北
        for ($i=1;$i<=2000;$i++)
        {
            $lat+=0.025;

            $hash=$geo->encode($lat,$lng,12);

            try
            {
                $myCheck=DB::connection('masterDB')->table($this->myTableName)->where('name',"n{$i}")->first();

                if ($myCheck)
                {
                    Redis::connection('default')->incr('inserttotle');

                }else
                {
                    DB::connection('masterDB')->table($this->myTableName)->insert([
                        'lat'=>number_format($lat,6),
                        'lng'=>number_format($lng,6),
                        'geohash'=>$hash,
                        'name'=>"n{$i}",
                        'price'=>10,
                        'hightPrice'=>10,
                        'belong'=>0,
                        'totle'=>0,
                        'showGrid'=>'1'
                    ]);
                }

            }catch (\Exception $e)
            {
                Redis::connection('default')->incr('inserttotle_error');
            }
        }


        $lat='39.9104';//-50.0896
        $lng='116.397392';//-63.602608

        $hash=$geo->encode($lat,$lng,12);

        //c0
        try
        {
            $myCheck=DB::connection('masterDB')->table($this->myTableName)->where('name',"c0")->first();

            if ($myCheck)
            {
                Redis::connection('default')->incr('inserttotle');

            }else
            {
                DB::connection('masterDB')->table($this->myTableName)->insert([
                    'lat'=>number_format($lat,6),
                    'lng'=>number_format($lng,6),
                    'geohash'=>$hash,
                    'name'=>"c0",
                    'price'=>10,
                    'hightPrice'=>10,
                    'belong'=>0,
                    'totle'=>0,
                    'showGrid'=>'1'
                ]);
            }

        }catch (\Exception $e)
        {
            Redis::connection('default')->incr('inserttotle_error');
        }


    }
}
