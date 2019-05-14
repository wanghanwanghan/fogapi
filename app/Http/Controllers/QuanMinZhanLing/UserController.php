<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\GridInfoModel;
use App\Model\GridModel;
use App\Model\UserTradeInfoModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Facades\Image;

class UserController extends BaseController
{
    //获取用户的全部格子信息
    public function getUserGridInfo(Request $request)
    {
        $uid=$request->uid;
        $page=$request->page;

        if ($uid=='') return response()->json(['resCode' => Config::get('resCode.601')]);

        if ($page)
        {
            if ($request->limit=='' || !is_numeric($request->limit))
            {
                $limit=10;
            }else
            {
                $limit=$request->limit;
            }

            $offset=($page-1)*$limit;

            $gridInfo=GridModel::where('belong',$uid)->limit($limit)->offset($offset)->get(['id','name','price','totle','updated_at'])->toArray();

        }else
        {
            $gridInfo=GridModel::where('belong',$uid)->get(['id','name','price','totle','updated_at'])->toArray();
        }

        if (empty($gridInfo)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>null]);

        //取出id
        $id=array_pluck($gridInfo,'id');

        $gridInfoExt=GridInfoModel::where('uid',$uid)->whereIn('gid',$id)
            ->orderBy('updated_at','desc')
            ->get(['gid','name','showName','pic1','showPic1'])->toArray();

        if (empty($gridInfoExt))
        {
            foreach ($gridInfo as &$one)
            {
                $one['price']=$one['price']+$one['totle'];
                $one['name']=null;
                $one['pic1']=null;
            }
            unset($one);

            return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$gridInfo]);
        }

        //获取用户头像
        $avatar=(new UserController())->getUserNameAndAvatar($uid);

        //不为空
        foreach ($gridInfoExt as $ext)
        {
            foreach ($gridInfo as &$info)
            {
                if ($info['id']==$ext['gid'])
                {
                    $info['gName']=$info['name'];

                    if ($ext['showName']==1)
                    {
                        $info['name']=$ext['name'];
                    }else
                    {
                        $info['name']=null;
                    }

                    if ($ext['showPic1']==1)
                    {
                        $info['pic1']=$ext['pic1'];
                    }else
                    {
                        $info['pic1']=$avatar['avatar'];
                    }

                    $info['timestamp']=Carbon::parse($info['updated_at'])->timestamp;
                    $info['price']=$info['price']+$info['totle'];
                }
            }
        }
        unset($info);

        $gridInfo=arraySort1($gridInfo,['desc','updated_at']);
        $gridInfo=changeArrKey($gridInfo,['updated_at'=>'updatedAt']);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$gridInfo]);
    }

    //获取用户金钱
    public function getUserMoney($uid)
    {
        $res=Redis::connection('UserInfo')->hget($uid,'money');

        if ($res!=null)
        {
            //有数据
            return $res;

        }else
        {
            //没数据
            Redis::connection('UserInfo')->hset($uid,'money',Config::get('myDefine.InitMoney'));

            return Config::get('myDefine.InitMoney');
        }
    }

    //买卖结束后增加或减少用户金钱
    public function exprUserMoney($uid,$belongid,$money,$expr='-')
    {
        if ($expr=='+' && $belongid==0)
        {
            $res=Redis::connection('UserInfo')->hget($uid,'money');

            Redis::connection('UserInfo')->hset($uid,'money',$res + $money);

            return true;
        }

        //买方扣款 被买方加款
        if ($belongid==0)
        {
            $res=Redis::connection('UserInfo')->hget($uid,'money');

            Redis::connection('UserInfo')->hset($uid,'money',$res - $money);

            $this->setTradeTotleForCareer($uid);

        }else
        {
            $res=Redis::connection('UserInfo')->hget($uid,'money');

            Redis::connection('UserInfo')->hset($uid,'money',$res - $money);

            $res=Redis::connection('UserInfo')->hget($belongid,'money');

            Redis::connection('UserInfo')->hset($belongid,'money',$res + $money);

            $this->setTradeTotleForCareer($uid);
            $this->setTradeTotleForCareer($belongid);
        }

        return true;
    }

    //获取购地卡数量
    public function getBuyCardCount($uid)
    {
        $today=Carbon::now()->format('Ymd');

        $key='BuyCard_'.$today.'_'.$uid;

        if (Redis::connection('UserInfo')->get($key)===null)
        {
            //每天给5张
            Redis::connection('UserInfo')->set($key,Config::get('myDefine.InitBuyCard'));

            Redis::connection('UserInfo')->expire($key,86400);

            return Config::get('myDefine.InitBuyCard');
        }

        return Redis::connection('UserInfo')->get($key);
    }

    //减购地卡
    public function setBuyCardCount($uid,$num=1)
    {
        $today=Carbon::now()->format('Ymd');

        $key='BuyCard_'.$today.'_'.$uid;

        $count=Redis::connection('UserInfo')->get($key);

        Redis::connection('UserInfo')->set($key,$count - $num);

        Redis::connection('UserInfo')->expire($key,86400);

        return true;
    }

    //获取用户姓名和头像
    public function getUserNameAndAvatar($uid,$update=false)
    {
        //redis里没有就从tssj里拿
        $userinfo['name']=Redis::connection('UserInfo')->hget($uid,'name');
        $userinfo['avatar']=Redis::connection('UserInfo')->hget($uid,'avatar');

        //自动更新
        //if (rand(1,100) > 80) $update=true;

        if (($userinfo['name']===null && $uid!=0) || ($update===true && $uid!=0))
        {
            $res=DB::connection('tssj_old')->table('tssj_member')->where('userid',$uid)->first();

            $userinfo['name']=trim($res->username);
            $userinfo['avatar']=trim($res->avatar);

            //判断远程文件存不存在，如果存在就储存头像
            //http://www.wodeluapp.com/attachment/avatar/000/13/77/19_avatar_135.jpg
            $res=checkFileExists('http://www.wodeluapp.com/attachment/'.$userinfo['avatar']);

            if ($res)
            {
                $img=file_get_contents('http://www.wodeluapp.com/attachment/'.$userinfo['avatar']);

                $userinfo['avatar']=storeFile($img,$uid,'','avatar');

            }else
            {
                $userinfo['avatar']='';
            }

            Redis::connection('UserInfo')->hset($uid,'name',$userinfo['name']);
            Redis::connection('UserInfo')->hset($uid,'avatar',$userinfo['avatar']);
        }

        if ($uid==0)
        {
            $userinfo['name']='系统';
            $userinfo['avatar']='';
        }

        return $userinfo;
    }

    //更新用户头像redis集合
    public function changeAvatarAlready(Request $request)
    {
        $uid=$request->uid;

        Redis::connection('WriteLog')->sadd('ChangeAvatarAlready',$uid);

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //can i buy this grid ?
    public function canIBuyThisGrid($uid,$gridInfo)
    {
        //钱不够
        $money=$this->getUserMoney($uid);

        $need=(new GridController())->needToPay($gridInfo);

        if ($money < $need) return Config::get('resCode.607');
        //=========================================================================

        //购地卡不够
        $card=$this->getBuyCardCount($uid);

        if ($card <= 0) return Config::get('resCode.610');
        //=========================================================================

        //格子达到交易上线
        $limit=(new GridController())->getBuyLimit($gridInfo->name);

        if ($limit >= (new GridController())->getGridTodayBuyTotle($gridInfo->name)) return Config::get('resCode.609');
        //=========================================================================

        //交易保护中
        $tradeGuard=(new GridController())->getTradeGuard($gridInfo->name);

        if ($tradeGuard > 0) return Config::get('resCode.606');
        //=========================================================================

        //是否限定
        if ($gridInfo->showGrid==0) return Config::get('resCode.606');
        //=========================================================================

        return Config::get('resCode.200');
    }

    //获取最近的交易信息
    public function getRecentlyTradeInfo(Request $request)
    {
        $uid=$request->uid;
        $page=$request->page;
        $paytime=trim($request->paytime);

        if (empty($paytime) || $paytime=='')
        {
            $paytime=Carbon::now()->firstOfMonth()->toDateTimeString();
            $paytime=strtotime($paytime);

        }elseif (strlen($paytime)==8 && is_numeric($paytime))
        {
            $paytime=Carbon::parse($paytime)->timestamp;

        }else
        {
            return response()->json(['resCode' => Config::get('resCode.604')]);
        }

        $limit=10;
        $offset=($page-1)*$limit;

        //unix时间戳
        $suffix=date('Ym',$paytime);

        if (!Schema::connection('masterDB')->hasTable('buy_sale_info_'.$suffix)) return response()->json(['resCode' => Config::get('resCode.621')]);

        UserTradeInfoModel::suffix($suffix);

        //select * from `buy_sale_info_201904` where (`uid` = ? or `belong` = ?) and `paytime` >= ? order by `paytime` desc limit 10 offset 0
        $res=UserTradeInfoModel::where(function ($query) use ($uid)
        {
            $query->where('uid',$uid)->orWhere('belong',$uid);
        })
            ->where('paytime','>=',$paytime)
            ->orderBy('paytime','desc')
            ->limit($limit)
            ->offset($offset)
            ->get()->toArray();

        foreach ($res as &$val)
        {
            if (isset($val['paytime']))
            {
                $val['datetime']=date('Y-m-d H:i:s',$val['paytime']);
                $val['paytime']=formatDate($val['paytime']);

            }else
            {
                $val['paytime']=null;
                $val['datetime']=null;
            }
        }
        unset($val);

        return response()->json(['resCode' => Config::get('resCode.200'),'data'=>$res]);
    }

    //格子生涯概况
    public function getGridCareer(Request $request)
    {
        $uid=$request->uid;

        //当前拥有格子数量
        $currentGridTotle=GridModel::where('belong',$uid)->count();

        //最高交易价格
        $maximumGridPirce=GridModel::where('belong',$uid)->orderBy('hightPrice','desc')->first()->hightPrice;

        //最多拥有格子数量
        $maximumGridTotle=Redis::connection('UserInfo')->hget($uid,'BuyGridTotle');

        //累计交易次数，这个需求分布到了买卖格子接口，一条一条记录吧
        $tradeTotle=Redis::connection('UserInfo')->hget($uid,'TradeGridTotle');

        $res['currentGridTotle']=(string)$currentGridTotle;
        $res['maximumGridPirce']=(string)$maximumGridPirce;
        $res['maximumGridTotle']=(string)$maximumGridTotle;
        $res['tradeTotle']=$tradeTotle==null ? "0" : $tradeTotle;

        return response()->json(['resCode' => Config::get('resCode.200'),'data'=>$res]);
    }

    //记录格子生涯的累计交易次数
    public function setTradeTotleForCareer($uid,$num=1)
    {
        $tradeTotle=Redis::connection('UserInfo')->hget($uid,'TradeGridTotle');

        Redis::connection('UserInfo')->hset($uid,'TradeGridTotle',(int)$tradeTotle+$num);

        return true;
    }

    //分享
    public function shareOnePicture()
    {
        $img=Image::make(public_path('test.jpg'));

        $img->text('1234567890', 300, 200, function($font) {
            $font->file(public_path('ttf/AliFont.ttf'));
            $font->size(24);
            $font->color('#fdf6e3');
            $font->align('center');
            $font->valign('top');
        });

        $img->text('草泥马沙比m1ma', 300, 100, function($font) {
            $font->file(public_path('ttf/AliFont.ttf'));
            $font->size(24);
            $font->color('#fdf6e3');
            $font->align('center');
            $font->valign('top');
        })->save(public_path('test1.jpg'));

        return url('test1.jpg');
    }
}