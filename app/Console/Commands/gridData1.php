<?php

namespace App\Console\Commands;

use App\Model\GridModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class gridData1 extends Command
{
    protected $signature = 'Admin:gridData1';

    protected $description = 'gridData1';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $res=GridModel::orderBy('price','desc')->orderBy('id')->limit(10000)->get([
            'name','price','hightPrice','totle','belong','updated_at'
        ]);

        foreach ($res as &$one)
        {
            $one->uname=trim(Redis::connection('UserInfo')->hget($one->belong,'name'));
        }
        unset($one);

        Redis::connection('default')->set('gridData1',jsonEncode($res));

        return true;
    }
}
