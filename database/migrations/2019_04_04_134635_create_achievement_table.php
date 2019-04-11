<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementTable extends Migration
{
    public $connection='masterDB';

    //成就主表
    public function up()
    {
        if (!Schema::hasTable('achievement'))
        {
            Schema::create('achievement', function (Blueprint $table) {

                $table->integer('id')->unsigned()->comment('成就id');
                $table->string('name','30')->comment('成就名称');
                $table->smallInteger('scheduleTotle')->unsigned()->comment('总共需要的进度');
                $table->smallInteger('price')->unsigned()->comment('奖励的价格');
                $table->primary('id');

            });
        }
    }

    public function down()
    {
        //Schema::dropIfExists('achievement');
    }
}
