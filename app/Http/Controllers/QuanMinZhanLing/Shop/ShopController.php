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

            });
        }

        //用户持有卡片表
        if (!Schema::connection('masterDB')->hasTable('shop'))
        {
            Schema::connection('masterDB')->create('shop', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');

            });
        }

        //使用明细表
        if (!Schema::connection('masterDB')->hasTable('shop'))
        {
            Schema::connection('masterDB')->create('shop', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');

            });
        }

        return true;
    }










}
