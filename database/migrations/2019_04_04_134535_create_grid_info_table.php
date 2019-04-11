<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGridInfoTable extends Migration
{
    public $connection='masterDB';

    //格子副表
    public function up()
    {
        if (!Schema::hasTable('grid_info'))
        {
            Schema::create('grid_info', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->integer('uid')->unsigned()->comment('用户主键')->index();
                $table->integer('gid')->unsigned()->comment('格子表主键')->index();
                $table->string('name','20')->nullable()->comment('用户命名');
                $table->string('showName','1')->nullable()->comment('可不可以显示用户命名');
                $table->string('pic1','200')->nullable()->comment('图片1');
                $table->string('showPic1','1')->nullable()->comment('可不可以显示图片1');
                $table->string('pic2','200')->nullable()->comment('图片2');
                $table->string('showPic2','1')->nullable()->comment('可不可以显示图片2');
                $table->string('pic3','200')->nullable()->comment('图片3');
                $table->string('showPic3','1')->nullable()->comment('可不可以显示图片3');
                $table->string('pic4','200')->nullable()->comment('图片4');
                $table->string('showPic4','1')->nullable()->comment('可不可以显示图片4');
                $table->timestamps();

            });
        }
    }

    public function down()
    {
        //Schema::dropIfExists('grid_info');
    }
}
