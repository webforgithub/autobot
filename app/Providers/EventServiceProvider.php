<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\AutoBotMACDEvent' => [
            'App\Listeners\CalculateSymbol',
        ],
        'App\Events\ProcessStrategy' => [
            'App\Listeners\ExecuteStrategy',
        ],
        'App\Events\SendNotification' => [
            'App\Listeners\NotifyUser',
        ],
        'App\Events\RunBinanaceTicker' => [
            'App\Listeners\InsertHistoricalData',
        ],
        'App\Events\SendAlertToUsers' => [
            'App\Listeners\SendAlertEmail',
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        //
    }
}
