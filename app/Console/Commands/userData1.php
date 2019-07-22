<?php

namespace App\Console\Commands;

use App\Model\RankListModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class userData1 extends Command
{
    protected $signature = 'Admin:userData1';

    protected $description = 'userData1';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $res=RankListModel::orderBy('id')->get();

        foreach ($res as &$one)
        {
            $one->uname=trim(Redis::connection('UserInfo')->hget($one->uid,'name'));
        }
        unset($one);

        Redis::connection('default')->set('userData1',jsonEncode($res));

        return true;
    }
}
