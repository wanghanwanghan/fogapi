<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Config;

class CheckRequestToken
{
    public function handle($request, Closure $next)
    {
        $verifyToken=trim($request->verifyToken);

        if ($verifyToken=='' || $verifyToken==null || empty($verifyToken))
        {
            if (Carbon::now()->format('Y') > 2020) return response()->json(['resCode'=>Config::get('resCode.626')]);
        }

        //验证token








        return $next($request);
    }
}
