<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class init
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

        if (!useRequestToken($request->uid))
        {
            return response()->json(['resCode'=>Config::get('resCode.600')]);
        }

        return $next($request);
    }
}
