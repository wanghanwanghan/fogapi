<?php

namespace App\Http\Controllers\admin;

use App\Model\GridModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

                $uid=trim($request->uid);

                if ($uid=='')
                {
                    //全部数据
                    $data=Cache::remember('allPoint',600,function()
                    {
                        return GridModel::where('belong','<>',0)->get(['id','lat','lng','name','price','totle','belong','updated_at']);
                    });

                    //$data=GridModel::where('belong','<>',0)->get(['id','lat','lng','name','price','totle','belong','updated_at']);

                    $count=$data->count();

                    //整理数组 [{"count":6,"geometry":{"type":"Point","coordinates":["116.395645","39.929986"]}}]
                    foreach ($data as $onePoint)
                    {
                        $res[]=[
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
                    $data=GridModel::where('belong',$uid)->get(['id','lat','lng','name','price','totle','belong','updated_at']);

                    if (empty($data->toArray())) return ['resCode'=>201,'data'=>[],'count'=>0];

                    $count=$data->count();

                    //整理数组 [{"count":6,"geometry":{"type":"Point","coordinates":["116.395645","39.929986"]}}]
                    foreach ($data as $onePoint)
                    {
                        $res[]=[
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

                return ['resCode'=>500,'data'=>[],'count'=>0];

                break;
        }

        return ['resCode'=>500];
    }
}