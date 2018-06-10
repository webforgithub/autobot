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
use MathPHP\Statistics\Average;

/**
 * Class ExampleCommand
 * @package App\Console\Commands
 *
 * AlertMACDStrategiesCommand will calculate the BUY/SELL/HOLD advise. It's depends on MACD algorithm.
 * 
 * We have used EMA (Exponential Moving Average) and than calculate the MACD. To calculate Exponential Moving Average we have used MathPHP libs.
 * Previously we have used PHP Trader extension but it was not working perfectly and giving wrong result.
 *
 */
class AlertMACDStrategiesCommand extends Command {

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
    protected $defaultWaitTime = 3;

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

    protected function GetLastFilledOrder($configurationId, $UserId, $symbol) {
        $retunAerts = array();

        $retunAerts = DB::table('alerts')
                ->where('configuremacdbot_id', "=", $configurationId)
                ->where('currency_name', "=", $symbol)
                ->where('user_id', "=", $UserId)
                ->whereDate('created_at', '>=', \Carbon\Carbon::today()->toDateString())
                ->whereNull('deleted_at')
                ->orderBy('id', "DESC")
                ->take(1)
                ->get();

        return $retunAerts;
    }

    protected function GetLastOrderType($configurationId, $symbol, $UserId) {
        $returnType = '';
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

    protected function GetLastTradeTime($configurationId, $symbol, $UserId, $orderType = 'BUY') {
        $returnType = date('Y-m-d H:i:s');

        $tmpStatus = DB::table('alerts')
                ->where('configuremacdbot_id', "=", $configurationId)
                ->where('currency_name', "=", $symbol)
                ->where('user_id', "=", $UserId)
                ->where('alert_type', "=", $orderType)
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

    protected function subtractTwoArray($ema_fast, $ema_slow) {
        $ret = array();
        foreach ($ema_fast as $key => $value) {
            $ret[$key] = number_format($ema_fast[$key] - $ema_slow[$key], 10);
        }
        return $ret;
    }

    /**
     * 
     * @return null
     * 
     * This command run as Cron job with cron tab. 
     * 
     * How it works ?
     * 
     * @Step 1: Get all coin list which is setup by user's of the system. Each coin is associated with user account and Binance API Key.
     * 
     * @Step 2: Fetch the User and Binance API Key.
     * 
     * @Step 3: Fetch current coin market price and store it for further use.
     * @Step 3.1: Fetch current coin Ticker prices and store it.
     * @Step 3.2: Fetch current coin Order book and find Last Bid and Ask price.
     * 
     * @Step 4: Fetch current coin Candle Stick for 3 minutes interval.
     * 
     * @Step 5: Fetch current coin last order detail. This will helps to avoid duplication of the order. 
     *          Mean if we have already place BUY order than we have to place SELL order for selected coin.
     * 
     * @Step 6: Now calculate the MACD with the help of Exponential Moving Average. It is 3 steps process.
     * 
     * @Step 6.1: 1st Calculate the Fast and Slow moving average. Then deducate SLOW from FAST moving average and store it in MACD variable
     *              FAST - SLOW => MACD 
     * 
     * @Step 6.2: 2nd Calculate MACD Signal moving average for MACD variable, which is we stored in previous step
     *               Exponential Moving Average ( MACD ) => SIGNAL 
     * 
     * @Step 6.3: 3rd Calculate LINE CROSSING for MACD. 
     *            IF (MACD[CURRENT INDEX] > SIGNAL[CURRENT INDEX] AND MACD[CURRENT INDEX - 1] <= SIGNAL[CURRENT INDEX - 1]) {
     *                  BUY -> SIGNALE
     *            ELSE IF (MACD[CURRENT INDEX] < SIGNAL[CURRENT INDEX] AND MACD[CURRENT INDEX - 1] >= SIGNAL[CURRENT INDEX - 1]) {
     *                  SELL -> SIGNALE
     *            ELSE
     *                  HOLD -> SIGNALE
     * 
     * @Step 7: Once we have BUY/SELL/HOLD signal, than we can process the coin NOTIFICATION email with current price type of transaction.
     * 
     * @Step 8: Restart the step 1 for other. LOOP.
     * 
     */
    public function handle() {
        @ini_set('precision', '10');
        @ini_set('trader.real_precision', '10');
        @date_default_timezone_set('Europe/Amsterdam');

        $rundemo = false;

        if ($rundemo = $this->argument('runtest')) {
            $this->info("Running the DEMO test of all the strategies:");
        }

//        echo "PRESS 'q' TO QUIT AND CLOSE ALL POSITIONS\n\n\n";
//        stream_set_blocking(STDIN, 0);

//        while (1) {

//            if (ord(fgetc(STDIN)) == 113) {
//                /*  try to catch keypress 'q'  */
//                echo "QUIT detected...";
//                return null;
//            }

            $symbols = DB::table('configuremacdbots')
                    ->select("*")
                    ->whereNull('deleted_at')
                    ->get();

            $this->output->write("-----------------------  ALERT LOOP IS STARTED :: " .\Carbon\Carbon::today()->toDayDateTimeString()." -----------------------------", true);

            foreach ($symbols as $instrumentObj) {
                $userObj = DB::table('users')->select("*")->where('id', "=", $instrumentObj->userid)->get();

                if (count($userObj) > 0) {
                    $this->output->write("************************************ USER ID :: " . $instrumentObj->userid, true);
                    $this->output->write("- COIN PAIR " . $instrumentObj->symbol, true);

                    $userObj = $userObj[0];
                    $binanceKey = $this->GetAPIKey($instrumentObj->userid);

                    if ($binanceKey !== false) {
                        $api = new \Binance\API($binanceKey->api_key, $binanceKey->secrete_key, ["useServerTime" => true]);

                        $configurationId = $instrumentObj->id;
                        $instrument = $instrumentObj->symbol;

                        $symbolCurrentPrice = $api->symbolPrice($instrument);
                        $symbolCurrentPrice = number_format((float) $symbolCurrentPrice, 8, '.', ' ');

                        # Fetches the ticker price
                        $lastPrice = $this->getTicker($api, $instrument);
                        $lastPrice = number_format((float) $lastPrice, 8, '.', ' ');

                        # Order book prices (lastBid, lastAsk)
                        $orderBook = $this->getOrderBook($api, $instrument);

                        $order = array();
                        /** Following price is commented because it giving LOWER price when we are notifiying use for SELL after BUY. **/
                        //$order["price"] = $symbolCurrentPrice;
                        $order["price"] = $lastPrice; 
                        $order["alertdate"] = date("Y-m-d H:i:s");
                        
                        $buyPrice = $orderBook["lastBid"];
                        $order["lastBid"] = $orderBook["lastBid"];
                        
                        $sellPrice = $orderBook["lastAsk"];
                        $order["lastAsk"] = $orderBook["lastAsk"];
                        
                        $recentData = $api->candlesticks($instrument, "3m");

                        if (count($recentData) > 0) {
                            $closeArray = [];
                            $tmpCloseArray = array_values(array_map(function($sub) {
                                        return ($sub["close"]);
                                    }, $recentData));

                            $closeArray["close"] = array_values($tmpCloseArray);

                            /*
                             * Fetch last alert order
                             * $configurationId, $UserId, $symbol
                             */
                            $lastBuySellOrder = $this->GetLastFilledOrder($configurationId, $instrumentObj->userid, $instrument);
                            $lastOrderType = 'BUYSELL';
                            $this->defaultWaitTime = 0;
                            $minutes = 0;

                            if (count($lastBuySellOrder) > 0) {
                                $lastTradeTime = $lastBuySellOrder[0]->alert_time;
                                $lastOrderType = $lastBuySellOrder[0]->alert_type;
                                $minutes = (time() - strtotime($lastTradeTime)) / 60;
                            }

                            /** https://simplecrypt.co/strategy.html * */
                            //$macdData = $indicators->macd($instrument, $closeArray, $instrumentObj->ema_short_period, $instrumentObj->ema_long_period, $instrumentObj->signal_period);

                            $macdData = 0;
                            $ema_fast = Average::exponentialMovingAverage($closeArray["close"], $instrumentObj->ema_short_period);
                            $ema_slow = Average::exponentialMovingAverage($closeArray["close"], $instrumentObj->ema_long_period);
                            $macd = array();
                            $signal = array();
                            $hist = array();

                            $macd = $this->subtractTwoArray($ema_fast, $ema_slow);

                            $signal = Average::exponentialMovingAverage($macd, $instrumentObj->signal_period);
                            $hist = $this->subtractTwoArray($macd, $signal);
                            $loopIndex = count($macd);
                            
                            $macdSignal = null;

                            if ($loopIndex > 0) {
                                $keys = array_keys($recentData);
                                for ($index = 1; $index < $loopIndex; $index++) {
                                    if ($macd[$index] > $signal[$index] && $macd[$index - 1] <= $signal[$index - 1]) {
                                        /* If the MACD crosses the signal line upward */
                                        $macdData = 1;      /** BUY * */
                                    } else if ($macd[$index] < $signal[$index] && $macd[$index - 1] >= $signal[$index - 1]) {
                                        /* The other way around */
                                        $macdData = -1;     /** SELL * */
                                    } else {
                                        /* Do nothing if not crossed */
                                        $macdData = 0;      /** HOLD * */
                                    }
                                    $recentData[$keys[$index]]['advice'] = $macdData;
                                }
                            }
                            
                            $macdSignal = array_pop($recentData);
                            
                            if (is_array($macdSignal) && $macdSignal != null) {
                                $macdData = $macdSignal['advice'];

                                /** BUY(1) / HOLD(0) / SELL(-1) **/
                                $arrayMACD = array(-1 => "SELL", 0 => "Hold", 1 => "BUY");

                                $this->output->write("- LAST TRANSACTION: " . $lastOrderType, true);
                                $this->output->write("----------> MACD ALERT TYPE: " . $arrayMACD[$macdData], true);

                                $sendNotification = false;
                                $tmpAlert = array();
                                
                                if ($macdData == -1) {
                                    if ($lastOrderType != $arrayMACD[$macdData] && $minutes >= $this->defaultWaitTime) {
                                        $sendNotification = true;
                                        
                                        $tmpAlert["alert_type"] = "SELL";
                                        $tmpAlert["alert_time"] = date("Y-m-d H:i:s");
                                        $tmpAlert["currency_name"] = $instrument;
                                        $tmpAlert["user_id"] = $instrumentObj->userid;
                                        $tmpAlert["configuremacdbot_id"] = $configurationId;
                                        /** BELOW LINE is commented becuase it's showing LOWER PRICE when SELLING after BUYING **/
                                        //$tmpAlert["currency_price"] = $symbolCurrentPrice;
                                        $tmpAlert["currency_price"] = $sellPrice;
                                    }
                                } else if ($macdData == 1) {
                                    if ($lastOrderType != $arrayMACD[$macdData] && $minutes >= $this->defaultWaitTime) {
                                        $sendNotification = true;

                                        $tmpAlert["alert_type"] = "BUY";
                                        $tmpAlert["alert_time"] = date("Y-m-d H:i:s");
                                        $tmpAlert["currency_name"] = $instrument;
                                        $tmpAlert["user_id"] = $instrumentObj->userid;
                                        $tmpAlert["configuremacdbot_id"] = $configurationId;
                                        /** BELOW LINE is commented becuase it's showing LOWER PRICE when SELLING after BUYING **/
                                        //$tmpAlert["currency_price"] = $symbolCurrentPrice;
                                        $tmpAlert["currency_price"] = $buyPrice;
                                    }
                                }

                                $tmpCount = count($tmpAlert);
                                if ($sendNotification === true && $tmpCount > 0) {
                                    $this->output->write("- TIME DIFFERENT BETWEEN ORDER: " . $minutes, true);
                                    $this->output->write("- BuyPrice: " . $buyPrice, true);
                                    $this->output->write("- SellPrice: " . $sellPrice, true);

                                    Alert::create($tmpAlert);

                                    $order["type"] = $tmpAlert["alert_type"];
                                    $order["alert_type"] = $tmpAlert["alert_type"];
                                    $order["alert_time"] = date("Y-m-d H:i:s e");
                                    $order["currency_name"] = $tmpAlert["currency_name"];
                                    $order["currency_price"] = $tmpAlert["currency_price"];
                                    $order["user_id"] = $tmpAlert["user_id"];
                                    $order["configuremacdbot_id"] = $tmpAlert["configuremacdbot_id"];

                                    if ($instrumentObj->alert_email != "") {
                                        $orderObj = array('orderObject' => $order, 'instrumentPair');

                                        $email = $instrumentObj->alert_email;
                                        $alertType = $order["alert_type"];

                                        $this->output->write("- EMAIL: " . $email, true);

                                        $isSent = \Mail::send('emails.tradealertnew', ['data' => $orderObj], function ($m) use ($alertType, $email, $instrument) {
                                                    $m->from('no-reply@autobot.com', 'CryptoBee Trader');
                                                    $m->cc('parmaramit1111@gmail.com', 'Amit P');
                                                    $m->cc('scalableapplication@gmail.com', 'Arpit H');

                                                    $subject = 'CryptoBee Trader: [' . $alertType . '] alert for ' . $instrument;

                                                    $m->to($email)->subject($subject);
                                                });

                                        $this->output->write("- IS SENT?: " . $isSent, true);
                                    }
                                }
                            }
                        }
                    }
                    sleep(30);
                }
            }
//            sleep(3 * 60);
//        }
        return null;
    }
}
