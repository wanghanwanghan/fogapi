<?php

namespace App\Http\Controllers\Server;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ContentCheckBase
{
    //向授权服务地址https://aip.baidubce.com/oauth/2.0/token发送请求（推荐使用POST），并在URL中带上以下参数：
    //
    //grant_type     必须参数 固定为client_credentials
    //client_id      必须参数 应用的API Key
    //client_secret  必须参数 应用的Secret Key
    //例如
    //https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=Va5yQRHlA4Fq5eR3LT0vuXV4&client_secret=0rDSjzQ20XUj5itV6WRtznPQSzr5pVw2

    //login page
    //https://login.bce.baidu.com/?account=&redirect=http%3A%2F%2Fconsole.bce.baidu.com%2F%3Ffromai%3D1#/aip/overview

    public $label=[
        0=>'绝对没有',
        1=>'暴恐违禁',
        2=>'文本色情',
        3=>'政治敏感',
        4=>'恶意推广',
        5=>'低俗辱骂',
        6=>'低质灌水'
    ];

    public function check($content)
    {
        if ($content=='' || empty($content)) return response()->json(['resCode' => Config::get('resCode.616')]);

        if (mb_strlen($content) < 0) return response()->json(['resCode' => Config::get('resCode.617')]);

        //缓存25天
        $token=Cache::remember('ContentCheckToken',36000,function ()
        {
            $res=file_get_contents('https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=EL5edZghypl5rNhZOKriA8eh&client_secret=AYlNbCLlfRt5OYy5QT8mzkN5OXZjzNcY');

            $res=jsonDecode($res);

            return $res['access_token'];
        });

        $url="https://aip.baidubce.com/rest/2.0/antispam/v2/spam?access_token={$token}";

        $content=['content'=>$content];

        $res=curlSend($url,$content,true,['Content-Type:application/x-www-form-urlencoded']);

        $res=jsonDecode($res['msg'],'json');

        //$logid=$res->log_id;

        $result=$res->result;

        $res=null;

        //处理结果
        if (!empty($result->review) || !empty($result->reject))
        {
            //含有违禁
            foreach ($result->review as $row)
            {
                if (array_key_exists($row->label,$this->label))
                {
                    $label=$this->label[$row->label];
                }

                $res[]=['label'=>$label,'hit'=>$row->hit];
            }

            foreach ($result->reject as $row)
            {
                if (array_key_exists($row->label,$this->label))
                {
                    $label=$this->label[$row->label];
                }

                $res[]=['label'=>$label,'hit'=>$row->hit];
            }
        }

        return $res;
    }
}