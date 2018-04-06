<?php

namespace App\Console\Commands;

use App\Console\Kernel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use AndreasGlaser\PPC\PPC;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\BalanceMaster;
use App\Models\Order;

/**
 * Class ExampleCommand
 * @package App\Console\Commands
 *
 *          SEE COMMENTS AT THE BOTTOM TO SEE WHERE TO ADD YOUR OWN
 *          CONDITIONS FOR A TEST.
 *
 */
class CheckOrderStatusCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'autobot:checkorderstatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all new order status and update it.';

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
        
        $myKeys = DB::table('order_history')
                ->join("binance_settings", "binance_settings.user_id", "=", "order_history.user_id")
                ->select("binance_settings.*", "order_history.*")
                ->whereNull('binance_settings.deleted_at')
                ->whereNull('order_history.deleted_at')
                ->where('order_history.status', '=', 'NEW')
                ->get();

        foreach ($myKeys as $item) {
            $api = new \Binance\API($item->api_key, $item->secrete_key, ["useServerTime" => true]);
            $order = $api->orderStatus($item->symbol, $item->orderId);
            
//            if($order["status"] == 'NEW') {
//                $order = $api->cancel($item->symbol, $item->orderId);
//            }
            
            $order["user_id"] = $item->user_id;
            $order["configuremacdbot_id"] = $item->configuremacdbot_id;

            unset($order["stopPrice"]);
            unset($order["icebergQty"]);
            unset($order["isWorking"]);
            unset($order["time"]);
            
            if(array_key_exists('side', $order) && $order["side"] == "SELL" && array_key_exists('executedQty', $order)) {
                $order["executedQty"] = $order["executedQty"] * -1;
            }
            
            Order::where("orderId", $item->orderId)->update($order); // note that this shortcut is available if the comparison is =
            echo "Order Id :: ".$item->orderId." \n";            
        }
        
        return null;
    }

}
