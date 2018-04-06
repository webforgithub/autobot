<?php

namespace App\Listeners;

use App\Events\ProcessStrategy;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Traits\OHLC;
use App\Traits\Signals;
use App\Traits\Strategies;
use App\Util\Indicators;
use App\Models\Order;
use DB;

class ExecuteStrategy {

    use Strategies,
        OHLC; // add our traits

    public $console;
    public $indicators;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {
        //
        $this->console = new \App\Util\Console();
        $this->indicators = new Indicators();
    }

    /**
     * Fetch the total number of orders for Symbols
     * 
     * @param type $configurationId 
     * @param type $symbol
     * @param type $orderType
     * @return type
     */
    protected function GetOrderCount($configurationId, $symbol, $orderType = 'Buy') {
        return DB::table('order_history')
                        ->where('configuremacdbot_id', "=", $configurationId)
                        ->where('symbol', "=", $symbol)
                        ->where('type', "=", $orderType)
                        ->count();
    }

    /**
     * Returns the available Amount for buying
     * 
     * @param type $api
     * @param type $symbol
     * @param type $percentage
     * @return type
     */
    protected function GetAccountBalance($api, $symbol, $percentage = 10, $orderCount = 0) {
        $$availableBalance = 0;
        $myAccount = $api->account();
        $balances = $api->balances(array($symbol));

        if ($orderCount == 0) {
            $availableBalance = ($balances['BTC']['available'] * $percentage / 100);
//            foreach ($myAccount['balances'] as $item) {
//                if ($item["asset"] == $symbol) {
//                    $availableBalance = $item["free"];
//                    $availableBalance = ($availableBalance * $percentage / 100);
//                    break;
//                }
//            }
        }
        return $availableBalance;
    }

    /**
     * Get the sum of previously ordered items
     * @param type $configurationId
     * @param type $symbol
     * @return type
     */
    protected function CalculateSellVolume($configurationId, $symbol) {
        return DB::table('order_history')
                        ->where('configuremacdbot_id', "=", $configurationId)
                        ->where('symbol', "=", $symbol)
                        ->where('type', "=", 'BUY')
                        ->groupBy("configuremacdbot_id")
                        ->sum('executedQty');
    }

    /**
     * Handle the event.
     *
     * @param  ProcessStrategy  $event
     * @return void
     */
    public function handle(ProcessStrategy $event) {
        //
        $api = new \Binance\API($event->binanceKey->api_key, $event->binanceKey->secrete_key);
        $balances = $api->balances();

        $balanceSetting = $event->userObj->balance_settings;
        $configurationId = $event->instrumentPair->id;
        $instrument = $event->instrumentPair->symbol;
        $totalOrder = $event->instrumentPair->totalorder;

        $buyOrder = $this->GetOrderCount($configurationId, $instrument);

        $sellQuantity = $this->CalculateSellVolume($configurationId, $instrument);
        $availableBalance = $this->GetAccountBalance($api, $instrument, $balanceSetting, $buyOrder);
        $symbolCurrentPrice = $api->symbolPrice($instrument);
        
        $recentData = $this->indicators->getRecentData($instrument, 500, false, 12, "1m");

        if (count($recentData) > 0) {
            # array $real [, integer $fastPeriod [, integer $slowPeriod [, integer $signalPeriod ]]]
            $macdData = $this->indicators->macd($instrument, $recentData[6], $event->instrumentPair->ema_short_period, $event->instrumentPair->ema_long_period, $event->instrumentPair->signal_period);

            /* buy(1)/hold(0)/sell(-1) * */
            $arrayMACD = array(-1 => "SELL", 0 => "Hold", 1 => "BUY");
            $currentPrice = array_pop($recentData[6]['close']);
            $currentPrice = $symbolCurrentPrice;
            
            if ($macdData == -1) { /** SELL NOW * */
                if ($sellQuantity > 0) {
                    $sellQuantity = number_format((float)$sellQuantity, 4, '.', '');
                    
                    $order = $api->sell($instrument, $sellQuantity, $currentPrice);
//                    $order = $api->marketSell($instrument, $sellQuantity);                    
//                    $order = $api->sell($instrument, $sellQuantity, $currentPrice);
                    //                $order = $api->sellTest($instrument, $sellQuantity, $currentPrice);
                    if (!isset($order['code'])) {
                        $order["user_id"] = $event->instrumentPair->userid;
                        $order["configuremacdbot_id"] = $configurationId;
                        Order::create($order);

                        \Event::fire(new \App\Events\SendNotification($event->userObj, $order, $event->instrumentPair));
                    } else {
                        $eMailBody = "Hi there \n";
                        $eMailBody .= "We can't process your SELL request. Please check below API response. \n";
                        $eMailBody .= "Coin pair: " . $instrument . " \n";
                        $eMailBody .= "Buy Volume : " . $sellQuantity . " \n";
                        $eMailBody .= "Meessage from API: " . $order['msg'] . " \n";
                        $eMailBody .= "Thanks,  \n";
                        $eMailBody .= "CryptoBee \n";

                        @mail($instrumentObj->alert_email, "We can't process your SELL request.", $eMailBody);
                    }
                }
            } else if ($macdData == 1) { /** BUY NOW * */
                if ($buyOrder < $totalOrder && $availableBalance > 0 && $currentPrice > 0) {
                    $buyQuantity = $availableBalance / $currentPrice;
                    $buyQuantity = number_format((float)$buyQuantity, 4, '.', '');
                    $flag = [];
                    $flag["stopPrice"] = $currentPrice - (($currentPrice * 10) / 100 );

                    $order = $api->buy($instrument, $buyQuantity, $currentPrice);
//                    $order = $api->marketBuy($instrument, $buyQuantity);
//                    $order = $api->buy($instrument, $buyQuantity, $currentPrice, "STOP_LOSS_LIMIT", $flag);
//                    $order = $api->buyTest($instrument, $buyQuantity, $currentPrice, "STOP_LOSS_LIMIT", $flag);
                    
                    if (!isset($order['code'])) {
                        $order["user_id"] = $event->instrumentPair->userid;
                        $order["configuremacdbot_id"] = $configurationId;

                        Order::create($order);
                        \Event::fire(new \App\Events\SendNotification($event->userObj, $order, $event->instrumentPair));
                    } else {
                        $eMailBody = "Hi there \n";
                        $eMailBody .= "We can't process your BUY request. Please check below API response. \n";
                        $eMailBody .= "Coin pair: " . $instrument . " \n";
                        $eMailBody .= "Buy Volume : " . $buyQuantity . " \n";
                        $eMailBody .= "Meessage from API: " . $order['msg'] . " \n";
                        $eMailBody .= "Thanks, " . $order['msg'] . " \n";
                        $eMailBody .= "CryptoBee \n";

                        @mail($instrumentObj->alert_email, "We can't process your BUY request.", $eMailBody);
                    }
                }
            }
//          $this->info("MACD Signal: " . $arrayMACD[$macdData]);
        }
    }

}
