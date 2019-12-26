<?php

namespace App\Http\Controllers\admin;

use App\Model\GridModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Vinkla\Hashids\Facades\Hashids;

class PlaceMapController extends AdminBaseController
{
    public function index()
    {
        return view('admin.placemap.place_map_index')->with(['res'=>123]);
    }

    public function ajax(Request $request)
    {
        switch ($request->type)
        {
            case 'get_one_or_all_data':

                //这个是输入框里的
                $uid=trim($request->uid);

                $selectCond=trim($request->selectCond);

                //无
                if ($selectCond==='0') $raw='1=1';
                //价格大于等于50的
                if ($selectCond==='1') $raw='price >= 50';
                //价格大于等于100的
                if ($selectCond==='2') $raw='price >= 100';
                //价格大于等于200的
                if ($selectCond==='3') $raw='price >= 200';
                //价格大于等于400的
                if ($selectCond==='4') $raw='price >= 400';
                //最贵的格子前5
                if ($selectCond==='5') $raw='1=1 order by price desc limit 5';
                //最贵的格子前50
                if ($selectCond==='6') $raw='1=1 order by price desc limit 50';
                //最贵的格子前500
                if ($selectCond==='7') $raw='1=1 order by price desc limit 500';
                //最贵的格子前5000
                if ($selectCond==='8') $raw='1=1 order by price desc limit 5000';
                //最近交易50个格子
                if ($selectCond==='9') $raw='1=1 order by updated_at desc limit 50';
                //最近交易150个格子
                if ($selectCond==='10') $raw='1=1 order by updated_at desc limit 150';
                //最近交易450个格子
                if ($selectCond==='11') $raw='1=1 order by updated_at desc limit 450';

                if ($uid=='')
                {
                    $data=GridModel::where('belong','<>',0)->whereRaw($raw)->get(['id','lat','lng','name','price','totle','belong','updated_at']);

                    $count=$data->count();

                    //整理数组 [{"count":6,"geometry":{"type":"Point","coordinates":["116.395645","39.929986"]}}]
                    foreach ($data as $onePoint)
                    {
                        $res[]=[
                            'count'=>$onePoint->price,
                            'uid'=>$onePoint->belong,
                            'name'=>$onePoint->name,
                            'price'=>$onePoint->price,
                            'totle'=>$onePoint->totle,
                            'updated_at'=>$onePoint->updated_at->format('Y-m-d H:i:s'),
                            'geometry'=>[
                                'type'=>'Point',
                                'coordinates'=>[$onePoint->lng,$onePoint->lat],
                            ],
                        ];
                    }

                    return ['resCode'=>200,'data'=>$res,'count'=>$count];
                }

                if (is_numeric($uid))
                {
                    //单用户数据
                    $data=GridModel::where('belong',$uid)->whereRaw($raw)->get(['id','lat','lng','name','price','totle','belong','updated_at']);

                    if (empty($data->toArray())) return ['resCode'=>201,'data'=>[],'count'=>0];

                    $count=$data->count();

                    //整理数组 [{"count":6,"geometry":{"type":"Point","coordinates":["116.395645","39.929986"]}}]
                    foreach ($data as $onePoint)
                    {
                        $res[]=[
                            'count'=>$onePoint->price,
                            'uid'=>$onePoint->belong,
                            'name'=>$onePoint->name,
                            'price'=>$onePoint->price,
                            'totle'=>$onePoint->totle,
                            'updated_at'=>$onePoint->updated_at->format('Y-m-d H:i:s'),
                            'geometry'=>[
                                'type'=>'Point',
                                'coordinates'=>[$onePoint->lng,$onePoint->lat],
                            ],
                        ];
                    }

                    return ['resCode'=>200,'data'=>$res,'count'=>$count];
                }

                if ($uid==='sbbbkios' || $uid==='sbbbkandroid')
                {
                    if ($uid==='sbbbkios') $uid=18426;
                    if ($uid==='sbbbkandroid') $uid=30209;

                    $date=Carbon::now()->format('Ymd');

                    $key="AccordingToUidUploadLatLng_{$uid}_{$date}";

                    $latlngInRedis=Redis::connection('default')->zrevrange($key,0,-1,'withscores');

                    //整理数组 [{"count":6,"geometry":{"type":"Point","coordinates":["116.395645","39.929986"]}}]
                    foreach ($latlngInRedis as $k => $v)
                    {
                        //$k是经纬度 $v是unixtime
                        $tmp=explode('_',$k);

                        $hash=Hashids::decode($tmp[0]);
                        $lat=$hash[0].'.'.$hash[1];

                        $hash=Hashids::decode($tmp[1]);
                        $lng=$hash[0].'.'.$hash[1];

                        $res[]=[
                            'count'=>'1',
                            'uid'=>$uid,
                            'name'=>'测试',
                            'price'=>'测试',
                            'totle'=>$v,
                            'updated_at'=>date('Y-m-d H:i:s',$v),
                            'geometry'=>[
                                'type'=>'Point',
                                'coordinates'=>[$lng,$lat],
                            ],
                        ];
                    }

                    $res=arraySort1($res,['desc','totle']);

                    $res[0]['count']='count';

                    return ['resCode'=>200,'data'=>$res,'count'=>count($res)];
                }

                //查单个格子
                $data=GridModel::where('name',$uid)->whereRaw($raw)->get(['id','lat','lng','name','price','totle','belong','updated_at']);

                if (empty($data->toArray())) return ['resCode'=>202,'data'=>[],'count'=>0];

                $count=$data->count();

                //整理数组 [{"count":6,"geometry":{"type":"Point","coordinates":["116.395645","39.929986"]}}]
                foreach ($data as $onePoint)
                {
                    $res[]=[
                        'count'=>$onePoint->price,
                        'uid'=>$onePoint->belong,
                        'name'=>$onePoint->name,
                        'price'=>$onePoint->price,
                        'totle'=>$onePoint->totle,
                        'updated_at'=>$onePoint->updated_at!='' ? $onePoint->updated_at->format('Y-m-d H:i:s') : null,
                        'geometry'=>[
                            'type'=>'Point',
                            'coordinates'=>[$onePoint->lng,$onePoint->lat],
                        ],
                    ];
                }

                return ['resCode'=>200,'data'=>$res,'count'=>$count];

                break;

            case 'get_user_fog':

                $uid=trim($request->uid);

                if (!is_numeric($uid)) return ['resCode'=>500];

                return ['resCode'=>500,'data'=>'','count'=>''];

                break;
        }

        return ['resCode'=>500];
    }
}
