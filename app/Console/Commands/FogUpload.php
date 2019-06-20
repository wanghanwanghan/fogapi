<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class FogUpload extends Command
{
    protected $signature = 'Tssj:FogUpload';

    protected $description = '处理用户上传的迷雾';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        while(true)
        {
            $one=Redis::connection('TssjFog')->rpop('FogUploadList');

            //没东西就退出
            if ($one=='') break;

            //解析
            $res=jsonDecode($one);

            //不含有处理内容，跳过该条数据
            if (!isset($res['uid']) || !isset($res['data'])) continue;

            //uid不正确
            if (!is_numeric($res['uid']) || $res['uid'] <= 0) continue;

            //经纬度不正确
            if ($res['data']['lat']==999 || $res['data']['lng']==999) continue;






        }

        $Geo=new \Geohash\GeoHash();

        $lng=\sprintf("%.4f",'108.6548');
        $lat=\sprintf("%.4f",'40.4503');

        $geohash=$Geo->encode($lat,$lng,'8');





        return true;
    }








}
