<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;


class ProcessStrategy extends Event {

    use SerializesModels;

    public $userObj;
    public $binanceKey;
    public $instrumentPair;

    /**
     * Create event to process the Symbol
     * 
     * @param type $userObj
     * @param type $binanceObj
     * @param type $instrumentObj
     */
    public function __construct($userObj, $binanceObj, $instrumentObj) {
        //
        $this->userObj = $userObj;
        $this->binanceKey = $binanceObj;
        $this->instrumentPair = $instrumentObj;
        
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