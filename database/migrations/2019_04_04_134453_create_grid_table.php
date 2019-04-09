<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGridTable extends Migration
{
    public $connection='aliyun';

    //格子主表
    public function up()
    {
        Schema::create('grid', function (Blueprint $table) {

            $table->increments('id')->unsigned()->comment('自增主键');
            $table->string('lat','15')->comment('纬度');
            $table->string('lng','15')->comment('经度');
            $table->string('geohash','15');
            $table->string('name','150')->comment('老康命名');
            $table->integer('price')->unsigned()->default(10)->comment('当前价格');
            $table->integer('hightPrice')->unsigned()->default(10)->comment('历史最高价格');
            $table->integer('belong')->unsigned()->default(0)->comment('当前所属');
            $table->integer('totle')->unsigned()->default(0)->comment('交易总数');//当天交易次数放到redis
            $table->char('showGrid','1')->default('1')->comment('格子是否开放');
            $table->timestamps();
            $table->index('geohash');
            $table->index('name');

        });
    }

    public function down()
    {
        Schema::dropIfExists('grid');
    }
}
