<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'masterDB'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'tssj_old' => [
            'driver' => 'mysql',
            'host' => '183.136.232.214',
            'port' => '3306',
            'database' => 'tssj',
            'username' => 'chinabody',
            'password' => 'chinaiiss(!@#)',
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
            'strict' => false,
            'engine' => null,
        ],

        'masterDB' => [
            'driver' => 'mysql',
            'host' => '183.136.232.236',
            'port' => '3306',
            'database' => 'grid_game',
            'username' => 'chinaiiss',
            'password' => 'chinaiiss',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'gridTradeInfoDB' => [
            'driver' => 'mysql',
            'host' => '183.136.232.236',
            'port' => '3306',
            'database' => 'grid_trade',
            'username' => 'chinaiiss',
            'password' => 'chinaiiss',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],
        //=============================================================================================================================
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => 'predis',

        'default' => [
            'host' => '183.136.232.236',
            'password' => 'wanghan123',
            'port' => '6379',
            'database' => 0,
        ],

        //session
        'Session' => [
            'host' => '183.136.232.236',
            'password' => 'wanghan123',
            'port' => '6379',
            'database' => 0,
        ],








        //log相关键值或队列，排行榜，更换头像
        'WriteLog' => [
            'host' => '183.136.232.236',
            'password' => 'wanghan123',
            'port' => '6379',
            'database' => 11,
        ],

        //存用户的金币（hash），购地卡数量
        'UserInfo' => [
            'host' => '183.136.232.236',
            'password' => 'wanghan123',
            'port' => '6379',
            'database' => 12,
        ],

        //每个格子当天交易次数，格子头像缓存（hash）
        'GridInfo' => [
            'host' => '183.136.232.236',
            'password' => 'wanghan123',
            'port' => '6379',
            'database' => 13,
        ],

        //每日签到
        'SignIn' => [
            'host' => '183.136.232.236',
            'password' => 'wanghan123',
            'port' => '6379',
            'database' => 14,
        ],

        //防止暴力请求
        'RequestToken' => [
            'host' => '183.136.232.236',
            'password' => 'wanghan123',
            'port' => '6379',
            'database' => 15,
        ],

        //4月25日剩余18g
    ],

];
