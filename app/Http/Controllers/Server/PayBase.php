<?php

namespace App\Http\Controllers\Server;

use App\Events\CreateWodeluOrderEvent;
use Carbon\Carbon;
use Ignited\LaravelOmnipay\Facades\OmnipayFacade;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class PayBase
{
    public function choseProduct($productId)
    {
        $arr=[
            '1'=>3,    //50km
            '2'=>8,    //月vip
            '3'=>18,   //季度vip
            '4'=>68,   //年vip
            '5'=>108,  //终身
            '999'=>1,  //测试
        ];

        $subject=[
            '1'=>'50km',
            '2'=>'月vip',
            '3'=>'季度vip',
            '4'=>'年vip',
            '5'=>'终身',
            '999'=>'测试',
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
                        $table->integer('alipayTime')->unsigned()->nullable()->comment('支付宝通知时间');
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

    //我的路支付（阿里）
    public function wodeluAlipay(Request $request)
    {
        //创建
        $this->createTable('wodelu');

        $uid=$request->uid;

        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //需要付款多少钱
        $price=$this->choseProduct($request->productId);

        if (!$price) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $subject=$price[1];

        //生成订单号
        $orderId=randomUUID();

        //安卓还是ios
        $type=$request->type;

        if ($type=='android')
        {
            $res=OmnipayFacade::gateway('wodelu_app_alipay')->purchase()->setBizContent([
                'subject'      => $subject,
                'out_trade_no' => $orderId,
                'total_amount' => sprintf("%.2f",$price[0]),
                'product_code' => 'QUICK_MSECURITY_PAY',
            ])->send();

            event(new CreateWodeluOrderEvent([
                'uid'=>$uid,
                'price'=>$price[0],
                'orderTime'=>time(),
                'orderId'=>$orderId,
                'subject'=>$subject,
                'type'=>$type,
                'productId'=>$request->productId,
            ]));

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
        $gateway=OmnipayFacade::gateway('wodelu_app_alipay');

        Redis::connection('default')->set('wodeluAlipayNotify',Carbon::now()->format('Y-m-d H:i:s'));

        try
        {
            $response=$gateway->completePurchase()->setParams($request->all())->send();

        }catch (\Exception $e)
        {
            die('fail');
        }

        if ($response->isPaid())
        {
            die('success');
        }else
        {
            die('fail');
        }

        return true;
    }








}
