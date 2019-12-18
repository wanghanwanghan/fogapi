<?php

namespace App\Http\Controllers\QuanMinZhanLing\FoodMap;

use App\Http\Traits\Singleton;
use App\Model\FoodMap\Patch;
use Illuminate\Support\Facades\Config;

class FoodMapPatchController
{
    use Singleton;

    public static $way=[
        '进app'=>1,//只送三次
        '签到'=>2,//每天一次
        '每日任务'=>3,//？？？
        '领钱袋'=>4,//每天一次
        '进入寻宝首页'=>5,//每天一次
        '买格子'=>6,//概率给
        '许愿池'=>7,//只是记录一下
        '交易所'=>8,//只是记录一下
    ];

    private static $db='FoodMap';

    private function getTreasureType()
    {
        return (new FoodMapController())->getTreasureType();
    }

    //用户通过某些方式得到一个碎片
    public function getOnePatchBelong($int,$lng,$lat)
    {
        $way=self::$way;

        if (!in_array($int,$way)) return null;

        //高德查询
        $lng=sprintf("%.5f",$lng);

        $lat=sprintf("%.5f",$lat);

        $url=Config::get('myDefine.AmapUrl');

        $key=array_random(Config::get('myDefine.AmapKey'));

        $fullUrl=$url.'?'.'key='.$key.'&location='.$lng.','.$lat;

        try
        {
            $res=file_get_contents($fullUrl);

        }catch (\Exception $e)
        {
            $res=null;
        }

        $res=jsonDecode($res);

        if ($res===null) return null;

        if (!isset($res['status']) || $res['status']!=1) return null;

        if (!isset($res['info']) || $res['info']!='OK') return null;

        if (!isset($res['regeocode']['addressComponent']['province']) || empty($res['regeocode']['addressComponent']['province'])) return null;

        //该坐标点的位置
        $province=trim($res['regeocode']['addressComponent']['province']);

        //返回北京，上海，广州

        return mb_substr($province,0,2);
    }

    //根据碎片中文名称换取碎片详细信息
    public function getPatchInfo($patchName)
    {
        return Patch::where('subject',$patchName)->first();
    }











}
