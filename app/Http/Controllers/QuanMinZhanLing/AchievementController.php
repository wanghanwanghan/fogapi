<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\AchievementInfoModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class AchievementController extends BaseController
{
    public $suffix;

    public $connection='masterDB';

    //从数据库取成就的缓存key
    public $KeyForDB='AchievementCacheForDB';

    //获取/统计用户成就
    public function getAchievementForUser(Request $request)
    {
        $uid=trim($request->uid);

        //过滤
        if (!is_numeric($uid) || $uid==null || $uid=='' || $uid==0) return false;

        //放入redis集合，下次统计
        Redis::connection('WriteLog')->sadd('Achievement',$uid);

        //取出成就json
        $achievementInfo=Redis::connection('UserInfo')->hget($uid,'Achievement');

        //没数据
        if ($achievementInfo==null) return response()->json(['resCode' => Config::get('resCode.200'),'data'=>null]);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$achievementInfo]);
    }

    //领取金币后，数据入库
    public function setAchievementForUser(Request $request)
    {
        $uid=trim($request->uid);
        $aid=trim($request->aid);

        if (!$this->checkTable($uid)) return response()->json(['resCode' => Config::get('resCode.614')]);

        //写入redis
        $userInfo=Redis::connection('UserInfo')->hget($uid,'Achievement');

        try
        {
            $userInfo=json_decode($userInfo,true);

            $achPrefix=substr($aid,0,1);

            $userInfo[$achPrefix.'xxx'][$aid]=2;

            //写入redis
            Redis::connection('UserInfo')->hset($uid,'Achievement',json_encode($userInfo));

        }catch (\Exception $e)
        {

        }

        //数据入库
        AchievementInfoModel::suffix($this->suffix);

        AchievementInfoModel::firstOrCreate(['aid'=>$aid,'uid'=>$uid,'isComplete'=>1]);

        return response()->json(['resCode' => Config::get('resCode.200')]);
    }

    //成就副表分表，按用户取模在5张表里
    public function checkTable($uid)
    {
        $i=$uid%5;

        $this->suffix=$i;

        try
        {
            if (Schema::connection($this->connection)->hasTable('achievement_info_'.$i)) return true;

            Schema::connection($this->connection)->create('achievement_info_'.$i, function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->integer('aid')->unsigned()->comment('成就表主键')->index();
                $table->integer('uid')->unsigned()->comment('用户主键')->index();
                $table->char('isComplete','1')->comment('是否领取完奖励');
                $table->timestamps();//完成时间

            });

        }catch (\Exception $e)
        {
            return false;
        }

        return true;
    }

    //获取所有成就信息
    public function getAchievement(Request $request)
    {
        //当天第一次请求时间
        $star=time();

        //当天结束时间
        $stop=Carbon::now()->endOfDay()->timestamp;

        //差多少分钟
        $m=($stop-$star)/60;

        $m=intval($m);

        $achievement=Cache::remember($this->KeyForDB,$m,function()
        {
            return DB::connection($this->connection)->table('achievement')->get()->toArray();
        });

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$achievement]);
    }












}
