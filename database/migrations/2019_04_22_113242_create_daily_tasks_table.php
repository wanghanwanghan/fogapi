<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyTasksTable extends Migration
{
    public $connection='masterDB';

    public function up()
    {
        if (!Schema::hasTable('dailytasks'))
        {
            Schema::create('dailytasks', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('每日任务id');
                $table->string('name','30')->comment('每日任务名称');
                $table->smallInteger('scheduleTotle')->unsigned()->comment('总共需要的进度');
                $table->smallInteger('price')->unsigned()->comment('奖励的价格');

            });
        }
    }

    public function down()
    {
        //Schema::dropIfExists('daily_tasks');
    }
}
