<?php

namespace App\Http\Controllers\admin;

use App\Model\Admin\SystemMessageModel;
use App\Model\GridModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminSysController extends AdminBaseController
{
    //首页
    public function index()
    {
        return true;
    }

    //ajax
    public function sysAjax(Request $request)
    {
        switch ($request->type)
        {
            case 'create_sys_msg':

                $this->createTable();

                $myType=(int)trim($request->myType);
                $myContent=filter4(trim($request->myContent));

                if ($myContent=='') return ['error'=>'1'];

                //上升
                if ($myType===1)
                {
                    return $this->myType1($request);
                }

                //下降
                if ($myType===2)
                {
                    return $this->myType2($request);
                }

                //限制
                if ($myType===3)
                {
                    return $this->myType3($request);
                }

                //解除限制
                if ($myType===4)
                {
                    return $this->myType4($request);
                }

                //其他
                if ($myType===5)
                {
                    return $this->myType5($request);
                }

                break;
        }
    }

    //mytype=1 上升
    public function myType1($request)
    {
        $myContent=filter4(trim($request->myContent));
        $myNum=(int)trim($request->myNum);

        $myRange=(int)trim($request->myRange);

        if ($myRange===1)
        {
            //全部
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            $arr=
                [
                    'myContent'=>$myContent,
                    'myType'=>(int)trim($request->myType),
                    'myRange'=>$myRange,
                    'myNum'=>$myNum,
                    'execTime'=>$myExecTime,
                    'exec'=>0
                ];

            SystemMessageModel::create($arr);

            return ['error'=>'0'];
        }

        if ($myRange===2)
        {
            //部分
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            //需要计算都哪些格子要改了
            $myStart='';
            $myStop='';
            foreach ($request->changeMyObj as $one)
            {
                if ($one['name']=='myStart')
                {
                    $myStart=$one['value'];
                }

                if ($one['name']=='myStop')
                {
                    $myStop=$one['value'];
                }
            }

            if ($myStart=='' || $myStop=='') return ['error'=>'1'];
            if (GridModel::whereIn('name',[$myStart,$myStop])->count()!=2) return ['error'=>'1'];

            //计算开始

            return ['error'=>'1'];
        }

        if ($myRange===3)
        {
            //个别
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            //拿到所有格子名称
            $girdName=[];
            foreach ($request->changeMyObj as $oneGirdName)
            {
                if ($oneGirdName['name']=='myGridName')
                {
                    $girdName[]=$oneGirdName['value'];
                }
            }

            $girdName=array_filter($girdName);

            if (empty($girdName)) return ['error'=>'1'];

            foreach ($girdName as &$oneGirdName)
            {
                $oneGirdName=strtolower($oneGirdName);
            }
            unset($oneGirdName);

            //开始计算范围
            $json=json_encode($girdName);

            $arr=
                [
                    'myContent'=>$myContent,
                    'myType'=>(int)trim($request->myType),
                    'myRange'=>$myRange,
                    'range'=>$json,
                    'myNum'=>$myNum,
                    'execTime'=>$myExecTime,
                    'exec'=>0
                ];

            SystemMessageModel::create($arr);

            return ['error'=>'0'];
        }

        return ['error'=>'1'];
    }

    //mytype=2 下降
    public function myType2($request)
    {
        $myContent=filter4(trim($request->myContent));
        $myNum=(int)trim($request->myNum);

        $myRange=(int)trim($request->myRange);

        if ($myRange===1)
        {
            //全部
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            $arr=
                [
                    'myContent'=>$myContent,
                    'myType'=>(int)trim($request->myType),
                    'myRange'=>$myRange,
                    'myNum'=>$myNum,
                    'execTime'=>$myExecTime,
                    'exec'=>0
                ];

            SystemMessageModel::create($arr);

            return ['error'=>'0'];
        }

        if ($myRange===2)
        {
            //部分
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            //需要计算都哪些格子要改了
            $myStart='';
            $myStop='';
            foreach ($request->changeMyObj as $one)
            {
                if ($one['name']=='myStart')
                {
                    $myStart=$one['value'];
                }

                if ($one['name']=='myStop')
                {
                    $myStop=$one['value'];
                }
            }

            if ($myStart=='' || $myStop=='') return ['error'=>'1'];
            if (GridModel::whereIn('name',[$myStart,$myStop])->count()!=2) return ['error'=>'1'];

            //计算开始

            return ['error'=>'1'];
        }

        if ($myRange===3)
        {
            //个别
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            //拿到所有格子名称
            $girdName=[];
            foreach ($request->changeMyObj as $oneGirdName)
            {
                if ($oneGirdName['name']=='myGridName')
                {
                    $girdName[]=$oneGirdName['value'];
                }
            }

            $girdName=array_filter($girdName);

            if (empty($girdName)) return ['error'=>'1'];

            foreach ($girdName as &$oneGirdName)
            {
                $oneGirdName=strtolower($oneGirdName);
            }
            unset($oneGirdName);

            //开始计算范围
            $json=json_encode($girdName);

            $arr=
                [
                    'myContent'=>$myContent,
                    'myType'=>(int)trim($request->myType),
                    'myRange'=>$myRange,
                    'range'=>$json,
                    'myNum'=>$myNum,
                    'execTime'=>$myExecTime,
                    'exec'=>0
                ];

            SystemMessageModel::create($arr);

            return ['error'=>'0'];
        }

        return ['error'=>'1'];
    }

    //mytype=3 限制
    public function myType3($request)
    {
        $myContent=filter4(trim($request->myContent));
        $myNum=(int)trim($request->myNum);

        $myRange=(int)trim($request->myRange);

        if ($myRange===1)
        {
            //全部
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            $arr=
                [
                    'myContent'=>$myContent,
                    'myType'=>(int)trim($request->myType),
                    'myRange'=>$myRange,
                    'myNum'=>$myNum,
                    'execTime'=>$myExecTime,
                    'exec'=>0
                ];

            SystemMessageModel::create($arr);

            return ['error'=>'0'];
        }

        if ($myRange===2)
        {
            //部分
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            //需要计算都哪些格子要改了
            $myStart='';
            $myStop='';
            foreach ($request->changeMyObj as $one)
            {
                if ($one['name']=='myStart')
                {
                    $myStart=$one['value'];
                }

                if ($one['name']=='myStop')
                {
                    $myStop=$one['value'];
                }
            }

            if ($myStart=='' || $myStop=='') return ['error'=>'1'];
            if (GridModel::whereIn('name',[$myStart,$myStop])->count()!=2) return ['error'=>'1'];

            //计算开始

            return ['error'=>'1'];
        }

        if ($myRange===3)
        {
            //个别
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            //拿到所有格子名称
            $girdName=[];
            foreach ($request->changeMyObj as $oneGirdName)
            {
                if ($oneGirdName['name']=='myGridName')
                {
                    $girdName[]=$oneGirdName['value'];
                }
            }

            $girdName=array_filter($girdName);

            if (empty($girdName)) return ['error'=>'1'];

            foreach ($girdName as &$oneGirdName)
            {
                $oneGirdName=strtolower($oneGirdName);
            }
            unset($oneGirdName);

            //开始计算范围
            $json=json_encode($girdName);

            $arr=
                [
                    'myContent'=>$myContent,
                    'myType'=>(int)trim($request->myType),
                    'myRange'=>$myRange,
                    'range'=>$json,
                    'myNum'=>$myNum,
                    'execTime'=>$myExecTime,
                    'exec'=>0
                ];

            SystemMessageModel::create($arr);

            return ['error'=>'0'];
        }

        return ['error'=>'1'];
    }

    //mytype=4 解除限制
    public function myType4($request)
    {
        $myContent=filter4(trim($request->myContent));
        $myNum=(int)trim($request->myNum);

        $myRange=(int)trim($request->myRange);

        if ($myRange===1)
        {
            //全部
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            $arr=
                [
                    'myContent'=>$myContent,
                    'myType'=>(int)trim($request->myType),
                    'myRange'=>$myRange,
                    'myNum'=>$myNum,
                    'execTime'=>$myExecTime,
                    'exec'=>0
                ];

            SystemMessageModel::create($arr);

            return ['error'=>'0'];
        }

        if ($myRange===2)
        {
            //部分
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            //需要计算都哪些格子要改了
            $myStart='';
            $myStop='';
            foreach ($request->changeMyObj as $one)
            {
                if ($one['name']=='myStart')
                {
                    $myStart=$one['value'];
                }

                if ($one['name']=='myStop')
                {
                    $myStop=$one['value'];
                }
            }

            if ($myStart=='' || $myStop=='') return ['error'=>'1'];
            if (GridModel::whereIn('name',[$myStart,$myStop])->count()!=2) return ['error'=>'1'];

            //计算开始

            return ['error'=>'1'];
        }

        if ($myRange===3)
        {
            //个别
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            //拿到所有格子名称
            $girdName=[];
            foreach ($request->changeMyObj as $oneGirdName)
            {
                if ($oneGirdName['name']=='myGridName')
                {
                    $girdName[]=$oneGirdName['value'];
                }
            }

            $girdName=array_filter($girdName);

            if (empty($girdName)) return ['error'=>'1'];

            foreach ($girdName as &$oneGirdName)
            {
                $oneGirdName=strtolower($oneGirdName);
            }
            unset($oneGirdName);

            //开始计算范围
            $json=json_encode($girdName);

            $arr=
                [
                    'myContent'=>$myContent,
                    'myType'=>(int)trim($request->myType),
                    'myRange'=>$myRange,
                    'range'=>$json,
                    'myNum'=>$myNum,
                    'execTime'=>$myExecTime,
                    'exec'=>0
                ];

            SystemMessageModel::create($arr);

            return ['error'=>'0'];
        }

        return ['error'=>'1'];
    }

    //mytype=5 其他
    public function myType5($request)
    {
        $myContent=filter4(trim($request->myContent));

        $myExecTime=$this->parseTime((int)trim($request->myExecTime));

        $arr=
            [
                'myContent'=>$myContent,
                'myType'=>(int)trim($request->myType),
                'execTime'=>$myExecTime,
                'exec'=>0
            ];

        SystemMessageModel::create($arr);

        return ['error'=>'0'];
    }

    //建表
    public function createTable()
    {
        if (!Schema::connection('masterDB')->hasTable('sys_msg'))
        {
            Schema::connection('masterDB')->create('sys_msg', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->text('myContent')->comment('通知内容');
                $table->integer('myType')->unsigned()->nullable()->comment('上升下降限制解除限制');
                $table->integer('myNum')->unsigned()->nullable()->comment('数值');
                $table->integer('myRange')->unsigned()->nullable()->comment('影响范围');
                $table->text('range')->nullable()->comment('具体的哪些范围');
                $table->integer('exec')->unsigned()->comment('是否执行了');
                $table->integer('execTime')->unsigned()->comment('执行时间')->index();
                $table->timestamps();
                $table->index('created_at');

            });
        }

        return true;
    }

    //换算执行时间
    public function parseTime($time)
    {
        //1是立即执行，artisan可能有延迟，实际上加两分钟
        if ($time===1)
        {
            return time() + 120;
        }

        //2是3小时以后
        if ($time===2)
        {
            return time() + 3600 * 3;
        }

        //3是6小时以后
        if ($time===3)
        {
            return time() + 3600 * 6;
        }

        //4是9小时以后
        if ($time===4)
        {
            return time() + 3600 * 9;
        }

        //啥没选就默认立即
        return time() + 120;
    }

    //公告首页
    public function sysCreate()
    {
        return view('admin.sys.sys_create');
    }

    //创建一个公告
    public function sysCreateMsg(Request $request)
    {
        return view('admin.sys.sys_create_msg');
    }












}