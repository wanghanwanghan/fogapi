<?php

namespace App\Http\Controllers\QuanMinZhanLing\FoodMap;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class FoodMapBaseController extends BaseController
{
    //已经开放的类别
    public $treasureType=[
        [
            'typeName'=>'地方美食',
            'openMonth'=>[1,5,9],
            'open'=>1,
        ],
        [
            'typeName'=>'地方电影',
            'openMonth'=>[2,6,10],
            'open'=>1,
        ],
        [
            'typeName'=>'地方品牌',
            'openMonth'=>[3,7,11],
            'open'=>1,
        ],
        [
            'typeName'=>'地方景点',
            'openMonth'=>[4,8,12],
            'open'=>1,
        ],
    ];

    //获取现在进行时的类别
    public function getTreasureType()
    {
        //每月只返回一个类别
        //顺序从上至下

        //当月
        $month=Carbon::now()->format('m');

        $open=[];

        foreach ($this->treasureType as $one)
        {
            if ($one['open']!=1 || !in_array($month,$one['openMonth'])) continue;

            $open[]=$one['typeName'];
        }

        if (empty($open)) $open=['地方电影'];

        return $open;
    }

    //概率
    public $rate=[
        'epicPatch'=>4,
        'commonPatch'=>30,
        'money'=>22,
        'buyCard'=>22,
        'diamond'=>22,
    ];
    public $rate_in=[
        'commonPatch'=>[
            0, //白
            95,//绿
            5, //蓝
            0, //紫
            0, //橙
        ],
        'money'=>[99,1],//普通，峰值
        'buyCard'=>[99,1],
        'diamond'=>[99,1],
    ];

    //100个许愿池物品
    public $pool;

    //1连抽或者5连抽后的数组
    public $wish;

    public $uid;

    public $db='FoodMap';

    //首先要第一个执行的
    public function setUid($uid)
    {
        $this->uid=$uid;

        return $this;
    }

    //1连抽 或者 5连抽
    public function getWish($num)
    {
        $this->createWishPool();

        //如果幸运值是100，必得史诗
        $luckNum=$this->getLuckNum();

        if ($luckNum >= 100)
        {
            $wish=['epicPatch'];
            $skip=1;

            //清空幸运值
            $this->deleteLuckNum();
        }else
        {
            $wish=[];
            $skip=0;
        }

        for ($i=1;$i<=$num-$skip;$i++)
        {
            $key=mt_rand(0,array_sum($this->rate)-1);

            if ($this->pool[$key]=='epicPatch' && in_array('epicPatch',$wish))
            {
                $i--;
                continue;
            }

            $wish[]=$this->pool[$key];
        }

        //增加幸运值
        $this->addLuckNum($num);

        //清空幸运值
        in_array('epicPatch',$wish) ? $this->deleteLuckNum() : null;

        $this->wish=$wish;
        $this->execWishArray();

        return $this->wish;
    }

    //生成的n连抽数组，整理一下
    public function execWishArray()
    {
        $tmp=[];

        foreach ($this->wish as $one)
        {
            switch ($one)
            {
                case 'epicPatch':

                    $tmp[]='epicPatch_'.$this->epicPatch('inEpicPatch');

                    break;

                case 'commonPatch':

                    $tmp[]='commonPatch_'.$this->commonPatch('inCommonPatch');

                    break;

                case 'money':

                    $tmp[]='money_'.$this->money('inMoney');

                    break;

                case 'buyCard':

                    $tmp[]='buyCard_'.$this->buyCard('inBuyCard');

                    break;

                case 'diamond':

                    $tmp[]='diamond_'.$this->diamond('inDiamond');

                    break;
            }
        }

        $this->wish=$tmp;

        return $this;
    }

    //获取uid的幸运值
    public function getLuckNum()
    {
        $luck=(int)Redis::connection('UserInfo')->hget($this->uid,'FoodMapLuckNum') > 10 ? (int)Redis::connection('UserInfo')->hget($this->uid,'FoodMapLuckNum') : 10;

        return $luck > 100 ? 100 : $luck;
    }

    //清空幸运值
    public function deleteLuckNum()
    {
        return Redis::connection('UserInfo')->hset($this->uid,'FoodMapLuckNum',10);
    }

    //增加幸运值
    public function addLuckNum($num)
    {
        return Redis::connection('UserInfo')->hincrby($this->uid,'FoodMapLuckNum',$num * 5);
    }

    //销毁许愿池
    public function deleteWishPool()
    {
        $this->pool=[];

        return $this;
    }

    //创建许愿池
    public function createWishPool()
    {
        $this->deleteWishPool()
            ->epicPatch('init')
            ->commonPatch('init')
            ->money('init')
            ->buyCard('init')
            ->diamond('init');

        shuffle($this->pool);

        return $this->pool;
    }

    //史诗碎片
    public function epicPatch($type='init')
    {
        if ($type==='inEpicPatch')
        {
            return $this->choseEpicPatch();
        }

        //$luckNum=(int)Redis::connection('UserInfo')->hget($this->uid,'FoodMapLuckNum');
        //$luckNum=substr($luckNum,0,1);
        $luckNum=0;//不加幸运值了

        for ($i=1;$i<=$this->rate['epicPatch']+$luckNum;$i++)
        {
            $this->pool[]='epicPatch';
        }

        return $this;
    }

    //普通碎片
    public function commonPatch($type='init')
    {
        if ($type==='inCommonPatch')
        {
            return $this->choseCommonPatch();
        }

        //$luckNum=(int)Redis::connection('UserInfo')->hget($this->uid,'FoodMapLuckNum');
        //$luckNum=substr($luckNum,0,1);
        $luckNum=0;

        for ($i=1;$i<=$this->rate['commonPatch']-$luckNum;$i++)
        {
            $this->pool[]='commonPatch';
        }

        return $this;
    }

    //地球币
    public function money($type='init')
    {
        if ($type==='inMoney')
        {
            $tmp=[];

            //普通
            for ($i=1;$i<=$this->rate_in['money'][0];$i++)
            {
                $tmp[]=mt_rand(70,200);
            }

            //最高
            for ($i=1;$i<=$this->rate_in['money'][1];$i++)
            {
                $tmp[]=999;
            }

            shuffle($tmp);

            //最大key
            $key=array_sum($this->rate_in['money']) - 1;

            $tar=mt_rand(0,$key);

            return $tmp[$tar];
        }

        for ($i=1;$i<=$this->rate['money'];$i++)
        {
            $this->pool[]='money';
        }

        return $this;
    }

    //购地卡
    public function buyCard($type='init')
    {
        if ($type==='inBuyCard')
        {
            $tmp=[];

            //普通
            for ($i=1;$i<=$this->rate_in['buyCard'][0];$i++)
            {
                $tmp[]=mt_rand(1,2);
            }

            //最高
            for ($i=1;$i<=$this->rate_in['buyCard'][1];$i++)
            {
                $tmp[]=5;
            }

            shuffle($tmp);

            //最大key
            $key=array_sum($this->rate_in['buyCard']) - 1;

            $tar=mt_rand(0,$key);

            return $tmp[$tar];
        }

        for ($i=1;$i<=$this->rate['buyCard'];$i++)
        {
            $this->pool[]='buyCard';
        }

        return $this;
    }

    //钻石
    public function diamond($type='init')
    {
        if ($type==='inDiamond')
        {
            $tmp=[];

            //普通
            for ($i=1;$i<=$this->rate_in['diamond'][0];$i++)
            {
                $tmp[]=mt_rand(30,88);
            }

            //最高
            for ($i=1;$i<=$this->rate_in['diamond'][1];$i++)
            {
                $tmp[]=188;
            }

            shuffle($tmp);

            //最大key
            $key=array_sum($this->rate_in['diamond']) - 1;

            $tar=mt_rand(0,$key);

            return $tmp[$tar];
        }

        for ($i=1;$i<=$this->rate['diamond'];$i++)
        {
            $this->pool[]='diamond';
        }

        return $this;
    }

    //本次获得哪个史诗碎片
    public function choseEpicPatch()
    {
        //每天固定一个

        $date=(int)date('md',time());

        $belongType='';
        foreach ($this->getTreasureType() as $one)
        {
            $belongType.='"'.$one.'",';
        }
        $belongType=rtrim($belongType,',');

        $sql="select * from patch where quality in ('蓝','紫','橙') and belongType in ({$belongType}) order by rand({$date}) limit 1";

        $res=Cache::remember('choseEpicPatch',1,function () use ($sql)
        {
            return DB::connection($this->db)->select($sql);
        });

        return current($res)->subject;
    }

    //本次获得哪个普通碎片
    public function choseCommonPatch()
    {
        //先做各个等级碎片的数组
        $all=Cache::remember('choseCommonPatch',1,function ()
        {
            return DB::connection($this->db)->table('patch')->whereIn('belongType',$this->getTreasureType())->get();
        });

        $white=$green=$blue=$purple=$orange=[];

        foreach ($all as $one)
        {
            switch ($one->quality)
            {
                case '白':
                    $white[]=jsonDecode(jsonEncode($one));
                    break;
                case '绿':
                    $green[]=jsonDecode(jsonEncode($one));
                    break;
                case '蓝':
                    $blue[]=jsonDecode(jsonEncode($one));
                    break;
                case '紫':
                    $purple[]=jsonDecode(jsonEncode($one));
                    break;
                case '橙':
                    $orange[]=jsonDecode(jsonEncode($one));
                    break;
            }
        }

        shuffle($white);
        shuffle($green);
        shuffle($blue);
        shuffle($purple);
        shuffle($orange);

        $tmp=[];

        //白
        for ($i=1;$i<=$this->rate_in['commonPatch'][0];$i++)
        {
            $tmp[]=$white[array_rand($white,1)];
        }

        //绿
        for ($i=1;$i<=$this->rate_in['commonPatch'][1];$i++)
        {
            $tmp[]=$green[array_rand($green,1)];
        }

        //蓝
        for ($i=1;$i<=$this->rate_in['commonPatch'][2];$i++)
        {
            $tmp[]=$blue[array_rand($blue,1)];
        }

        //紫
        for ($i=1;$i<=$this->rate_in['commonPatch'][3];$i++)
        {
            $tmp[]=$purple[array_rand($purple,1)];
        }

        //橙
        for ($i=1;$i<=$this->rate_in['commonPatch'][4];$i++)
        {
            $tmp[]=$orange[array_rand($orange,1)];
        }

        shuffle($tmp);

        //随机出去一个名字
        $patchName=$tmp[mt_rand(0,count($tmp)-1)]['subject'];

        return $patchName;
    }

    //建表
    public function createTable($type)
    {
        if ($type=='') return true;

        switch ($type)
        {
            case 'patch':

                //碎片信息

                if (!Schema::connection($this->db)->hasTable($type))
                {
                    Schema::connection($this->db)->create($type, function (Blueprint $table)
                    {
                        $table->increments('id')->unsigned()->comment('主键');
                        $table->string('subject','50')->comment('碎片名');
                        $table->string('place','10')->comment('碎片位置');
                        $table->string('quality','50')->comment('碎片品质');
                        $table->string('belongType','50')->comment('所属类别');
                        $table->string('belongCity','50')->comment('所属城市');
                        $table->engine = 'InnoDB';
                    });
                }

                break;

            case 'userPatch':

                //用户有哪些碎片，并有多少个

                if (!Schema::connection($this->db)->hasTable($type))
                {
                    Schema::connection($this->db)->create($type, function (Blueprint $table)
                    {
                        $table->integer('uid')->unsigned()->comment('用户主键');
                        $table->integer('pid')->unsigned()->comment('碎片主键');
                        $table->integer('num')->unsigned()->comment('碎片个数');
                        $table->string('belongType','50')->comment('所属类别');
                        $table->primary(['uid','pid']);
                        $table->timestamps();
                        $table->engine = 'InnoDB';
                    });
                }

                break;

            case 'userSuccess':

                //用户合成成功了哪些

                if (!Schema::connection($this->db)->hasTable($type))
                {
                    Schema::connection($this->db)->create($type, function (Blueprint $table)
                    {
                        $table->increments('id')->unsigned()->comment('主键');
                        $table->integer('uid')->unsigned()->comment('用户主键')->index();
                        $table->string('subject','50')->comment('菜名');
                        $table->string('belongType','50')->comment('所属类别');
                        $table->tinyInteger('isShow')->unsigned()->default(0)->comment('是否已展示');
                        $table->timestamps();
                        $table->engine = 'InnoDB';
                    });
                }

                break;

            case 'auctionHouse':

                //拍卖行

                if (!Schema::connection($this->db)->hasTable($type))
                {
                    Schema::connection($this->db)->create($type, function (Blueprint $table)
                    {
                        $table->increments('id')->unsigned()->comment('主键');
                        $table->integer('uid')->unsigned()->comment('用户主键')->index();
                        $table->integer('pid')->unsigned()->comment('碎片主键')->index();
                        $table->integer('expireTime')->unsigned()->comment('过期时间')->index();
                        $table->integer('money')->unsigned()->comment('卖多少钱');
                        $table->integer('diamond')->unsigned()->comment('卖多少钻石');
                        $table->integer('num')->unsigned()->comment('卖几个');
                        $table->tinyInteger('status')->unsigned()->comment('1正在卖，2被买走，3被下架，4被定时任务刷回');
                        $table->timestamps();
                        $table->index('created_at');
                        $table->engine = 'InnoDB';
                    });
                }

                break;

            case 'userGetPatchByWay':

                //用户获得碎片途径
                //今天通过那个途径，获取了几个碎片

                if (!Schema::connection($this->db)->hasTable($type))
                {
                    Schema::connection($this->db)->create($type, function (Blueprint $table)
                    {
                        $table->integer('uid')->unsigned()->comment('用户主键');
                        $table->integer('way')->unsigned()->comment('途径编号');
                        $table->integer('date')->unsigned()->comment('Ymd');
                        $table->integer('num')->unsigned()->comment('获取个数');
                        $table->primary(['uid','way','date']);
                        $table->timestamps();
                        $table->engine = 'InnoDB';
                    });
                }

                break;
        }

        return true;
    }


}
