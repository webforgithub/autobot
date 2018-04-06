<?php

namespace App\Console\Commands;

use App\Console\Kernel;
use App\Traits\OHLC;
use App\Traits\Signals;
use App\Traits\Strategies;
use App\Util\Indicators;
use Illuminate\Console\Command;
use App\Util;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use AndreasGlaser\PPC\PPC;
use Symfony\Component\Console\Input\InputArgument;
use App\Events\ProcessStrategy;
use App\Models\Order;

/**
 * Class ExampleCommand
 * @package App\Console\Commands
 *
 *          SEE COMMENTS AT THE BOTTOM TO SEE WHERE TO ADD YOUR OWN
 *          CONDITIONS FOR A TEST.
 *
 */
class TestPyMACDStrategiesCommand extends Command {

    use Signals,
        Strategies,
        OHLC; // add our traits    

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'autobot:testmacd_strategies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Running our short strategies for MACD';

    /**
     * @return array
     */
    public function getArguments() {
        return [
            ['runtest', InputArgument::OPTIONAL, 'Run DEMO tests?'],
        ];
    }

    public function GetAPIKey($userId) {
        $myKeys = DB::table('binance_settings')->select("*")->whereNull('deleted_at')->where('user_id', '=', $userId)->first();

        if ($myKeys && count($myKeys) > 0) {
            return $myKeys;
        }
        return false;
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
    protected function GetAccountBalance($exchangeId, $userId, $symbol) {
        $availableBalance = 0;
        $orderCount = DB::table('balance_master')
                ->where('bh_exchanges_id', "=", $exchangeId)
                ->where('symbol', "=", 'BTC')
                ->where('user_id', "=", $userId)
                ->count();

        if ($orderCount > 0) {
            $balances = DB::table('balance_master')
                    ->where('bh_exchanges_id', "=", $exchangeId)
                    ->where('symbol', "=", 'BTC')
                    ->where('user_id', "=", $userId)
                    ->first();
            $availableBalance = $balances->allocated_balance;
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
//        $currentTime = time();
        return DB::table('order_history')
                        ->where('configuremacdbot_id', "=", $configurationId)
                        ->where('symbol', "=", $symbol)
                        ->where('created_at', ">=", $symbol)
                        ->where('type', "=", 'BUY')
                        ->groupBy("configuremacdbot_id")
                        ->sum('executedQty');
    }

    /** -------------------------------------------------------------------
     * @return null
     *
     *  this is the part of the command that executes.
     *  -------------------------------------------------------------------
     */
    public function handle() {
        $rundemo = false;

        if ($rundemo = $this->argument('runtest')) {
            $this->info("Running the DEMO test of all the strategies:");
        }

        echo "PRESS 'q' TO QUIT AND CLOSE ALL POSITIONS\n\n\n";
        stream_set_blocking(STDIN, 0);

        $console = new \App\Util\Console();
        $indicators = new Indicators();
        while (1) {
            if (ord(fgetc(STDIN)) == 113) { // try to catch keypress 'q'
                echo "QUIT detected...";
                return null;
            }

            $symbols = DB::table('configuremacdbots')
                    ->select("*")
                    ->whereNull('deleted_at')
                    ->get();

            foreach ($symbols as $instrumentObj) {
                $userObj = DB::table('users')->select("*")->where('id', "=", $instrumentObj->userid)->get();
                if (count($userObj) > 0) {
                    $this->output->write("Symbol " . $instrumentObj->symbol, true);
                    $this->output->write("User found:: " . $instrumentObj->userid, true);
                    $userObj = $userObj[0];
                    $binanceKey = $this->GetAPIKey($instrumentObj->userid);
                    //$exchangeId, $userId, $symbol
                    $availableBalance = $this->GetAccountBalance(6, $instrumentObj->userid, $instrumentObj->symbol);
                    $this->output->write("availableBalance : $availableBalance", true);

                    if ($binanceKey !== false) {
                        $api = new \Binance\API($binanceKey->api_key, $binanceKey->secrete_key, ["useServerTime" => true]);

                        $configurationId = $instrumentObj->id;
                        $instrument = $instrumentObj->symbol;
                        $totalOrder = $instrumentObj->totalorder;

                        $buyOrder = $this->GetOrderCount($configurationId, $instrument);
                        $this->output->write("Get All Buy order count.:: " . $buyOrder, true);

                        $sellQuantity = $this->CalculateSellVolume($configurationId, $instrument);
                        $symbolCurrentPrice = $api->symbolPrice($instrument);

                        //$pair = 'BTC/USD', $limit = 168, $day_data = false, $hour = 12, $periodSize = '1m', $returnRS = false
//                        $recentData = $indicators->getRecentData($instrument, 500, false, 12, "1w");
                        $recentData = $api->candlesticks($instrument, "15m");

                        if (count($recentData) > 0) {
                            $closeArray = [];
                            $tmpCloseArray = array_values(array_map(function($sub) {
                                        return ($sub["close"]);
                                    }, $recentData));
                                    
                            $closeArray["close"] = array_values($tmpCloseArray);
                            $this->output->write("Candlesticks Data. -> " . count($closeArray["close"]), true);                            
                            $macdData = $indicators->macd($instrument, $closeArray, $instrumentObj->ema_short_period, $instrumentObj->ema_long_period, $instrumentObj->signal_period);
//                          $macdData = $indicators->macd($instrument, $recentData[6], $instrumentObj->ema_short_period, $instrumentObj->ema_long_period, $instrumentObj->signal_period);

                            /* buy(1)/hold(0)/sell(-1) * */
                            $arrayMACD = array(-1 => "SELL", 0 => "Hold", 1 => "BUY");
                            
//                            $currentPrice = array_pop($recentData[6]['close']);
                            $currentPrice = array_pop($closeArray['close']);
                            $currentPrice = $symbolCurrentPrice;

                            if ($macdData == -1) {
                                $this->info("MACD SELL Signal: " . $arrayMACD[$macdData]);

                                /** SELL NOW * */
                                if ($sellQuantity > 0) {
                                    $sellQuantity = number_format(round((float) $sellQuantity, 8), 8, '.', '');

                                    $this->info("MACD SELL - PRICE: " . $sellQuantity . " - " . $currentPrice);
                                    
                                    $flag = [];
                                    $flag["stopPrice"] = number_format(round((float)($currentPrice - (($currentPrice * 2) / 100 )), 8), 8, '.', '') ;
                                    $order = $api->sellTest($instrument, $sellQuantity, $currentPrice, "TAKE_PROFIT_LIMIT", $flag);
//                                    $order = $api->sell($instrument, $sellQuantity, $currentPrice, "TAKE_PROFIT_LIMIT", $flag);
                                    
//                                    $order = $api->sell($instrument, $sellQuantity, $currentPrice);
//                                    $order = $api->marketSell($instrument, $sellQuantity);
                                    if (!isset($order['code'])) {
                                        $order["user_id"] = $instrumentObj->userid;
                                        $order["configuremacdbot_id"] = $configurationId;
                                        
                                        Order::create($order);
                                        \Event::fire(new \App\Events\SendNotification($userObj, $order, $instrumentObj));
                                    } else {
                                        $eMailBody = "Hi there \n";
                                        $eMailBody .= "We can't process your SELL request. Please check below API response.\n";
                                        $eMailBody .= "Coin pair: " . $instrument . " \n";
                                        $eMailBody .= "Buy Volume : " . $sellQuantity . " \n";
                                        $eMailBody .= "Meessage from API: " . $order['msg'] . " \n";
                                        $eMailBody .= "Thanks, \n";
                                        $eMailBody .= "CryptoBee \n";

                                        //@mail($instrumentObj->alert_email, "We can't process your SELL request.", $eMailBody);
                                        @mail("parmaramit1111@gmail.com", "We can't process your SELL request.", $eMailBody);
                                    }
                                }
                            } else if ($macdData == 1) {

                                /** BUY NOW * */
                                $this->info("MACD BUY Signal: " . $arrayMACD[$macdData], true);
                                $this->info("Available Balanace: " . $availableBalance, true);
                                $this->info("Available $buyOrder < $totalOrder == $currentPrice", true);
                                $this->info("BTC Balance:: " . $api->btc_value, true);

                                if ($buyOrder < $totalOrder && $availableBalance > 0 && $currentPrice > 0) {
                                    $buyQuantity = $availableBalance / $currentPrice;
                                    $buyQuantity = number_format(round((float) $buyQuantity, 8), 8, '.', '');

                                    $this->info("MACD BUY Signal: " . $arrayMACD[$macdData], true);
                                    $this->info("MACD BUY QTY - PRICE: " . $buyQuantity . " - " . $currentPrice, true);

                                    $flag = [];
                                    $flag["stopPrice"] = number_format(round((float) $currentPrice - (($currentPrice * 2) / 100 ), 8), 8, '.', '');

                                    $order = $api->buyTest($instrument, $buyQuantity, $currentPrice, "STOP_LOSS_LIMIT", $flag);
//                                    $order = $api->buy($instrument, $buyQuantity, $currentPrice,  "STOP_LOSS_LIMIT", $flag);
                                    
                                    /** Test Order type * */
                                    //$order = $api->buy($instrument, $buyQuantity, $currentPrice);
                                    //$order = $api->buy($instrument, $buyQuantity, $currentPrice, "STOP_LOSS_LIMIT", $flag);
                                    //$order = $api->marketBuy($instrument, $buyQuantity);

                                    if (!isset($order['code'])) {
                                        $order["user_id"] = $instrumentObj->userid;
                                        $order["configuremacdbot_id"] = $configurationId;

                                        Order::create($order);
                                        \Event::fire(new \App\Events\SendNotification($userObj, $order, $instrumentObj));
                                    } else {
                                        $eMailBody = "Hi there \n";
                                        $eMailBody .= "We can't Buy request. Please increase your wallet balance percentage \n";
                                        $eMailBody .= "Coin pair: " . $instrument . " \n";
                                        $eMailBody .= "Buy Volume : " . $buyQuantity . " \n";
                                        $eMailBody .= "Meessage from API: " . $order['msg'] . " \n";
                                        $eMailBody .= "Thanks, " . $order['msg'] . " \n";
                                        $eMailBody .= "CryptoBee \n";

                                        //@mail($instrumentObj->alert_email, "We can't Buy request. Please increase your wallet balance percentage.", $eMailBody);
                                        @mail("parmaramit1111@gmail.com", "We can't Buy request. Please increase your wallet balance percentage.", $eMailBody);
                                    }
                                }
                            } else {
                                $this->info("MACD HOLD Signal: " . $arrayMACD[$macdData]);
                            }
                        }
                        sleep(5);
                    }
                }
            }
            sleep(5);
        }
        return null;
    }
}