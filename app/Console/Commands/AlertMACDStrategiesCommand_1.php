<?php

namespace App\Console\Commands;

use App\Traits\OHLC;
use App\Traits\Signals;
use App\Traits\Strategies;
use App\Util\Indicators;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputArgument;
use App\Traits\Orders;
use App\Models\Alert;

/**
 * Class ExampleCommand
 * @package App\Console\Commands
 *
 *          SEE COMMENTS AT THE BOTTOM TO SEE WHERE TO ADD YOUR OWN
 *          CONDITIONS FOR A TEST.
 *
 */
class AlertMACDStrategiesCommand_1 extends Command {

    use Signals,
        Strategies,
        Orders,
        OHLC; // add our traits    

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'autobot:alertmacd_strategies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send buy/sell alert to end users.';

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

    protected function GetLastFilledBuyOrder($configurationId, $UserId, $symbol, $orderType = 'BUY') {
        return DB::table('alerts')
                        ->where('configuremacdbot_id', "=", $configurationId)
                        ->where('currency_name', "=", $symbol)
                        ->where('alert_type', "=", $orderType)
                        ->where('user_id', "=", $UserId)
                        ->whereNull('deleted_at')
                        ->orderBy('id', "DESC")
                        ->take(1)
                        ->get();
    }

    protected function GetLastOrderType($configurationId, $symbol, $UserId) {
        $returnType = 'SELL';
        $tmpStatus = DB::table('alerts')
                ->where('configuremacdbot_id', "=", $configurationId)
                ->where('currency_name', "=", $symbol)
                ->where('user_id', "=", $UserId)
                ->whereDate('created_at', '>=', \Carbon\Carbon::today()->toDateString())
                ->whereNull('deleted_at')
                ->select("*")
                ->orderBy('id', "DESC")
                ->take(1)
                ->get();

        if (count($tmpStatus) > 0) {
            return $tmpStatus[0]->alert_type;
        }

        return $returnType;
    }

    protected function GetLastTradeTime($configurationId, $symbol, $UserId) {
        $returnType = date('Y-m-d H:i:s');

        $tmpStatus = DB::table('alerts')
                ->where('configuremacdbot_id', "=", $configurationId)
                ->where('currency_name', "=", $symbol)
                ->where('user_id', "=", $UserId)
                ->whereDate('created_at', '>=', \Carbon\Carbon::today()->toDateString())
                ->whereNull('deleted_at')
                ->select("*")
                ->orderBy('id', "DESC")
                ->take(1)
                ->get();

        if (count($tmpStatus) > 0) {
            return $tmpStatus[0]->alert_time;
        }

        return $returnType;
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

                    if ($binanceKey !== false) {
                        $api = new \Binance\API($binanceKey->api_key, $binanceKey->secrete_key, ["useServerTime" => true]);

                        $configurationId = $instrumentObj->id;
                        $instrument = $instrumentObj->symbol;

                        //$configurationId, $UserId, $symbol, $orderType = 'BUY'
                        $lastBuyOrder = $this->GetLastFilledBuyOrder($configurationId, $instrumentObj->userid, $instrument);

                        $symbolCurrentPrice = $api->symbolPrice($instrument);
                        $symbolCurrentPrice = number_format(round((float) $symbolCurrentPrice, 8), 8, '.', ' ');

                        # Fetches the ticker price
                        $lastPrice = $this->getTicker($api, $instrument);

                        # Order book prices (lastBid, lastAsk)
                        $orderBook = $this->getOrderBook($api, $instrument);

                        $buyPrice = $orderBook["lastBid"];
                        $buyPrice = number_format(round((float) $buyPrice, 8), 8, '.', ' ');

                        $sellPrice = $orderBook["lastAsk"];
                        $sellPrice = number_format(round((float) $sellPrice, 8), 8, '.', ' ');

                        $order = array();
                        $order["price"] = $symbolCurrentPrice;
                        $order["lastBid"] = $orderBook["lastBid"];
                        $order["lastAsk"] = $orderBook["lastAsk"];
                        $order["alertdate"] = date("Y-m-d H:i:s");

//                          $recentData = $indicators->getRecentData($instrument, 168, false, 12, '10m');
                        $recentData = $api->candlesticks($instrument, "3m");

                        if (count($recentData) > 0) {
                            $isBuy = $this->GetLastOrderType($configurationId, $instrument, $instrumentObj->userid);

                            $closeArray = [];
                            $tmpCloseArray = array_values(array_map(function($sub) {
                                        return ($sub["close"]);
                                    }, $recentData));

                            $closeArray["close"] = array_values($tmpCloseArray);

                            $macdData = $indicators->macd($instrument, $closeArray, $instrumentObj->ema_short_period, $instrumentObj->ema_long_period, $instrumentObj->signal_period);

                            /* buy(1)/hold(0)/sell(-1) * */
                            $arrayMACD = array(-1 => "SELL", 0 => "Hold", 1 => "BUY");

                            $currentPrice = array_pop($closeArray['close']);
                            $currentPrice = $symbolCurrentPrice;
                            $lastTradeTime = $this->GetLastTradeTime($configurationId, $instrument, $instrumentObj->userid);

                            $this->output->write("- ALERT TYPE " . $arrayMACD[$macdData], true);
                            $this->output->write("- DB Signale " . $isBuy, true);
                            
                            if ($macdData == -1) {
                                /** SELL NOW * */
                                $minutes = (time() - strtotime($lastTradeTime)) / 60;                                    
                                
                                if ($isBuy == 'BUY') {
                                    
                                    $this->output->write("- LAST ORDER BUY COUNT " . count($lastBuyOrder), true);
                                    $this->output->write("- LAST ORDER SELL COUNT " . $minutes, true);
                                    
                                    if (count($lastBuyOrder) == 0) {
                                        $tmpAlert = array();
                                        $tmpAlert["alert_type"] = "SELL";
                                        $tmpAlert["alert_time"] = date("Y-m-d H:i:s");
                                        $tmpAlert["currency_name"] = $instrument;
                                        $tmpAlert["currency_price"] = $buyPrice;
                                        $tmpAlert["user_id"] = $instrumentObj->userid;
                                        $tmpAlert["configuremacdbot_id"] = $configurationId;

                                        Alert::create($tmpAlert);

                                        $order["type"] = "SELL";
                                        \Event::fire(new \App\Events\SendAlertToUsers($userObj, $order, $instrumentObj, 'SELL'));
                                    } else {
                                        if ($minutes > 3) {
                                            $tmpAlert = array();
                                            $tmpAlert["alert_type"] = "SELL";
                                            $tmpAlert["alert_time"] = date("Y-m-d H:i:s");
                                            $tmpAlert["currency_name"] = $instrument;
                                            $tmpAlert["currency_price"] = $buyPrice;
                                            $tmpAlert["user_id"] = $instrumentObj->userid;
                                            $tmpAlert["configuremacdbot_id"] = $configurationId;

                                            Alert::create($tmpAlert);

                                            $order["type"] = "BUY";
                                            \Event::fire(new \App\Events\SendAlertToUsers($userObj, $order, 'SELL'));
                                        }
                                    }
                                }
                            } else if ($macdData == 1) {
                                /*                                 * *
                                 * Before BUYING check previouly OPEN Order. If there is already OPEN order than DON'T BUY.
                                 */
//                                $lastBuyOrder = $this->GetLastFilledBuyOrder($configurationId, $instrumentObj->userid, $instrument, 'SELL');                                
                                $minutes = (time() - strtotime($lastTradeTime)) / 60;                                  
                                
                                /** BUY NOW * */
                                if (count($lastBuyOrder) == 0) {
                                    $isBuy = 'SELL';
                                }

                                if ($isBuy == 'SELL') {                   
                                    $this->output->write("- LAST ORDER SELL COUNT " . count($lastBuyOrder), true);
                                    
                                    if (count($lastBuyOrder) == 0) {
                                        $tmpAlert = array();
                                        $tmpAlert["alert_type"] = "BUY";
                                        $tmpAlert["alert_time"] = date("Y-m-d H:i:s");
                                        $tmpAlert["currency_name"] = $instrument;
                                        $tmpAlert["currency_price"] = $buyPrice;
                                        $tmpAlert["user_id"] = $instrumentObj->userid;
                                        $tmpAlert["configuremacdbot_id"] = $configurationId;

                                        Alert::create($tmpAlert);

                                        $order["type"] = "BUY";
                                        \Event::fire(new \App\Events\SendAlertToUsers($userObj, $order, $instrumentObj, 'BUY'));
                                    } else {
                                        $this->output->write("- LAST ORDER SELL COUNT " . $minutes, true);
                                        if ($minutes > 3) {
                                            $tmpAlert = array();
                                            $tmpAlert["alert_type"] = "BUY";
                                            $tmpAlert["alert_time"] = date("Y-m-d H:i:s");
                                            $tmpAlert["currency_name"] = $instrument;
                                            $tmpAlert["currency_price"] = $buyPrice;
                                            $tmpAlert["user_id"] = $instrumentObj->userid;
                                            $tmpAlert["configuremacdbot_id"] = $configurationId;

                                            Alert::create($tmpAlert);

                                            $order["type"] = "BUY";
                                            \Event::fire(new \App\Events\SendAlertToUsers($userObj, $order, $instrumentObj, 'BUY'));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                sleep(30);
            }            
        }
        return null;
    }
}
