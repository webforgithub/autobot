<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Models\ExchangePairs;
use App\Models\Exchanges;
use App\Models\MarketTickers;

/**
 * Class DataRunnerCoinigyCommand
 * @package App\Console\Commands
 *
 *          KEEP IN MIND THAT COINIGY HAS A RATE LIMIT
 *          https://coinigy.docs.apiary.io/#introduction/rate-limiting:
 */
class DataRunnerCoinigyCommand extends Command {

    use \App\Traits\DataCoinigy;

    /**
     * @var string
     */
    protected $name = 'autobot:datarunner_coinigy';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autobot:datarunner_coinigy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    #public function __construct()
    #{
    #parent::__construct();
    #}

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $getAPIKeys = DB::table('coinigy_settings')
                ->select("*")
                ->whereNull('deleted_at')
                ->take(1)
                ->get();

        foreach ($getAPIKeys as $keyItem) {
            $this->COINIGY_API = $keyItem->api_key;
            $this->COINIGY_SEC = $keyItem->secrete_key;

            $all_coinigy = Exchanges::where('coinigy_exch_code', '=', 'BINA')->get()->toArray();

            while (1) {
                $exchanges = $tick = $bh_exchanges = [];

                foreach ($all_coinigy as $list_coinigy) {
                    $exchanges[$list_coinigy['coinigy_exch_code']] = $list_coinigy['exchange'];
                    $bh_exchanges[$list_coinigy['coinigy_exch_code']] = $list_coinigy['id'];
                }
                $trading_pairs = [];

                foreach ($exchanges as $code => $ex) {
                    $_pairs = ExchangePairs::where('exchange_id', '=', $bh_exchanges[$code])->get()->toArray(); // get current list of pairs for exchange
                    $pairs = array_pluck($_pairs, 'exchange_pair');      // only select the pair name
                    $looping_pairs = $pairs; // get intersection of the exchanges pairs with the users selected pairs.

                    foreach ($looping_pairs as $pair) {
                        $ticker = $this->get_ticker($code, $pair);

                        if (!empty($ticker['err_msg'])) {
                            continue;
                        } else {
                            $ticker = $ticker['data'][0];
                        }

                        $tick['high'] = $ticker['high_trade'];
                        $tick['low'] = $ticker['low_trade'];
                        $tick['bid'] = $ticker['bid'];
                        $tick['ask'] = $ticker['ask'];
                        $tick['basevolume'] = $ticker['current_volume'];
                        $tick['last'] = $ticker['last_trade'];
                        $tick['symbol'] = $ticker['market'];
                        $tick['timestamp'] = time();
                        $tick['bh_exchanges_id'] = $bh_exchanges[$code];
                        $tick['datetime'] = $ticker['timestamp'];

                        $tickers_model = new MarketTickers();
                        $tickers_model::updateOrCreate(
                                ['bh_exchanges_id' => $tick['bh_exchanges_id'],
                            'symbol' => $tick['symbol'],
                            'timestamp' => $tick['timestamp']]
                                , $tick);
                        $this->output->write("Coinigy Data:: " . $ticker['market']."\n");
                        sleep(15);
                    }
                    // TODO Do OHLC here. Not really needed, but could be nice
                    // TODO https://coinigy.docs.apiary.io/#reference/market-data/market-data/data-{type:all}
                }
                sleep(10);
            }
        }
    }

}
