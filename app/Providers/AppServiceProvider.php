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
                $table->string('uuid',35)->nullable()->index()->comment('sql uuid');
                $table->text('sql')->nullable()->comment('执行语句');
                $table->text('bind')->nullable()->comment('绑定数值');
                $table->float('execTime',4,2)->nullable()->unsigned()->index()->comment('执行时间');

            });
        }

        DB::connection('masterDB')->listen(function ($query)
        {
            $time=round($query->time/1000,2);

            //超过2秒的sql存入数据库
            if ($time > 2)
            {
                $sql=addslashes($query->sql);

                $sql=str_replace(["\n","\r\n"],'',$sql);

                $md5Sql=md5($sql);

                $res=DB::connection('masterDB')->table('slow_sql')->where('uuid',$md5Sql)->first();

                if ($res==null)
                {
                    $query->bindings==[] ? $bind='' : $bind=json_encode($query->bindings);

                    $sql="insert into slow_sql values(null,'{$md5Sql}','{$sql}','{$bind}',{$time})";

                    try
                    {
                        DB::connection('masterDB')->insert($sql);

                    }catch (\Exception $e)
                    {

                    }
                }else
                {
                    //更新sql执行时间
                    $res->execTime=$time;
                    $res->save();
                }
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
