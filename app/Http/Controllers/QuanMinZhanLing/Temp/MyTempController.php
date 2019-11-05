<?php

namespace App\Http\Controllers\QuanMinZhanLing\Temp;

use App\Exports\ArticleExport;
use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\Server\ContentCheckBase;
use App\Http\Controllers\WoDeLu\TrackFogController;
use App\Model\Community\ArticleModel;
use App\Model\Community\CommentsModel;
use Carbon\Carbon;
use DfaFilter\SensitiveHelper;
use Geohash\GeoHash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Qiniu\Auth;
use Qiniu\Sms\Sms;
use Ysnowflake\SnowflakeFacade;

class MyTempController extends BaseController
{
    public function test()
    {
        $res=Carbon::createFromTimestamp(time())->addDays(365)->format('Y-m-d H:i:s');

        dd($res);



        for ($i=1;$i<=30;$i++)
        {
            //dump(SnowflakeFacade::nextId());
            dump(randomUUID());
        }

        dd('wancheng');


        $obj=new GeoHash();

        $res10=$obj->encode('35.9262980000','105.3660250000','10');
        $res11=$obj->encode('35.9262980000','105.3660250000','11');
        $res12=$obj->encode('35.9262980000','105.3660250000','12');









        dd($res10,$res11,$res12);




        $wordData=[
            '傻逼',
        ];

        $handle=SensitiveHelper::init()->setTree($wordData);

        $content='傻哈逼';

        //检测是否含有敏感词
        $a=$handle->islegal($content);

        //敏感词替换为*为例（会替换为相同字符长度的*）
        $b=$handle->replace($content,'*',true);

        //或敏感词替换为***为例
        $c=$handle->replace($content,'***');

        //获取内容中所有的敏感词
        $d=$handle->getBadWord($content);

        //仅且获取一个敏感词
        $e=$handle->getBadWord($content,1);




        dd($a,$b,$c);




        dd(randomUserName());


        return Excel::download(new ArticleExport([]),'xxx.xlsx');




        $ak="PPlFNlpidaN6rrcRcgnLAKX2NC1EXSq98smv72XQ";
        $sk="QHwYaLC8XtB6IZ9o3K8fsCj8B4EMaYAd4KmkM8JI";
        $auth =new Auth($ak,$sk);
        $client=new Sms($auth);

        $template_id="1182207669239291904";
        $mobiles=['18618457910','15210929119','18511936093','15011449324'];

        $code=$this->GetRandStr(4);

        try
        {
            //发送短信
            $resp=$client->sendMessage($template_id,$mobiles,['code'=>$code]);

            dd($resp);

        }catch(\Exception $e)
        {
            echo "Error:", $e, "\n";
        }

        return view('inMap');
    }

    private function GetRandStr($len)
    {
        $chars=["0","1","2","3","4","5","6","7","8","9"];

        $charsLen=count($chars)-1;

        shuffle($chars);

        $output="";

        for ($i=0;$i<$len;$i++)
        {
            $output.=$chars[mt_rand(0,$charsLen)];
        }

        return $output;
    }






}
