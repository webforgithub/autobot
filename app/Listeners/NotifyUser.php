<?php

namespace App\Listeners;

use App\Events\SendNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyUser {

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NotifyUser  $event
     * @return void
     */
    public function handle(SendNotification $event) {
        //
        if ($event->instrumentPair->alert_email != "") {
            \Mail::send('emails.autotrade', ['data' => $event], function ($m) use ($event) {
                $m->from('no-reply@autobot.com', 'CryptoBee Trader');
                $subject = 'CryptoBee Trader: Autobot purchased ' . $event->instrumentPair->symbol;
                $m->to($event->instrumentPair->alert_email)->subject($subject);
            });
        }
    }
}
