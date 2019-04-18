<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class Achievement1 extends Command
{
    protected $signature = 'Grid:Achievement1';

    protected $description = '延时统计用户成就，除了同时拥有格子外的其他成就';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //取出待处理uid
        $allUid=Redis::connection('WriteLog')->smembers('Achievement');

        //删掉集合
        Redis::connection('WriteLog')->del('Achievement');














    }
}
