<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Http\Controllers\Server\ContentCheckBase;
use App\Model\GridModel;
use App\Model\GridTradeInfoModel;
use App\Model\GridInfoModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class GridController extends BaseController
{
    //交易保护的redisKey
    public $TradeGuardKey='TradeGuard_';

    //买格子
    public function buyGrid(Request $request)
    {
        $uid = $request->uid;
        $name = $request->name;

        if (!(is_numeric($uid)) || !(is_string($name))) return response()->json(['resCode' => Config::get('resCode.604')]);

        //取出格子信息
        $gridInfo = DB::connection('masterDB')->table('grid')->where('name', $name)->first();

        //格子不存在
        if (!$gridInfo) return response()->json(['resCode' => Config::get('resCode.605')]);

        //不能买自己的格子
        if ($uid==$gridInfo->belong) return response()->json(['resCode' => Config::get('resCode.615')]);

        //格子是不可交易状态
        if ($gridInfo->showGrid != 1 || $this->getTradeGuard($name) > 0) return response()->json(['resCode' => Config::get('resCode.606')]);

        //查看格子当天剩余购买次数
        $buyTotle = $this->getBuyLimit($name);

        //超出限制，不可以买
        if ($buyTotle >= $this->getGridTodayBuyTotle($name)) return response()->json(['resCode' => Config::get('resCode.609')]);

        //用户当天购地卡使用情况
        if ((new UserController())->getBuyCardCount($uid) <= 0) return response()->json(['resCode' => Config::get('resCode.610')]);

        $money=(new UserController())->getUserMoney($uid);

        //需要花费的价格
        $payMoney=$this->needToPay($gridInfo);

        //没钱了，不能买
        if ($money < $payMoney) return response()->json(['resCode'=>Config::get('resCode.607')]);

        try
        {
            //修改归属，修改当前价格，总交易次数+1
            DB::connection('masterDB')->table('grid')->where('name',$gridInfo->name)->update([

                'belong'=>$uid,
                'price'=>$payMoney,
                'hightPrice'=>$payMoney > $gridInfo->hightPrice ? $payMoney : $gridInfo->hightPrice,
                'totle'=>$gridInfo->totle + 1,
                'updated_at'=>Carbon::now()->format('Y-m-d H:i:s'),

            ]);

            //给格子一个默认名称
            $res=GridInfoModel::where(['uid'=>$uid,'gid'=>$gridInfo->id])->first();

            if ($res==null)
            {
                //第一次买这个格子
                $count=(int)Redis::connection('UserInfo')->hget($uid,'BuyGridTotle');

                $count++;

                Redis::connection('UserInfo')->hset($uid,'BuyGridTotle',$count);

                $userInfo=(new UserController())->getUserNameAndAvatar($uid);

                $newName=mb_substr($userInfo['name'],0,5).'的格子'.$count;

                GridInfoModel::updateOrCreate(['uid'=>$uid,'gid'=>$gridInfo->id],['name'=>$newName,'showName'=>1]);
            }

            //扣款
            (new UserController())->exprUserMoney($uid,$gridInfo->belong,$payMoney);

            //格子当天交易次数加1
            $this->setBuyLimit($name);

            //该用户购地卡减1
            (new UserController())->setBuyCardCount($uid);

            //交易保护
            $this->setTradeGuard($name);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.608')]);
        }

        //记录交易详情
        $arr['gid']=$gridInfo->id;
        $arr['gName']=$gridInfo->name;
        $arr['uid']=$uid;
        $arr['uName']='';
        $arr['belong']=$gridInfo->belong;
        $arr['belongName']='';
        $arr['payMoney']=$payMoney;
        $arr['payCount']=$gridInfo->totle + 1;
        $arr['payTime']=time();

        (new WriteLogController())->writeGridTradeLog($arr);

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    public function getBuyLimit($name)
    {
        //$key=w1n1_20190101

        $today=Carbon::now()->format('Ymd');

        $key=$name.'_'.$today;

        if (Redis::connection('GridInfo')->get($key)===null)
        {
            Redis::connection('GridInfo')->set($key,0);

            Redis::connection('GridInfo')->expire($key,86400);

            return 0;

        }else
        {
            return Redis::connection('GridInfo')->get($key);
        }
    }

    public function setBuyLimit($name,$num=1)
    {
        $today=Carbon::now()->format('Ymd');

        $key=$name.'_'.$today;

        $count=Redis::connection('GridInfo')->get($key);

        Redis::connection('GridInfo')->set($key,$count + $num);

        Redis::connection('GridInfo')->expire($key,86400);

        return true;
    }

    //购买格子需要花多少钱
    public function needToPay($grid)
    {
        return $grid->price + $grid->totle;
    }

    //格子当天最大交易次数
    public function getGridTodayBuyTotle($gName)
    {
        return Config::get('myDefine.GridTodayBuyTotle');
    }

    //给格子加上交易保护
    public function setTradeGuard($gName)
    {
        $key=$this->TradeGuardKey.trim($gName);

        try
        {
            Redis::connection('GridInfo')->set($key,'wait..');

            Redis::connection('GridInfo')->expire($key,Config::get('myDefine.TradeGuard'));

        }catch (\Exception $e)
        {
            return true;
        }

        return true;
    }

    //获取格子的交易保护剩余时间
    public function getTradeGuard($gName)
    {
        $key=$this->TradeGuardKey.trim($gName);

        try
        {
            $ttl=(int)Redis::connection('GridInfo')->ttl($key);

            if ($ttl <= 0) $ttl=0;

        }catch (\Exception $e)
        {
            return 0;
        }

        return $ttl;
    }

    //获取当前格子信息和周围格子头像
    public function getGridInfo(Request $request)
    {
        $uid=$request->uid;

        //点到的格子name
        $name=$request->name;

        //只需取得头像的格子名称Array
        $near=$request->near;

        if (is_array($near))
        {

        }else
        {
            $near=json_decode($near,true);
        }

        if (!is_array($near)) return response()->json(['resCode' => Config::get('resCode.604')]);

        //取出格子信息
        $gridInfo = DB::connection('masterDB')->table('grid')->where('name', $name)->first();

        //格子不存在
        if (!$gridInfo) return response()->json(['resCode' => Config::get('resCode.605')]);

        //查询格子扩展信息
        $gridExt=GridInfoModel::where(['gid'=>$gridInfo->id,'uid'=>$gridInfo->belong])->first();

        $showName='';
        $showPic1='';
        if ($gridExt)
        {
            //判断一下用户自定义格子名称是不是通过审核
            $gridExt->showName==1 ? $showName=$gridExt->name : $showName='';

            //判断一下用户上传图片是不是通过审核
            $gridExt->showPic1==1 ? $showPic1=$gridExt->pic1 : $showPic1='';
        }

        //得到当前格子的信息
        $uObj=new UserController();
        $userInfo=$uObj->getUserNameAndAvatar($gridInfo->belong);

        $info['showName']=$showName;//用户自定义名字
        $info['showPic1']=$showPic1;//用户自定义图片
        $info['price']=$this->needToPay($gridInfo);//价格
        $info['gName']=$gridInfo->name;//格子坐标例如w1n1
        $info['belong']=$gridInfo->belong;//所有者uid
        $info['belongName']=$userInfo['name'];//所有者名字
        $info['belongAvatar']=$userInfo['avatar'];//所有者头像
        $info['currentCount']=$this->getBuyLimit($gridInfo->name);//当天交易几次
        $info['maxCount']=$this->getGridTodayBuyTotle($gridInfo->name);//当天可交易几次
        $info['tradeGuard']=$this->getTradeGuard($gridInfo->name);//获取交易保护redisTTL
        $info['canIBuyThisGrid']=$uObj->canIBuyThisGrid($uid,$gridInfo);//返回resCode

        //取出附近格子信息
        $nearUid = DB::connection('masterDB')->table('grid')->whereIn('name',$near)->get(['id','name','belong'])->toArray();

        foreach ($nearUid as $row)
        {
            if ($row->belong==0)
            {
                $one=$uObj->getUserNameAndAvatar($row->belong);

                $tmp[$row->name]=$one['avatar'];

                continue;

            }else
            {
                //一个一个查吧
                $one=GridInfoModel::where([

                    'gid'=>$row->id,
                    'uid'=>$row->belong,
                    'showPic1'=>1

                ])->get(['pic1'])->first();

                if ($one==null)
                {
                    $one=$uObj->getUserNameAndAvatar($row->belong);

                }else
                {
                    $one['avatar']=$one->pic1;
                }

                $tmp[$row->name]=$one['avatar'];
            }
        }

        $near=$tmp;

        return response()->json(['resCode' => Config::get('resCode.200'),'current'=>$info,'near'=>$near]);
    }

    //重命名格子
    public function renameGrid(Request $request)
    {
        $uid=trim($request->uid);
        $gName=trim($request->gName);
        $newName=trim($request->newName);

        //内容检查
        if ($newName=='') return response()->json(['resCode' => Config::get('resCode.612')]);

        $res=(new ContentCheckBase())->check($newName);

        if (!empty($res) || $res!=null || $res!='') return response()->json(['resCode' => Config::get('resCode.613'),'data'=>$res]);

        //插入数据
        $girdId=GridModel::where(['name'=>$gName,'belong'=>$uid])->first();

        GridInfoModel::updateOrCreate(['uid'=>$uid,'gid'=>$girdId->id],['name'=>$newName,'showName'=>1]);

        return response()->json(['resCode' => Config::get('resCode.200')]);
    }

    //上传格子图片
    public function uploadPic(Request $request)
    {
        $uid=$request->uid;
        $gName=$request->gName;
        $base64Pic=$request->pic;
        $base64Pic2=$request->pic2;

        $grid=GridModel::where('name',$gName)->first();

        if ($grid->belong!=$uid) return response()->json(['resCode' => Config::get('resCode.618')]);

        //得到orm实例
        $gridInfo=GridInfoModel::firstOrNew(['uid'=>$uid,'gid'=>$grid->id]);

        //保存用户上传的图片base64格式
        $img =uploadMyImg($base64Pic);
        $img2=uploadMyImg($base64Pic2);

        if (!$img && !$img2) return response()->json(['resCode' => Config::get('resCode.619')]);

        //保存图片到服务器
        if ($img!=false)
        {
            $path=storeFile($img,$uid,$grid,'pic1');

        }elseif ($img2!=false)
        {
            $path=storeFile($img2,$uid,$grid,'pic2');

        }else
        {

        }

        if (!$path) return response()->json(['resCode' => Config::get('resCode.620')]);

        //path入库，等待后台审核
        if ($img!=false)
        {
            $gridInfo->pic1=$path;

            //pic1需要审核
            $gridInfo->showPic1=0;

        }elseif ($img2!=false)
        {
            $gridInfo->pic2=$path;

            //pic2不审核了
            $gridInfo->showPic2=1;

        }else
        {

        }

        $gridInfo->save();

        return response()->json(['resCode' => Config::get('resCode.200')]);
    }

    //格子详情
    public function gridDetails(Request $request)
    {
        $uid=$request->uid;
        $gName=trim($request->gName);

        //必然有信息，但是可能是没有交易过的
        $info1=GridModel::where('name',$gName)->first();

        //可能是空
        $info2=GridInfoModel::where(['gid'=>$info1->id,'uid'=>$info1->belong,'showName'=>1])->first();

        //必然有信息
        $info3=getTssjUserInfo($info1->belong);

        $suffix=string2Number($gName);
        $suffix=$suffix%50;

        if (Schema::connection('gridTradeInfoDB')->hasTable('grid_trade_info_'.$suffix))
        {
            GridTradeInfoModel::suffix($suffix);
            $info4=GridTradeInfoModel::where(['gname'=>$gName,'belong'=>0])->first();
        }else
        {
            $info4=null;
        }

        //以下拼数组
        $gridInfo['gname']=$info1->name;
        $gridInfo['name']=$info2==null ? null : $info2->name;
        $gridInfo['belong']=$info3->username;
        $gridInfo['tradeNow']=(int)Redis::connection('GridInfo')->get($gName.'_'.Carbon::now()->format('Ymd'));
        $gridInfo['tradeAll']=$this->getGridTodayBuyTotle($info1->name);
        $gridInfo['totle']=$info1->totle;
        $gridInfo['price']=$this->needToPay($info1);
        $gridInfo['highPrice']=$info1->hightPrice;
        $gridInfo['firstTrade']=$info4==null ? null : date('Y-m-d H:i:s',$info4->paytime);
        $gridInfo['recentlyTrade']=$info1->updated_at==null ? null : ($info1->updated_at)->format('Y-m-d H:i:s');
        $gridInfo['status']=(int)$info1->showGrid;

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$gridInfo]);
    }



}