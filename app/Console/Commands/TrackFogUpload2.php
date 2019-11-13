<?php

namespace App\Console\Commands;

use App\Http\Controllers\WoDeLu\TrackFogController;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class TrackFogUpload2 extends Command
{
    protected $signature = 'Wodelu:FogUpload2';

    protected $myTarget = 2;

    protected $description = '处理用户上传的迷雾';

    protected $fogControllerObj;

    public function __construct()
    {
        parent::__construct();

        $this->fogControllerObj=new TrackFogController();
    }

    public function createTable($suffix)
    {
        if (!Schema::connection('TrackFog'.$suffix['db'])->hasTable('user_fog_'.$suffix['table']))
        {
            Schema::connection('TrackFog'.$suffix['db'])->create('user_fog_'.$suffix['table'], function (Blueprint $table)
            {
                $table->integer('uid')->unsigned()->comment('用户主键');
                $table->string('lat','15')->comment('纬度');
                $table->string('lng','15')->comment('精度');
                $table->string('geo','10')->comment('geohash');
                $table->integer('unixTime')->unsigned()->comment('unix时间戳')->index();
                $table->timestamps();
                $table->unique(['uid','geo']);//insert ignore要用到
                $table->engine='InnoDB';
            });
        }

        return true;
    }

    public function handle()
    {
        $Geo=new \Geohash\GeoHash();

        if (!$this->fogControllerObj->runWork) return true;

        while(true)
        {
            //$one是5000个坐标点组成的json串
            $one=Redis::connection('TrackFog')->rpop('FogUploadList_'.$this->myTarget);

            //没东西就退出
            if ($one=='') break;

            //解析
            $res=jsonDecode($one);

            //不含有处理内容，跳过该条数据
            if (!isset($res['uid']) || !isset($res['data'])) continue;

            //uid不正确
            if (!is_numeric($res['uid']) || $res['uid'] <= 0) continue;

            $uid=$res['uid'];

            //准备执行的insert ignore或者on duplicate key update语句
            $targetObj=[];

            //循环这5000个数组
            foreach ($res['data'] as $oneData)
            {
                //经纬度不存在
                if (!isset($oneData['latitude']) || !isset($oneData['longitude'])) continue;

                //经纬度不正确
                if ($oneData['latitude']=='' || $oneData['longitude']=='') continue;

                //生成geo是否出错和时间
                try
                {
                    $lat=\sprintf("%.6f",$oneData['latitude']);
                    $lng=\sprintf("%.6f",$oneData['longitude']);
                    $geohash=$Geo->encode($lat,$lng,'8');

                    $thisDotUnix=time();

                    if (isset($oneData['dateline']))
                    {
                        //android
                        if (is_numeric($oneData['dateline']))
                        {
                            $thisDotUnix=$oneData['dateline'];
                        }
                    }

                    if (isset($oneData['timestamp']))
                    {
                        //apple
                        if (is_numeric($oneData['timestamp']))
                        {
                            $thisDotUnix=$oneData['timestamp'];
                        }
                    }

                }catch (\Exception $e)
                {
                    continue;
                }

                //一条一条插，改成批量
                $targetObj[]=['uid'=>$uid,'geo'=>$geohash,'lat'=>$lat,'lng'=>$lng,'unixTime'=>(int)$thisDotUnix];
            }

            //是否有可以插入的坐标
            if (!empty($targetObj))
            {
                //生成后缀
                $suffix=$this->fogControllerObj->getDatabaseNoOrTableNo($uid);

                //创建表
                $this->createTable($suffix);

                //整理sql
                $sql="insert ignore into user_fog_{$suffix['table']} values ";

                $time=date('Y-m-d H:i:s',time());

                foreach ($targetObj as $oneTarget)
                {
                    $sql.="({$oneTarget['uid']},'{$oneTarget['lat']}','{$oneTarget['lng']}','{$oneTarget['geo']}',{$oneTarget['unixTime']},'{$time}','{$time}'),";
                }

                $sql=rtrim($sql,',');

                //插入数据
                try
                {
                    DB::connection("TrackFog{$suffix['db']}")->insert($sql);

                }catch (\Exception $e)
                {
                    //todo
                }
            }
        }

        return true;
    }
}
