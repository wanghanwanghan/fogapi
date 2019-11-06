<?php

namespace App\Http\Controllers\Server;

use App\Events\CreateWodeluOrderEvent;
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
        $arr=[
            '1'=>6,     //一个月vip
            '2'=>18,    //三个月vip
            '3'=>68,    //一年vip
            '4'=>6,     //100km
            '5'=>12,    //200km
            '6'=>18,    //300km
            '7'=>30,    //500km
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
            '7'=>'500km',
            '8'=>'750km',
            '9'=>'1000km',

            '255'=>'测试',
        ];

        if (isset($arr[$productId])) return [$arr[$productId],$subject[$productId]];

        return false;
    }

    public function createTable($type)
    {
        switch ($type)
        {
            case 'wodelu':

                $year=Carbon::now()->year;

                if (!Schema::connection('userOrder')->hasTable($type.$year))
                {
                    Schema::connection('userOrder')->create($type.$year, function (Blueprint $table)
                    {
                        $table->increments('id')->unsigned()->comment('订单主键');
                        $table->integer('uid')->unsigned()->comment('用户主键');
                        $table->string('orderId','50')->comment('订单号uuid');
                        $table->tinyInteger('productId')->unsigned()->comment('产品编号');
                        $table->string('productSubject','100')->comment('产品名称');
                        $table->integer('price')->unsigned()->comment('订单金额');
                        $table->integer('orderTime')->unsigned()->comment('下单时间');
                        $table->integer('payTime')->unsigned()->nullable()->comment('异步通知时间');
                        $table->tinyInteger('status')->unsigned()->comment('订单状态，0未付，1付款完成');
                        $table->string('plant','10')->comment('ios android');
                        $table->timestamps();
                        $table->index(['uid','orderId']);
                        $table->index('orderId');
                        $table->engine='InnoDB';
                    });
                }

                break;
        }
    }

    //我的路支付（苹果）
    public function wodeluApplePay(Request $request)
    {
        //创建
        $this->createTable('wodelu');

        $uid=$request->uid;

        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //ios
        $type=$request->type;
        $type='ios';

        //需要付款多少钱
        $price=$this->choseProduct($request->productId,$type);

        if (!$price) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $subject=$price[1];

        //生成订单号
        $orderId=randomUUID();

        event(new CreateWodeluOrderEvent([
            'uid'=>$uid,
            'price'=>$price[0],
            'orderTime'=>time(),
            'orderId'=>$orderId,
            'subject'=>$subject,
            'type'=>$type,
            'productId'=>$request->productId,
        ]));

        return response()->json(['resCode'=>Config::get('resCode.200'),'orderId'=>$orderId]);
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
            //omnipay===========================================================================================
//            $payWay='omnipay';
//            $res=OmnipayFacade::gateway('wodelu_app_alipay')->purchase()->setBizContent([
//                'subject'      => $subject,
//                'out_trade_no' => $orderId,
//                'total_amount' => sprintf("%.2f",$price[0]),
//                'product_code' => 'QUICK_MSECURITY_PAY',
//            ])->send();
            //omnipay===========================================================================================

            //yansongda=========================================================================================
            $payWay='yansongda';
            $res = Pay::alipay()->app([
                'subject' => $subject,
                'out_trade_no' => $orderId,
                'total_amount' => sprintf("%.2f",$price[0]),
            ]);
            //yansongda=========================================================================================

            event(new CreateWodeluOrderEvent([
                'uid'=>$uid,
                'price'=>$price[0],
                'orderTime'=>time(),
                'orderId'=>$orderId,
                'subject'=>$subject,
                'type'=>$type,
                'productId'=>$request->productId,
            ]));

            if ($payWay==='yansongda') return response()->json(['resCode'=>Config::get('resCode.200'),'str'=>$res->getContent()]);

            return response()->json(['resCode'=>Config::get('resCode.200'),'str'=>$res->getOrderString()]);

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

            // 请自行对 trade_status 进行判断及其它逻辑进行判断，在支付宝的业务通知中，只有交易通知状态为 TRADE_SUCCESS 或 TRADE_FINISHED 时，支付宝才会认定为买家付款成功。
            // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号；
            // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）；
            // 3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）；
            // 4、验证app_id是否为该商户本身。
            // 5、其它业务逻辑情况

            Redis::connection('default')->set('alipayTest1',jsonEncode($data));

            //是否支付成功
            if (!isset($data->trade_status) || $data->trade_status!='TRADE_SUCCESS') return response()->json(['resCode'=>Config::get('resCode.641'),'status'=>'fail']);

            //找到找到订单，设置状态
            $orderId=$data->out_trade_no;

            $suffix=Carbon::now()->year;

            $res=DB::connection('userOrder')->table('wodelu'.$suffix)->where('orderId',$orderId)->first();

            //金额不正确
            if ((int)$res->price!==(int)$data->total_amount) return response()->json(['resCode'=>Config::get('resCode.642'),'status'=>'fail']);

            DB::connection('userOrder')->table('wodelu'.$suffix)->where('orderId',$orderId)->update(['payTime'=>time(),'status'=>1,'updated_at'=>date('Y-m-d H:i:s',time())]);

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

        return response()->json(['resCode'=>Config::get('resCode.200'),'status'=>$alipay->success()]);







//        $gateway=OmnipayFacade::gateway('wodelu_app_alipay');
//
//        Redis::connection('default')->set('wodeluAlipayNotify',jsonEncode($request->all()));
//
//        try
//        {
//            $response=$gateway->completePurchase()->setParams($request->all())->send();
//
//        }catch (\Exception $e)
//        {
//            die('fail');
//        }
//
//        if ($response->isPaid())
//        {
//            die('success');
//        }else
//        {
//            die('fail');
//        }
    }








}
