<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\MarketTickers;
use DB;

class DataRunnerBinanceCommand extends Command {

    /**
     * @var string
     */
    protected $name = 'autobot:datarunner_binance';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autobot:datarunner_binance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command will insert data for selected symbols';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        //$this->output->write("Fetching Data from Binance:: ". date("Y-m-d H:i:s"), true);
        $carbon = new Carbon();

        $getAPIKeys = DB::table('binance_settings')
                ->select("*")
                ->whereNull('deleted_at')
                ->take(1)
                ->get();

        foreach ($getAPIKeys as $keyItem) {
            $api = new \Binance\API($keyItem->api_key, $keyItem->secrete_key, ['useServerTime' => true]);

            while (1) {
                $this->output->write("Fetching Data from Binance:: " . date("Y-m-d H:i:s"), true);
                $exchangePairs = DB::table('exchange_pairs')
                        ->select("*")
                        ->whereNull('deleted_at')
                        ->where('exchange_pair', '=', 'DGDBTC')
                        ->get();

                foreach ($exchangePairs as $item) {
                    $symbol = $item->exchange_pair;
                    \Event::fire(new \App\Events\RunBinanaceTicker($symbol, $api));
                        
//                    $tickers = $api->candlesticks($symbol, "3m", 500);
//                    foreach ($tickers as $ticker) {
//                        $tick = array();
//                        $tick['symbol'] = $symbol;
//                        $tick['high'] = $ticker['high'];
//                        $tick['low'] = $ticker['low'];
//
//                        $tick['ask'] = $ticker['open'];
//                        $tick['bid'] = $ticker['close'];
//
//                        $tick['open'] = $ticker['open'];
//                        $tick['close'] = $ticker['close'];
//                        $tick['basevolume'] = $ticker['volume'];
//
//                        $tick['timestamp'] = time();
//                        $tick['bh_exchanges_id'] = 6; // Binance                       
//                        $tick['datetime'] = $carbon->createFromTimestamp($ticker['closeTime'] / 1000)->toDateTimeString();
//
//                        $tickers_model = new \App\Models\MarketTickers();
//                        $tickers_model::updateOrCreate(
//                                ['bh_exchanges_id' => $tick['bh_exchanges_id'],
//                                'symbol' => $tick['symbol'],
//                                'timestamp' => $tick['timestamp']]
//                                , $tick);                        
//                    }                    
                }
                $this->output->write("Processed pair:" . date("Y-m-d H:i:s") . " = " . $symbol, true);
                sleep(5);
            }
        }
    }

}
