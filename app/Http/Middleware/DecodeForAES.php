<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class DecodeForAES
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
        if (!($request->uid!='' && is_numeric($request->uid)))
        {
            return response()->json(['resCode'=>Config::get('resCode.601')]);
        }

        if ($request->secret=='' || !decodeForAES($request->uid,$request->secret))
        {
            return response()->json(['resCode'=>Config::get('resCode.622')]);
        }

        return $next($request);
    }
}
