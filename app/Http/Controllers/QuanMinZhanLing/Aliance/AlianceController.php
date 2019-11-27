<?php

namespace App\Http\Controllers\QuanMinZhanLing\Aliance;

use App\Model\Aliance\AlianceGroupModel;
use App\Model\Aliance\AnnouncementModel;
use App\Model\Aliance\InviteModel;
use App\Model\GridModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AlianceController extends AlianceBaseController
{
    //是否可以进入联盟
    public function canJoin($uid)
    {
        //退出联盟，3天之内不能进

        $res=AlianceGroupModel::where('uid',$uid)->first();

        //从来没进入过联盟，直接放行
        if (!$res) return true;

        //目前已经属于某个联盟
        if ($res->alianceNum != 0) return false;

        //判断一下时间
        $time=strtotime($res->updated_at);

        return time() - $time > 86400 * 3 ? true : false;
    }

    //是否可以退联盟
    public function canExit($uid)
    {
        //进了联盟，1天之内不能退

        $res=AlianceGroupModel::where('uid',$uid)->first();

        //从来没进入过联盟，退你麻痹退
        if (!$res) return false;

        //判断一下时间
        $time=strtotime($res->updated_at);

        return time() - $time > 86400 * 1 ? true : false;
    }

    //补全联盟信息
    public function fillAlianceByNum($arr)
    {
        foreach ($arr as &$one)
        {
            if (is_object($one))
            {
                $one->alianceInfo=$this->alianceName($one->alianceNum);

            }elseif (is_array($one) && !empty($one))
            {
                $one['alianceInfo']=$this->alianceName($one['alianceNum']);

            }else
            {
                continue;
            }
        }
        unset($one);

        return $arr;
    }

    //补全用户信息
    public function fillUserInfoById($arr)
    {
        //一般就是uid，tid

        foreach ($arr as &$one)
        {
            if (is_object($one))
            {
                if (isset($one->uid))
                {
                    $username=trim(Redis::connection('UserInfo')->hget($one->uid,'name'));

                    //if ($username=='') Redis::connection('UserInfo')->hset($one->uid,'name',randomUserName());

                    $one->uName=Redis::connection('UserInfo')->hget($one->uid,'name');
                    $one->uAvatar=Redis::connection('UserInfo')->hget($one->uid,'avatar');
                }

                if (isset($one->tid))
                {
                    $username=trim(Redis::connection('UserInfo')->hget($one->tid,'name'));

                    //if ($username=='') Redis::connection('UserInfo')->hset($one->tid,'name',randomUserName());

                    $one->tName=Redis::connection('UserInfo')->hget($one->tid,'name');
                    $one->tAvatar=Redis::connection('UserInfo')->hget($one->tid,'avatar');
                }

            }elseif (is_array($one) && !empty($one))
            {
                if (isset($one['uid']))
                {
                    $username=trim(Redis::connection('UserInfo')->hget($one['uid'],'name'));

                    //if ($username=='') Redis::connection('UserInfo')->hset($one['uid'],'name',randomUserName());

                    $one['uName']=Redis::connection('UserInfo')->hget($one['uid'],'name');
                    $one['uAvatar']=Redis::connection('UserInfo')->hget($one['uid'],'avatar');
                }

                if (isset($one['tid']))
                {
                    $username=trim(Redis::connection('UserInfo')->hget($one['tid'],'name'));

                    //if ($username=='') Redis::connection('UserInfo')->hset($one['tid'],'name',randomUserName());

                    $one['tName']=Redis::connection('UserInfo')->hget($one['tid'],'name');
                    $one['tAvatar']=Redis::connection('UserInfo')->hget($one['tid'],'avatar');
                }

            }else
            {
                continue;
            }
        }
        unset($one);

        return $arr;
    }

    //入盟请帖  //寻找盟友
    public function getUserInviteList(Request $request)
    {
        $uid=$request->uid;

        switch ($request->type)
        {
            case '1':

                //入盟请帖

                //查看谁邀请了我，返回最近20个结果
                $res=DB::connection($this->db)->table('invite')
                    ->where(['tid'=>$uid,'yesOrNo'=>0])
                    ->orderBy('created_at','desc')
                    ->limit(20)->get();

                //补全信息
                $res=$this->fillAlianceByNum($res);
                $res=$this->fillUserInfoById($res);

                foreach ($res as &$one)
                {
                    $created_at=strtotime($one->created_at);

                    $one->time=formatDate($created_at);
                }
                unset($one);

                break;

            case '2':

                //寻找盟友

                //从redis中取得还没有加入到联盟的用户，随机出5个

                $keyArr=[];
                $user=[];

                for ($i=1;$i<=1000;$i++)
                {
                    $key=Redis::connection('UserInfo')->randomkey();

                    $key=explode('_',$key);

                    $key=last($key);

                    if (in_array($key,$keyArr)) continue;

                    $keyArr[]=$key;

                    //取200人就退
                    if (count($keyArr) >= 100) break;
                }

                //从100中挑选5个
                foreach ($keyArr as $one)
                {
                    if (count($user) >= 5) break;

                    $res=AlianceGroupModel::where('uid',$one)->where('alianceNum','<>',0)->first();

                    //查到了说明已经有联盟了
                    if ($res) continue;

                    $uName=trim(Redis::connection('UserInfo')->hget($one,'name'));

                    if (!$uName)
                    {
                        //给个随机名字
                        $uName=randomUserName();
                        Redis::connection('UserInfo')->hset($one,'name',$uName);
                    }

                    $uAvatar=trim(Redis::connection('UserInfo')->hget($one,'avatar'));

                    $lastLogin=mt_rand(1,3).'年前';
                    $tmp=Redis::connection('UserInfo')->hget($one,'lastLogin');

                    if ($tmp) $lastLogin=formatDate($tmp);

                    $user[]=['uid'=>$one,'uName'=>$uName,'uAvatar'=>$uAvatar,'lastLogin'=>$lastLogin];
                }

                $res=$user;

                break;

            case '3':

                $tid=$request->tid;

                $alianceNum=$request->aliance;

                InviteModel::updateOrCreate(['uid'=>$uid,'tid'=>$tid,'alianceNum'=>$alianceNum],['yesOrNo'=>0]);

                $res=[];

                break;
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }

    //加入联盟
    public function joinAliance(Request $request)
    {
        $uid=$request->uid;

        $tid=$request->tid;

        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $alianceNum=$request->aliance;

        $this->createTable('alianceGroup');
        $this->createTable('announcement');
        $this->createTable('invite');

        //判断一下是否可以进了
        //进了联盟，1天之内不能退，退出联盟，3天之内不能进

        if ($tid && is_numeric($tid) && $tid >= 1)
        {
            //别人邀请进的

            if (!$this->canJoin($tid)) return response()->json(['resCode'=>Config::get('resCode.604')]);

            AlianceGroupModel::updateOrCreate(['uid'=>$tid],['alianceNum'=>$alianceNum]);

            $uName=trim(Redis::connection('UserInfo')->hget($uid,'name'));
            $tName=trim(Redis::connection('UserInfo')->hget($tid,'name'));

            AnnouncementModel::create(['alianceNum'=>$alianceNum,'content'=>"恭喜: {$uName} 成功寻找盟友 {$tName}, 双方各得[地球币]200"]);
            AnnouncementModel::create(['alianceNum'=>$alianceNum,'content'=>"{$tName} 加入了联盟"]);

            if (Carbon::now()->format('Ymd') > 20191225)
            {
                Redis::connection('UserInfo')->hincrby($uid,'money',200);
                Redis::connection('UserInfo')->hincrby($tid,'money',200);
            }

            //修改入盟请帖表
            InviteModel::updateOrCreate(['uid'=>$uid,'tid'=>$tid,'alianceNum'=>$alianceNum],['yesOrNo'=>1]);
            InviteModel::where(['tid'=>$tid,'yesOrNo'=>0])->delete();

        }else
        {
            //自己主动进

            if (!$this->canJoin($uid)) return ['resCode'=>Config::get('resCode.604')];

            AlianceGroupModel::updateOrCreate(['uid'=>$uid],['alianceNum'=>$alianceNum]);

            $uName=trim(Redis::connection('UserInfo')->hget($uid,'name'));

            AnnouncementModel::create(['alianceNum'=>$alianceNum,'content'=>"{$uName} 加入了联盟"]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //退出联盟
    public function exitAliance(Request $request)
    {
        $uid=$request->uid;

        if (!$this->canExit($uid))return response()->json(['resCode'=>Config::get('resCode.604')]);

        $res=AlianceGroupModel::where('uid',$uid)->first();

        $uName=trim(Redis::connection('UserInfo')->hget($uid,'name'));

        //写退出公告
        AnnouncementModel::create(['alianceNum'=>$res->alianceNum,'content'=>"{$uName} 退出了联盟"]);

        $res->alianceNum=0;
        $res->save();

        //随便清理一下
        InviteModel::where(['tid'=>$uid,'yesOrNo'=>0])->delete();

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //获取联盟信息
    public function getAlianceInfoByAlianceId(Request $request)
    {
        $alianceNum=trim($request->aliance);

        if (!is_numeric($alianceNum)) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $res=$this->alianceName($alianceNum);

        //人数
        $res['userNum']=AlianceGroupModel::where('alianceNum',$alianceNum)->count();

        //格子总数 //格子总价
        $userTotal=AlianceGroupModel::where('alianceNum',$alianceNum)->get(['uid']);

        $tarUser=[];

        foreach ($userTotal as $one)
        {
            $tarUser[]=$one->uid;
        }

        if (empty($tarUser))
        {
            $res['gridNum']=0;

            $res['gridTotalPrice']=0;

        }else
        {
            $res['gridNum']=GridModel::whereIn('belong',$tarUser)->count();

            $gridTotalPrice=GridModel::whereIn('belong',$tarUser)->select(DB::connection('masterDB')->raw('sum(price) as priceTotal'))->get()->toArray();

            if (empty($gridTotalPrice))
            {
                $res['gridTotalPrice']=0;

            }else
            {
                $res['gridTotalPrice']=(int)$gridTotalPrice[0]['priceTotal'];
            }
        }

        //获取繁荣度
        $star=Carbon::now()->startOfMonth()->format('Ymd');
        $stop=Carbon::now()->endOfMonth()->format('Ymd');
        $yday=Carbon::now()->subDay()->format('Ymd');

        $flourishMonth=DB::connection($this->db)->table('flourish')
            ->where('alianceNum',$alianceNum)
            ->whereBetween('date',[$star,$stop])
            ->select(DB::connection($this->db)->raw('sum(flourish) as flourish'))
            ->get();

        $res['flourishMonth']=(int)current($flourishMonth)[0]->flourish;

        $flourishDay=DB::connection($this->db)->table('flourish')->where(['alianceNum'=>$alianceNum,'date'=>$yday])->get(['flourish']);

        $res['flourishDay']=(int)current($flourishDay)[0]->flourish;

        //取公告，最近50条
        $res['announcement']=AnnouncementModel::where('alianceNum',$alianceNum)->orderBy('id','desc')->limit(50)->get()->toArray();

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }















}
