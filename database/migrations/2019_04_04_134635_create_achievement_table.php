<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementTable extends Migration
{
    public $connection='aliyun';

    //成就主表
    public function up()
    {
        Schema::create('achievement', function (Blueprint $table) {

            $table->integer('id')->unsigned()->comment('成就id');
            $table->string('name','30')->comment('成就名称');
            $table->smallInteger('totle')->unsigned()->comment('允许完成的总次数');
            $table->smallInteger('price')->unsigned()->comment('奖励的价格');
            $table->primary('id');

        });
    }

    public function down()
    {
        Schema::dropIfExists('achievement');
    }
}
