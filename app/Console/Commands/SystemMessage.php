<?php

namespace App\Console\Commands;

use App\Http\Controllers\admin\AdminSysController;
use App\Model\Admin\SystemMessageModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SystemMessage extends Command
{
    protected $signature = 'Admin:SystemMessage';

    protected $description = '后台写的系统消息';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //建表
        (new AdminSysController())->createTable();

        //每次只处理10个
        for ($i=1;$i<=10;$i++)
        {
            //取出没执行的对象
            $res=SystemMessageModel::where('exec',0)->where('execTime','>=',time())->orderBy('execTime','asc')->first();

            //没有符合的
            if ($res==null) break;

            //如果第一个就离执行时间很远
            //if (is_numeric($res->execTime) && $res->execTime - time() >= 90) break;

            switch ($res->myType)
            {
                case '1':

                    //处理 上升
                    $this->type1($res);

                    break;

                case '2':

                    //处理 下降
                    $this->type2($res);

                    break;

                case '3':

                    //处理 限制
                    $this->type3($res);

                    break;

                case '4':

                    //处理 解除限制
                    $this->type4($res);

                    break;

                case '5':

                    //处理 其他
                    $this->type5($res);

                    break;

                default:

                    break;
            }

            //设置成已经执行完毕
            $this->mySave($res);
        }
    }

    public function type1($model)
    {
        //看myrange和range
        //myrange有全部，部分和个别，分别是1，2，3

        $myRange=(int)$model->myRange;
        $num=$model->myNum / 100;

        if ($myRange===1)
        {
            $sql="update grid set price=round(price + price * {$num})";

            DB::connection('masterDB')->update($sql);
        }

        if ($myRange===2)
        {

        }

        if ($myRange===3)
        {
            if ($model->range=='') return true;

            $in=json_decode($model->range,true);

            if (!is_array($in) || empty($in)) return true;

            //拼接字符串
            $star='(';
            $stop=')';

            foreach ($in as $oneGridName)
            {
                $star.="'".$oneGridName."'";
                $star.=',';
            }

            $star=rtrim($star,',');
            $star.=$stop;

            $in=$star;

            $sql="update grid set price=round(price + price * {$num}) where `name` in {$in}";

            DB::connection('masterDB')->update($sql);
        }
    }

    public function type2($model)
    {
        //看myrange和range
        //myrange有全部，部分和个别，分别是1，2，3

        $myRange=(int)$model->myRange;
        $num=$model->myNum / 100;

        if ($myRange===1)
        {
            $sql="update grid set price=case when round(price - price * {$num}) < 10 then 10 else round(price - price * {$num}) end";

            DB::connection('masterDB')->update($sql);
        }

        if ($myRange===2)
        {

        }

        if ($myRange===3)
        {
            if ($model->range=='') return true;

            $in=json_decode($model->range,true);

            if (!is_array($in) || empty($in)) return true;

            //拼接字符串
            $star='(';
            $stop=')';

            foreach ($in as $oneGridName)
            {
                $star.="'".$oneGridName."'";
                $star.=',';
            }

            $star=rtrim($star,',');
            $star.=$stop;

            $in=$star;

            $sql="update grid set price=case when round(price - price * {$num}) < 10 then 10 else round(price - price * {$num}) end where `name` in {$in}";

            DB::connection('masterDB')->update($sql);
        }
    }

    public function type3($model)
    {
        //看myrange和range
        //myrange有全部，部分和个别，分别是1，2，3

        $myRange=(int)$model->myRange;
        $num=$model->myNum / 100;

        if ($myRange===1)
        {
            $sql="update grid set showGrid=0";

            DB::connection('masterDB')->update($sql);
        }

        if ($myRange===2)
        {

        }

        if ($myRange===3)
        {
            if ($model->range=='') return true;

            $in=json_decode($model->range,true);

            if (!is_array($in) || empty($in)) return true;

            //拼接字符串
            $star='(';
            $stop=')';

            foreach ($in as $oneGridName)
            {
                $star.="'".$oneGridName."'";
                $star.=',';
            }

            $star=rtrim($star,',');
            $star.=$stop;

            $in=$star;

            $sql="update grid set showGrid=0 where `name` in {$in}";

            DB::connection('masterDB')->update($sql);
        }
    }

    public function type4($model)
    {
        //看myrange和range
        //myrange有全部，部分和个别，分别是1，2，3

        $myRange=(int)$model->myRange;
        $num=$model->myNum / 100;

        if ($myRange===1)
        {
            $sql="update grid set showGrid=1";

            DB::connection('masterDB')->update($sql);
        }

        if ($myRange===2)
        {

        }

        if ($myRange===3)
        {
            if ($model->range=='') return true;

            $in=json_decode($model->range,true);

            if (!is_array($in) || empty($in)) return true;

            //拼接字符串
            $star='(';
            $stop=')';

            foreach ($in as $oneGridName)
            {
                $star.="'".$oneGridName."'";
                $star.=',';
            }

            $star=rtrim($star,',');
            $star.=$stop;

            $in=$star;

            $sql="update grid set showGrid=1 where `name` in {$in}";

            DB::connection('masterDB')->update($sql);
        }
    }

    public function type5($model)
    {
        //啥也不用干
    }

    public function mySave($model)
    {
        $model->exec=1;
        $model->save();
    }
}
