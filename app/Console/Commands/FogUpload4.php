<?php

namespace App\Console\Commands;

use App\Http\Controllers\TanSuoShiJie\FogController;
use App\Model\Tssj\FogModel;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class FogUpload4 extends Command
{
    protected $signature = 'Tssj:FogUpload4';

    protected $myTarget = 4;

    protected $description = '处理用户上传的迷雾';

    protected $fogControllerObj;

    public function __construct()
    {
        parent::__construct();

        $this->fogControllerObj=new FogController();
    }

    public function createTable($suffix)
    {
        if (!Schema::connection('TssjFog'.$suffix['db'])->hasTable('user_fog_'.$suffix['table']))
        {
            Schema::connection('TssjFog'.$suffix['db'])->create('user_fog_'.$suffix['table'], function (Blueprint $table)
            {
                $table->increments('id')->unsigned()->comment('主键');
                $table->integer('uid')->unsigned()->comment('用户主键');
                $table->string('lat','15')->comment('纬度');
                $table->string('lng','15')->comment('精度');
                $table->string('geo','10')->comment('geohash');
                $table->integer('unixTime')->unsigned()->comment('unix时间戳')->index();
                $table->timestamps();
                $table->index(['uid','geo']);
                $table->engine='InnoDB';
            });
        }

        return true;
    }

    public function handle()
    {
        $Geo=new \Geohash\GeoHash();

        while(true)
        {
            $one=Redis::connection('TssjFog')->rpop('FogUploadList_'.$this->myTarget);

            //没东西就退出
            if ($one=='') break;

            //解析
            $res=jsonDecode($one);

            //不含有处理内容，跳过该条数据
            if (!isset($res['uid']) || !isset($res['data'])) continue;

            //uid不正确
            if (!is_numeric($res['uid']) || $res['uid'] <= 0) continue;

            $uid=$res['uid'];

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

                //生成后缀
                $suffix=$this->fogControllerObj->getDatabaseNoOrTableNo($uid);

                //创建表
                $this->createTable($suffix);

                //插入数据
                try
                {
                    FogModel::databaseSuffix($suffix['db']);
                    FogModel::tableSuffix($suffix['table']);

                    FogModel::updateOrCreate(['uid'=>$uid,'geo'=>$geohash],['lat'=>$lat,'lng'=>$lng,'unixTime'=>$thisDotUnix]);

                }catch (\Exception $e)
                {
                    continue;
                }
            }
        }

        return true;
    }
}
