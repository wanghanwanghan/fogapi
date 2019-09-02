<?php

namespace App\Http\Controllers\TanSuoShiJie;

use App\Http\Controllers\Controller;
use App\Model\RankListModel;
use App\Model\Tssj\AssociatedAccountModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
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
                        $table->bigInteger('phone')->unsigned()->comment('手机号');
                        $table->tinyInteger('game')->unsigned()->comment('游戏uid');
                        $table->tinyInteger('fog')->unsigned()->comment('迷雾uid');
                        $table->string('from',20)->comment('来源');
                        $table->string('uniqueid',60)->comment('第三方注册唯一标识值');
                        $table->string('unionid',60)->comment('多应用唯一ID值');
                        $table->timestamps();
                        $table->primary(['uid','phone']);
                        $table->index('phone');

                    });

                    //添加分区
                    DB::connection($this->aboutTssj)->statement("Alter table {$tableName} partition by linear key(`phone`) partitions 8");
                }

                return true;

                break;
        }
    }

    //返回正确的uid
    public function selectCorrectUid(Request $request)
    {
        $ym=Carbon::now()->year.Carbon::now()->month;

        if ($ym==201909 || $ym==20199) $this->createTable('AssociatedAccount');

        $uid=(int)$request->uid;

        //如果已经添加进来了，就不查别的数据库了
        $new=AssociatedAccountModel::where('uid',$uid)->first();

        //找到数据，直接返回
        if ($new!=null) return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>AssociatedAccountModel::where(['phone'=>$new->phone,'fog'=>1,'game'=>1])->first()->uid]);

        //先通过uid拿到手机号码
        $res=DB::connection($this->tssjold)->table('tssj_member')->where('userid',$uid)->first();

        if ($res==null) return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>null]);

        $phone=trim($res->phone);

        //找不到传入uid所绑定的手机号
        if ($phone=='') return response()->json(['resCode'=>Config::get('resCode.680'),'uid'=>null]);

        //如果存在uid，就去旧关联表里找其他的uid
        $res=DB::connection($this->tssjold)->table('tssj_member_connection')->where('phone',$phone)->get(['userid'])->toArray();

        if (empty($res)) return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>null]);

        //拿到所有关联的uid，确定一下我这边的主账号
        foreach ($res as $oneUid)
        {
            if ((int)$oneUid->userid <= 0) continue;

            $userInfo=DB::connection($this->tssjold)->table('tssj_member')->where('userid',$oneUid->userid)->first();

            $userTotleAssets=RankListModel::where('uid',$userInfo->userid)->first();

            $userTotleAssets==null ?
                $tmp[]=['uid'=>$userInfo->userid,'from'=>$userInfo->origin,'uniqueid'=>$userInfo->uniqueid,'unionid'=>$userInfo->unionid,'totleAssets'=>0] :
                $tmp[]=['uid'=>$userInfo->userid,'from'=>$userInfo->origin,'uniqueid'=>$userInfo->uniqueid,'unionid'=>$userInfo->unionid,'totleAssets'=>(int)$userTotleAssets->totleAssets];
        }

        //拿到uid和总资产，下面添加到新账户关联表中
        $tmp=arraySort1($tmp,['desc','totleAssets']);

        //第一个作为主账号
        $master=1;
        foreach ($tmp as $one)
        {
            $master===1 ? $readyInsert=['fog'=>1,'game'=>1] : $readyInsert=['fog'=>0,'game'=>0];

            $readyInsert['uid']=trim($one['uid']);
            $readyInsert['phone']=$phone;
            $readyInsert['from']=trim($one['from']);
            $readyInsert['uniqueid']=trim($one['uniqueid']);
            $readyInsert['unionid']=trim($one['unionid']);

            if (AssociatedAccountModel::where(['uid'=>$readyInsert['uid'],'phone'=>$readyInsert['phone']])->first()==null)
            {
                AssociatedAccountModel::create($readyInsert);
            }

            $master++;
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'uid'=>AssociatedAccountModel::where(['phone'=>$phone,'fog'=>1,'game'=>1])->first()->uid]);
    }




}
