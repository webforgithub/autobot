<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RunBinanaceTicker extends Event {

    use SerializesModels;

    public $symbol;
    public $api;

    /**
     * Sending emails to user
     * 
     * @param type $userObj
     * @param type $orderObject
     * @param type $instrumentPair
     * @param type $orderType
     */
    public function __construct($symbol, $api) {
        //
        $this->symbol = $symbol;
        $this->api = $api;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn() {
        return [];
    }

}
