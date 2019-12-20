<?php

namespace App\Http\Controllers\TanSuoShiJie;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Server\PayBase;
use App\Model\RankListModel;
use App\Model\Tssj\AssociatedAccountModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class AboutUserController extends Controller
{
    public $aboutTssj='aboutTssj';

    public $tssjold='tssj_old';

    public function createTable($tableName)
    {
        switch ($tableName)
        {
            case 'AssociatedAccount':

                //关联账号
                if (!Schema::connection($this->aboutTssj)->hasTable($tableName))
                {
                    Schema::connection($this->aboutTssj)->create($tableName, function (Blueprint $table) {

                        $table->integer('uid')->unsigned()->comment('用户主键');
                        $table->bigInteger('phone')->unsigned()->nullable()->comment('手机号');
                        $table->tinyInteger('game')->unsigned()->comment('游戏uid');
                        $table->tinyInteger('fog')->unsigned()->comment('迷雾uid');
                        $table->string('from',20)->nullable()->comment('来源');
                        $table->string('uniqueid',60)->nullable()->comment('第三方注册唯一标识值');
                        $table->string('unionid',60)->nullable()->comment('多应用唯一ID值');
                        $table->timestamps();
                        $table->primary('uid');
                        $table->index('phone');

                    });

                    //添加分区
                    DB::connection($this->aboutTssj)->statement("Alter table {$tableName} partition by linear key(`uid`) partitions 8");
                }

                return true;

                break;
        }
    }

    //返回正确的uid
    public function selectCorrectUid(Request $request)
    {
        //$y=Carbon::now()->year;
        //$m=Carbon::now()->month;

        //if ($y==2019 && $m==9) $this->createTable('AssociatedAccount');

        $uid=(int)$request->uid;

        return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>$uid]);

        //先看看在新关联表中是否存在
        //$checkExist=AssociatedAccountModel::where('uid',$uid)->first();

        //存在的情况
        //if ($checkExist!=null)
        //{
        //    //查主账号uid后返回
        //    $resUid=AssociatedAccountModel::where(['phone'=>$checkExist->phone,'game'=>1])->first();

        //    if ($resUid!=null) return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>$resUid->uid]);

        //    //如果为空，说明频繁绑定，绑定乱了
        //}

        //不存在的情况
        //先从旧库看看有没有关联账号，账号都是通过手机号关联的
        $getPhone=DB::connection($this->tssjold)->table('tssj_member')->where('userid',$uid)->first();

        //这个uid不存在
        if ($getPhone==null) return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>null]);

        //得到手机号
        $phone=trim($getPhone->phone);

        //找不到传入uid所绑定的手机号，第三方账号解绑状态，就是没有手机号
        if ($phone=='') return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>$getPhone->userid]);

        //取出该手机的所有关联账号
        $checkAssociatedAccount=DB::connection($this->tssjold)->table('tssj_member')->where('phone',$phone)->get(['userid'])->toArray();

        //拿到所有关联的uid，确定一下我这边的主账号
        foreach ($checkAssociatedAccount as $oneUid)
        {
            //$userInfo=DB::connection($this->tssjold)->table('tssj_member')->where('userid',$oneUid->userid)->first();

            //$userTotleAssets=RankListModel::where('uid',$userInfo->userid)->first();

            //$userTotleAssets==null ?
            //    $tmp[]=['uid'=>$userInfo->userid,'from'=>$userInfo->origin,'uniqueid'=>$userInfo->uniqueid,'unionid'=>$userInfo->unionid,'totleAssets'=>0] :
            //    $tmp[]=['uid'=>$userInfo->userid,'from'=>$userInfo->origin,'uniqueid'=>$userInfo->uniqueid,'unionid'=>$userInfo->unionid,'totleAssets'=>(int)$userTotleAssets->totleAssets];

            $userTotleAssets=RankListModel::where('uid',$oneUid->userid)->first();

            $userTotleAssets==null ?
                $tmp[]=['uid'=>$oneUid->userid,'totleAssets'=>0] :
                $tmp[]=['uid'=>$oneUid->userid,'totleAssets'=>(int)$userTotleAssets->totleAssets];
        }

        //拿到uid和总资产，下面添加到新账户关联表中
        $tmp=arraySort1($tmp,['desc','totleAssets']);

        return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>current($tmp)['uid']]);

        //第一个作为主账号
        //$master=1;

        //foreach ($tmp as $one)
        //{
        //    $master===1 ? $readyInsert=['fog'=>1,'game'=>1] : $readyInsert=['fog'=>0,'game'=>0];
        //    $readyInsert['phone']=$phone;
        //    $readyInsert['from']=trim($one['from']);
        //    $readyInsert['uniqueid']=trim($one['uniqueid']);
        //    $readyInsert['unionid']=trim($one['unionid']);

        //    AssociatedAccountModel::updateOrCreate(['uid'=>trim($one['uid'])],$readyInsert);

        //    $master++;
        //}

        //return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>AssociatedAccountModel::where(['phone'=>$phone,'game'=>1])->first()->uid]);
    }

    //tssj用户修改手机后，修改关联表中的手机号，解绑，绑定
    public function modifyPhoneNotice(Request $request)
    {
        //修改手机的用户
        $uid=(int)$request->uid;

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

}
