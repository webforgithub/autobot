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

/**
 * Class ExampleCommand
 * @package App\Console\Commands
 *
 *          SEE COMMENTS AT THE BOTTOM TO SEE WHERE TO ADD YOUR OWN
 *          CONDITIONS FOR A TEST.
 *
 */
class ShortStrategiesCommand extends Command {

    use Signals,
        Strategies,
        OHLC; // add our traits

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'autobot:short_strategies';

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
            
            $this->output->write("I have restarted", true);
            $symbols = DB::table('configuremacdbots')
                    ->select("*")
                    ->whereNull('deleted_at')
                    ->get();
            
//            $this->output->write(json_encode($symbols), true);
            
            foreach ($symbols as $instrumentObj) {
                $userObj = DB::table('users')->select("*")->where('id', "=", $instrumentObj->userid)->get();

                if (count($userObj) > 0) {
                    $userObj = $userObj[0];
                    $binanceKey = $this->GetAPIKey($instrumentObj->userid);
                    
                    if ($binanceKey !== false) {
                        $this->output->write("Start Event", true);                        
                        \Event::fire( new ProcessStrategy($userObj, $binanceKey, $instrumentObj));                        
                        $this->output->write("End Event", true);
                        
//                        $api = new \Binance\API($binanceKey->api_key, $binanceKey->secrete_key);
//                        $balances = $api->balances(true);
//                        
//                        $availableBalance = $userObj->balance_settings;
//                        $instrument = $instrumentObj->symbol;
//
//                        $this->info("Available Balance to use: " . $availableBalance);
//                        $this->info("Current Symbol: " . $instrument);
//
//                        $recentData = $indicators->getRecentData($instrument);
//                        if (count($recentData) > 0) {
//                            $macdData = $indicators->macd($instrument, $recentData[6], $instrumentObj->ema_short_period, $instrumentObj->ema_long_period, $instrumentObj->signal_period);
//
//                            /* buy(1)/hold(0)/sell(-1) * */
//                            $arrayMACD = array(-1 => "Sell", 0 => "Hold", 1 => "Buy");
//                            $this->info("MACD Signal: " . $arrayMACD[$macdData]);
//                        }
                    }
                }
            }
            sleep(5);
        }
        return null;
    }
}