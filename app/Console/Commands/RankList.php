<?php

namespace App\Console\Commands;

use App\Http\Controllers\QuanMinZhanLing\GridController;
use App\Http\Controllers\QuanMinZhanLing\UserController;
use App\Model\GridInfoModel;
use App\Model\GridModel;
use App\Model\RankListModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class RankList extends Command
{
    protected $signature = 'Grid:RankList';

    protected $description = '排行榜统计';

    protected $gridRankListKey = 'GridRankList';

    public function __construct()
    {
        parent::__construct();
    }

    //处理
    public function handle()
    {
        try
        {
            //个人资产
            $this->userAssets();

            //格子
            $this->gridAssets();

            //===================20190626之后更新的新排行榜===================
            //格子总价榜
            $this->gridTotlePrice();

            //格子数量榜
            $this->gridTotle();

            //购买格子纳税榜
            $this->gridTax();

            //迷雾总排行
            $this->fogTotal();

            //迷雾周排行
            $this->fogWeek();

            //===================20191128之后更新的新排行榜===================
            //用户买格子花费的总金额
            $this->payMoneyTotalForBuyGrid();


        }catch (\Exception $e)
        {

        }
    }

    //个人资产
    public function userAssets()
    {
        if (!Schema::connection('masterDB')->hasTable('user_rank_list'))
        {
            Schema::connection('masterDB')->create('user_rank_list', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->integer('uid')->unsigned()->nullable()->comment('用户主键')->index();
                $table->integer('now')->unsigned()->nullable()->comment('当前排名');
                $table->integer('last')->unsigned()->nullable()->comment('上一次排名');
                $table->integer('grid')->unsigned()->nullable()->comment('当前格子数');
                $table->bigInteger('gridPrice')->unsigned()->nullable()->comment('当前格子价值');
                $table->bigInteger('money')->unsigned()->nullable()->comment('当前有多少金币');
                $table->bigInteger('totleAssets')->unsigned()->nullable()->comment('总资产')->index();

            });
        }

        //取出所有用户的uid
        Redis::connection('UserInfo')->multi();//开启事务
        Redis::connection('UserInfo')->keys('1*');
        Redis::connection('UserInfo')->keys('2*');
        Redis::connection('UserInfo')->keys('3*');
        Redis::connection('UserInfo')->keys('4*');
        Redis::connection('UserInfo')->keys('5*');
        Redis::connection('UserInfo')->keys('6*');
        Redis::connection('UserInfo')->keys('7*');
        Redis::connection('UserInfo')->keys('8*');
        Redis::connection('UserInfo')->keys('9*');
        $all=Redis::connection('UserInfo')->exec();//提交事务

        $all=array_flatten($all);

        //取出所有用户的金币
        Redis::connection('UserInfo')->multi();//开启事务
        foreach ($all as $one)
        {
            Redis::connection('UserInfo')->hget($one,'money');
        }
        $money=Redis::connection('UserInfo')->exec();//提交事务

        //储存到排行榜专用的key中
        Redis::connection('UserInfo')->multi();//开启事务
        foreach ($all as $key=>$val)
        {
            Redis::connection('UserInfo')->hset('RankList_'.$val,'money',$money[$key]);
        }
        Redis::connection('UserInfo')->exec();//提交事务

        //算出每个用户格子总数和格子总价，这个所有有格子的用户
        $sql='select belong as uid,count(1) as grid,sum(price+totle) as gridPrice from grid where belong in (select distinct belong from grid where belong <> 0) group by belong';

        $dataInGridTable=DB::connection('masterDB')->transaction(function () use ($sql)
        {
            return DB::connection('masterDB')->select($sql);
        });

        //组成对象
        foreach ($dataInGridTable as &$oneUser)
        {
            obj2arr($oneUser);

            //取redis中的金币数量
            $money=Redis::connection('UserInfo')->hget('RankList_'.$oneUser['uid'],'money');

            $oneUser['money']=(int)$money;
            $oneUser['totleAssets']=$oneUser['gridPrice']+(int)$money;
        }
        unset($oneUser);

        //更新数据，这是所有有格子的用户
        foreach ($dataInGridTable as $oneUpdate)
        {
            $uid=$oneUpdate['uid'];

            unset($oneUpdate['uid']);

            RankListModel::updateOrCreate(['uid'=>$uid],$oneUpdate);
        }

        //更新排名
        $sql='select uid,rownum from (select a.*,(@rownum:=@rownum+1) as rownum from user_rank_list as a,(select @rownum:=0) as c order by a.totleAssets desc) as b';

        $data=DB::connection('masterDB')->select($sql);

        foreach ($data as $oneUpdate)
        {
            //如果本次名次和上一次不一样，更新排名
            $model=RankListModel::where('uid',$oneUpdate->uid)->where(function ($query) use ($oneUpdate){

                $query->where('now','<>',(int)$oneUpdate->rownum)->orWhere(function ($queryy){

                    $queryy->whereNull('now');

                });

            })->first();

            //没查到结果，说明一样
            if ($model==null) continue;

            //查到结果，说明排名变化，更新排名
            $model->now=='' ? $model->last=(int)$oneUpdate->rownum : $model->last=$model->now;
            $model->now=(int)$oneUpdate->rownum;
            $model->save();
        }
    }

    //最贵格子排行
    public function gridAssets()
    {
        //这个不用存表了，直接放redis里

        //统计价格最高的格子200名
        $res=GridModel::where('belong','>',0)->orderBy('price','desc')->orderBy('updated_at','asc')->limit(200)->get()->toArray();

        $data=[];

        $userController=new UserController();

        $gridController=new GridController();

        //名次
        $i=1;
        foreach ($res as $oneGrid)
        {
            $userInfo=$userController->getUserNameAndAvatar($oneGrid['belong']);

            //格子图片
            $gridInfo=GridInfoModel::where(['uid'=>$oneGrid['belong'],'gid'=>$oneGrid['id']])->first();

            if ($gridInfo==null)
            {
                $pic1=null;
                $pic2=null;
                $name=null;

            }else
            {
                $gridInfo->showPic1==1 ? $pic1=$gridInfo->pic1 : $pic1=null;
                $gridInfo->showPic2==1 ? $pic2=$gridInfo->pic2 : $pic2=null;
                $name=$gridInfo->name;
            }

            $data[]=[

                'row'=>$i,
                'uid'=>$oneGrid['belong'],
                'avatar'=>$userInfo['avatar'],
                'pic1'=>$pic1,
                'pic2'=>$pic2,
                'userName'=>$userInfo['name'],
                'gridName'=>$oneGrid['name'],
                'name'=>$name,
                'price'=>$gridController->nextNeedToPayOrGirdworth($oneGrid)

            ];

            $i++;
        }

        Redis::connection('WriteLog')->set($this->gridRankListKey,jsonEncode($data));
    }

    //格子总价榜
    public function gridTotlePrice()
    {
        $sql='select belong,sum(price) as priceTotle,count(1) as gridTotle from grid where belong <> 0 group by belong order by priceTotle desc,gridTotle desc';

        $res=DB::connection('masterDB')->select($sql);

        //名次
        $i=1;

        //准备做成json的数组
        $ret=[];

        $userObj=new UserController();

        foreach ($res as $one)
        {
            $userInfo=$userObj->getUserNameAndAvatar($one->belong);

            $ret[]=[
                'row'=>$i,
                'uid'=>$one->belong,
                'name'=>$userInfo['name'],
                'avatar'=>$userInfo['avatar'],
                'picInRedis1'=>Redis::connection('UserInfo')->hget($one->belong,'PicInRedis1'),
                'priceTotle'=>$one->priceTotle,
                'gridTotle'=>$one->gridTotle,
            ];

            $i++;
        }

        Redis::connection('WriteLog')->set('GridTotlePriceRank',jsonEncode($ret));

        return true;
    }

    //格子数量榜
    public function gridTotle()
    {
        $sql='select belong,count(1) as gridTotle from grid where belong <> 0 group by belong order by gridTotle desc';

        $res=DB::connection('masterDB')->select($sql);

        //名次
        $i=1;

        //准备做成json的数组
        $ret=[];

        $userObj=new UserController();

        foreach ($res as $one)
        {
            $userInfo=$userObj->getUserNameAndAvatar($one->belong);

            $ret[]=[
                'row'=>$i,
                'uid'=>$one->belong,
                'name'=>$userInfo['name'],
                'avatar'=>$userInfo['avatar'],
                'gridTotle'=>$one->gridTotle,
            ];

            $i++;
        }

        Redis::connection('WriteLog')->set('GridTotleRank',jsonEncode($ret));

        return true;
    }

    //购买格子纳税榜
    public function gridTax()
    {
        $ymd=date('Ymd',time());

        //记录税的key
        $key='BuyGridPayTax_'.$ymd;

        //手机端http请求的key
        $key2='GridTaxRank';

        $res=Redis::connection('WriteLog')->hgetall($key);

        if (empty($res) || $res==null)
        {
            Redis::connection('WriteLog')->set($key2,null);
        }

        $res=collect($res)->sort()->all();

        //名次
        $i=count($res);

        //返回
        $ret=[];

        $userObj=new UserController();

        foreach ($res as $key=>$val)
        {
            $userInfo=$userObj->getUserNameAndAvatar($key);

            $ret[]=[
                'row'=>$i,
                'uid'=>$key,
                'name'=>$userInfo['name'],
                'avatar'=>$userInfo['avatar'],
                'tax'=>$val,
            ];

            $i--;
        }

        Redis::connection('WriteLog')->set($key2,jsonEncode(arraySort1($ret,['asc','row'])));

        return true;
    }

    //迷雾总排行
    public function fogTotal()
    {

    }

    //迷雾周排行
    public function fogWeek()
    {

    }

    //用户买格子花费的总金额
    public function payMoneyTotalForBuyGrid()
    {

    }















    //同时更新多个记录，参数，表名，数组，别忘了在一开始use DB
    public function updateBatch($tableName='', $multipleData=[])
    {
        if( $tableName && !empty($multipleData) )
        {
            $updateColumn = array_keys($multipleData[0]);
            $referenceColumn = $updateColumn[0];
            unset($updateColumn[0]);
            $whereIn = "";

            $q = "UPDATE ".$tableName." SET ";
            foreach ( $updateColumn as $uColumn )
            {
                $q .=  $uColumn." = CASE ";

                foreach( $multipleData as $data )
                {
                    $q .= "WHEN ".$referenceColumn." = ".$data[$referenceColumn]." THEN '".$data[$uColumn]."' ";
                }

                $q .= "ELSE ".$uColumn." END, ";
            }

            foreach( $multipleData as $data )
            {
                $whereIn .= "'".$data[$referenceColumn]."', ";
            }

            $q = rtrim($q, ", ")." WHERE ".$referenceColumn." IN (".  rtrim($whereIn, ', ').")";

            return DB::connection('masterDB')->update(DB::connection('masterDB')->raw($q));

        }else
        {
            return false;
        }
    }
}
