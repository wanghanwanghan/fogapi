<?php

namespace App\Http\Controllers\admin;

use EasyWeChat\Factory;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WechatController extends AdminBaseController
{
    //首页
    public function index()
    {
        return view('admin.wechat.wechat_index');
    }

    //请求微信接口的公用配置, 所以单独提出来
    private function payment()
    {
        //必要配置, 这些都是之前在.env里配置好的
        $config=[

            'app_id' => config('wechat.payment.default.app_id'),
            'mch_id' => config('wechat.payment.default.mch_id'),
            'key'    => config('wechat.payment.default.key'),//API 密钥
            'notify_url' => config('wechat.payment.default.notify_url'),//通知地址

        ];

        //这个就是easywechat封装的了,一行代码搞定,照着写就行了
        $app=Factory::payment($config);

        return $app;
    }

    //请求二维码
    public function makeQr(Request $request)
    {
        //$qr=QrCode::format('png')->size(200)->margin(1)->generate('http://www.baidu.com');
        //<img class="thumbnail img-responsive" src="data:image/png;base64, {!! base64_encode($qrcode) !!}">
        $qr=QrCode::size(200)->margin(1)->generate('http://www.baidu.com');

        return ['code'=>200,'data'=>$qr];
    }

    //监听是否支付成功
    public function listening(Request $request)
    {
        $arr=[
            200,
            640,
            300,
            300,
            300,
            300,
            300,
            300,
            300,
            300,
        ];

        return ['code'=>array_random($arr),'data'=>'listening order'];
    }


















    public function place_order($id)
    {
        // 因为没有先创建订单, 所以这里先生成一个随机的订单号, 存在 pay_log 里, 用来标识订单, 支付成功后再把这个订单号存到 order 表里
        $order_sn = date('ymd').substr(time(),-5).substr(microtime(),2,5);
        // 根据文章 id 查出文章价格
        $post_price = optional(Post::where('id', $id)->first())->pirce;
        // 创建 Paylog 记录
        PayLog::create([
            'appid' => config('wechat.payment.default.app_id'),
            'mch_id' => config('wechat.payment.default.mch_id'),
            'out_trade_no' => $order_sn,
            'post_id' => $id
        ]);

        $app = $this->payment();

        $total_fee = env('APP_DEBUG') ? 1 : $post_price;
        // 用 easywechat 封装的方法请求微信的统一下单接口
        $result = $app->order->unify([
            'trade_type'       => 'NATIVE', // 原生支付即扫码支付，商户根据微信支付协议格式生成的二维码，用户通过微信“扫一扫”扫描二维码后即进入付款确认界面，输入密码即完成支付。
            'body'             => '投资平台-订单支付', // 这个就是会展示在用户手机上巨款界面的一句话, 随便写的
            'out_trade_no'     => $order_sn,
            'total_fee'        => $total_fee,
            'spbill_create_ip' => request()->ip(), // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
        ]);

        if ($result['result_code'] == 'SUCCESS') {
            // 如果请求成功, 微信会返回一个 'code_url' 用于生成二维码
            $code_url = $result['code_url'];
            return [
                'code'     => 200,
                // 订单编号, 用于在当前页面向微信服务器发起订单状态查询请求
                'order_sn' => $order_sn,
                // 生成二维码
                'html' => QrCode::size(200)->generate($code_url),
            ];
        }
    }


}