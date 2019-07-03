<?php

namespace App\Http\Controllers\QuanMinZhanLing\Pay;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use Yansongda\LaravelPay\Facades\Pay;

class PayController extends BaseController
{
    //微信app支付
    public function wechatPayInApp()
    {
        $order = [
            'out_trade_no' => time(),
            'body' => 'subject-测试',
            'total_fee'      => '1',
            'openid' => '',//jsxxx支付方式必须传
        ];

        $result = Pay::wechat()->app($order);


        dd($result);










    }








}
