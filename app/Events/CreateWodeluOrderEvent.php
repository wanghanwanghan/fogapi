<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CreateWodeluOrderEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    //传进来的参数
    public $avg;

    public function __construct($avg)
    {
        $this->avg=$avg;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
