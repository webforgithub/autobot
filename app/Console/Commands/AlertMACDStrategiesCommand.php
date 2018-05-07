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

    /** -------------------------------------------------------------------
     * @return null
     *
     *  this is the part of the command that executes.
     *  -------------------------------------------------------------------
     */
    public function handle() {
        @ini_set('trader.real_precision', '8');
        @date_default_timezone_set('Europe/Amsterdam');

        $rundemo = false;

        if ($rundemo = $this->argument('runtest')) {
            $this->info("Running the DEMO test of all the strategies:");
        }

        echo "PRESS 'q' TO QUIT AND CLOSE ALL POSITIONS\n\n\n";
        stream_set_blocking(STDIN, 0);

        $console = new \App\Util\Console();
        $indicators = new Indicators();

        while (1) {
            if (ord(fgetc(STDIN)) == 113) {
                /*  try to catch keypress 'q'  */
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
                    $this->output->write("- USER ID :: " . $instrumentObj->userid, true);
                    $this->output->write("- COIN PAIR " . $instrumentObj->symbol, true);
                    

                    $userObj = $userObj[0];
                    $binanceKey = $this->GetAPIKey($instrumentObj->userid);

                    if ($binanceKey !== false) {
                        $api = new \Binance\API($binanceKey->api_key, $binanceKey->secrete_key, ["useServerTime" => true]);

                        $configurationId = $instrumentObj->id;
                        $instrument = $instrumentObj->symbol;

                        $symbolCurrentPrice = $api->symbolPrice($instrument);
                        $symbolCurrentPrice = number_format(round((float) $symbolCurrentPrice, 8), 8, '.', ' ');

                        # Fetches the ticker price
                        $lastPrice = $this->getTicker($api, $instrument);

                        # Order book prices (lastBid, lastAsk)
                        $orderBook = $this->getOrderBook($api, $instrument);

                        $order = array();
                        $order["price"] = $symbolCurrentPrice;
                        $order["lastBid"] = $orderBook["lastBid"];
                        $order["lastAsk"] = $orderBook["lastAsk"];
                        $order["alertdate"] = date("Y-m-d H:i:s");

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

                            /** https://simplecrypt.co/strategy.html **/
                            $macdData = $indicators->macd($instrument, $closeArray, $instrumentObj->ema_short_period, $instrumentObj->ema_long_period, $instrumentObj->signal_period);
                            //$macdData = $indicators->macd($instrument, $closeArray, 120, 260, 90);

                            /* buy(1)/hold(0)/sell(-1) * */
                            $arrayMACD = array(-1 => "SELL", 0 => "Hold", 1 => "BUY");

                            $this->output->write("- MACD ALERT TYPE: " . $arrayMACD[$macdData], true);
                            $this->output->write("- LAST TRANSACTION: " . $lastOrderType, true);

                            $sendNotification = false;
                            $tmpAlert = array();

                            if ($macdData == -1) {
                                if ($lastOrderType != $arrayMACD[$macdData] && $minutes >= $this->defaultWaitTime) {
                                    $sendNotification = true;
                                    
                                    $tmpAlert["alert_type"] = "SELL";
                                    $tmpAlert["alert_time"] = date("Y-m-d H:i:s");
                                    $tmpAlert["currency_name"] = $instrument;
                                    $tmpAlert["currency_price"] = $symbolCurrentPrice;
                                    $tmpAlert["user_id"] = $instrumentObj->userid;
                                    $tmpAlert["configuremacdbot_id"] = $configurationId;
                                }
                            } else if ($macdData == 1) {
                                
                                if ($lastOrderType != $arrayMACD[$macdData] && $minutes >= $this->defaultWaitTime) {
                                    $sendNotification = true;
                                    
                                    $tmpAlert["alert_type"] = "BUY";
                                    $tmpAlert["alert_time"] = date("Y-m-d H:i:s");
                                    $tmpAlert["currency_name"] = $instrument;
                                    $tmpAlert["currency_price"] = $symbolCurrentPrice;
                                    $tmpAlert["user_id"] = $instrumentObj->userid;
                                    $tmpAlert["configuremacdbot_id"] = $configurationId;
                                }
                            }

                            $tmpCount = count($tmpAlert);
                            if ($sendNotification === true && $tmpCount > 0) {
                                $this->output->write("- TIME DIFFERENT BETWEEN ORDER: " . $minutes, true);
                                
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
                                        $m->cc('parmaramit1111@gmail.com', 'Amit');
                                        $m->cc('scalableapplication@gmail.com', 'Arpit');
                                        $subject = 'CryptoBee Trader: ['.$alertType.'] alert for ' . $instrument;
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
        return null;
    }
}