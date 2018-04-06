<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\AutoBotMACDEvent;
use DB;

class MACDRule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MACD:PlaceOrder';
    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage all symbols for auto trading.';
    
    //public $listing_cols = ['id', 'alert_email', 'alert_mobile_no', 'user_id', 'buy_sale_volume', 'trade_type', 'trade_symbol', 'current_price', 'is_percentage', 'buy_price', 'sell_price'];
    public $listing_cols = ["id", "deleted_at", "created_at", "updated_at", "symbol", "userid", "volume", "totalorder", "period", "period_length", "min_periods", "ema_short_period", "ema_long_period", "signal_period", "up_trend_threshold", "down_trend_threshold", "overbought_periods", "overbought_rsi", "use_all_fund", "alert_email", "alert_mobile"];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $setupRules = DB::table('configuremacdbots')
                ->select($this->listing_cols)
                ->whereNull('deleted_at')
                ->get();
        
        foreach($setupRules as $item) {
            \Event::fire(new AutoBotMACDEvent($item));
        }
    }
}
