<?php

namespace App\Console\Commands;

use App\Http\Controllers\WoDeLu\TrackFogController;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class TrackFogUploadForZUJI3 extends Command
{
    protected $signature = 'Wodelu:TrackFogUploadForZUJI3';

    protected $myTarget = 3;

    protected $description = '处理用户上传的足迹';

    protected $fogControllerObj;

    public function __construct()
    {
        parent::__construct();

        $this->fogControllerObj=new TrackFogController();
    }

    public function createTable($suffix)
    {
        if (!Schema::connection('TrackFogForZUJI'.$suffix['db'])->hasTable('user_zuji_index'))
        {
            Schema::connection('TrackFogForZUJI'.$suffix['db'])->create('user_zuji_index', function (Blueprint $table)
            {
                $table->increments('id')->unsigned()->comment('自增主键');
                $table->integer('uid')->unsigned()->comment('用户主键');
                $table->integer('date')->unsigned()->comment('用户的足迹时间');
                $table->integer('locationsTotal')->unsigned()->comment('有多少个点');
                $table->string('randomUUID','35')->comment('唯一id');
                $table->tinyInteger('status')->unsigned()->comment('静止还是移动');
                $table->integer('distance')->unsigned()->comment('距离');
                $table->string('stopLocationStr','100')->comment('停止位置');
                $table->integer('startTimestamp')->unsigned()->comment('开始时间');
                $table->string('startLocationStr','100')->comment('开始位置');
                $table->integer('endTimestamp')->unsigned()->comment('停止时间');
                $table->integer('interval')->unsigned()->comment('持续时间');
                $table->timestamps();
                $table->index(['uid','date']);
                $table->engine='InnoDB';
            });
        }

        if (!Schema::connection('TrackFogForZUJI'.$suffix['db'])->hasTable('user_zuji_'.$suffix['table']))
        {
            Schema::connection('TrackFogForZUJI'.$suffix['db'])->create('user_zuji_'.$suffix['table'], function (Blueprint $table)
            {
                $table->integer('uid')->unsigned()->comment('用户主键');
                $table->string('randomUUID','35')->comment('唯一id');
                $table->integer('timestamp')->unsigned()->comment('时间');
                $table->string('lat','20')->comment('纬度');
                $table->string('lng','20')->comment('精度');
                $table->string('geo','15')->comment('geohash');
                $table->index(['uid','randomUUID']);
                $table->index('timestamp');
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
            //$one是一天的足迹json串
            $one=Redis::connection('TrackFog')->rpop('FogUploadZuJiList_'.$this->myTarget);

            //没东西就退出
            if ($one=='') break;

            //解析
            $res=jsonDecode($one);

            //不含有处理内容，跳过该条数据
            if (!isset($res['uid']) || !isset($res['date']) || !isset($res['data'])) continue;

            //uid不正确
            if (!is_numeric($res['uid']) || $res['uid'] <= 0) continue;

            //时间不正确
            if (!is_numeric($res['date']) || $res['date'] < 20180101) continue;

            $uid=$res['uid']-0;
            $date=$res['date']-0;
            isset($res['update']) ? $update=$res['update']-0 : $update=0;

            $suffix=$this->fogControllerObj->getDatabaseNoOrTableNoForZUJI($date);

            $this->createTable($suffix);

            //传过了，或者不更新就跳过
            if ($this->checkAndUpdate($uid,$date,$update)) continue;

            //循环这一天的数组
            foreach ($res['data'] as $oneData)
            {
                //先插入外层的数据到index表
                $locationsTotal=count($oneData['locations']);

                $randomUUID=randomUUID();

                if (isset($oneData['status']))
                {
                    $status=$oneData['status']-0;

                    if ($status < 0 ) continue;

                }else
                {
                    $status=0;
                }

                if (isset($oneData['distance']))
                {
                    $distance=$oneData['distance']-0;
                }else
                {
                    $distance=0;
                }

                if (isset($oneData['stopLocationStr']))
                {
                    $stopLocationStr=trim($oneData['stopLocationStr']);
                }else
                {
                    $stopLocationStr='';
                }

                $startTimestamp=mb_substr($oneData['startTimestamp']-0,0,10);

                if (isset($oneData['startlocationStr']))
                {
                    $startLocationStr=trim($oneData['startlocationStr']);
                }else
                {
                    $startLocationStr='';
                }

                $endTimestamp=mb_substr($oneData['endTimestamp']-0,0,10);

                $interval=$oneData['interval']-0;

                $created_at=Carbon::now()->format('Y-m-d H:i:s');

                $updated_at=Carbon::now()->format('Y-m-d H:i:s');

                $sql="insert into user_zuji_index values ('',{$uid},{$date},{$locationsTotal},'{$randomUUID}',{$status},{$distance},'{$stopLocationStr}',{$startTimestamp},'{$startLocationStr}',{$endTimestamp},{$interval},'{$created_at}','{$updated_at}')";

                try
                {
                    DB::connection('TrackFogForZUJI'.$suffix['db'])->insert($sql);

                }catch (\Exception $e)
                {
                    continue;
                }

                $targetObj=[];
                foreach ($oneData['locations'] as $locations)
                {
                    //添加足迹点
                    $lat=\sprintf("%.14f",$locations['latitude']);
                    $lng=\sprintf("%.14f",$locations['longitude']);
                    $geohash=$Geo->encode($lat,$lng,'9');
                    $time=mb_substr($locations['timestamp']-0,0,10);

                    //一条一条插，改成批量
                    $targetObj[]=['uid'=>$uid,'randomUUID'=>$randomUUID,'timestamp'=>$time,'lat'=>$lat,'lng'=>$lng,'geo'=>$geohash];
                }

                //整理sql
                $sql="insert into user_zuji_{$suffix['table']} values ";

                foreach ($targetObj as $oneTarget)
                {
                    $sql.="({$oneTarget['uid']},'{$oneTarget['randomUUID']}',{$oneTarget['timestamp']},'{$oneTarget['lat']}','{$oneTarget['lng']}','{$oneTarget['geo']}'),";
                }

                $sql=rtrim($sql,',');

                //插入数据
                try
                {
                    DB::connection('TrackFogForZUJI'.$suffix['db'])->insert($sql);

                }catch (\Exception $e)
                {
                    continue;
                }
            }
        }

        return true;
    }

    //当前传过没传过，更不更改
    public function checkAndUpdate($uid,$date,$update)
    {
        $suffix=$this->fogControllerObj->getDatabaseNoOrTableNoForZUJI($date);

        //先看看索引表中有没有记录
        $check=DB::connection('TrackFogForZUJI'.$suffix['db'])->table('user_zuji_index')->where(['uid'=>$uid,'date'=>$date])->count();

        if ($check && $update===0)
        {
            //已经传过了，并且不更新
            return true;
        }

        if ($check && $update===1)
        {
            //更新
            $randomUUID=DB::connection('TrackFogForZUJI'.$suffix['db'])->table('user_zuji_index')->where(['uid'=>$uid,'date'=>$date])->get(['randomUUID'])->toArray();

            DB::connection('TrackFogForZUJI'.$suffix['db'])->table('user_zuji_index')->where(['uid'=>$uid,'date'=>$date])->delete();

            $cond=[];

            foreach ($randomUUID as $oneRandomUUID)
            {
                $cond[]=$oneRandomUUID->randomUUID.',';
            }

            DB::connection('TrackFogForZUJI'.$suffix['db'])->table("user_zuji_{$suffix['table']}")->where('uid',$uid)->whereIn('randomUUID',$cond)->delete();
        }

        //其他情况直接插入

        return false;
    }
}
