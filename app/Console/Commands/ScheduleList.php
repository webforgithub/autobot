<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleList extends Command
{
    protected $signature = 'schedule:list';
    protected $description = 'List when scheduled commands are executed.';

    /**
     * @var Schedule
     */
    protected $schedule;

    /**
     * ScheduleList constructor.
     *
     * @param Schedule $schedule
     */
    public function __construct(Schedule $schedule)
    {
        parent::__construct();

        $this->schedule = $schedule;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $events = array_map(function ($event) {
            return [
                'cron' => $event->expression,
                'command' => static::fixupCommand($event->command),
            ];
        }, $this->schedule->events());

        $this->table(
            ['Cron', 'Command'],
            $events
        );
    }

    /**
     * If it's an artisan command, strip off the PHP
     *
     * @param $command
     * @return string
     */
    protected static function fixupCommand($command)
    {
        $parts = explode(' ', $command);
        if (count($parts) > 2 && $parts[1] === "'artisan'") {
            array_shift($parts);
        }

        return implode(' ', $parts);
    }
}