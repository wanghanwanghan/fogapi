<?php

namespace App\Http\Middleware;

use Closure;

class AdminLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#',$request->getClientIp()))
        {
            return '无权查看';
        }

        $ipArray=explode('.',$request->getClientIp());

        //去掉最后
        array_pop($ipArray);

        $ip=implode('.',$ipArray);

        //ip白名单，只取前三段
        $whiteList=[
            '127.0.0',
            '221.216.228',
        ];

        if (!in_array($ip,$whiteList))
        {
            return '无权查看';
        }

        return $next($request);
    }
}
