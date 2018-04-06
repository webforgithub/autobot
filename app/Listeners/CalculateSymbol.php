<?php

namespace App\Listeners;

use App\Events\AutoBotMACDEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use DB;
use Mail;

class CalculateSymbol {

    protected $APIKey = '6N9ih2aoSMqAkoHVvs8M4SMy2GwCyQooqLZWmgu8dwJAyIYkB36LhjCpQqTOvGCp';
    protected $ScreateKey = 'bnbsT9gIzPuSHLhaqMt7j4eE914Y6hvFPrO8VVz768kC3lI8j6GGWMFdFtnoukpb';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AutoBotMACDEvent  $event
     * @return void
     */
    public function handleOLD(AutoBotMACDEvent $event) {
        //
        $myKeys = DB::table('binance_settings')->select('*')->whereNull('deleted_at')->where('user_id', '=', $event->configBotModel->userid)->first();

        if ($myKeys && count($myKeys) > 0) {
            $this->APIKey = $myKeys->api_key;
            $this->ScreateKey = $myKeys->secrete_key;
        }
        
        if ($event->configBotModel->alert_email != "") {
            /**
             * python trader.py --symbol XVGBTC

              Example parameters

              # Profit mode (default)
              python trader.py --symbol XVGBTC --quantity 300 --profit 1.3
              or by amount
              python trader.py --symbol XVGBTC --amount 0.0022 --profit 3

              # Range mode
              python trader.py --symbol XVGBTC --mode range --quantity 300 --buyprice 0.00000780 --sellprice 0.00000790
              or by amount
              python trader.py --symbol XVGBTC --mode range --amount 0.0022 --buyprice 0.00000780 --sellprice 0.00000790

              --quantity     Buy/Sell Quantity (default 0) (If zero, auto calc)
              --amount       Buy/Sell BTC Amount (default 0)
              --symbol       Market Symbol (default XVGBTC or XVGETH)
              --profit       Target Profit Percentage (default 1.3)
              --stop_loss    Decrease sell price at loss Percentage (default 0)
              --orderid      Target Order Id (default 0)
              --wait_time    Wait Time (seconds) (default 0.7)
              --increasing   Buy Price Increasing  +(default 0.00000001)
              --decreasing   Sell Price Decreasing -(default 0.00000001)
              --prints       Scanning Profit Screen Print (default True)
              --loop         Loop (default 0 unlimited)

              --mode         Working modes profit or range (default profit)
              profit: Profit Hunter. Find defined profit, buy and sell. (Ex: 1.3% profit)
              range: Between target two price, buy and sell. (Ex: <= 0.00000780 buy - >= 0.00000790 sell )

              --buyprice     Buy price (Ex: 0.00000780)
              --sellprice    Buy price (Ex: 0.00000790)00000790
             */
            
            $prepareCommand = "/usr/bin/python /var/www/html/auto-bot/trader.py ";
            $prepareCommand .= "--symbol ". $event->configBotModel->symbol ." ";
            $prepareCommand .= "--quantity ". $event->configBotModel->volume ." ";
//            $prepareCommand .= "--increasing 0.00000000 ";
//            $prepareCommand .= "--decreasing 0.00000000 ";
            //$prepareCommand .= "--loop 1 ";
            shell_exec($prepareCommand);
            
//            Mail::send('emails.autotrade', ['data' => $event->configBotModel], function ($m) use ($event) {
//                $m->from('no-reply@autobot.com', 'CryptoBee Trader');
//                $m->to($event->configBotModel->alert_email)->subject('CryptoBee Trader: Processed your auto trade.');
//            });
        }
    }   
    
    /**
     * Handle the event.
     *
     * @param  AutoBotMACDEvent  $event
     * @return void
     */
    public function handle(AutoBotMACDEvent $event) {
        //
        $myKeys = DB::table('binance_settings')->select('*')->whereNull('deleted_at')->where('user_id', '=', $event->configBotModel->userid)->first();

        if ($myKeys && count($myKeys) > 0) {
            $this->APIKey = $myKeys->api_key;
            $this->ScreateKey = $myKeys->secrete_key;
        }
        //trader_macd();
        
        if ($event->configBotModel->alert_email != "") {                
//            Mail::send('emails.autotrade', ['data' => $event->configBotModel], function ($m) use ($event) {
//                $m->from('no-reply@autobot.com', 'CryptoBee Trader');
//                $m->to($event->configBotModel->alert_email)->subject('CryptoBee Trader: Processed your auto trade.');
//            });
        }
    }
    
}
