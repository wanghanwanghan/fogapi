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
                $table->string('goodsName','100')->nullable()->comment('名称');
                $table->string('describe','200')->nullable()->comment('描述');
                $table->tinyInteger('goodsLevel')->nullable()->unsigned()->comment('等级');

                $table->tinyInteger('goodsType')->nullable()->unsigned()->comment('1卡片');
                $table->tinyInteger('targetType')->nullable()->unsigned()->comment('1对人，2对格');
                $table->tinyInteger('effectType')->nullable()->unsigned()->comment('1增益，2抑制，3普通');









            });
        }

        //用户持有卡片表

        //使用明细表

        return true;
    }










}
