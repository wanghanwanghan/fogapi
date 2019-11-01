<?php

namespace App\Listeners;

use App\Events\CreateWodeluOrderEvent;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CreateDataInMysql
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  CreateWodeluOrderEvent  $event
     * @return void
     */
    public function handle(CreateWodeluOrderEvent $event)
    {
        $year=Carbon::now()->year;

        $insert=[
            'uid'=>$event->avg['uid'],
            'orderId'=>$event->avg['orderId'],
            'productId'=>$event->avg['productId'],
            'productSubject'=>$event->avg['subject'],
            'price'=>$event->avg['price'],
            'orderTime'=>$event->avg['orderTime'],
            //'alipayTime'
            'status'=>0,
            'plant'=>$event->avg['type'],
            'created_at'=>Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at'=>Carbon::now()->format('Y-m-d H:i:s'),
        ];

        DB::connection('userOrder')->table('wodelu'.$year)->insert($insert);
    }
}
