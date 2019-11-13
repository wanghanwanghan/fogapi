<?php

namespace App\Listeners;

use App\Events\CreateWodeluOrderEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;

class CreateDataInRedis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  CreateWodeluOrderEvent  $event
     * @return void
     */
    public function handle(CreateWodeluOrderEvent $event)
    {

    }
}
