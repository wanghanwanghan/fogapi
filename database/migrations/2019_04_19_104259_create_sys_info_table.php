<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSysInfoTable extends Migration
{
    public $connection='masterDB';

    //成就主表
    public function up()
    {
//        if (!Schema::hasTable('sys_info'))
//        {
//            Schema::create('sys_info', function (Blueprint $table) {
//
//                $table->increments('id')->unsigned()->comment('自增主键');
//                $table->string('obj','50')->comment('调整对象');
//                $table->string('range','250')->comment('调整范围');
//
//            });
//        }





    }

    public function down()
    {
        //Schema::dropIfExists('sys_info');
    }
}
