<?php

namespace App\Console\Commands;

use App\Http\Controllers\TanSuoShiJie\FogController;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

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
        Redis::connection('UserInfo')->multi();//开启事务
        Redis::connection('UserInfo')->keys('1*');
        Redis::connection('UserInfo')->keys('2*');
        Redis::connection('UserInfo')->keys('3*');
        Redis::connection('UserInfo')->keys('4*');
        Redis::connection('UserInfo')->keys('5*');
        Redis::connection('UserInfo')->keys('6*');
        Redis::connection('UserInfo')->keys('7*');
        Redis::connection('UserInfo')->keys('8*');
        Redis::connection('UserInfo')->keys('9*');
        $all=Redis::connection('UserInfo')->exec();//提交事务

        $all=array_flatten($all);

        $suffixOBJ=new FogController();

        foreach ($all as $one)
        {
            $suffix=$suffixOBJ->getDatabaseNoOrTableNo($one);

            try
            {
                $count=DB::connection("TssjFog{$suffix['db']}")->table("user_fog_{$suffix['table']}")->where('uid',$one)->count();

            }catch (\Exception $e)
            {
                continue;
            }

            //迷雾面积在：
            //1.  100k㎡（12660迷雾点）-200k㎡（25320迷雾点）的人数
            //2.  200k㎡（25320迷雾点）-300k㎡（37980迷雾点）的人数
            //3.  300k㎡（37980迷雾点）-2000k㎡（253170迷雾点）的人数
            //4.  2000k㎡（253170迷雾点）以上的人数

            $count=$count*0.0079;

            if ($count<100) Redis::connection('default')->hincrby('userFog','1',1);

            if ($count>=100 && $count<=200) Redis::connection('default')->hincrby('userFog','2',1);

            if ($count>200 && $count<=300) Redis::connection('default')->hincrby('userFog','3',1);

            if ($count>300 && $count<=2000) Redis::connection('default')->hincrby('userFog','4',1);

            if ($count>2000) Redis::connection('default')->hincrby('userFog','5',1);
        }
    }
}
