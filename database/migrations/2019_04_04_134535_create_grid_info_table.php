<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGridInfoTable extends Migration
{
    public $connection='aliyun';

    //格子副表
    public function up()
    {
        Schema::create('grid_info', function (Blueprint $table) {

            $table->increments('id')->unsigned()->comment('自增主键');
            $table->integer('gid')->unsigned()->comment('格子表主键');
            $table->string('name','20')->comment('用户命名');
            $table->char('showName','1')->default('0')->comment('可不可以显示用户命名');
            $table->string('pic1','100')->default(0)->comment('图片1');
            $table->char('showPic1','1')->default('0')->comment('可不可以显示图片1');
            $table->string('pic2','100')->default(0)->comment('图片2');
            $table->char('showPic2','1')->default('0')->comment('可不可以显示图片2');
            $table->string('pic3','100')->default(0)->comment('图片3');
            $table->char('showPic3','1')->default('0')->comment('可不可以显示图片3');
            $table->string('pic4','100')->default(0)->comment('图片4');
            $table->char('showPic4','1')->default('0')->comment('可不可以显示图片4');
            $table->index('gid');

        });
    }

    public function down()
    {
        Schema::dropIfExists('grid_info');
    }
}
