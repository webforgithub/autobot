<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\MACDRule;
use App\Console\Commands\ScheduleList;
use App\Console\Commands\DataRunnerBinanceCommand;
use App\Console\Commands\ShortStrategiesCommand;
use App\Console\Commands\TestShortStrategiesCommand;
use App\Console\Commands\DataRunnerCoinigyCommand;
use App\Console\Commands\SignalsExampleCommand;
use App\Console\Commands\TradingPairsBinanceCommand;
use App\Console\Commands\TestMACDStrategiesCommand;
use App\Console\Commands\CheckBalanceCommand;
use App\Console\Commands\CheckOrderStatusCommand;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        MACDRule::class,
        ScheduleList::class,
        DataRunnerBinanceCommand::class,
        DataRunnerCoinigyCommand::class,
        ShortStrategiesCommand::class,
        SignalsExampleCommand::class,
        TradingPairsBinanceCommand::class,
        TestShortStrategiesCommand::class,
        TestMACDStrategiesCommand::class,
        CheckBalanceCommand::class,
        CheckOrderStatusCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        //$schedule->command('MACD:PlaceOrder')->cron('*/30 * * * * *');                 
        //$schedule->command('autobot:datarunner_coinigy')->withoutOverlapping()->everyMinute();
        
        $schedule->command('autobot:tradingpairs_binance')->withoutOverlapping()->daily();        
        $schedule->command('autobot:datarunner_binance')->withoutOverlapping()->everyMinute();  
        $schedule->command('autobot:checkbalance')->withoutOverlapping()->everyMinute();
        $schedule->command('autobot:checkorderstatus')->withoutOverlapping()->everyMinute();
		
//		$filePath = storage_path(date('Y-m-d'));
//        $schedule->command('autobot:testmacd_strategies')->withoutOverlapping()->appendOutputTo($filePath)->everyMinute();
        
//        $schedule->command('autobot:testmacd_strategies')->withoutOverlapping()->everyTenMinutes();
         
//        $schedule->command('autobot:datarunner_binance')->withoutOverlapping()->everyMinute();
//        $schedule->command('autobot:short_strategies')->withoutOverlapping()->everyMinute();
//        $schedule->command('autobot:testshort_strategies')->withoutOverlapping()->everyMinute();
//        $schedule->command('autobot:testmacd_strategies')->withoutOverlapping()->everyMinute();
    }
}