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
 * https://github.com/hurdad/doo-forex
 * https://github.com/ewgRa/tradeSystem
 * https://www.quantopian.com/posts/help-with-the-macd-sample-code
 *
 */
class MACDStrategiesCommand extends Command {

    use Signals,
        Strategies,
        Orders,
        OHLC; // add our traits    

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'autobot:macd_strategies';

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
        $macd = new \App\Traits\MACD();

//        if (ord(fgetc(STDIN)) == 113) {
//            /*  try to catch keypress 'q'  */
//            echo "QUIT detected...";
//            return null;
//        }

        while (1) {
            $symbols = DB::table('configuremacdbots')
                    ->select("*")
                    ->whereNull('deleted_at')
                    ->get();

            $this->output->write("******************************  Loop Started  ******************************", true);
            foreach ($symbols as $instrumentObj) {
                $userObj = DB::table('users')->select("*")->where('id', "=", $instrumentObj->userid)->get();

                if (count($userObj) > 0) {

                    $this->output->write("- USER ID :: " . $instrumentObj->userid, true);
                    $this->output->write("- COIN PAIR " . $instrumentObj->symbol, true);

                    $userObj = $userObj[0];
                    $binanceKey = $this->GetAPIKey($instrumentObj->userid);

                    if ($binanceKey !== false) {
                        $api = new \Binance\API($binanceKey->api_key, $binanceKey->secrete_key, ["useServerTime" => true]);

                        $configurationId = $instrumentObj->id;
                        $instrument = $instrumentObj->symbol;

                        $recentData = $api->candlesticks($instrument, "3m", 200);

                        if (count($recentData) > 0) {
                            $closeArray = [];
                            $tmpCloseArray = array_values(array_map(function($sub) { return ($sub["close"]); }, $recentData));

                            $closeArray["close"] = array_values($tmpCloseArray);

                            $oldMACD = trader_macd($closeArray["close"], $instrumentObj->ema_short_period, $instrumentObj->ema_long_period, $instrumentObj->signal_period);
                            $this->output->write("----------------------- MACD ". $instrumentObj->ema_short_period .'='. $instrumentObj->ema_long_period .'='. $instrumentObj->signal_period ."  -----------------------------", true);
                            
                            $macd = $oldMACD[0];
                            $signal = $oldMACD[1];
                            if (!$macd || !$signal) {
                                //If not enough Elements for the Function to complete
                            } else {
                                $loopIndex = count($macd);
                                $macd = array_values($macd);
                                $signal = array_values($signal);
                                
                                for ($index = $loopIndex - 1; $index < $loopIndex; $index++) {                                    
                                    if ($macd[$index] > $signal[$index] && $macd[$index - 1] <= $signal[$index - 1]) {
                                        /* If the MACD crosses the signal line upward  */
                                        $this->output->write("BUY <*****<", true);
                                    } else if ($macd[$index] < $signal[$index] && $macd[$index - 1] >= $signal[$index - 1]) {
                                        /* The other way around */
                                        $this->output->write("SELL >*****>", true);
                                    } else {
                                        /* Do nothing if not crossed */
                                        $this->output->write("HOLD >----->", true);
                                    }
                                }
                            }
                            
//                            $this->output->write("-----------------------  LIB MACD  -----------------------------", true);
//
//                            $short_ema = trader_ema($closeArray["close"], $instrumentObj->ema_short_period);
//                            $long_ema = trader_ema($closeArray["close"], $instrumentObj->ema_long_period);
//                            $signal_period = trader_ema($closeArray["close"], $instrumentObj->signal_period);
//
//                            //$lib_singal = number_format((array_pop($short_ema) - array_pop($long_ema)), 10);
//                            $lib_singal = (int) (array_pop($short_ema) - array_pop($long_ema));
//                            $this->output->write("- short_ema: " . count($short_ema), true);
//                            $this->output->write("- long_ema: " . count($long_ema), true);
//                            $this->output->write("- signal_period: " . count($signal_period), true);
//                            $this->output->write("- Singal: " . $lib_singal, true);
                        }
                    }
                }
            }
            sleep(3 * 60);
//            sleep(30);
        }
        return null;
    }
}
