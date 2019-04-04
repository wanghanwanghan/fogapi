<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class dis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wanghan:dis';

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

    public $connection='tssj_old';

    public function createTable()
    {
        $table=[
            'tssj_fog_2015','tssj_fog_2016',
            'tssj_fog_2017','tssj_fog_201801','tssj_fog_201802',
            'tssj_fog_201803','tssj_fog_201804','tssj_fog_201805',
            'tssj_fog_201806','tssj_fog_201807','tssj_fog_201808',
            'tssj_fog_201809','tssj_fog_201810','tssj_fog_201811',
            'tssj_fog_201812','tssj_fog_201901','tssj_fog_201902','tssj_fog_201903',
        ];

        foreach ($table as $name)
        {
            $name.='_new';

            $sql=<<<Eof
CREATE TABLE {$name} (
  `fogid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键编号',
  `userid` int(10) unsigned NOT NULL COMMENT '用户编号',
  `longitude` float(10,6) DEFAULT '0.000000' COMMENT '地点经度',
  `latitude` float(10,6) DEFAULT '0.000000' COMMENT '地点纬度',
  `geohash` char(8) DEFAULT NULL,
  `dateline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '记录时间',
  `type` int(1) NOT NULL DEFAULT '0' COMMENT '1:自动上传 2:手动上传 0:旧上传数据未区分',
  `created` int(10) NOT NULL DEFAULT '0' COMMENT '迷雾点导入时间',
  PRIMARY KEY (`fogid`),
  KEY `dateline` (`dateline`),
  KEY `userid` (`userid`),
  KEY `geohash` (`geohash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='足迹迷雾坐标';
Eof;
            DB::connection($this->connection)->select($sql);
        }
    }

    public function handle()
    {
        $this->createTable();






















    }
}
