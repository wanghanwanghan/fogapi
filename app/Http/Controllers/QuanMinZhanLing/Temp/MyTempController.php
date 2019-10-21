<?php

namespace App\Http\Controllers\QuanMinZhanLing\Temp;

use App\Exports\ArticleExport;
use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Model\Community\ArticleModel;
use App\Model\Community\CommentsModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Qiniu\Auth;
use Qiniu\Sms\Sms;

class MyTempController extends BaseController
{
    public function test()
    {
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
