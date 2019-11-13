<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGetGoodsBySysMsgTable extends Migration
{
    public $connection='masterDB';

    public function up()
    {
        if (!Schema::hasTable('get_goods_by_sys_msg_0'))
        {
            Schema::create('get_goods_by_sys_msg_0', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->integer('uid')->unsigned()->comment('用户主键')->index();
                $table->integer('sid')->unsigned()->comment('系统通知主键');

            });

            Schema::create('get_goods_by_sys_msg_1', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->integer('uid')->unsigned()->comment('用户主键')->index();
                $table->integer('sid')->unsigned()->comment('系统通知主键');

            });

            Schema::create('get_goods_by_sys_msg_2', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->integer('uid')->unsigned()->comment('用户主键')->index();
                $table->integer('sid')->unsigned()->comment('系统通知主键');

            });
        }
    }


    public function down()
    {
        //Schema::dropIfExists('get_goods_by_sys_msg');
    }
}
