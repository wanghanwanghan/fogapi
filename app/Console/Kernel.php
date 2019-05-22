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

        //延时更新用户头像
        $schedule->command('Grid:ChangeAvatar')->everyMinute()->withoutOverlapping();

        //排行榜统计
        $schedule->command('Grid:RankList')->everyFiveMinutes()->withoutOverlapping();

        //n天不交易的格子自动降价m%
        $schedule->command('Grid:ReducePrice')->cron('30 1 * * *')->withoutOverlapping();

        //后台admin的控制面板，计算cpu，内存，硬盘占用
        $schedule->command('Admin:ServerInfo')->everyMinute()->withoutOverlapping();

        //后台admin的控制面板，统计用户分布情况
        $schedule->command('Admin:UserDistribution')->daily()->withoutOverlapping();

        //后台发的系统通知
        $schedule->command('Admin:SystemMessage')->everyMinute()->withoutOverlapping();
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
