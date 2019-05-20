<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\GridModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SecurityController extends BaseController
{
    //uv的rediskey前缀
    public $uvKey='AccessUV_';

    //pv的rediskey前缀
    public $pvKey='AccessPV_';

    //用户分布rediskey
    public $userDistribution='UserDistribution';

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
select elt(interval(tmp.gridTotle,0,10,20,30,40,50,60,70,80,90,100,110,120,130,140),'1','2','3','4','5','6','7','8','9','10','11','12','13','14','15') as rangeType,count(1) as num 
from (select belong,count(1) as gridTotle from grid group by belong having belong <> 0) as tmp 
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

                return array_combine(array_values($rangeType),array_values($rangeArry));

                break;

            case 'download_img':

                $imgContent=current($request->img);

                $imgContent=str_replace('data:image/png;base64,','',$imgContent);

                $imgContent=base64_decode($imgContent);

                $filename=str_random(8).'.jpg';
                $path=public_path('imgCanDelete/');

                file_put_contents($path.$filename,$imgContent);

                return response()->download($path.$filename);

                break;

            case '':

                break;

            default:

                break;
        }
    }




}