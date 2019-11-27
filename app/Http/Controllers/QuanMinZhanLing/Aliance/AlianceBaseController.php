<?php

namespace App\Http\Controllers\QuanMinZhanLing\Aliance;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlianceBaseController extends Controller
{
    protected $db='Aliance';

    //入联盟税收减20%
    protected $tixBase=0.2;

    protected function alianceName(String $num='')
    {
        $arr=[
            '1'=>['name'=>'风相联盟',
                'constellation'=>['双子座','天秤座','水瓶座'],
                'welfare'=>['public'=>'20% 税收减免','private'=>'每天给钱'],
            ],
            '2'=>['name'=>'土相联盟',
                'constellation'=>['金牛座','处女座','摩羯座'],
                'welfare'=>['public'=>'20% 税收减免','private'=>'签到奖励翻倍'],
            ],
            '3'=>['name'=>'水相联盟',
                'constellation'=>['巨蟹座','天蝎座','双鱼座'],
                'welfare'=>['public'=>'20% 税收减免','private'=>'每日任务奖励翻倍'],
            ],
            '4'=>['name'=>'火相联盟',
                'constellation'=>['白羊座','狮子座','射手座'],
                'welfare'=>['public'=>'20% 税收减免','private'=>'钱袋增长速度翻倍'],
            ],
        ];

        if ($num==='') return $arr;

        return $arr[$num];
    }

    protected function createTable($type)
    {
        if ($type=='') return true;

        switch ($type)
        {
            case 'invite':

                //入盟请帖

                if (!Schema::connection($this->db)->hasTable($type))
                {
                    Schema::connection($this->db)->create($type, function (Blueprint $table)
                    {
                        $table->increments('id')->unsigned()->comment('没用');
                        $table->integer('uid')->unsigned()->comment('谁发出的邀请')->index();
                        $table->integer('tid')->unsigned()->comment('被邀请者')->index();
                        $table->tinyInteger('alianceNum')->unsigned()->comment('联盟编号');
                        $table->tinyInteger('yesOrNo')->unsigned()->comment('是否同意邀请');
                        $table->timestamps();
                        $table->engine='InnoDB';
                    });
                }

                break;

            case 'alianceGroup':

                //联盟表

                if (!Schema::connection($this->db)->hasTable($type))
                {
                    Schema::connection($this->db)->create($type, function (Blueprint $table)
                    {
                        $table->integer('uid')->unsigned()->comment('用户主键');
                        $table->tinyInteger('alianceNum')->unsigned()->comment('联盟编号');
                        $table->timestamps();
                        $table->primary('uid');
                        $table->engine='InnoDB';
                    });
                }

                break;

            case 'announcement':

                //联盟公告

                if (!Schema::connection($this->db)->hasTable($type))
                {
                    Schema::connection($this->db)->create($type, function (Blueprint $table)
                    {
                        $table->increments('id')->unsigned()->comment('主键');
                        $table->tinyInteger('alianceNum')->unsigned()->comment('联盟编号')->index();
                        $table->string('content')->comment('公告内容');
                        $table->timestamps();
                        $table->engine='InnoDB';
                    });
                }

                break;

            case 'flourish':

                //联盟公告

                if (!Schema::connection($this->db)->hasTable($type))
                {
                    Schema::connection($this->db)->create($type, function (Blueprint $table)
                    {
                        $table->integer('date')->unsigned()->comment('日期')->index();
                        $table->tinyInteger('alianceNum')->unsigned()->comment('联盟编号');
                        $table->integer('userTotal')->unsigned()->comment('人数');
                        $table->integer('gridTotal')->unsigned()->comment('格子数');
                        $table->integer('gridPriceTotal')->unsigned()->comment('格子总价');
                        $table->integer('gridPriceAverageTotal')->unsigned()->comment('格子均价');
                        $table->integer('flourish')->unsigned()->comment('繁荣度');
                        $table->engine='InnoDB';
                    });
                }

                break;
        }

        return true;
    }




}
