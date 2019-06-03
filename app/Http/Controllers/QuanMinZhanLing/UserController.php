<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\GridInfoModel;
use App\Model\GridModel;
use App\Model\RankListModel;
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
    //用户钱袋
    public function userWallet(Request $request)
    {
        //get查看钱，post加钱

        $uid =(int)trim($request->uid);
        $area=intval((int)trim($request->area));

        $wallet=Redis::connection('UserInfo')->hget($uid,'wallet');

        if ($uid==0) return ['resCode'=>Config::get('resCode.200'),'money'=>0];

        //第一次进
        if ($wallet==null)
        {
            if ($request->isMethod('post'))
            {
                Redis::connection('UserInfo')->hset($uid,'wallet',json_encode(['area'=>$area,'lastUpdate'=>time()]));

                //加钱
                $this->exprUserMoney($uid,0,10,'+');
            }

            return response()->json(['resCode'=>Config::get('resCode.200'),'money'=>10]);
        }

        //以后每探索1km给25，自然时间是每12分钟给1
        $wallet=json_decode($wallet,true);

        $areaMoney=0;
        $timeMoney=0;

        if ($area > $wallet['area'])
        {
            $areaMoney=($area - $wallet['area']) * 25;

            //为这次存入redis做准备
            $wallet['area']=$area;
        }

        if (time() - $wallet['lastUpdate'] >= 720)
        {
            $timeMoney=intval((time() - $wallet['lastUpdate']) / 720);

            //为这次存入redis做准备
            $wallet['lastUpdate']=time();
        }

        $areaMoney + $timeMoney > 200 ? $money=200 : $money=$areaMoney + $timeMoney;

        if ($request->isMethod('post'))
        {
            Redis::connection('UserInfo')->hset($uid,'wallet',json_encode($wallet));

            //加钱
            $this->exprUserMoney($uid,0,$money,'+');
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'money'=>$money]);
    }

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

        if (empty($gridInfo)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>[]]);

        //取出id
        $id=array_pluck($gridInfo,'id');

        $gridInfoExt=GridInfoModel::where('uid',$uid)->whereIn('gid',$id)
            ->orderBy('updated_at','desc')
            ->get(['gid','name','showName','pic1','showPic1'])->toArray();

        $gridController=new GridController();

        if (empty($gridInfoExt))
        {
            foreach ($gridInfo as &$one)
            {
                $one['price']=$gridController->nextNeedToPayOrGirdworth($one);
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
                    $info['price']=$gridController->nextNeedToPayOrGirdworth($info);
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
            //从系统买
            $res=Redis::connection('UserInfo')->hget($uid,'money');

            Redis::connection('UserInfo')->hset($uid,'money',$res - $money);

            $this->setTradeTotleForCareer($uid);

        }else
        {
            //买方减钱
            $res=Redis::connection('UserInfo')->hget($uid,'money');

            Redis::connection('UserInfo')->hset($uid,'money',$res - $money);

            //卖方加钱
            $res=Redis::connection('UserInfo')->hget($belongid,'money');

            //钱不全额加，系统要扣除部分
            $money=(new GridController())->gridTradeTax('SaleUser',$money);

            Redis::connection('UserInfo')->hset($belongid,'money',$res + $money);

            //设置生涯信息
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
        $userinfo['name']  =Redis::connection('UserInfo')->hget($uid,'name');
        $userinfo['avatar']=Redis::connection('UserInfo')->hget($uid,'avatar');

        //自动更新
        //if (rand(1,100) > 80) $update=true;

        if (($userinfo['name']=='' && $uid!=0) || ($userinfo['avatar']=='' && $uid!=0) || ($update===true && $uid!=0))
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
                $userinfo['avatar']='/imgModel/systemAvtar.png';
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

        $need=(new GridController())->nextNeedToPayOrGirdworth($gridInfo);

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
        $uid=(int)trim($request->uid);
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

        $gridTradeTax=new GridController();

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

            //如果这条记录的belong属于该uid，需要显示收税以后的价格
            if ($val['belong']==$uid && $uid!=0)
            {
                $val['paymoney']=$gridTradeTax->gridTradeTax('SaleUser',$val['paymoney']);
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
        $currentGridTotle=(int)GridModel::where('belong',$uid)->count();

        if ($currentGridTotle==0)
        {
            //一个格子没有
            $res['currentGridTotle']=0;
            $res['maximumGridPirce']=(int)Redis::connection('UserInfo')->hget($uid,'HighestPirceOfGird');
            $res['maximumGridTotle']=(int)Redis::connection('UserInfo')->hget($uid,'BuyGridTotle');

            $tradeTotle=Redis::connection('UserInfo')->hget($uid,'TradeGridTotle');

            $res['tradeTotle']=$tradeTotle==null ? "0" : $tradeTotle;

            return response()->json(['resCode' => Config::get('resCode.200'),'data'=>$res]);
        }

        //最高交易价格
        $maximumGridPirce=(int)Redis::connection('UserInfo')->hget($uid,'HighestPirceOfGird');

        //最多拥有格子数量
        $maximumGridTotle=(int)Redis::connection('UserInfo')->hget($uid,'BuyGridTotle');

        //累计交易次数
        $tradeTotle=(int)Redis::connection('UserInfo')->hget($uid,'TradeGridTotle');

        $res['currentGridTotle']=$currentGridTotle;
        $res['maximumGridPirce']=$maximumGridPirce;
        $res['maximumGridTotle']=$maximumGridTotle;
        $res['tradeTotle']=$tradeTotle;

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
    public function sharePicture(Request $request)
    {
        $uid=$request->uid;

        if (!is_numeric($uid)) return response()->json(['resCode' => Config::get('resCode.601')]);

        $img=Image::make(public_path('imgModel/sharePicModel.jpg'));

        //用户的格子总数
        $gridTotle=GridModel::where('belong',$uid)->count();

        //格子的占地面积
        $gridArea=round(2.8 * 2.8 * $gridTotle,2).' km²';

        //总资产
        $moneyTotle=(new RankListController())->getUserAssets($uid);

        if (isset($moneyTotle['usr']['totleAssets']))
        {
            $moneyTotle=$moneyTotle['usr']['totleAssets'];
        }else
        {
            $moneyTotle=Redis::connection('UserInfo')->hget($uid,'money');
        }

        //从user_rank_list表中取数据
        $percent=RankListModel::where('uid',$uid)->first();

        if ($percent==null)
        {
            $percent='0%';

        }else
        {
            $num=RankListModel::count();

            if ($num==$percent->now)
            {
                $percent='0%';

            }else
            {
                //小超说这算法真机霸难
                $percent=intval((1-(($percent->now-1)/$num))*100).'%';
            }
        }

        $fontFree=public_path('ttf/Arial.ttf');

        $w=0;
        $h=0;

        $fileName='sharePicture_'.$uid.'.jpg';
        $savePath=public_path('imgCanDelete/'.$fileName);

        //已经占领了多少个格子
        $img->text($gridTotle, 686+$w, 500+$h, function($font) use ($fontFree){
            $font->file($fontFree);
            $font->size(75);
            $font->color('#e74a3b');
            $font->align('center');
            $font->valign('top');
        });

        //占地面积
        $img->text($gridArea, 600+$w, 605+$h, function($font) use ($fontFree){
            $font->file($fontFree);
            $font->size(75);
            $font->color('#e74a3b');
            $font->align('left');
            $font->valign('top');
        });

        //总资产
        $img->text(number_format($moneyTotle), 600+$w, 710+$h, function($font) use ($fontFree){
            $font->file($fontFree);
            $font->size(75);
            $font->color('#e74a3b');
            $font->align('left');
            $font->valign('top');
        });

        try
        {
            //超过多少用户
            $img->text($percent, 582+$w, 890+$h, function($font) use ($fontFree){
                $font->file($fontFree);
                $font->size(80);
                $font->color('#e74a3b');
                $font->align('center');
                $font->valign('top');
            })->save($savePath);

        }catch (\Exception $e)
        {
            return response()->json(['resCode' => Config::get('resCode.623')]);
        }

        //删除30分钟以前的图片
        delFileByCtime(public_path('imgCanDelete'),30);

        return response()->json(['resCode' => Config::get('resCode.200'),'data'=>url('imgCanDelete/'.$fileName."?".time())]);
    }
}