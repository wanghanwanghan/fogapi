<?php

namespace App\Console\Commands;

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
        $Geo=new \Geohash\GeoHash();

        DB::connection('tssj_old')->table('tssj_fog')->orderBy('fogid')->chunk(5000,function ($data) use ($Geo)
        {
            $connection='tssj_new_2019';

            //最后要执行数组
            $sqlGeoArray=[];
            $sqlUsrArray=[];

            foreach ($data as $one)
            {
                if (!is_numeric($one->longitude) || !is_numeric($one->latitude)) continue;

                $lng=\sprintf("%.4f",$one->longitude);
                $lat=\sprintf("%.4f",$one->latitude);

                $geohash=$Geo->encode($lat,$lng,'9');

                $res=amapSelect($lng,$lat);

                if ($res===false)
                {
                    $tmp=DB::connection($connection)->table('Unknown_geohash')->where('geohash',$geohash)->first();

                    if ($tmp==null)
                    {
                        $arr['geohash']=$geohash;
                        $arr['lng']=empty($lng)?'':$lng;
                        $arr['lat']=empty($lat)?'':$lat;

                        DB::connection($connection)->table('Unknown_geohash')->insert($arr);
                    }

                    continue;
                }

                //生成基础坐标点sql语句['tableName'=>'要插入的值']
                $res=insertGeohash($Geo,$lng,$lat,$res);

                //下面要循环拼接的sql
                $sqlGeoArray[$res[0]][]=$res[1];

                //关联当前坐标和用户
                if (!is_numeric($one->userid))
                {
                    continue;
                }else
                {
                    $userid=$one->userid;
                }

                if (!is_numeric($one->dateline))
                {
                    $dateline=time();
                }else
                {
                    $dateline=$one->dateline;
                }

                $res=insertUserGeo($geohash,$userid,$dateline);

                //下面要循环拼接的sql
                $sqlUsrArray[$res[0]][]=$res[1];
            }

            //====================准备插入数据 拼接数组中的value====================

            //最后要执行数组
            $sqlGeoArray_tmp=[];
            $sqlUsrArray_tmp=[];

            //$sqlGeoArray
            //$key是表名 $value是需要插入的数据["'','wx4eqy9fb','','海淀区'"]
            //$sqlUsrArray
            //$key是表名 $value是需要插入的数据["'',18343,'wx4g3bmcz','1453683658'"]

            foreach ($sqlGeoArray as $key=>$value)
            {
                foreach ($value as $one)
                {
                    if (isset($sqlGeoArray_tmp[$key]))
                    {
                        $sqlGeoArray_tmp[$key].='('.$one.'),';
                    }else
                    {
                        $sqlGeoArray_tmp[$key]='('.$one.'),';
                    }
                }
            }

            foreach ($sqlUsrArray as $key=>$value)
            {
                foreach ($value as $one)
                {
                    if (isset($sqlUsrArray_tmp[$key]))
                    {
                        $sqlUsrArray_tmp[$key].='('.$one.'),';
                    }else
                    {
                        $sqlUsrArray_tmp[$key]='('.$one.'),';
                    }
                }
            }

            //插入数据
            foreach ($sqlGeoArray_tmp as $key=>$value)
            {
                DB::connection($connection)->insert("insert ignore into ".$key.' values '.rtrim($value,','));
            }

            foreach ($sqlUsrArray_tmp as $key=>$value)
            {
                DB::connection($connection)->insert("insert ignore into ".$key.' values '.rtrim($value,','));
            }

            //每处理5000条记录一下
            $ExecCout=Redis::get('ExecCout');

            if ($ExecCout)
            {
                $ExecCout++;
            }else
            {
                $ExecCout=1;
            }

            Redis::set('ExecCout',$ExecCout);
        });



























    }
}
