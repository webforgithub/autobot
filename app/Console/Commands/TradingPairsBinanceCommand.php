<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExchangePairs;
use DB;

class TradingPairsBinanceCommand extends Command {

    /**
     * @var string
     */
    protected $name = 'autobot:tradingpairs_binance';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autobot:tradingpairs_binance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all trading pairs for Binances';

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
        $this->output->write("Updating Binance Trading pairs:: ". date("Y-m-d H:i:s"), true);
        
        $getAPIKeys = DB::table('binance_settings')
                ->select("*")
                ->whereNull('deleted_at')
                ->take(1)
                ->get();

        foreach ($getAPIKeys as $keyItem) {
            $api = new \Binance\API($keyItem->api_key, $keyItem->secrete_key, ['useServerTime' => true]);

            $pairs = $api->exchangeInfo();
            foreach ($pairs["symbols"] as $item) {
                $tick = [];

                $tick['exchange_pair'] = $item["symbol"];
                $tick['baseAsset'] = $item["baseAsset"];
                $tick['quoteAsset'] = $item["quoteAsset"];
                $tick['quotePrecision'] = $item["quotePrecision"];
                $tick['baseAssetPrecision'] = $item["baseAssetPrecision"];
                $tick['market_id'] = 0;
                $tick['exchange_id'] = 6;
                
                ExchangePairs::updateOrCreate(
                                ['exchange_pair' => $tick['exchange_pair'],
                                'market_id' => $tick['market_id'],
                                'exchange_id' => $tick['exchange_id']]
                                , $tick);                
            }
        }
        
        $this->output->write("Coin pairs is updated successfully. ::". date("Y-m-d H:i:s"), true);
    }
}
