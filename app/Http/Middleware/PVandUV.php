<?php

namespace App\Http\Middleware;

use App\Http\Controllers\QuanMinZhanLing\SecurityController;
use Closure;

class PVandUV
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
        $count=new SecurityController();

        $count->recodePV();
        $count->recodeUV($request);

        return $next($request);
    }
}
