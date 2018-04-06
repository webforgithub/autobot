<?php

namespace App\Listeners;

use App\Events\RunBinanaceTicker;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use App\Models\MarketTickers;
use DB;

class InsertHistoricalData {

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
    public function handle(RunBinanaceTicker $event) {
        //
        $carbon = new Carbon();

        $tickers = $event->api->candlesticks($event->symbol, "3m", 500);
        foreach ($tickers as $ticker) {
            $tick = array();
            $tick['symbol'] = $event->symbol;
            $tick['high'] = $ticker['high'];
            $tick['low'] = $ticker['low'];

            $tick['ask'] = $ticker['open'];
            $tick['bid'] = $ticker['close'];

            $tick['open'] = $ticker['open'];
            $tick['close'] = $ticker['close'];
            $tick['basevolume'] = $ticker['volume'];

            $tick['timestamp'] = time();
            $tick['bh_exchanges_id'] = 6; // Binance                       
            $tick['datetime'] = $carbon->createFromTimestamp($ticker['closeTime'] / 1000)->toDateTimeString();

            $tickers_model = new \App\Models\MarketTickers();
            $tickers_model::updateOrCreate(
                    ['bh_exchanges_id' => $tick['bh_exchanges_id'],
                        'symbol' => $tick['symbol'],
                        'timestamp' => $tick['timestamp']]
                    , $tick);
        }
    }
}
