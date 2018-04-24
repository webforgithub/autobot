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
use App\Traits\Orders;

/**
 * Class ExampleCommand
 * @package App\Console\Commands
 *
 *          SEE COMMENTS AT THE BOTTOM TO SEE WHERE TO ADD YOUR OWN
 *          CONDITIONS FOR A TEST.
 *
 */
class TestMACDStrategiesCommand extends Command {

    use Signals,
        Strategies,
        Orders,
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
                        ->whereIn('status', ['FILLED', 'PARTIALLY_FILLED'])
                        ->where('side', "=", $orderType)
                        ->whereNull('deleted_at')
                        ->count();
    }

    protected function GetLastFilledBuyOrder($configurationId, $symbol, $orderType = 'BUY') {
        return DB::table('order_history')
                        ->where('configuremacdbot_id', "=", $configurationId)
                        ->where('symbol', "=", $symbol)
                        ->where('side', "=", $orderType)
                        ->whereNull('deleted_at')
                        ->orderBy('id', "DESC")
                        ->take(1)
                        ->get();
    }

    protected function GetOpenOrder($configurationId, $symbol, $orderType = 'BUY') {
        return DB::table('order_history')
                        ->where('configuremacdbot_id', "=", $configurationId)
                        ->where('symbol', "=", $symbol)
                        ->where('side', "=", $orderType)
                        ->whereDate('created_at', '=', \Carbon\Carbon::today()->toDateString())
                        ->whereNull('deleted_at')
                        ->whereIn('status', ['NEW', ''])
                        ->count();
    }
    
    protected function GetLastOrderType($configurationId, $symbol) {
        $returnType = 'SELL';
        $tmpStatus =  DB::table('order_history')
                        ->where('configuremacdbot_id', "=", $configurationId)
                        ->where('symbol', "=", $symbol)                        
                        ->whereDate('created_at', '=', \Carbon\Carbon::today()->toDateString())
                        ->whereNull('deleted_at')
                        ->select("*")
                        ->orderBy('id', "DESC")
                        ->take(1)
                        ->get();
        
        if(count($tmpStatus) > 0) {
            return $tmpStatus[0]->side;
        }
        
        return $returnType;
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
//        return DB::table('order_history')
//                        ->where('configuremacdbot_id', "=", $configurationId)
//                        ->where('symbol', "=", $symbol)
//                        ->where('status', "=", 'FILLED')
//                        ->where('type', "=", 'BUY')
//                        ->groupBy("configuremacdbot_id")
//                        ->sum('executedQty');

//        return DB::table('order_history')
//                        ->where('configuremacdbot_id', "=", $configurationId)
//                        ->where('symbol', "=", $symbol)
//                        ->whereIn('status', ['FILLED', 'PARTIALLY_FILLED'])
//                        ->whereNull('deleted_at')
//                        ->groupBy("symbol")
//                        ->sum('executedQty');
        $tmpTotal = 0;
        $tmpSet = DB::table('order_history')
                        ->where('configuremacdbot_id', "=", $configurationId)
                        ->where('symbol', "=", $symbol)
                        ->whereIn('status', ['FILLED', 'PARTIALLY_FILLED'])
                        ->whereNull('deleted_at')
                        ->select('executedQty')
                        ->orderBy('id', "DESC")
                        ->take(1)
                        ->get();
        
        if(count($tmpSet) > 0) {
            $tmpTotal = $tmpSet[0]->executedQty;
        }
        return $tmpTotal;
    }

    /** -------------------------------------------------------------------
     * @return null
     *
     *  this is the part of the command that executes.
     *  -------------------------------------------------------------------
     */
    public function handle() {
        @ini_set('trader.real_precision', '8');

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
                    $this->output->write("-----------------------  Restarted  -----------------------------", true);
                    $this->output->write("- Symbol " . $instrumentObj->symbol, true);
                    $this->output->write("- User Id :: " . $instrumentObj->userid, true);

                    $userObj = $userObj[0];
                    $binanceKey = $this->GetAPIKey($instrumentObj->userid);

                    //$exchangeId, $userId, $symbol
                    $availableBalance = $this->GetAccountBalance(6, $instrumentObj->userid, $instrumentObj->symbol);
                    $this->output->write("- Available Balance : $availableBalance", true);

                    if ($binanceKey !== false) {
                        $api = new \Binance\API($binanceKey->api_key, $binanceKey->secrete_key, ["useServerTime" => true]);

                        $configurationId = $instrumentObj->id;
                        $instrument = $instrumentObj->symbol;
                        $totalOrder = $instrumentObj->totalorder;

                        if ($this->ValidateOrder($api, $instrument, $availableBalance, 0)) {

                            $buyOrder = $this->GetOrderCount($configurationId, $instrument);
                            $lastBuyOrder = $this->GetLastFilledBuyOrder($configurationId, $instrument);
                            $openOrder = $this->GetOpenOrder($configurationId, $instrument);

                            $this->output->write("** BUY Order count.:: " . $buyOrder, true);
                            $this->output->write("** OPEN Order count.:: " . $openOrder, true);

                            $sellQuantity = $this->CalculateSellVolume($configurationId, $instrument);
                            $sellQuantity = number_format(round((float) $sellQuantity, 8), 8, '.', '');
                            
                            $this->output->write("- sellQuantity:: " . $sellQuantity, true);
                            $symbolCurrentPrice = $api->symbolPrice($instrument);
                            $symbolCurrentPrice = number_format(round((float) $symbolCurrentPrice, 8), 8, '.', ' ');

                            # Fetches the ticker price
                            $lastPrice = $this->getTicker($api, $instrument);

                            # Order book prices (lastBid, lastAsk)
                            $orderBook = $this->getOrderBook($api, $instrument);

                            # Target buy price, add little increase #87
                            
                            $buyPrice = $orderBook["lastBid"] + $this->increasing;
                            $buyPrice = number_format(round((float) $buyPrice, 8), 8, '.', ' ');
                            
                            # Target sell price, decrease little 
                            $sellPrice = $orderBook["lastAsk"] - $this->decreasing;
                            $sellPrice = number_format(round((float) $sellPrice, 8), 8, '.', ' ');

//                          $recentData = $indicators->getRecentData($instrument, 168, false, 12, '10m');
                            $recentData = $api->candlesticks($instrument, "3m", 200);
                            
                            if (count($recentData) > 0) {
                                $isBuy = $this->GetLastOrderType($configurationId, $instrument);
                                
                                $closeArray = [];
                                $tmpCloseArray = array_values(array_map(function($sub) {
                                            return ($sub["close"]);
                                        }, $recentData));

                                $closeArray["close"] = array_values($tmpCloseArray);
                                $this->output->write("Candlesticks Data. -> " . count($closeArray["close"]), true);
                                $this->output->write("MACD with. -> " . $instrumentObj->ema_short_period . ' = ' . $instrumentObj->ema_long_period . ' = ' . $instrumentObj->signal_period, true);

                                $macdData = $indicators->macd($instrument, $closeArray, $instrumentObj->ema_short_period, $instrumentObj->ema_long_period, $instrumentObj->signal_period);
//                                $macdData = trader_macd($closeArray["close"], $instrumentObj->ema_short_period, $instrumentObj->ema_long_period, $instrumentObj->signal_period);
                                
                                /* buy(1)/hold(0)/sell(-1) * */
                                $arrayMACD = array(-1 => "SELL", 0 => "Hold", 1 => "BUY");

                                $currentPrice = array_pop($closeArray['close']);
                                $currentPrice = $symbolCurrentPrice;

                                $this->info("- MACD Signal Type: " . $arrayMACD[$macdData], true);
                                $this->info("- Buy Price: " . $buyPrice, true);
                                $this->info("- Sell Price: " . $sellPrice, true);
                                $this->info("- Current Price: " . $currentPrice, true);


                                
                                if ($macdData == -1) {                                    
                                    /** SELL NOW * */
                                    $this->output->write("*-- lastBuyOrder Data. -> " . count($lastBuyOrder), true);
                                    
//                                    if ($sellQuantity > 0 && count($lastBuyOrder) > 0 && $sellPrice > $lastBuyOrder[0]->price) {
                                    if ($sellQuantity > 0 && $isBuy == 'BUY') {
                                        
                                        $currentPrice = $sellPrice;
                                        $stopPrice = $currentPrice - (($this->decreasing * 5));
                                        $flag = [];
                                        $flag["stopPrice"] = number_format(round((float) $stopPrice, 8), 8, '.', '');

                                        $this->info("- SELLING NOW: P: $sellPrice | Q: $sellQuantity | SP: " . $flag["stopPrice"], true);
//                                        $order = $api->sellTest($instrument, $sellQuantity, $currentPrice, "TAKE_PROFIT_LIMIT", $flag);
//                                        $order = $api->sell($instrument, $sellQuantity, $currentPrice, "STOP_LOSS_LIMIT", $flag);
                                        $order = $api->marketSell($instrument, $sellQuantity);
//                                        $order = $api->sell($instrument, $sellQuantity, $currentPrice);

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

                                            @mail("parmaramit1111@gmail.com", "We can't process your SELL request.", $eMailBody);
                                        }
                                    }
                                } else if ($macdData == 1) {
                                    /*                                     * *
                                     * Before BUYING check previouly OPEN Order. If there is already OPEN order than DON'T BUY.
                                     */
                                    
                                    /** BUY NOW * */                                   
                                    if(count($lastBuyOrder) == 0) {
                                        $isBuy = 'SELL';
                                        $openOrder = 0;
                                    }
                                    
                                    if ($availableBalance > 0 && $openOrder == 0 && $isBuy == 'SELL'  && $buyOrder < $totalOrder) {
                                        $buyPrice = $buyPrice + $this->increasing;
                                        $currentPrice = number_format(round((float) $buyPrice, 8), 8, '.', '');

                                        $buyQuantity = number_format(round((float) $this->quantity, 8), 8, '.', '');
                                        $stopPrice = $currentPrice + (($this->increasing * 5));
//                                        $stopPrice = $currentPrice + (($currentPrice * 0.1));
                                        $flag = [];
                                        $flag["stopPrice"] = number_format(round((float) $stopPrice, 8), 8, '.', '');

                                        $this->info("- BUYING NOW: P: $currentPrice | Q: $buyQuantity | SP: " . $flag["stopPrice"], true);

//                                        $order = $api->buyTest($instrument, $buyQuantity, $currentPrice);
//                                        $order = $api->buyTest($instrument, $buyQuantity, $currentPrice, "STOP_LOSS_LIMIT", $flag);
//                                      $order = $api->buy($instrument, $buyQuantity, $currentPrice, "STOP_LOSS_LIMIT", $flag);
                                      $order = $api->marketBuy($instrument, $buyQuantity);
//                                        $order = $api->buy($instrument, $buyQuantity, $currentPrice);

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
                                    
                                }
                            }
                        }
                    }
                }
            }
//            sleep(60 * 5);
            sleep(60);
        }
        return null;
    }
}