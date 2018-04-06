<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SendNotification extends Event {

    use SerializesModels;

    public $userObj;
    public $orderObject;
    public $instrumentPair;
    public $orderType;

    /**
     * Sending emails to user
     * 
     * @param type $userObj
     * @param type $orderObject
     * @param type $instrumentPair
     * @param type $orderType
     */
    public function __construct($userObj, $orderObject, $instrumentPair, $orderType = 'BUY') {
        //
        $this->userObj = $userObj;
        $this->orderObject = $orderObject;
        $this->instrumentPair = $instrumentPair;
        $this->orderType = $orderType;
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
