<?php

namespace App\Http\Controllers\Server;

use Illuminate\Http\Request;
use Overtrue\EasySms\EasySms;

class SendSmsBase
{
    //配置文件
    public function getConfig()
    {
        return $config = [
            // HTTP 请求的超时时间（秒）
            'timeout' => 5.0,

            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                // 默认可用的发送网关
                'gateways' => [
                    'qcloud',
                ],
            ],
            // 可用的网关配置
            'gateways' => [
                'errorlog' => [
                    'file' => '/tmp/easy-sms.log',
                ],
                'qcloud' => [
                    'sdk_app_id' => '1400216573', // SDK APP ID
                    'app_key' => '8f59f9b1424047820bd4abcca99df95e', // APP KEY
                    'sign_name' => '探索世界APP', // 短信签名，如果使用默认签名，该字段可缺省（对应官方文档中的sign）
                ],
                //...
            ],
        ];
    }

    //发送短信
    public function sendSms()
    {
        $sms=new EasySms($this->getConfig());

        $sendContent=['content'=>'验证码：6666，您正在进行绑定手机操作，切勿将验证码泄露于他人。如验证码泄露会有帐号被盗风险。'];

        dd($sms->send(18618457910,$sendContent));

        return true;
    }

    //验证用户收到的验证码
    public function checkUserInput(Request $request)
    {

    }

    //探索世界旧的
    public function send_sms_tssj_new()
    {
        $mobile=18618457910;
        $msg='【探索世界APP】验证码：6666，您正在进行绑定手机操作，切勿将验证码泄露于他人。如验证码泄露会有帐号被盗风险。';
        $nationcode=86;

        $appid = "1400067654";
        $appkey = "4e4f5a259ef7984075a9629b2d02f395";
        $time = time();
        $random = substr(uniqid(), -10);

        if (empty($nationcode)||$nationcode==''||!is_numeric($nationcode)) $nationcode='86';

        $url = "https://yun.tim.qq.com/v5/tlssmssvr/sendsms?sdkappid={$appid}&random={$random}";

        $sig = "appkey={$appkey}&random={$random}&time={$time}&mobile={$mobile}";
        $sig = hash('sha256',$sig);

        $post_data = array(
            'ext'=>'',
            'extend'=>'',
            'msg'=>$msg,
            'sig'=>$sig,
            'tel'=>array(
                'mobile'=>$mobile,
                'nationcode'=>$nationcode,
            ),
            'time'=>$time,
            'type'=>0
        );

        $post_data = jsonEncode($post_data);

        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_ENCODING, "");
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ci, CURLOPT_HEADER, FALSE);
        curl_setopt($ci, CURLOPT_URL, $url );
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );//设置为POST
        curl_setopt($ci, CURLOPT_POST, 1);
        //把POST的变量加上
        curl_setopt($ci, CURLOPT_POSTFIELDS, $post_data);

        $response = curl_exec($ci);

        curl_close($ci);



        dd(jsonDecode($response));



        return $response;
    }







}
