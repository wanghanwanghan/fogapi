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
        //检查session中的adminLastLogin
        $time=(int)session()->get('adminLastLogin');

        if (time() - $time > 3600 * 10)
        {
            //超过10小时重新登陆
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
