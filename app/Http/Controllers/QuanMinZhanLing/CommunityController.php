<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommunityController extends BaseController
{
    //打开某格子的印象板
    public function openOneGridCommunity(Request $request)
    {
        $this->createTable();

        $gName=trim($request->gName);



        dd(str_random());











    }

    public function createTable()
    {
        $now=Carbon::now();
        $year=$now->year;
        //$suffix是第几季度
        $suffix=$now->quarter;

        $db='communityDB';
        $table="community_article_{$year}_{$suffix}";

        if (!Schema::connection($db)->hasTable($table))
        {
            //印象板表
            Schema::connection($db)->create($table, function (Blueprint $table)
            {
                $table->increments('id')->unsigned()->comment('自增主键');
                $table->string('aid',20)->comment('真正的印象主键，10位的unixTime加上6位随机字符串');
                $table->integer('uid')->unsigned()->comment('用户主键');
                $table->string('gName',20)->comment('格子编号，老康命名');
                $table->text('content')->nullable()->comment('内容');
                $table->tinyInteger('isShow')->unsigned()->default(0)->comment('是否可以显示，1是可以，0是不可以');
                $table->string('picOrVideo1',200)->nullable()->comment('图片1或视频1');
                $table->string('picOrVideo2',200)->nullable()->comment('图片2或视频2');
                $table->string('picOrVideo3',200)->nullable()->comment('图片3或视频3');
                $table->string('picOrVideo4',200)->nullable()->comment('图片4或视频4');
                $table->string('picOrVideo5',200)->nullable()->comment('图片5或视频5');
                $table->string('picOrVideo6',200)->nullable()->comment('图片6或视频6');
                $table->string('picOrVideo7',200)->nullable()->comment('图片7或视频7');
                $table->string('picOrVideo8',200)->nullable()->comment('图片8或视频8');
                $table->string('picOrVideo9',200)->nullable()->comment('图片9或视频9');
                $table->integer('unixTime')->unsigned()->nullable()->comment('排序用的时间');
                $table->timestamps();
                $table->index('aid');
                $table->index('uid');
                $table->index('gName');
                $table->index('unixTime');
            });
        }

        //以下用不到，没准以后能用到

        //重建主键
        //DB::connection($db)->statement("Alter table {$table} drop primary key,add primary key (`id`,`uid`)");

        //添加分区
        //DB::connection($db)->statement("Alter table {$table} partition by linear key(`uid`) partitions 16");

        //印象板拓展信息表，存印象的标签，或者其他什么别的
        $table="community_ext_{$year}_{$suffix}";

        if (!Schema::connection($db)->hasTable($table))
        {
            Schema::connection($db)->create($table, function (Blueprint $table)
            {
                $table->string('aid',20)->comment('真正的印象主键，10位的unixTime加上6位随机字符串');
                $table->integer('tab1')->unsigned()->nullable()->comment('第1个标签');
                $table->integer('tab2')->unsigned()->nullable()->comment('第2个标签');
                $table->integer('tab3')->unsigned()->nullable()->comment('第3个标签');
                $table->primary('aid');
                $table->index('tab1');
                $table->index('tab2');
                $table->index('tab3');
            });
        }

        //印象板的评论表
        $table="community_comment_{$year}_{$suffix}";

        if (!Schema::connection($db)->hasTable($table))
        {
            Schema::connection($db)->create($table, function (Blueprint $table)
            {
                $table->string('aid',20)->comment('真正的印象主键，10位的unixTime加上6位随机字符串');
                $table->integer('activeUid')->unsigned()->comment('评论者uid');
                $table->integer('unactiveUid')->unsigned()->comment('被评论者uid');
                $table->tinyInteger('isShow')->unsigned()->default(0)->comment('是否可以显示，1是可以，0是不可以');
                $table->text('content')->nullable()->comment('内容');
                $table->integer('unixTime')->unsigned()->nullable()->comment('排序用的时间');
                $table->primary('aid');
                $table->index('activeUid');
                $table->index('unactiveUid');
                $table->index('unixTime');
            });
        }

        return true;
    }













}