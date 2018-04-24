<?php

namespace App\Listeners;

use App\Events\SendAlertToUsers;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAlertEmail {

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
    public function handle(SendAlertToUsers $event) {
        //
        if ($event->instrumentPair->alert_email != "") {
            \Mail::send('emails.tradealert', ['data' => $event], function ($m) use ($event) {
                $m->from('no-reply@autobot.com', 'CryptoBee Trader');
                $m->cc('parmaramit1111@gmail.com', 'Amit');
                $m->cc('scalableapplication@gmail.com', 'Arpit');
                $subject = 'CryptoBee Trader: Trading alert for ' . $event->instrumentPair->symbol;
                $m->to($event->instrumentPair->alert_email)->subject($subject);
            });
        }
    }
}
