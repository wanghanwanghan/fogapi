<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //记录慢查询，此表可以随意删
        if (!Schema::connection('masterDB')->hasTable('slow_sql'))
        {
            Schema::connection('masterDB')->create('slow_sql', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->text('sql')->nullable()->comment('执行语句');
                $table->text('bind')->nullable()->comment('绑定数值');
                $table->string('execTime',10)->nullable()->comment('执行时间');
                $table->timestamps();

            });
        }

        DB::connection('masterDB')->listen(function ($query)
        {
            $sql=$query->sql;
            $bind=json_encode($query->bindings);
            $time=round($query->time/1000,2);

            //超过2秒的sql存入数据库
            if ($time > 2)
            {
                $sql="insert into slow_sql values('','{$sql}','{$bind}','{$time}')";

                DB::connection('masterDB')->insert($sql);
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
