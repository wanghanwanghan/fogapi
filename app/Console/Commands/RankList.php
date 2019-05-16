<?php

namespace App\Console\Commands;

use App\Http\Controllers\QuanMinZhanLing\UserController;
use App\Model\GridInfoModel;
use App\Model\GridModel;
use App\Model\RankListModel;
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

    //处理
    public function handle()
    {
        try
        {
            //个人资产
            $this->userAssets();

        }catch (\Exception $e)
        {

        }

        try
        {
            //格子
            $this->gridAssets();

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

        $sql='select belong as uid,count(1) as grid,sum(price+totle) as gridPrice from grid where belong in (select belong from grid group by belong having belong <> 0) group by belong';

        $dataInGridTable=DB::connection('masterDB')->select($sql);

        //组成对象
        foreach ($dataInGridTable as &$oneUser)
        {
            obj2arr($oneUser);

            //取redis中的金币数量
            $money=Redis::connection('UserInfo')->hget($oneUser['uid'],'money');

            $oneUser['money']=(int)$money;
            $oneUser['totleAssets']=$oneUser['gridPrice']+(int)$money;
        }
        unset($oneUser);

        //更新数据
        foreach ($dataInGridTable as $oneUpdate)
        {
            $uid=$oneUpdate['uid'];

            unset($oneUpdate['uid']);

            RankListModel::updateOrCreate(['uid'=>$uid],$oneUpdate);
        }

        //last=now
        DB::connection('masterDB')->update('update user_rank_list set last=now');

        //更新排名
        $sql='select uid,rownum from (select a.*,(@rownum:=@rownum+1) as rownum from user_rank_list as a,(select @rownum:=0) as c order by a.totleAssets desc) as b';
        $data=DB::connection('masterDB')->select($sql);

        foreach ($data as $oneUpdate)
        {
            RankListModel::updateOrCreate(['uid'=>$oneUpdate->uid],['now'=>(int)$oneUpdate->rownum]);
        }
    }

    //最贵格子
    public function gridAssets()
    {
        //这个不用存表了，直接放redis里

        //统计价格最高的格子200名
        $res=GridModel::where('belong','>',0)->orderBy('price','desc')->limit(200)->offset(0)->get()->toArray();

        $data=[];

        $userController=new UserController();

        //名次
        $i=1;
        foreach ($res as $oneGrid)
        {
            $userInfo=$userController->getUserNameAndAvatar($oneGrid['belong']);

            //格子图片
            $pic1=GridInfoModel::where(['uid'=>$oneGrid['belong'],'gid'=>$oneGrid['id'],'showPic1'=>1])->first();

            //格子第一显示图片
            $pic2=GridInfoModel::where(['uid'=>$oneGrid['belong'],'gid'=>$oneGrid['id'],'showPic2'=>1])->first();

            $data[]=[

                'row'=>$i,
                'uid'=>$oneGrid['belong'],
                'avatar'=>$userInfo['avatar'],
                'pic1'=>$pic1==null ? null : $pic1->pic1,
                'pic2'=>$pic2==null ? null : $pic2->pic2,
                'userName'=>$userInfo['name'],
                'gridName'=>$oneGrid['name'],
                'price'=>$oneGrid['price']+$oneGrid['totle']

            ];

            $i++;
        }

        Redis::connection('WriteLog')->set($this->gridRankListKey,json_encode($data));
    }
}
