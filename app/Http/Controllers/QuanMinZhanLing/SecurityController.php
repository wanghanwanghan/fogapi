<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\GridModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class SecurityController extends BaseController
{
    //uv的rediskey前缀
    public $uvKey='AccessUV_';

    //pv的rediskey前缀
    public $pvKey='AccessPV_';

    //用户分布rediskey
    public $userDistribution='UserDistribution';

    //虚拟用户uid
    public $uid='103595,104994,191662,138283,106241,187126,18656,137544,18658,18657,104563,22357,137545,97105';
    public $uidArr=[103595,104994,191662,138283,106241,187126,18656,137544,18658,18657,104563,22357,137545,97105];

    //统计pv，访问量
    public function recodePV()
    {
        $day=Carbon::now()->format('Ymd');

        $key=$this->pvKey.$day;

        Redis::connection('SignIn')->incr($key);

        return true;
    }

    //统计uv，独立访客
    public function recodeUV(Request $request)
    {
        $day=Carbon::now()->format('Ymd');

        $key=$this->uvKey.$day;

        $ip=trim($request->getClientIp());

        if ($ip!='')
        {
            Redis::connection('SignIn')->zincrby($key,1,$ip);
        }

        return true;
    }

    //ajax
    public function ajax(Request $request)
    {
        switch ($request->type)
        {
            case 'get_uv':

                //拿出当月所有uv，当天往后减，直到后两位是01，等于减到了当月第一天
                $res=[];

                $day=Carbon::now()->format('Ymd');

                for ($i=1;$i<=33;$i++)
                {
                    $key=$this->uvKey.$day;

                    $arrKey=substr($day,-2);

                    $res[(int)$arrKey]=Redis::connection('SignIn')->zcard($key);

                    if ($arrKey=='01') break;

                    $day--;
                }

                return $res;

                break;

            case 'get_pv':

                //拿出当月所有uv，当天往后减，直到后两位是01，等于减到了当月第一天
                $res=[];

                $day=Carbon::now()->format('Ymd');

                for ($i=1;$i<=33;$i++)
                {
                    $key=$this->pvKey.$day;

                    $arrKey=substr($day,-2);

                    $res[(int)$arrKey]=(int)Redis::connection('SignIn')->get($key);

                    if ($arrKey=='01') break;

                    $day--;
                }

                return $res;

                break;

            case 'get_user_distribution':

                //还是要通过定时任务，把昨天的uvKey通过请求接口加过来
                //这里只要取得redis值就行，不计算

                return Redis::connection('SignIn')->zrevrange($this->userDistribution,0,4,'withscores');

                break;

            case 'get_all_grid_trade_info':

                $suffix=Carbon::now()->format('Ym');

                $date=DB::connection('masterDB')->select("select FROM_UNIXTIME(paytime,'%Y%m%d') as myDay,count(1) as myTotal from buy_sale_info_{$suffix} group by myDay");

                foreach ($date as $one)
                {
                    $all[$one->myDay]=$one->myTotal;
                }

                $mStart=Carbon::now()->startOfMonth()->format('Ymd');

                $mNow=Carbon::now()->format('Ymd');

                for ($i=1;$i<=33;$i++)
                {
                    if ($mNow - $mStart < 0) break;

                    $arrKey=(int)substr($mNow,-2);

                    if (array_key_exists($mNow,$all))
                    {
                        $res[$arrKey]=$all[$mNow];

                    }else
                    {
                        $res[$arrKey]=0;
                    }

                    $mNow--;
                }

                return $res;

                break;

            case 'get_grid_frequency':

                $suffix=Carbon::now()->format('Ym');

                $data=DB::connection('masterDB')->select("select gid,count(1) as count from buy_sale_info_{$suffix} group by gid order by count desc limit 0,5");

                foreach ($data as $one)
                {
                    $gridName=GridModel::where('id',$one->gid)->first();

                    $res[$gridName->name]=$one->count;
                }

                return $res;

                break;

            case 'get_grid_installation_base':

                $sql=<<<Eof
select elt(interval(tmpOne.gridTotle,0,10,20,30,40,50,60,70,80,90,100,110,120,130,140),'1','2','3','4','5','6','7','8','9','10','11','12','13','14','15') as rangeType,count(1) as num 
from (select belong,count(1) AS gridTotle from (select belong from grid where belong <> 0) as tmpTwo group by belong) as tmpOne 
group by rangeType;
Eof;
                //统计15个区间
                $rangeType=[
                    '1'=>'<10',
                    '2'=>'<20',
                    '3'=>'<30',
                    '4'=>'<40',
                    '5'=>'<50',
                    '6'=>'<60',
                    '7'=>'<70',
                    '8'=>'<80',
                    '9'=>'<90',
                    '10'=>'<100',
                    '11'=>'<110',
                    '12'=>'<120',
                    '13'=>'<130',
                    '14'=>'<140',
                    '15'=>'>140',
                ];

                $rangeArry=[
                    '1'=>0,
                    '2'=>0,
                    '3'=>0,
                    '4'=>0,
                    '5'=>0,
                    '6'=>0,
                    '7'=>0,
                    '8'=>0,
                    '9'=>0,
                    '10'=>0,
                    '11'=>0,
                    '12'=>0,
                    '13'=>0,
                    '14'=>0,
                    '15'=>0,
                ];

                $data=DB::connection('masterDB')->select($sql);

                foreach ($data as $one)
                {
                    $rangeArry[$one->rangeType]+=$one->num;
                }

                //多少人拥有格子
                $userTotle=array_sum(array_values($rangeArry));

                return ['data'=>array_combine(array_values($rangeType),array_values($rangeArry)),'userTotle'=>$userTotle];

                break;

            case 'download_img':

                $imgContent=current($request->img);

                $imgContent=str_replace('data:image/png;base64,','',$imgContent);

                $imgContent=base64_decode($imgContent);

                $filename=str_random(8).'.jpg';
                $path=public_path('imgCanDelete/');

                file_put_contents($path.$filename,$imgContent);

                return [url('imgCanDelete/'.$filename)];

                break;

            case 'get_user_publish_article_totle':

                //虚拟用户uid
                $arr=$this->uid;

                $suffix=Carbon::now()->year;

                //真实用户
                $sql="select right(myDay,2) as myDay,total from (select left(created_at,10) as myDay,count(1) as total from community_article_{$suffix} where uid not in ({$arr}) group by myDay) as tmp";

                $real=DB::connection('communityDB')->select($sql);

                //虚拟用户
                $sql="select right(myDay,2) as myDay,total from (select left(created_at,10) as myDay,count(1) as total from community_article_{$suffix} where uid in ({$arr}) group by myDay) as tmp";

                $notReal=DB::connection('communityDB')->select($sql);

                //整理数组
                foreach ($real as $one)
                {
                    $tmp[$one->myDay]=$one->total;
                }
                $real=$tmp;

                unset($tmp);

                foreach ($notReal as $one)
                {
                    $tmp[$one->myDay]=$one->total;
                }
                $notReal=$tmp;

                //今天是当月第几天
                $currentDay=(int)Carbon::now()->day;

                for ($i=1;$i<=$currentDay-1;$i++)
                {
                    isset($real[$i]) ?    null : $real[$i]=0;
                    isset($notReal[$i]) ? null : $notReal[$i]=0;
                }
                ksort($real);
                ksort($notReal);

                return [$real,$notReal,[array_sum($real),array_sum($notReal)]];

                break;

            case 'user_publish_article_totle_datatables_1':

                $now=Carbon::now();

                //只取得最近4年的？此处留坑
                $tmp=[];
                for ($i=0;$i<4;$i++)
                {
                    $suffix=$now->year - $i;

                    $table="community_article_{$suffix}";

                    if (!Schema::connection('communityDB')->hasTable($table)) continue;

                    $sql="select uid,count(1) as total from {$table} where uid not in ({$this->uid}) group by uid";

                    $res=DB::connection('communityDB')->select($sql);

                    foreach ($res as $one)
                    {
                        isset($tmp[$one->uid]) ? $tmp[$one->uid]=$tmp[$one->uid]+$one->total : $tmp[$one->uid]=$one->total;
                    }

                    arsort($tmp);
                }

                $res=[];
                foreach ($tmp as $k=>$v)
                {
                    $res[]=[$k=>$v];
                }

                //只取得前200个
                $res=collect($res)->slice(0,200);

                $tmp=[];
                foreach ($res as $one)
                {
                    $uid=key($one);
                    $publishTotal=current($one);

                    $userName=trim(Redis::connection('UserInfo')->hget($uid,'name'));

                    //如果名字是空
                    if ($userName=='')
                    {
                        $userName=DB::connection('tssj_old')->table('tssj_member')->where('userid',$uid)->first()->username;
                        Redis::connection('UserInfo')->hset($uid,'name',$userName);
                    }

                    $tmp[]=['uid'=>$uid,'userName'=>$userName,'publishTotal'=>$publishTotal];
                }

                return $tmp;

                break;











            default:

                break;
        }
    }




}
