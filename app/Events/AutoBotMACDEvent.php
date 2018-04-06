<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AutoBotMACDEvent extends Event
{
    use SerializesModels;
    
    public $configBotModel;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($configBotModel)
    {
        //
        $this->configBotModel = $configBotModel;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
