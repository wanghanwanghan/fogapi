<?php

namespace App\Http\Controllers\admin;

use App\Model\Admin\SystemMessageModel;
use App\Model\GridModel;
use Carbon\Carbon;
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
        $this->createTable();

        switch ($request->type)
        {
            case 'create_sys_msg_for_grid':

                $myType=(int)trim($request->myType);
                $mySubject=filter4(trim($request->mySubject));
                $myContent=filter4(trim($request->myContent));

                if ($myContent=='' || $mySubject=='') return ['error'=>'1'];

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

            case 'create_sys_msg_for_user':

                $myType=(int)trim($request->myType);
                $mySubject=filter4(trim($request->mySubject));
                $myContent=filter4(trim($request->myContent));

                if ($myContent=='' || $mySubject=='') return ['error'=>'1'];

                if ($myType===1)
                {
                    //加钱
                    return $this->myTypeForUser1($request);
                }

                break;
        }
    }

    //加钱
    public function myTypeForUser1($request)
    {
        $mySubject=filter4(trim($request->mySubject));
        $myContent=filter4(trim($request->myContent));
        $myNum=(int)trim($request->myNum);

        $myRange=(int)trim($request->myRange);

        if ($myRange===1)
        {
            //全部
            $myExecTime=$this->parseTime((int)trim($request->myExecTime),2);

            $arr=
                [
                    'mySubject'=>$mySubject,
                    'myObj'=>2,
                    'myContent'=>$myContent,
                    'myType'=>$request->myType,
                    'myRange'=>$myRange,
                    'myNum'=>$myNum,
                    'execTime'=>$myExecTime,
                    'exec'=>0
                ];

            SystemMessageModel::create($arr);

            return ['error'=>'0'];
        }
    }

    //mytype=1 上升
    public function myType1($request)
    {
        $mySubject=filter4(trim($request->mySubject));
        $myContent=filter4(trim($request->myContent));
        $myNum=(int)trim($request->myNum);

        $myRange=(int)trim($request->myRange);

        if ($myRange===1)
        {
            //全部
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            $arr=
                [
                    'mySubject'=>$mySubject,
                    'myObj'=>1,
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
            $json=jsonEncode($girdName);

            $arr=
                [
                    'mySubject'=>$mySubject,
                    'myObj'=>1,
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
        $mySubject=filter4(trim($request->mySubject));
        $myContent=filter4(trim($request->myContent));
        $myNum=(int)trim($request->myNum);

        $myRange=(int)trim($request->myRange);

        if ($myRange===1)
        {
            //全部
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            $arr=
                [
                    'mySubject'=>$mySubject,
                    'myObj'=>1,
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
            $json=jsonEncode($girdName);

            $arr=
                [
                    'mySubject'=>$mySubject,
                    'myObj'=>1,
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
        $mySubject=filter4(trim($request->mySubject));
        $myContent=filter4(trim($request->myContent));
        $myNum=(int)trim($request->myNum);

        $myRange=(int)trim($request->myRange);

        if ($myRange===1)
        {
            //全部
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            $arr=
                [
                    'mySubject'=>$mySubject,
                    'myObj'=>1,
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
            $json=jsonEncode($girdName);

            $arr=
                [
                    'mySubject'=>$mySubject,
                    'myObj'=>1,
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
        $mySubject=filter4(trim($request->mySubject));
        $myContent=filter4(trim($request->myContent));
        $myNum=(int)trim($request->myNum);

        $myRange=(int)trim($request->myRange);

        if ($myRange===1)
        {
            //全部
            $myExecTime=$this->parseTime((int)trim($request->myExecTime));

            $arr=
                [
                    'mySubject'=>$mySubject,
                    'myObj'=>1,
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
            $json=jsonEncode($girdName);

            $arr=
                [
                    'mySubject'=>$mySubject,
                    'myObj'=>1,
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
        $mySubject=filter4(trim($request->mySubject));
        $myContent=filter4(trim($request->myContent));

        $myExecTime=$this->parseTime((int)trim($request->myExecTime));

        $arr=
            [
                'mySubject'=>$mySubject,
                'myObj'=>1,
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
                $table->string('mySubject',50)->nullable()->comment('通知标题');
                $table->integer('myObj')->unsigned()->nullable()->comment('针对人还是格子');
                $table->text('myContent')->nullable()->comment('通知内容');
                $table->integer('myType')->unsigned()->nullable()->comment('上升下降限制解除限制');
                $table->integer('myNum')->unsigned()->nullable()->comment('数值');
                $table->integer('myRange')->unsigned()->nullable()->comment('影响范围');
                $table->text('range')->nullable()->comment('具体的哪些范围');
                $table->integer('exec')->unsigned()->nullable()->comment('是否执行了或是否领取过期');
                $table->integer('execTime')->unsigned()->nullable()->comment('执行时间或领取截至日')->index();
                $table->timestamps();
                $table->index('created_at');

            });
        }

        return true;
    }

    //换算执行时间
    public function parseTime($time,$obj=1)
    {
        if ($obj===1)
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

        if ($obj===2)
        {
            //针对人
            //领取截止日期是今天
            if ($time===1)
            {
                return Carbon::now()->endOfDay()->timestamp;
            }

            //领取截止日期是3天后
            if ($time===2)
            {
                return Carbon::now()->endOfDay()->addDays(3)->timestamp;
            }

            //领取截止日期是9天后
            if ($time===3)
            {
                return Carbon::now()->endOfDay()->addDays(9)->timestamp;
            }

            //领取截止日期是27天后
            if ($time===4)
            {
                return Carbon::now()->endOfDay()->addDays(27)->timestamp;
            }

            //啥没选就默认今天
            return Carbon::now()->endOfDay()->timestamp;
        }
    }

    //对格
    public function sysCreateForGrid()
    {
        //取出所有信息
        $res=SystemMessageModel::where('myObj',1)->orderBy('id','desc')->get();

        foreach ($res as &$one)
        {
            mb_strlen($one->myContent) > 20 ? $one->myContent=mb_substr($one->myContent,0,20).'...' : null;

            $one->myRange==1 ? $one->myRange='全部' : null;
            $one->myRange==2 ? $one->myRange='部分' : null;
            $one->myRange==3 ? $one->myRange='个别' : null;

            $one->myType==1 ? $one->myType='上升' : null;
            $one->myType==2 ? $one->myType='下降' : null;
            $one->myType==3 ? $one->myType='限制' : null;
            $one->myType==4 ? $one->myType='解除限制' : null;
            $one->myType==5 ? $one->myType='其他' : null;

            $one->exec==1 ? $one->exec='执行完毕' : $one->exec='未执行';

            $one->execTime=date('Y-m-d H:i:s',$one->execTime);
        }
        unset($one);

        return view('admin.sys.sys_create_for_grid')->with(['res'=>$res]);
    }

    //对人
    public function sysCreateForUser()
    {
        //取出所有信息
        $res=SystemMessageModel::where('myObj',2)->orderBy('id','desc')->get();

        foreach ($res as &$one)
        {
            mb_strlen($one->myContent) > 20 ? $one->myContent=mb_substr($one->myContent,0,20).'...' : null;

            $one->myRange==1 ? $one->myRange='全部' : null;

            $one->myType==1 ? $one->myType='加钱' : null;

            $one->exec==1 ? $one->exec='已经截至' : $one->exec='未截至';

            $one->execTime=date('Y-m-d H:i:s',$one->execTime);
        }
        unset($one);

        return view('admin.sys.sys_create_for_user')->with(['res'=>$res]);
    }

    //详细信息
    public function sysMsgDetail($id)
    {
        if (($one=SystemMessageModel::find($id))==null) return 'no page';

        if ($one->myObj===1)
        {
            //对格的详情
            $one->myType==1 ? $one->myType='上升' : null;
            $one->myType==2 ? $one->myType='下降' : null;
            $one->myType==3 ? $one->myType='限制' : null;
            $one->myType==4 ? $one->myType='解除限制' : null;
            $one->myType==5 ? $one->myType='其他' : null;

            $one->myRange==1 ? $one->myRange='全部' : null;
            $one->myRange==2 ? $one->myRange='部分' : null;
            $one->myRange==3 ? $one->myRange='个别' : null;

            if ($one->range!='')
            {
                $res=jsonDecode($one->range);

                $one->range='';
                foreach ($res as $row)
                {
                    $one->range.=$row.',';
                }
            }
            $one->range=rtrim($one->range,',');

            $one->exec==1 ? $one->exec='执行完毕' : $one->exec='未执行';

            $one->execTime=date('Y-m-d H:i:s',$one->execTime);

            return view('admin.sys.sys_msg_detail_for_grid')->with(['res'=>$one]);

        }elseif ($one->myObj===2)
        {
            //对格的详情
            $one->myType==1 ? $one->myType='加钱' : null;

            $one->myRange==1 ? $one->myRange='全部' : null;

            $one->exec==1 ? $one->exec='已经截至' : $one->exec='未截至';

            $one->execTime=date('Y-m-d H:i:s',$one->execTime);

            //对人的详情
            return view('admin.sys.sys_msg_detail_for_user')->with(['res'=>$one]);

        }else
        {

        }
    }

    //创建一个公告
    public function sysCreateMsgForGrid(Request $request)
    {
        return view('admin.sys.sys_create_msg_for_grid');
    }

    //创建一个对人的公告
    public function sysCreateMsgForUser(Request $request)
    {
        return view('admin.sys.sys_create_msg_for_user');
    }










}