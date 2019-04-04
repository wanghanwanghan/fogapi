<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AchievementController extends BaseController
{
    public $connection='aliyun';

    //成就副表分表，按用户取模在5张表里
    public function checkTable($uid)
    {
        $i=$uid%5;

        try
        {
            if (Schema::connection($this->connection)->hasTable('achievement_'.$i)) return true;

            Schema::connection($this->connection)->create('achievement_'.$i, function (Blueprint $table) {

                $table->integer('aid')->unsigned()->comment('成就表主键');
                $table->integer('userid')->unsigned()->comment('用户主键');
                $table->smallInteger('totle')->unsigned()->comment('完成的次数');
                $table->char('isComplete','1')->default(0)->comment('是否领取完奖励');
                $table->index(['aid','userid']);

            });

        }catch (\Exception $e)
        {
            return false;
        }

        return true;
    }
}
