<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\FoodMap\UserPatch;
use App\Model\FoodMap\UserSuccess;
use App\Model\GridInfoModel;
use App\Model\GridModel;
use App\Model\RankListModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class RankListController extends BaseController
{
    public function getRankList(Request $request)
    {
        $uid=$request->uid;
        $type=$request->type;

        //迷雾面积
        $fogArea=trim($request->fogArea);

        if ($fogArea=='') $fogArea=random_int(3000,12000);

        $fogArea=sprintf("%.2f",$fogArea);

        switch ($type)
        {
            //获取个人资产
            case '1':

                return response()->json($this->getUserAssets($uid));

                break;

            //格子排行
            case '2':

                return response()->json($this->getGridAssets($uid));

                break;

            //格子总价榜
            case '3':

                return response()->json($this->getGridTotlePrice($uid));

                break;

            //格子数量榜
            case '4':

                return response()->json($this->getGridTotle($uid));

                break;

            //购买格子纳税榜
            case '5':

                return response()->json($this->getGridTax($uid));

                break;

            //迷雾总排行
            case '6':

                return response()->json($this->getFogTotal($uid,$fogArea));

                break;

            //迷雾月排行
            case '7':

                return response()->json($this->getFogMonth($uid,$fogArea));

                break;

            //买格总花费
            case '8':

                //买格总花费
                $key='BuyGridPayMoneyTotal_'.Carbon::now()->format('Ymd');
                $key='BuyGridPayMoneyTotal';

                $paymoney=0;
                for ($i=0;$i<=100;$i++)
                {
                    $mouth=Carbon::now()->subMonths($i)->format('Ym');

                    if ($mouth < 201905) break;

                    $res=DB::connection('masterDB')->table('buy_sale_info_'.$mouth)
                        ->where('uid',$uid)
                        ->select(DB::connection('masterDB')->raw('sum(paymoney) as paymoney'))->get();

                    $tmp=(int)current($res)[0]->paymoney;

                    $paymoney+=$tmp;
                }

                //加入集合
                Redis::connection('WriteLog')->zadd($key,$paymoney,$uid);
                // Redis::connection('WriteLog')->expire($key,86400);

                //取得前200
                $limit200=Redis::connection('WriteLog')->zrevrange($key,0,199,'withscores');

                //整理数组
                $i=1;
                foreach ($limit200 as $k => $v)
                {
                    $uName=trim(Redis::connection('UserInfo')->hget($k,'name'));
                    $uAvatar=trim(Redis::connection('UserInfo')->hget($k,'avatar'));

                    $limit[]=['row'=>$i,'name'=>$uName,'avatar'=>$uAvatar,'payMoneyTotal'=>$v];

                    $i++;
                }

                //我的排名
                $myRank=Redis::connection('WriteLog')->zrevrank($key,$uid)+1;
                $myPayMoneyTotal=Redis::connection('WriteLog')->zscore($key,$uid);

                $my=[
                    'row'=>$myRank,
                    'name'=>trim(Redis::connection('UserInfo')->hget($uid,'name')),
                    'avatar'=>trim(Redis::connection('UserInfo')->hget($uid,'avatar')),
                    'payMoneyTotal'=>$myPayMoneyTotal
                ];

                return ['resCode'=>Config::get('resCode.200'),'all'=>$limit,'my'=>$my];

                break;

            //宝物排行榜
            case '9':

                $all=[];
                $my=[];

                $res=Cache::remember('getRankList_9',5,function()
                {
                    return UserSuccess::select(DB::connection('FoodMap')->raw('uid,count(1) as num'))
                        ->groupBy('uid')
                        ->orderBy('num','desc')
                        ->get()->toArray();
                });

                //总榜是空
                if (empty($res)) return response()->json(['resCode'=>200,'all'=>$all,'my'=>$my]);

                //整理数组
                $row=1;
                foreach ($res as $one)
                {
                    if ($row <= 200)
                    {
                        $all[]=[
                            'row'=>$row,
                            'num'=>$one['num'],
                            'uid'=>$one['uid'],
                            'name'=>trim(Redis::connection('UserInfo')->hget($one['uid'],'name')),
                            'avatar'=>trim(Redis::connection('UserInfo')->hget($one['uid'],'avatar')),
                        ];
                    }

                    //是自己
                    if ($one['uid']==$uid)
                    {
                        $my[]=[
                            'row'=>$row,
                            'num'=>$one['num'],
                            'uid'=>$one['uid'],
                            'name'=>trim(Redis::connection('UserInfo')->hget($one['uid'],'name')),
                            'avatar'=>trim(Redis::connection('UserInfo')->hget($one['uid'],'avatar')),
                        ];
                    }

                    $row++;
                }

                return response()->json(['resCode'=>200,'all'=>$all,'my'=>$my]);

                break;

            //碎片排行榜
            case '10':

                $all=[];
                $my=[];

                $res=Cache::remember('getRankList_10',5,function()
                {
                    return UserPatch::select(DB::connection('FoodMap')->raw('uid,sum(num) as num'))
                        ->groupBy('uid')
                        ->orderBy('num','desc')
                        ->get()->toArray();
                });

                //总榜是空
                if (empty($res)) return response()->json(['resCode'=>200,'all'=>$all,'my'=>$my]);

                //整理数组
                $row=1;
                foreach ($res as $one)
                {
                    if ($row <= 200)
                    {
                        $all[]=[
                            'row'=>$row,
                            'num'=>$one['num'],
                            'uid'=>$one['uid'],
                            'name'=>trim(Redis::connection('UserInfo')->hget($one['uid'],'name')),
                            'avatar'=>trim(Redis::connection('UserInfo')->hget($one['uid'],'avatar')),
                        ];
                    }

                    //是自己
                    if ($one['uid']==$uid)
                    {
                        $my[]=[
                            'row'=>$row,
                            'num'=>$one['num'],
                            'uid'=>$one['uid'],
                            'name'=>trim(Redis::connection('UserInfo')->hget($one['uid'],'name')),
                            'avatar'=>trim(Redis::connection('UserInfo')->hget($one['uid'],'avatar')),
                        ];
                    }

                    $row++;
                }

                return response()->json(['resCode'=>200,'all'=>$all,'my'=>$my]);

                break;

            //钻石充值排行榜
            case '11':

                $all=[];
                $my=[];
                $key='DiamondRankListForTssj';

                //前200
                $limit200=Redis::connection('WriteLog')->zrevrange($key,0,199,'withscores');

                //总榜是空
                if (empty($limit200)) return response()->json(['resCode'=>200,'all'=>[],'my'=>[]]);

                $row=1;
                foreach ($limit200 as $k=>$v)
                {
                    $all[]=[
                        'row'=>$row,
                        'num'=>$v,
                        'uid'=>$k,
                        'name'=>trim(Redis::connection('UserInfo')->hget($k,'name')),
                        'avatar'=>trim(Redis::connection('UserInfo')->hget($k,'avatar')),
                    ];

                    $row++;
                }

                //我的排名
                if (Redis::connection('WriteLog')->zrevrank($key,$uid)!=null)
                {
                    $myRank=Redis::connection('WriteLog')->zrevrank($key,$uid)+1;
                    $myNum=Redis::connection('WriteLog')->zscore($key,$uid);

                    $my=[
                        'row'=>$myRank,
                        'num'=>$myNum,
                        'uid'=>$uid,
                        'name'=>trim(Redis::connection('UserInfo')->hget($uid,'name')),
                        'avatar'=>trim(Redis::connection('UserInfo')->hget($uid,'avatar')),
                    ];
                }

                return response()->json(['resCode'=>200,'all'=>$all,'my'=>$my]);

                break;

            default:

                return response()->json(['resCode'=>Config::get('resCode.604')]);

                break;
        }
    }

    //个人资产
    public function getUserAssets($uid)
    {
        $all=RankListModel::orderBy('now')->limit(200)->get()->toArray();

        $usr=RankListModel::where('uid',$uid)->get()->toArray();

        $userController=new UserController();

        //添加头像
        foreach ($all as &$oneUser)
        {
            $userInfo=$userController->getUserNameAndAvatar($oneUser['uid']);

            $oneUser['avatar']=$userInfo['avatar'];
            $oneUser['name']=$userInfo['name'];

            unset($oneUser['id']);
            unset($oneUser['last']);
            unset($oneUser['gridPrice']);
            unset($oneUser['money']);
        }
        unset($oneUser);

        //个人排行
        if (empty($usr))
        {
            $usr=null;

        }else
        {
            $usr=current($usr);

            $userInfo=$userController->getUserNameAndAvatar($usr['uid']);

            $usr['avatar']=$userInfo['avatar'];
            $usr['name']=$userInfo['name'];

            unset($usr['id']);
            unset($usr['gridPrice']);
        }

        if (empty($all))
        {
            $all=null;
        }else
        {
            $all=arraySort1($all,['asc','now']);
        }

        return ['resCode'=>Config::get('resCode.200'),'all'=>$all,'usr'=>$usr];
    }

    //格子资产
    public function getGridAssets($uid)
    {
        $res=Redis::connection('WriteLog')->get('GridRankList');

        if ($res==null) return ['resCode'=>Config::get('resCode.604')];

        $res=jsonDecode($res);

        $res=arraySort1($res,['asc','row']);
        $res=changeArrKey($res,['row'=>'now']);

        $pic2=null;

        //格子排行榜第一的图片随时更新
        if (isset($res[0]['uid']) && $res[0]['uid']!='' && $res[0]['uid']!=0 && $res[0]['gridName']!='')
        {
            $info=GridModel::where('name',$res[0]['gridName'])->first();

            if ($info)
            {
                $info=GridInfoModel::where(['uid'=>$res[0]['uid'],'gid'=>$info->id,'showPic2'=>1])->first();

                if ($info) $pic2=$info->pic2;
            }
        }

        $res[0]['pic2']=$pic2;

        return ['resCode'=>Config::get('resCode.200'),'data'=>$res];
    }

    //格子总价榜
    public function getGridTotlePrice($uid)
    {
        $key='GridTotlePriceRank';

        $res=jsonDecode(Redis::connection('WriteLog')->get($key));

        $col=current(collect($res)->where('uid',$uid)->all());

        if ($col==false) $col=null;

        return ['resCode'=>Config::get('resCode.200'),'usr'=>$col,'all'=>collect($res)->slice(0,200)->all()];
    }

    //格子数量榜
    public function getGridTotle($uid)
    {
        $key='GridTotleRank';

        $res=jsonDecode(Redis::connection('WriteLog')->get($key));

        $col=current(collect($res)->where('uid',$uid)->all());

        if ($col==false) $col=null;

        return ['resCode'=>Config::get('resCode.200'),'usr'=>$col,'all'=>collect($res)->slice(0,200)->all()];
    }

    //购买格子纳税榜
    public function getGridTax($uid)
    {
        $key='GridTaxRank';

        $res=jsonDecode(Redis::connection('WriteLog')->get($key));

        if ($res==null || empty($res) || $res==false)
        {
            return ['resCode'=>Config::get('resCode.200'),'usr'=>null,'all'=>[]];
        }

        $col=current(collect($res)->where('uid',$uid)->all());

        if ($col==false) $col=null;

        return ['resCode'=>Config::get('resCode.200'),'usr'=>$col,'all'=>collect($res)->slice(0,200)->all()];
    }

    //增加迷雾排行榜统计对象
    public function addFogObj($uid)
    {
        $suffix=Carbon::now()->format('YmdH');

        //uid放redis里，定时任务统计
        Redis::connection('WriteLog')->sadd('RankForUserFog_'.$suffix,$uid);

        //存活1小时
        Redis::connection('WriteLog')->expire('RankForUserFog_'.$suffix,3600);

        return true;
    }

    //迷雾总排行
    public function getFogTotal($uid,$fogArea)
    {
        $this->addFogObj($uid);

        //有序集合，返回前200和自己的当前排名

        //先更改或添加
        Redis::connection('WriteLog')->zadd('GetUserFogTotalRank',$fogArea,$uid);

        //前200
        $limit200=Redis::connection('WriteLog')->zrevrange('GetUserFogTotalRank',0,199,'withscores');

        //我的排名
        $myRank=Redis::connection('WriteLog')->zrevrank('GetUserFogTotalRank',$uid)+1;

        //整理数组
        $userObj=new UserController();

        $my['row']=$myRank;
        $my['fogArea']=$fogArea;
        $my['uid']=$uid;
        $userInfo=$userObj->getUserNameAndAvatar($uid);
        $my['uName']=$userInfo['name'];
        $my['uAvatar']=$userInfo['avatar'];

        $num=1;
        $all=[];
        foreach ($limit200 as $k=>$v)
        {
            $one['row']=$num;
            $one['fogArea']=sprintf("%.2f",$v);
            $one['uid']=$k;

            $userInfo=$userObj->getUserNameAndAvatar($k);

            $one['uName']=$userInfo['name'];
            $one['uAvatar']=$userInfo['avatar'];

            $all[]=$one;

            $num++;
        }

        return ['resCode'=>Config::get('resCode.200'),'all'=>$all,'my'=>$my];
    }

    //迷雾周排行
    public function getFogWeek($uid,$fogArea)
    {
        $this->addFogObj($uid);

        //有序集合，返回前200和自己的当前排名

        //当周开始时间
        $startOfWeek=Carbon::now()->startOfWeek()->format('Ymd');

        if (Redis::connection('WriteLog')->zscore("GetUserFogWeekRank_{$startOfWeek}_first",$uid)===null)
        {
            //当周第一次进
            Redis::connection('WriteLog')->zadd("GetUserFogWeekRank_{$startOfWeek}_first",sprintf("%.2f",0-$fogArea),$uid);
        }

        //先更改或添加
        Redis::connection('WriteLog')->zadd("GetUserFogWeekRank_{$startOfWeek}_more",$fogArea,$uid);

        //求并集，分数相加，组成新key
        Redis::connection('WriteLog')->zunionstore('GetUserFogWeekRank',2,"GetUserFogWeekRank_{$startOfWeek}_first","GetUserFogWeekRank_{$startOfWeek}_more");

        //前200
        $limit200=Redis::connection('WriteLog')->zrevrange('GetUserFogWeekRank',0,199,'withscores');

        //我的排名
        $myRank=Redis::connection('WriteLog')->zrevrank('GetUserFogWeekRank',$uid)+1;

        //整理数组
        $userObj=new UserController();

        $my['row']=$myRank;
        $a=sprintf("%.2f",Redis::connection('WriteLog')->zscore('GetUserFogWeekRank',$uid));
        $a < 0 ? $a = 0 : null;
        $my['fogArea']=sprintf("%.2f",$a);
        $my['uid']=$uid;
        $userInfo=$userObj->getUserNameAndAvatar($uid);
        $my['uName']=$userInfo['name'];
        $my['uAvatar']=$userInfo['avatar'];

        $num=1;
        $all=[];
        foreach ($limit200 as $k=>$v)
        {
            $one['row']=$num;

            $v < 0 ? $v = 0 : null;

            $one['fogArea']=sprintf("%.2f",$v);
            $one['uid']=$k;

            $userInfo=$userObj->getUserNameAndAvatar($k);

            $one['uName']=$userInfo['name'];
            $one['uAvatar']=$userInfo['avatar'];

            $all[]=$one;

            $num++;
        }

        //设置过期
        Redis::connection('WriteLog')->expireat("GetUserFogWeekRank_{$startOfWeek}_first",Carbon::now()->endOfWeek()->timestamp);
        Redis::connection('WriteLog')->expireat("GetUserFogWeekRank_{$startOfWeek}_more",Carbon::now()->endOfWeek()->timestamp);
        Redis::connection('WriteLog')->expireat("GetUserFogWeekRank",Carbon::now()->endOfWeek()->timestamp);

        return ['resCode'=>Config::get('resCode.200'),'all'=>$all,'my'=>$my];
    }

    //迷雾月排行
    public function getFogMonth($uid,$fogArea)
    {
        $this->addFogObj($uid);

        //有序集合，返回前200和自己的当前排名

        //当月开始时间
        $startOfMonth=Carbon::now()->startOfMonth()->format('Ymd');

        if (Redis::connection('WriteLog')->zscore("GetUserFogMonthRank_{$startOfMonth}_first",$uid)===null)
        {
            //当月第一次进
            Redis::connection('WriteLog')->zadd("GetUserFogMonthRank_{$startOfMonth}_first",sprintf("%.2f",0-$fogArea),$uid);
        }

        //先更改或添加
        Redis::connection('WriteLog')->zadd("GetUserFogMonthRank_{$startOfMonth}_more",$fogArea,$uid);

        //求并集，分数相加，组成新key
        Redis::connection('WriteLog')->zunionstore('GetUserFogMonthRank',2,"GetUserFogMonthRank_{$startOfMonth}_first","GetUserFogMonthRank_{$startOfMonth}_more");

        //前200
        $limit200=Redis::connection('WriteLog')->zrevrange('GetUserFogMonthRank',0,199,'withscores');

        //我的排名
        $myRank=Redis::connection('WriteLog')->zrevrank('GetUserFogMonthRank',$uid)+1;

        //整理数组
        $userObj=new UserController();

        $my['row']=$myRank;
        $a=sprintf("%.2f",Redis::connection('WriteLog')->zscore('GetUserFogMonthRank',$uid));
        $a < 0 ? $a = 0 : null;
        $my['fogArea']=sprintf("%.2f",$a);
        $my['uid']=$uid;
        $userInfo=$userObj->getUserNameAndAvatar($uid);
        $my['uName']=$userInfo['name'];
        $my['uAvatar']=$userInfo['avatar'];

        $num=1;
        $all=[];
        foreach ($limit200 as $k=>$v)
        {
            $one['row']=$num;

            $v < 0 ? $v = 0 : null;

            $one['fogArea']=sprintf("%.2f",$v);
            $one['uid']=$k;

            $userInfo=$userObj->getUserNameAndAvatar($k);

            $one['uName']=$userInfo['name'];
            $one['uAvatar']=$userInfo['avatar'];

            $all[]=$one;

            $num++;
        }

        //设置过期
        Redis::connection('WriteLog')->expireat("GetUserFogMonthRank_{$startOfMonth}_first",Carbon::now()->endOfMonth()->timestamp);
        Redis::connection('WriteLog')->expireat("GetUserFogMonthRank_{$startOfMonth}_more",Carbon::now()->endOfMonth()->timestamp);
        Redis::connection('WriteLog')->expireat("GetUserFogMonthRank",Carbon::now()->endOfMonth()->timestamp);

        return ['resCode'=>Config::get('resCode.200'),'all'=>$all,'my'=>$my];
    }


}
