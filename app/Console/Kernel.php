<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //* * * * * /usr/local/php/bin/php /root/project/api.com/artisan schedule:run >> /dev/null 2>&1

        //买格子后的日志，记录到数据库
        $schedule->command('Grid:TradeInfo')->everyMinute()->withoutOverlapping();

        //延时统计用户成就
        $schedule->command('Grid:Achievement')->everyFiveMinutes()->withoutOverlapping();



    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
