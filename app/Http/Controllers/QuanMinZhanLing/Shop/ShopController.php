<?php

namespace App\Http\Controllers\QuanMinZhanLing\Shop;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ShopController extends BaseController
{
    public function createTable()
    {
        //商店主表
        if (!Schema::connection('masterDB')->hasTable('shop'))
        {
            Schema::connection('masterDB')->create('shop', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->string('name',50)->comment('卡片名称');
                $table->string('depict',200)->comment('卡片描述');
                $table->integer('target')->unsigned()->comment('1无限制，2自己的格子，3去过的格子');
                $table->integer('times')->unsigned()->comment('每天最大使用次数');

            });
        }

        //用户持有卡片表

        //使用明细表

        return true;
    }










}
