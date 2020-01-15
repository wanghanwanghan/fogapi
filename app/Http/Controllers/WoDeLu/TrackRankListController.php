<?php

namespace App\Http\Controllers\WoDeLu;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TrackRankListController extends Controller
{
    //获取总排行榜
    public function getRankList(Request $request)
    {
        $type=(int)trim($request->type);
        $uid=(int)trim($request->uid);
        $fog=trim($request->fog);
        $walk=(int)trim($request->walk);

        $obj=new TrackUserController();

        switch ($type)
        {
            case 1:

                //探索排行
                $fog=sprintf("%.2f",$fog);

                $data=$this->getType1($uid,$fog,$obj);

                return response()->json(['resCode'=>200,'data'=>$data[0],'my'=>$data[1]]);

                break;

            case 2:

                //运动排行
                $data=$this->getType2($uid,$walk,$obj);

                return response()->json(['resCode'=>200,'data'=>$data[0],'my'=>$data[1]]);

                break;
        }

        return true;
    }

    private function getType1($uid,$fog,TrackUserController $obj)
    {
        $key='WoDeLuRankListType1';

        Redis::connection('WriteLog')->zadd($key,$fog,$uid);

        //从大到小前200
        $limit200=Redis::connection('WriteLog')->zrevrange($key,0,199,'withscores');

        //整理数组
        $i=1;
        foreach ($limit200 as $k => $v)
        {
            $uInfo=$obj->getUserNameAndAvatar($k);

            $rank200[]=['row'=>$i,'name'=>$uInfo['name'],'avatar'=>$uInfo['avatar'],'fog'=>sprintf("%.2f",$v)];

            $i++;
        }

        //我的排名
        $myRank=Redis::connection('WriteLog')->zrevrank($key,$uid)+1;

        $uInfo=$obj->getUserNameAndAvatar($uid);

        $my=[
            'row'=>$myRank,
            'name'=>$uInfo['name'],
            'avatar'=>$uInfo['avatar'],
            'fog'=>$fog
        ];

        return [$rank200,[$my]];
    }

    private function getType2($uid,$walk,TrackUserController $obj)
    {
        $key='WoDeLuRankListType2_'.Carbon::now()->format('Ymd');

        Redis::connection('WriteLog')->zadd($key,$walk,$uid);

        //从大到小前200
        $limit200=Redis::connection('WriteLog')->zrevrange($key,0,199,'withscores');

        //整理数组
        $i=1;
        foreach ($limit200 as $k => $v)
        {
            $uInfo=$obj->getUserNameAndAvatar($k);

            $rank200[]=['row'=>$i,'name'=>$uInfo['name'],'avatar'=>$uInfo['avatar'],'walk'=>(int)$v];

            $i++;
        }

        //我的排名
        $myRank=Redis::connection('WriteLog')->zrevrank($key,$uid)+1;

        $uInfo=$obj->getUserNameAndAvatar($uid);

        $my=[
            'row'=>$myRank,
            'name'=>$uInfo['name'],
            'avatar'=>$uInfo['avatar'],
            'walk'=>$walk
        ];

        Redis::connection('WriteLog')->expire($key,86400);

        return [$rank200,[$my]];
    }



}
