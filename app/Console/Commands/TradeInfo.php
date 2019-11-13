<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class TradeInfo extends Command
{
    protected $signature = 'Grid:TradeInfo';

    protected $description = '买格子后记录日志';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        while (true)
        {
            $info=Redis::connection('WriteLog')->rpop('tradeInfo');

            if ($info==null) break;

            //整理这个数组，插入到数据表
            //$arr['gid']=$gridInfo->id;
            //$arr['gName']=$gridInfo->name;
            //$arr['uid']=$uid;
            //$arr['uName']='';
            //$arr['belong']=$gridInfo->belong;
            //$arr['belongName']='';
            //$arr['payMoney']=$payMoney;
            //$arr['payCount']=$gridInfo->totle + 1;
            //$arr['payTime']=time();

            $tableName=$this->createTable('masterDB');

            $info=jsonDecode($info);

            $userinfo=DB::connection('tssj_old')->table('tssj_member')->where('userid',$info['uid'])->first();

            if ($userinfo==null)
            {
                //没找到用户信息
                continue;
            }

            $info['uName']=$userinfo->username;

            if ($info['belong']=='0')
            {
                $info['belongName']='系统';
            }else
            {
                $belonginfo=DB::connection('tssj_old')->table('tssj_member')->where('userid',$info['belong'])->first();

                if ($belonginfo==null)
                {
                    $info['belongName']='该用户不存在';
                }else
                {
                    $info['belongName']=$belonginfo->username;
                }
            }

            //写入记录
            DB::connection('masterDB')->table($tableName)->insert($info);

            $tableName=$this->createTable('gridTradeInfoDB',$info['gName']);

            //写入记录
            DB::connection('gridTradeInfoDB')->table($tableName)->insert($info);
        }
    }

    public function createTable($DB,$gname=null)
    {
        if ($DB=='masterDB')
        {
            $suffix=Carbon::now()->format('Ym');

            //格子交易信息表
            if (!Schema::connection($DB)->hasTable('buy_sale_info_'.$suffix))
            {
                Schema::connection($DB)->create('buy_sale_info_'.$suffix, function (Blueprint $table) {

                    $table->increments('id')->unsigned()->comment('自增主键');
                    $table->integer('gid')->unsigned()->comment('格子主键');
                    $table->string('gname','30')->comment('格子名')->index();
                    $table->integer('uid')->unsigned()->comment('买用户主键')->index();
                    $table->string('uname','30')->comment('买用户名');
                    $table->integer('belong')->unsigned()->comment('卖用户主键')->index();
                    $table->string('belongname','30')->comment('卖用户名');
                    $table->integer('paymoney')->unsigned()->comment('买的价格');
                    $table->integer('paycount')->unsigned()->comment('当前是格子的第几次交易');
                    $table->integer('paytime')->unsigned()->comment('买的时间');

                });
            }

            return 'buy_sale_info_'.$suffix;
        }

        if ($DB=='gridTradeInfoDB' && $gname!=null)
        {
            $ghash=string2Number($gname);

            $suffix=$ghash%50;

            //另外一个格子交易信息表，已格子名字hash分表，以后统计柱状图什么的用
            if (!Schema::connection($DB)->hasTable('grid_trade_info_'.$suffix))
            {
                Schema::connection($DB)->create('grid_trade_info_'.$suffix, function (Blueprint $table) {

                    $table->increments('id')->unsigned()->comment('自增主键');
                    $table->integer('gid')->unsigned()->comment('格子主键');
                    $table->string('gname','30')->comment('格子名')->index();
                    $table->integer('uid')->unsigned()->comment('买用户主键')->index();
                    $table->string('uname','30')->comment('买用户名');
                    $table->integer('belong')->unsigned()->comment('卖用户主键')->index();
                    $table->string('belongname','30')->comment('卖用户名');
                    $table->integer('paymoney')->unsigned()->comment('买的价格');
                    $table->integer('paycount')->unsigned()->comment('当前是格子的第几次交易');
                    $table->integer('paytime')->unsigned()->comment('买的时间');

                });
            }

            return 'grid_trade_info_'.$suffix;
        }

        return true;
    }
}
