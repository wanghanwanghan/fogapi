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
        //* * * * * /usr/bin/php /your/projectPath/artisan schedule:run >> /dev/null 2>&1

        //买格子后的日志，记录到数据库
        $schedule->command('Grid:TradeInfo')->everyMinute()->withoutOverlapping();

        //延时统计用户成就
        $schedule->command('Grid:Achievement')->everyFiveMinutes()->withoutOverlapping();

        //延时更新用户头像
        $schedule->command('Grid:ChangeAvatar')->everyMinute()->withoutOverlapping();

        //排行榜统计
        $schedule->command('Grid:RankList')->everyFiveMinutes()->withoutOverlapping();

        //n天不交易的格子自动降价m%
        $schedule->command('Grid:ReducePrice')->cron('30 2 * * *')->withoutOverlapping();




        //后台发的系统通知
        $schedule->command('Admin:SystemMessage')->everyMinute()->withoutOverlapping();

        //后台admin的控制面板，计算cpu，内存，硬盘占用
        $schedule->command('Admin:ServerInfoNew')->everyMinute()->withoutOverlapping();

        //后台admin的控制面板，统计用户分布情况
        $schedule->command('Admin:UserDistribution')->daily()->withoutOverlapping();

        //后台的数据统计
        $schedule->command('Admin:userData1')->everyThirtyMinutes()->withoutOverlapping();
        $schedule->command('Admin:gridData1')->everyThirtyMinutes()->withoutOverlapping();



        //处理探索世界迷雾
        $schedule->command('Tssj:FogUpload0')->everyMinute()->withoutOverlapping();
        $schedule->command('Tssj:FogUpload1')->everyMinute()->withoutOverlapping();
        $schedule->command('Tssj:FogUpload2')->everyMinute()->withoutOverlapping();
        $schedule->command('Tssj:FogUpload3')->everyMinute()->withoutOverlapping();
        $schedule->command('Tssj:FogUpload4')->everyMinute()->withoutOverlapping();
        $schedule->command('Tssj:FogUpload5')->everyMinute()->withoutOverlapping();
        $schedule->command('Tssj:FogUpload6')->everyMinute()->withoutOverlapping();
        $schedule->command('Tssj:FogUpload7')->everyMinute()->withoutOverlapping();
        $schedule->command('Tssj:FogUpload8')->everyMinute()->withoutOverlapping();
        $schedule->command('Tssj:FogUpload9')->everyMinute()->withoutOverlapping();

        //处理我的路迷雾
        $schedule->command('Wodelu:FogUpload0')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:FogUpload1')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:FogUpload2')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:FogUpload3')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:FogUpload4')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:FogUpload5')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:FogUpload6')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:FogUpload7')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:FogUpload8')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:FogUpload9')->everyMinute()->withoutOverlapping();

        //处理我的路足迹
        $schedule->command('Wodelu:TrackFogUploadForZUJI0')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:TrackFogUploadForZUJI1')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:TrackFogUploadForZUJI2')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:TrackFogUploadForZUJI3')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:TrackFogUploadForZUJI4')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:TrackFogUploadForZUJI5')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:TrackFogUploadForZUJI6')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:TrackFogUploadForZUJI7')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:TrackFogUploadForZUJI8')->everyMinute()->withoutOverlapping();
        $schedule->command('Wodelu:TrackFogUploadForZUJI9')->everyMinute()->withoutOverlapping();

        //计算昨日繁荣度 联盟的
        $schedule->command('Aliance:Flourish')->cron('1 0 * * *')->withoutOverlapping();
        //计算昨日繁荣度 用户的
        $schedule->command('Aliance:FlourishForUser')->cron('1 0 * * *')->withoutOverlapping();

        //每月第一天计算联盟战绩
        $schedule->command('Aliance:PKinfo')->cron('30 0 1 */1 *')->withoutOverlapping();



        //每分钟刷拍卖行物品，过期的返回给用户
        $schedule->command('FoodMap:AuctionHouse')->everyMinute()->withoutOverlapping();




        $schedule->command('Tssj:OneJoke')->everyFifteenMinutes()->withoutOverlapping();
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
