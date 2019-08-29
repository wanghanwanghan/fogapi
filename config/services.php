<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    //facebook, github, google, linkedin, outlook, weibo, taobao, qq, wechat, douyin, and douban.

    'wechat' => [
        'client_id'     => 'your-app-id',
        'client_secret' => 'your-app-secret',
        'redirect'      => 'http://localhost/socialite/callback.php',
    ],

    'github' => [
        'client_id'     => 'ab38f215ea4f7bbafce4',
        'client_secret' => '35886c6a008155014603432a7eb0e7c93b2eb15e',
        'redirect'      => 'http://n2.iissbbs.com',
    ],

];
