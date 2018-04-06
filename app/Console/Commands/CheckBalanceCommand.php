<?php

namespace App\Console\Commands;

use App\Console\Kernel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use AndreasGlaser\PPC\PPC;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\BalanceMaster;

/**
 * Class ExampleCommand
 * @package App\Console\Commands
 *
 *          SEE COMMENTS AT THE BOTTOM TO SEE WHERE TO ADD YOUR OWN
 *          CONDITIONS FOR A TEST.
 *
 */
class CheckBalanceCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'autobot:checkbalance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Running our short strategies for MACD';

    /**
     * Returns the available Amount for buying
     * 
     * @param type $api
     * @param type $symbol
     * @param type $percentage
     * @return type
     */
    protected function GetAccountBalance($api) {
        return $api->balances();
    }

    /** -------------------------------------------------------------------
     * @return null
     *
     *  this is the part of the command that executes.
     *  -------------------------------------------------------------------
     */
    public function handle() {

        while (true) {
            $myKeys = DB::table('binance_settings')
                    ->join("users", "users.id", "=", "binance_settings.user_id")
                    ->select("binance_settings.*", "users.*")
                    ->whereNull('binance_settings.deleted_at')
                    ->whereNull('users.deleted_at')
                    ->get();

            foreach ($myKeys as $item) {
                $api = new \Binance\API($item->api_key, $item->secrete_key, ["useServerTime" => true]);
                $balanceList = $this->GetAccountBalance($api);
                foreach ($balanceList as $key => $balance) {
                    $balanceItem = array();
                    $balanceItem["bh_exchanges_id"] = 6;
                    $balanceItem['user_id'] = $item->user_id;
                    $balanceItem['symbol'] = $key;
                    $balanceItem['available_balance'] = $balance['available'];
                    
                    if(((float)$balance['available']) > 0 && $item->balance_settings > 0) {                        
                        $balanceItem['allocated_balance'] = ($balance['available'] * $item->balance_settings) / 100;
                        $balanceItem['allocated_balance'] = number_format(round((float)$balanceItem['allocated_balance'], 8), 8, '.', '');
                    }

                    $balance_model = new \App\Models\BalanceMaster();
                    $balance_model::updateOrCreate(
                            ['bh_exchanges_id' => 6,
                        'symbol' => $key,
                        'user_id' => $item->user_id]
                            , $balanceItem);
                }
            }
            sleep(30);
        }
        return null;
    }
}