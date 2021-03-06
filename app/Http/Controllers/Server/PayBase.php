<?php

namespace App\Http\Controllers\Server;

use App\Events\CreateTssjOrderEvent;
use App\Events\CreateWodeluOrderEvent;
use App\Http\Controllers\QuanMinZhanLing\UserController;
use App\Http\Controllers\TanSuoShiJie\AboutUserController;
use App\Http\Controllers\WoDeLu\TrackUserController;
use Carbon\Carbon;
use Ignited\LaravelOmnipay\Facades\OmnipayFacade;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Yansongda\LaravelPay\Facades\Pay;

class PayBase
{
    public function choseProduct($productId,$plant='android')
    {
        $product=$productId;

        $arr=[
            '1'=>6,     //一个月vip
            '2'=>18,    //三个月vip
            '3'=>60,    //一年vip
            '4'=>6,     //100km
            '5'=>12,    //200km
            '6'=>18,    //300km
            '7'=>30,    //550km
            '8'=>45,    //750km
            '9'=>60,    //1000km

            '255'=>1,   //测试
        ];

        $subject=[
            '1'=>'一个月vip',
            '2'=>'三个月vip',
            '3'=>'一年vip',
            '4'=>'100km',
            '5'=>'200km',
            '6'=>'300km',
            '7'=>'550km',
            '8'=>'750km',
            '9'=>'1000km',

            '255'=>'测试',
        ];

        if ($plant==='ios')
        {
            $arr=[
                'wodeluapp.zujiyigeyuehuiyuan'=>6,   //一个月vip
                'wodeluapp.zujisangeyuehuiyuan'=>18, //三个月vip
                'wodeluapp.zujinianhuiyuan'=>60,     //一年vip
                'wodeluapp.zuji100km'=>6,            //100km
                'wodeluapp.zuji200km'=>12,           //200km
                'wodeluapp.zuji300km'=>18,           //300km
                'wodeluapp.zuji550km'=>30,           //550km
                'wodeluapp.zuji750km'=>45,           //750km
                'wodeluapp.zuji1000km'=>60,          //1000km

                '255'=>1,   //测试
            ];

            $subject=[
                'wodeluapp.zujiyigeyuehuiyuan'=>'一个月vip',
                'wodeluapp.zujisangeyuehuiyuan'=>'三个月vip',
                'wodeluapp.zujinianhuiyuan'=>'一年vip',
                'wodeluapp.zuji100km'=>'100km',
                'wodeluapp.zuji200km'=>'200km',
                'wodeluapp.zuji300km'=>'300km',
                'wodeluapp.zuji550km'=>'550km',
                'wodeluapp.zuji750km'=>'750km',
                'wodeluapp.zuji1000km'=>'1000km',

                '255'=>'测试',
            ];

            $productIdArr=[
                'wodeluapp.zujiyigeyuehuiyuan'=>1,
                'wodeluapp.zujisangeyuehuiyuan'=>2,
                'wodeluapp.zujinianhuiyuan'=>3,
                'wodeluapp.zuji100km'=>4,
                'wodeluapp.zuji200km'=>5,
                'wodeluapp.zuji300km'=>6,
                'wodeluapp.zuji550km'=>7,
                'wodeluapp.zuji750km'=>8,
                'wodeluapp.zuji1000km'=>9,
            ];

            $product=$productIdArr[$productId];
        }

        if (isset($arr[$productId])) return [$arr[$productId],$subject[$productId],$product];

        return false;
    }

    public function createTable($type)
    {
        $year=Carbon::now()->year;

        switch ($type)
        {
            case 'wodelu':

                if (!Schema::connection('userOrder')->hasTable($type.$year))
                {
                    Schema::connection('userOrder')->create($type.$year, function (Blueprint $table)
                    {
                        $table->increments('id')->unsigned()->comment('订单主键');
                        $table->integer('uid')->unsigned()->comment('用户主键');
                        $table->string('orderId','50')->comment('订单号uuid');
                        $table->string('transactionId','50')->nullable()->comment('订单号');
                        $table->text('receiptData')->nullable()->comment('苹果receiptData');
                        $table->tinyInteger('productId')->unsigned()->comment('产品编号');
                        $table->string('productSubject','100')->comment('产品名称');
                        $table->integer('price')->unsigned()->comment('订单金额');
                        $table->integer('orderTime')->unsigned()->comment('下单时间');
                        $table->integer('payTime')->unsigned()->nullable()->comment('异步通知时间');
                        $table->tinyInteger('status')->unsigned()->comment('订单状态，0未付，1付款完成');
                        $table->tinyInteger('autoPay')->unsigned()->nullable()->comment('自动订阅状态');
                        $table->string('plant','10')->comment('ios android');
                        $table->timestamps();
                        $table->index(['uid','orderId']);
                        $table->index(['uid','transactionId']);
                        $table->index('orderId');
                        $table->engine='InnoDB';
                    });
                }

                break;

            case 'tssj':

                if (!Schema::connection('userOrder')->hasTable($type.$year))
                {
                    Schema::connection('userOrder')->create($type.$year, function (Blueprint $table)
                    {
                        $table->increments('id')->unsigned()->comment('订单主键');
                        $table->integer('uid')->unsigned()->comment('用户主键');
                        $table->string('orderId','50')->comment('订单号uuid');
                        $table->string('transactionId','50')->nullable()->comment('订单号');
                        $table->text('receiptData')->nullable()->comment('苹果receiptData');
                        $table->tinyInteger('productId')->unsigned()->comment('产品编号');
                        $table->string('productSubject','100')->comment('产品名称');
                        $table->integer('price')->unsigned()->comment('订单金额');
                        $table->integer('orderTime')->unsigned()->comment('下单时间');
                        $table->integer('payTime')->unsigned()->nullable()->comment('异步通知时间');
                        $table->tinyInteger('status')->unsigned()->comment('订单状态，0未付，1付款完成');
                        $table->tinyInteger('autoPay')->unsigned()->nullable()->comment('自动订阅状态');
                        $table->string('plant','10')->comment('ios android');
                        $table->timestamps();
                        $table->index(['uid','orderId']);
                        $table->index(['uid','transactionId']);
                        $table->index('orderId');
                        $table->engine='InnoDB';
                    });
                }

                break;
        }
    }

    //我的路支付（阿里）
    public function wodeluAlipay(Request $request)
    {
        //创建
        $this->createTable('wodelu');

        $uid=$request->uid;

        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //安卓还是ios
        $type=$request->type;

        //需要付款多少钱
        $price=$this->choseProduct($request->productId,$type);

        if (!$price) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $subject=$price[1];

        //生成订单号
        $orderId=randomUUID();

        if ($type=='android')
        {
            $res = Pay::alipay()->app([
                'subject' => $subject,
                'out_trade_no' => $orderId,
                'total_amount' => sprintf("%.2f",$price[0]),
            ]);

            event(new CreateWodeluOrderEvent([
                'uid'=>$uid,
                'price'=>$price[0],
                'orderTime'=>time(),
                'orderId'=>$orderId,
                'subject'=>$subject,
                'type'=>$type,
                'productId'=>$request->productId,
            ]));

            return response()->json(['resCode'=>Config::get('resCode.200'),'str'=>$res->getContent()]);

        }elseif ($type=='ios')
        {

        }else
        {

        }
    }

    //我的路支付回调（阿里）
    public function wodeluAlipayNotify(Request $request)
    {
        $alipay=Pay::alipay();

        try
        {
            //data返回的是laravel集合
            $data=$alipay->verify(); // 是的，验签就这么简单！

            Redis::connection('default')->set('androidAlipayReturn',jsonEncode($data));

            // 请自行对 trade_status 进行判断及其它逻辑进行判断，在支付宝的业务通知中，只有交易通知状态为 TRADE_SUCCESS 或 TRADE_FINISHED 时，支付宝才会认定为买家付款成功。
            // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号；
            // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）；
            // 3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）；
            // 4、验证app_id是否为该商户本身。
            // 5、其它业务逻辑情况

            //是否支付成功
            if (!isset($data->trade_status) || $data->trade_status!='TRADE_SUCCESS') return response()->json(['resCode'=>Config::get('resCode.641'),'status'=>'fail']);

            //找到找到订单，设置状态
            $orderId=$data->out_trade_no;

            $suffix=Carbon::now()->year;

            $res=DB::connection('userOrder')->table('wodelu'.$suffix)->where('orderId',$orderId)->first();

            //金额不正确
            if ((int)$res->price!==(int)$data->total_amount) return response()->json(['resCode'=>Config::get('resCode.642'),'status'=>'fail']);

            DB::connection('userOrder')->table('wodelu'.$suffix)->where('orderId',$orderId)->update(['transactionId'=>$data->trade_no,'payTime'=>time(),'status'=>1,'updated_at'=>date('Y-m-d H:i:s',time())]);

        }catch (\Exception $e)
        {
            // $e->getMessage();
            // laravel 框架中请直接 `return $alipay->success()`
            return response()->json(['resCode'=>Config::get('resCode.643'),'status'=>'fail']);
        }

        //如果都通过了
        $uid=$res->uid;

        $productId=(int)$res->productId;

        //操作对应的$productId逻辑
        (new TrackUserController())->modifyVipStatus($uid,$productId);

        return response()->json(['resCode'=>Config::get('resCode.200'),'status'=>$alipay->success()->send()]);
    }

    //我的路支付回调（苹果内购）
    public function wodeluApplePayNotify(Request $request)
    {
        //创建
        $this->createTable('wodelu');

        $receiptData=jsonDecode($request->receiptData);

        if ($receiptData!==null)
        {
            $receiptData=$receiptData[0];
        }else
        {
            //这是直接传的，不是json格式
            $receiptData=$request->receiptData;
        }

        $uid=$request->uid;

        //给苹果验证
        $data=$this->acurl($receiptData,1);

        $data=jsonDecode($data);

        //* 21000 App Store不能读取你提供的JSON对象
        //* 21002 receipt-data域的数据有问题
        //* 21003 receipt无法通过验证
        //* 21004 提供的shared secret不匹配你账号中的shared secret
        //* 21005 receipt服务器当前不可用
        //* 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
        //* 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
        //* 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务

        if (intval($data['status'])!==0)
        {
            $data=$this->acurl($receiptData);

            $data=jsonDecode($data);
        }

        //支付失败
        if (intval($data['status'])!==0) return response()->json(['resCode'=>Config::get('resCode.641'),'msg'=>$data]);

        //根据最新的一单transaction_id查询是否处理了
        isset($data['receipt']['in_app']) ? $in_app=$data['receipt']['in_app'] : $in_app=[];

        if (empty($in_app)) return response()->json(['resCode'=>Config::get('resCode.641'),'status'=>'in_app empty']);

        //整理好，取第一个
        $in_app=current(arraySort1($in_app,['desc','purchase_date_ms']));

        $product_id=$in_app['product_id'];
        $price=$this->choseProduct($product_id,'ios');
        $transactionId=$in_app['transaction_id'];

        //查看这个订单是不是处理了
        $suffix=Carbon::now()->year;
        $res=DB::connection('userOrder')->table('wodelu'.$suffix)->where(['uid'=>$uid,'transactionId'=>$transactionId,'productId'=>$price[2]])->first();

        if ($res) return response()->json(['resCode'=>Config::get('resCode.641'),'status'=>'exist order']);

        $subject=$price[1];

        //生成订单号
        $orderId=randomUUID();

        event(new CreateWodeluOrderEvent([
            'uid'=>$uid,
            'price'=>$price[0],
            'orderTime'=>time(),
            'orderId'=>$orderId,
            'subject'=>$subject,
            'type'=>'ios',
            'productId'=>$price[2],
        ]));

        //修改订单状态
        DB::connection('userOrder')->table('wodelu'.$suffix)->where(['uid'=>$uid,'orderId'=>$orderId])->update(['transactionId'=>$transactionId,'payTime'=>time(),'status'=>1,'updated_at'=>date('Y-m-d H:i:s',time())]);

        //操作对应的$productId逻辑
        (new TrackUserController())->modifyVipStatus($uid,$price[2]);

        return response()->json(['resCode'=>Config::get('resCode.200'),'status'=>'success']);
    }

    //苹果内购时候，验证收据用的
    public function acurl($receiptData,$sandbox=0,$app='wodelu')
    {
        //小票信息
        if ($app=='wodelu')
        {
            $POSTFIELDS = ["receipt-data" => $receiptData,'password'=>'8d681df8dd78403fbee2201fc99dc6dd'];
            $POSTFIELDS = jsonEncode($POSTFIELDS);

        }elseif ($app=='tssj')
        {
            $POSTFIELDS = ["receipt-data" => $receiptData,'password'=>'8b8d1b9679d44232b9f84a45e1b996a1'];
            $POSTFIELDS = jsonEncode($POSTFIELDS);

        }else
        {

        }

        //正式购买地址 沙盒购买地址
        $urlBuy = "https://buy.itunes.apple.com/verifyReceipt";
        $urlSandbox = "https://sandbox.itunes.apple.com/verifyReceipt";
        $url = $sandbox ? $urlSandbox : $urlBuy;//向正式环境url发送请求(默认)

        //简单的curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }


    //  上面是我的路支付   下面是探索世界支付


    private function tssjAlipayConfig()
    {
        return [
            // 支付宝分配的 APPID
            'app_id' => '2021000197692091',
            // 支付宝异步通知地址
            'notify_url' => 'http://newfogapi.wodeluapp.com/tssj/alipay/notify',
            // 支付成功后同步通知地址
            'return_url' => '',
            // 阿里公共密钥，验证签名时使用
            'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtOjdLtvmH8Rs9PhEkoSWe5e0LWyweflkTWCtCTiV8yMXh8NMLGrx4dCIpfj2pyBad7cSKOAlNYaXu4rjkY+EnUFgffsA5XVbQvpFFIAVCALLmSb0z2b0X+Za6ax8Igh8Y8kZo4RS7axsix5mIe1HncVyOY7vGBVOTQxfHBsU9/kch/xvjFKq/b+M/NXOcqgE/PIdfftjvoI9eRNk7OvMy3x1kt11eYhwILZRZ/khtBXWWPMcZ4BvQEDbokWgrWR7aaL2LfpmmBmdafWUXOgmk962PdFH0688W6UcVh7NChQrUyJY7KVq2e0JCfxwiyKm9JLJvDQrtjqgAvLDsLkoLwIDAQAB',
            // 自己的私钥，签名时使用
            'private_key' => 'MIIEpAIBAAKCAQEAhLB5b95lKKUaak1BwRsXTjQxyQr0o/X/PKCxOxXwVuzkddi2HH3mvJ4bQVG3UEt27ELbCm7he2uvdLCRHssaM0ha6IQFzVinCnSbQGJVd5qEcRE2b8uOBeRmq8qZgNnY1M4PFu/dyUtbHOf2SAuPjzIdhPqk8OCJlZ5bWLoqgOKxecMWDdm3zW2SBpFIeUBqLs1Au6TTw5nTSV+fmwieLYtqelOg8C11caOhcPhY69tiJHhC15MdNB8di2kZEbVWGCkoWQZzgfJH+kX3C6oajnDebWi6PH+gUnSki7d2Q/kF+aYENoMe7bdpzhtVY6oY99kimRXmyTNPAOPQkryvkQIDAQABAoIBAEBcjXr66ByQAUEg2k09w882OBPpkYLadwTEeVNMr/iqDaMwDB3D4CELm/LSHVYAVN4DC9aCtDK2qDO01Z+XKs1HQnlYWKwjXVsP9qKDUCuksqtZiwstNGWdRUP9EPpUMP4AOYeJsA3M1JQv2+FUYj02NOVk4o7Ii1QcrPhbzPQYztU5WgEHEehpzdVk1k6unpvikRWfpeIFNcpy5ESlkMfY+hjsP2qZhoJRr/iGA5nPczzUy7mio+So3fCfCyqMQt4OfeQ55XuJ1lYMONd4es/cKmBdB5G9XlrA3iWnuwquJeVmogdZDy1b39jtyJcC6tPEbdQmmu9d2a3khtdJggECgYEAvg6TxS17Bd9qIZPyyC4t1nRVEfqkwr5T1wQ9Nu8fMe+Knhj6CP76VrgXBpdm79if+bEVr1lv3BBxEfDeD2gECyQWbJqzWzuFht0CuNeMNlmYUC7F2qHiM/5KI3r4SWoCA8i+HIPNPySE/L6weRs+/49wedTsuYnYkWFK47VivbECgYEAsrpWVuOZPdk04A/mGonEO/IPAFekyd/HA4M108Hfu0OR2/rdCqK7ElMD/WC7OqyxB/0JU8Pbx2gDKWmOZckasDwUw188PALhgluAJkOh63OUap0OOMktr4XaFCKNnF4Q/9O6LRVw1KlGKZnaanhWmOxVKGtZBw9V5oLWjLpuJ+ECgYAppBn+TquwqrWfK8I619tVLGHjMY5d2MOXzab33UZxc3FkmEZYKD2DOIxa9lsoW8cZNxJwO+FFTxjm/GY66+hO5JZBL1fyukTUOqI5C4j9831qvAS/lU5xY9qskWnK8/4DBD2bE8mpdv/oPIN/1VdlOPFE0EEZmbkoiS+WWoyK0QKBgQCrfXXIq1vnZ1l/wGGWhyf+KNVSC8Z3WTuY2DY2uCjXgw8aVwvu35PGEleasE0WEItQ0e84K47fN6MJAlp6ucrc3NlDWUbvggglT2yXyn8770uyPH5f6FDowPMuLLVaGzwObHaQOalos/85fYGAdXUKCIHxZYcn6gQPSO1aXKvDoQKBgQCl9Te27RXodpkLSlRvat8ku6wVYIyzulBFBmhRMefpbzmyl8F9c67v7hVOvatNxORMlSFq+kIKNU3fikdnn3HmnhucoutrXC/89zZsaE+RBa9sGxVba5LX1eLzw8r7lvvJS7A86pY0nbiPVIUhQP/DnLC56lAptyQAm3ypq8ZsUw==',
            // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
            'log' => [
                'file' => storage_path('logs/alipay.log'),
                //  'level' => 'debug'
                //  'type' => 'single', // optional, 可选 daily.
                //  'max_file' => 30,
            ],
            // optional，设置此参数，将进入沙箱模式
            // 'mode' => 'dev',
        ];
    }

    public function choseProductForTssj($productId,$plant='android')
    {
        $product=$productId;

        $arr=[
            '1'=>6,    //300钻石
            '2'=>30,   //1500钻石
            '3'=>68,   //3400钻石
            '4'=>128,  //6400钻石
            '5'=>258,  //12900钻石
            '6'=>648,  //32400钻石
            '7'=>25,   //30天，2400钻石，每天80钻石
        ];

        $subject=[
            '1'=>'300钻石',
            '2'=>'1500钻石',
            '3'=>'3400钻石',
            '4'=>'6400钻石',
            '5'=>'12900钻石',
            '6'=>'32400钻石',
            '7'=>'30天，2400钻石，每天80钻石',
        ];

        //送多少钻石
        $gift=[
            '1'=>0,
            '2'=>66,
            '3'=>188,
            '4'=>388,
            '5'=>888,
            '6'=>3688,
            '7'=>0,
        ];

        if ($plant==='ios')
        {
            $arr=[
                'com.wodelu.fogMap6RMB300Z'=>6,   //300钻石
                'com.wodelu.fogMap30RMB1500Z'=>30, //1500钻石
                'com.wodelu.fogMap68RMB3400Zs'=>68,     //3400钻石
                'com.wodelu.fogMap128RMB6400Z'=>128,          //6400钻石
                'com.wodelu.fogMap258RMB12900Z'=>258,          //12900钻石
                'com.wodelu.fogMap648RMB32400Z'=>648,          //32400钻石
                'com.wodelu.fogMap25RMBDAY80Zs'=>25,           //30天，2400钻石，每天80钻石
            ];

            $subject=[
                'com.wodelu.fogMap6RMB300Z'=>'300钻石',
                'com.wodelu.fogMap30RMB1500Z'=>'1500钻石',
                'com.wodelu.fogMap68RMB3400Zs'=>'3400钻石',
                'com.wodelu.fogMap128RMB6400Z'=>'6400钻石',
                'com.wodelu.fogMap258RMB12900Z'=>'12900钻石',
                'com.wodelu.fogMap648RMB32400Z'=>'32400钻石',
                'com.wodelu.fogMap25RMBDAY80Zs'=>'钻石月卡',
            ];

            $productIdArr=[
                'com.wodelu.fogMap6RMB300Z'=>1,
                'com.wodelu.fogMap30RMB1500Z'=>2,
                'com.wodelu.fogMap68RMB3400Zs'=>3,
                'com.wodelu.fogMap128RMB6400Z'=>4,
                'com.wodelu.fogMap258RMB12900Z'=>5,
                'com.wodelu.fogMap648RMB32400Z'=>6,
                'com.wodelu.fogMap25RMBDAY80Zs'=>7,
            ];

            $product=$productIdArr[$productId];
        }

        if (isset($arr[$productId])) return [$arr[$productId],$subject[$productId],$product,$gift[$product]];

        return false;
    }

    //探索世界支付（阿里）
    public function tssjAlipay(Request $request)
    {
        //创建
        $this->createTable('tssj');

        $uid=$request->uid;

        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //安卓还是ios
        $type=$request->type;

        //需要付款多少钱
        $price=$this->choseProductForTssj($request->productId,$type);

        if (!$price) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $subject=$price[1];

        //送多少钻石
        $gift=$price[3];

        //生成订单号
        $orderId=randomUUID();

        if ($type=='android')
        {
            $res = \Yansongda\Pay\Pay::alipay($this->tssjAlipayConfig())->app([
                'subject' => $subject,
                'out_trade_no' => $orderId,
                'total_amount' => sprintf("%.2f",$price[0]),
            ]);

            event(new CreateTssjOrderEvent([
                'uid'=>$uid,
                'price'=>$price[0],
                'orderTime'=>time(),
                'orderId'=>$orderId,
                'subject'=>$subject,
                'type'=>$type,
                'productId'=>$request->productId,
            ]));

            return response()->json(['resCode'=>Config::get('resCode.200'),'str'=>$res->getContent()]);

        }elseif ($type=='ios')
        {

        }else
        {

        }
    }

    //探索世界支付回调（阿里）
    public function tssjAlipayNotify(Request $request)
    {
        $alipay=\Yansongda\Pay\Pay::alipay($this->tssjAlipayConfig());

        try
        {
            //data返回的是laravel集合
            $data=$alipay->verify(); // 是的，验签就这么简单！

            Redis::connection('default')->set('androidAlipayReturn',jsonEncode($data));

            // 请自行对 trade_status 进行判断及其它逻辑进行判断，在支付宝的业务通知中，只有交易通知状态为 TRADE_SUCCESS 或 TRADE_FINISHED 时，支付宝才会认定为买家付款成功。
            // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号；
            // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）；
            // 3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）；
            // 4、验证app_id是否为该商户本身。
            // 5、其它业务逻辑情况

            //是否支付成功
            if (!isset($data->trade_status) || $data->trade_status!='TRADE_SUCCESS') return response()->json(['resCode'=>Config::get('resCode.641'),'status'=>'fail']);

            //找到找到订单，设置状态
            $orderId=$data->out_trade_no;

            $suffix=Carbon::now()->year;

            $res=DB::connection('userOrder')->table('tssj'.$suffix)->where('orderId',$orderId)->first();

            //金额不正确
            if ((int)$res->price!==(int)$data->total_amount) return response()->json(['resCode'=>Config::get('resCode.642'),'status'=>'fail']);

            DB::connection('userOrder')->table('tssj'.$suffix)->where('orderId',$orderId)->update(['transactionId'=>$data->trade_no,'payTime'=>time(),'status'=>1,'updated_at'=>date('Y-m-d H:i:s',time())]);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.643'),'status'=>'fail']);
        }

        //如果都通过了
        $uid=$res->uid;

        $productId=(int)$res->productId;

        //加多少钻石
        (new UserController())->exprUserDiamond($uid,0,'+',$productId);

        return response()->json(['resCode'=>Config::get('resCode.200'),'status'=>$alipay->success()->send()]);
    }

    //探索世界支付回调（苹果内购）
    public function tssjApplePayNotify(Request $request)
    {
        //创建
        $this->createTable('tssj');

        $receiptData=jsonDecode($request->receiptData);

        if ($receiptData!==null)
        {
            $receiptData=$receiptData[0];
        }else
        {
            //这是直接传的，不是json格式
            $receiptData=$request->receiptData;
        }

        $uid=$request->uid;

        //给苹果验证
        $data=$this->acurl($receiptData,1,'tssj');

        $data=jsonDecode($data);

        //* 21000 App Store不能读取你提供的JSON对象
        //* 21002 receipt-data域的数据有问题
        //* 21003 receipt无法通过验证
        //* 21004 提供的shared secret不匹配你账号中的shared secret
        //* 21005 receipt服务器当前不可用
        //* 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
        //* 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
        //* 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务

        if (intval($data['status'])!==0)
        {
            $data=$this->acurl($receiptData,0,'tssj');

            $data=jsonDecode($data);
        }

        //支付失败
        if (intval($data['status'])!==0) return response()->json(['resCode'=>Config::get('resCode.641'),'msg'=>$data]);

        //根据最新的一单transaction_id查询是否处理了
        isset($data['receipt']['in_app']) ? $in_app=$data['receipt']['in_app'] : $in_app=[];

        if (empty($in_app)) return response()->json(['resCode'=>Config::get('resCode.641'),'status'=>'in_app empty']);

        //整理好，取第一个
        $in_app=current(arraySort1($in_app,['desc','purchase_date_ms']));

        $product_id=$in_app['product_id'];
        $price=$this->choseProductForTssj($product_id,'ios');
        $transactionId=$in_app['transaction_id'];

        //查看这个订单是不是处理了
        $suffix=Carbon::now()->year;
        $res=DB::connection('userOrder')->table('tssj'.$suffix)->where(['uid'=>$uid,'transactionId'=>$transactionId,'productId'=>$price[2]])->first();

        if ($res) return response()->json(['resCode'=>Config::get('resCode.641'),'status'=>'exist order']);

        $subject=$price[1];

        //生成订单号
        $orderId=randomUUID();

        event(new CreateTssjOrderEvent([
            'uid'=>$uid,
            'price'=>$price[0],
            'orderTime'=>time(),
            'orderId'=>$orderId,
            'subject'=>$subject,
            'type'=>'ios',
            'productId'=>$price[2],
        ]));

        //修改订单状态
        DB::connection('userOrder')->table('tssj'.$suffix)->where(['uid'=>$uid,'orderId'=>$orderId])->update(['transactionId'=>$transactionId,'payTime'=>time(),'status'=>1,'updated_at'=>date('Y-m-d H:i:s',time())]);

        //加多少钻石
        (new UserController())->exprUserDiamond($uid,0,'+',$price[2]);

        return response()->json(['resCode'=>Config::get('resCode.200'),'status'=>'success']);
    }

}
