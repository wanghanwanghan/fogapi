<?php

namespace App\Http\Middleware;

use App\Model\FoodMap\UserSuccess;
use Closure;

class AfterControllerMiddleware
{
    //用户进入宝物页，展示所有没弹出的宝物后，把是否已经展示修改成1
    //后置中间件
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $uid=trim($request->uid);

        UserSuccess::where(['uid'=>$uid,'isShow'=>0])->update(['isShow'=>1]);

        return $response;
    }

    public function terminate($request, $response)
    {
        //善后中间件
        //响应到达用户后做的事情
    }
}
