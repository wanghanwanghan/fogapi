<?php

namespace App\Http\Controllers\QuanMinZhanLing\Aliance;

use App\Http\Controllers\QuanMinZhanLing\UserController;
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
    //联盟相关用户信息
    public function getUserInfoForAliance(Request $request)
    {
        $uid=trim($request->uid);

        //用户名 头像
        $name=Redis::connection('UserInfo')->hget($uid,'name');
        $avatar=Redis::connection('UserInfo')->hget($uid,'avatar');

        //是否入盟
        $res=AlianceGroupModel::where('uid',$uid)->where('alianceNum','<>',0)->first();

        $res ? $alianceNum=$res->alianceNum : $alianceNum=0;

        return response()->json([
            'resCode'=>Config::get('resCode.200'),
            'name'=>$name,
            'avatar'=>$avatar,
            'alianceNum'=>$alianceNum
        ]);
    }

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

                    //取100人就退
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
        $this->createTable('flourishForUser');

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

            if (Carbon::now()->format('Ymd') > 20201212)
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

    //获取联盟成员
    public function getAlianceMember(Request $request)
    {
        $uid=trim($request->uid);

        $res=AlianceGroupModel::where('uid',$uid)->first();

        //拿到用户的联盟编号
        $alianceNum=$res->alianceNum;

        //获取这个联盟中所有成员，不包括自己
        $allMember=AlianceGroupModel::where('alianceNum',$alianceNum)->where('uid','<>',$uid)->get();

        $star=Carbon::now()->startOfMonth()->format('Ymd');
        $stop=Carbon::now()->endOfMonth()->format('Ymd');
        $data=[];

        //获取这些人的本月繁荣度，和该uid和他们的关系
        foreach ($allMember as $one)
        {
            //我关注他没
            Redis::connection('CommunityInfo')->zscore('follower_'.$uid,$one->uid)!=null ? $followerNum=1 : $followerNum=0;

            //他关注我没
            Redis::connection('CommunityInfo')->zscore('fans_'.$uid,$one->uid)!=null ? $fansNum=2 : $fansNum=0;

            $relation=$followerNum + $fansNum;

            //0：双方都没关注对方
            //1：我关注他，他没关注我
            //2：他关注我，我没关注他
            //3：相互关注

            $name=Redis::connection('UserInfo')->hget($one->uid,'name');

            $avatar=Redis::connection('UserInfo')->hget($one->uid,'avatar');

            //最后拿本月的繁荣度，从flourishForUser表中

            $res=DB::connection($this->db)->table('flourishForUser')
                ->where('uid',$one->uid)
                ->whereBetween('date',[$star,$stop])
                ->select(DB::connection($this->db)->raw('sum(flourish) as flourish'))->get();

            $flourish=(int)current($res)[0]->flourish;

            $data[]=[
                'uid'=>$one->uid,
                'avatar'=>$avatar,
                'name'=>$name,
                'flourish'=>$flourish,
                'relation'=>$relation
            ];
        }

        //获取该uid繁荣度
        $res=DB::connection($this->db)->table('flourishForUser')
            ->where('uid',$uid)
            ->whereBetween('date',[$star,$stop])
            ->select(DB::connection($this->db)->raw('sum(flourish) as flourish'))->get();

        $flourish=(int)current($res)[0]->flourish;

        $name=Redis::connection('UserInfo')->hget($uid,'name');

        $avatar=Redis::connection('UserInfo')->hget($uid,'avatar');

        //夺冠次数放redis里得了，懒得链表
        $winNum=0;
        for ($i=1;$i<=4;$i++)
        {
            $winNum+=(int)Redis::connection('UserInfo')->hget($uid,"AlianceWinOrLose{$i}");
        }

        $my=[
            'avatar'=>$avatar,
            'name'=>$name,
            'flourish'=>$flourish,
            'winNum'=>$winNum
        ];

        return response()->json(['resCode'=>Config::get('resCode.200'),'all'=>$data,'my'=>$my]);
    }

    //关注和取消关注
    public function follower(Request $request)
    {
        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        $tid=trim($request->tid);

        if (!is_numeric($tid) || $tid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        //我关注他没
        if (Redis::connection('CommunityInfo')->zscore('follower_'.$uid,$tid)!=null)
        {
            //我关注他了，那么就取消关注
            Redis::connection('CommunityInfo')->zrem('follower_'.$uid,$tid);

            //他的粉丝集合里也去掉我
            Redis::connection('CommunityInfo')->zrem('fans_'.$tid,$uid);

        }else
        {
            //我没关注他，那么就关注
            Redis::connection('CommunityInfo')->zadd('follower_'.$uid,time(),$tid);

            //他的粉丝集合里加上我
            Redis::connection('CommunityInfo')->zadd('fans_'.$tid,time(),$uid);
        }

        //我关注他没
        Redis::connection('CommunityInfo')->zscore('follower_'.$uid,$tid)!=null ? $followerNum=1 : $followerNum=0;
        //他关注我没
        Redis::connection('CommunityInfo')->zscore('fans_'.$uid,$tid)!=null ? $fansNum=2 : $fansNum=0;

        return response()->json(['resCode'=>Config::get('resCode.200'),'relation'=>$followerNum+$fansNum]);
    }

    //战绩情况
    public function getMilitaryExploits(Request $request)
    {
        $uid=$request->uid;

        $winOrLose=jsonDecode(Redis::connection('UserInfo')->hget($uid,"AlianceWinOrLose"));

        //$winOrLose[]=['mouth'=>$mouth,'alianceNum'=>$one['alianceNum'],'winOrLose'=>1];
        if (Carbon::now()->format('Ymd') < 20201212)
        {
            $winOrLose=[
                ['mouth'=>201904,'alianceNum'=>mt_rand(1,4),'winOrLose'=>mt_rand(0,1)],
                ['mouth'=>201905,'alianceNum'=>mt_rand(1,4),'winOrLose'=>mt_rand(0,1)],
                ['mouth'=>201906,'alianceNum'=>mt_rand(1,4),'winOrLose'=>mt_rand(0,1)],
                ['mouth'=>201907,'alianceNum'=>mt_rand(1,4),'winOrLose'=>mt_rand(0,1)],
                ['mouth'=>201908,'alianceNum'=>mt_rand(1,4),'winOrLose'=>mt_rand(0,1)],
                ['mouth'=>201909,'alianceNum'=>mt_rand(1,4),'winOrLose'=>mt_rand(0,1)],
                ['mouth'=>201910,'alianceNum'=>mt_rand(1,4),'winOrLose'=>mt_rand(0,1)],
                ['mouth'=>201911,'alianceNum'=>mt_rand(1,4),'winOrLose'=>mt_rand(0,1)],
            ];
        }

        if (!is_array($winOrLose))
        {
            return response()->json(['resCode'=>Config::get('resCode.200'),'count'=>[],'data'=>[]]);

        }else
        {
            for ($i=1;$i<=4;$i++)
            {
                $count[]=[
                    'aliance'=>$i,
                    'count'=>(int)Redis::connection('UserInfo')->hget($uid,"AlianceWinOrLose{$i}")
                ];
            }

            $winOrLose=arraySort1($winOrLose,['desc','mouth']);

            foreach ($winOrLose as &$one)
            {
                $res=DB::connection($this->db)->table('flourishForUser')
                    ->whereBetween('date',[$one['mouth'].'00',$one['mouth'].'32'])
                    ->where('uid',$uid)
                    ->select(DB::connection($this->db)->raw('sum(flourish) as flourish'))
                    ->get();

                $one['flourish']=(int)current($res)[0]->flourish;
            }
            unset($one);
        }

        $data=$winOrLose;

        return response()->json(['resCode'=>Config::get('resCode.200'),'count'=>$count,'data'=>$data]);
    }

    //占领概况
    public function getChartInfo(Request $request)
    {
        $uid=$request->uid;

        $type=$request->type;

        switch ($type)
        {
            case 1:

                //繁荣度

                //1-4号联盟各个的繁荣度

                $userInAliance=[];
                $flourishTotal=0;

                $data=[];
                for ($i=1;$i<=4;$i++)
                {
                    $res=AlianceGroupModel::where('alianceNum',$i)->get();

                    if (empty($res))
                    {
                        $flourishTotal+=0;
                        $data[]=['aliance'=>$i,'flourish'=>0];
                        continue;
                    }

                    $cond=[];
                    //组成cond
                    foreach ($res as $one)
                    {
                        $cond[]=$one->uid;
                        $userInAliance[]=$one->uid;
                    }

                    $res=GridModel::whereIn('belong',$cond)
                        ->select(DB::connection('masterDB')->raw('sum(case when price / 30 > 0 then left(price / 30,1) + 1 else 1 end) as flourish'))
                        ->get();

                    $flourishTotal+=(int)current($res)[0]->flourish;
                    $data[]=['aliance'=>$i,'flourish'=>(int)current($res)[0]->flourish];
                }

                //自由人的繁荣度
                //先找到所有自由人
                $res=DB::connection('masterDB')->table('grid')->where('belong','>',0)->whereNotIn('belong',$userInAliance)->groupBy('belong')->get(['belong']);

                foreach ($res as $one)
                {
                    $freedomUser[]=$one->belong;
                }

                $res=GridModel::whereIn('belong',$freedomUser)
                    ->select(DB::connection('masterDB')->raw('sum(case when price / 30 > 0 then left(price / 30,1) + 1 else 1 end) as flourish'))
                    ->get();

                //自由人的繁荣度
                $freedomUserFlourish=(int)current($res)[0]->flourish;

                //把自由人的加进去
                $data[]=['aliance'=>999,'flourish'=>$freedomUserFlourish];

                //总的繁荣度
                $flourishTotal+=$freedomUserFlourish;

                //做除法
                foreach ($data as &$one)
                {
                    //小数点后2位
                    $one['percent']=bcdiv($one['flourish'],$flourishTotal,2);
                }
                unset($one);

                break;

            case 2:

                //格子数

                //1-4号联盟各个的格子数

                $userInAliance=[];
                $gridNumTotal=0;

                $data=[];
                for ($i=1;$i<=4;$i++)
                {
                    $res=AlianceGroupModel::where('alianceNum',$i)->get();

                    if (empty($res))
                    {
                        $gridNumTotal+=0;
                        $data[]=['aliance'=>$i,'gridNum'=>0];
                        continue;
                    }

                    $cond=[];
                    //组成cond
                    foreach ($res as $one)
                    {
                        $cond[]=$one->uid;
                        $userInAliance[]=$one->uid;
                    }

                    $res=GridModel::whereIn('belong',$cond)
                        ->select(DB::connection('masterDB')->raw('count(*) as gridNum'))
                        ->get();

                    $gridNumTotal+=(int)current($res)[0]->gridNum;
                    $data[]=['aliance'=>$i,'gridNum'=>(int)current($res)[0]->gridNum];
                }

                //自由人的格子数
                //先找到所有自由人
                $res=DB::connection('masterDB')->table('grid')->where('belong','>',0)->whereNotIn('belong',$userInAliance)->groupBy('belong')->get(['belong']);

                foreach ($res as $one)
                {
                    $freedomUser[]=$one->belong;
                }

                $res=GridModel::whereIn('belong',$freedomUser)
                    ->select(DB::connection('masterDB')->raw('count(*) as gridNum'))
                    ->get();

                //自由人的繁荣度
                $freedomUserGridNum=(int)current($res)[0]->gridNum;

                //把自由人的加进去
                $data[]=['aliance'=>999,'gridNum'=>$freedomUserGridNum];

                //总的格子数
                $gridNumTotal+=$freedomUserGridNum;

                //做除法
                foreach ($data as &$one)
                {
                    //小数点后2位
                    $one['percent']=bcdiv($one['gridNum'],$gridNumTotal,2);
                }
                unset($one);

                break;

            case 3:

                //格子总价

                //1-4号联盟各个的格子总价

                $userInAliance=[];
                $gridPriceTotal=0;

                $data=[];
                for ($i=1;$i<=4;$i++)
                {
                    $res=AlianceGroupModel::where('alianceNum',$i)->get();

                    if (empty($res))
                    {
                        $gridPriceTotal+=0;
                        $data[]=['aliance'=>$i,'gridPrice'=>0];
                        continue;
                    }

                    $cond=[];
                    //组成cond
                    foreach ($res as $one)
                    {
                        $cond[]=$one->uid;
                        $userInAliance[]=$one->uid;
                    }

                    $res=GridModel::whereIn('belong',$cond)
                        ->select(DB::connection('masterDB')->raw('sum(price) as gridPrice'))
                        ->get();

                    $gridPriceTotal+=(int)current($res)[0]->gridPrice;
                    $data[]=['aliance'=>$i,'gridPrice'=>(int)current($res)[0]->gridPrice];
                }

                //自由人的格子总价
                //先找到所有自由人
                $res=DB::connection('masterDB')->table('grid')->where('belong','>',0)->whereNotIn('belong',$userInAliance)->groupBy('belong')->get(['belong']);

                foreach ($res as $one)
                {
                    $freedomUser[]=$one->belong;
                }

                $res=GridModel::whereIn('belong',$freedomUser)
                    ->select(DB::connection('masterDB')->raw('sum(price) as gridNum'))
                    ->get();

                //自由人的繁荣度
                $freedomUserGridPrice=(int)current($res)[0]->gridNum;

                //把自由人的加进去
                $data[]=['aliance'=>999,'gridPrice'=>$freedomUserGridPrice];

                //总的格子总价
                $gridPriceTotal+=$freedomUserGridPrice;

                //做除法
                foreach ($data as &$one)
                {
                    //小数点后2位
                    $one['percent']=bcdiv($one['gridPrice'],$gridPriceTotal,2);
                }
                unset($one);

                break;
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);
    }

    //aliance=2时候每天领取88地球币
    public function getAlianceReward(Request $request)
    {
        $uid=$request->uid;

        if (is_numeric($uid) && $uid >= 1 && AlianceGroupModel::where(['uid'=>$uid,'alianceNum'=>2])->first() != null)
        {
            $res=Redis::connection('UserInfo')->get("Aliance2Reward_{$uid}_".Carbon::now()->format('Ymd'));

            if ($res!=null) return response()->json(['resCode'=>Config::get('resCode.603')]);

            (new UserController())->exprUserMoney($uid,0,88,'+');

            Redis::connection('UserInfo')->set("Aliance2Reward_{$uid}_".Carbon::now()->format('Ymd'),1);
            Redis::connection('UserInfo')->expire("Aliance2Reward_{$uid}_".Carbon::now()->format('Ymd'),86400);

            return response()->json(['resCode'=>Config::get('resCode.200')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.604')]);
    }










}
