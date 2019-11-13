<?php

namespace App\Http\Middleware;

use App\Http\Controllers\QuanMinZhanLing\SecurityController;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        //自己玩的
//        $url='http://api.guaqb.cn/v1/onesaid/';
//
//        try
//        {
//            $text=trim((string)file_get_contents($url));
//
//            $md5=md5($text);
//
//            $sql="select * from oneSaid where md5Index='{$md5}'";
//
//            $res=DB::connection('masterDB')->select($sql);
//
//            if (empty($res))
//            {
//                $time=Carbon::now()->format('Y-m-d H:i:s');
//
//                $sql="insert into oneSaid values (null,'{$md5}','{$text}','{$time}','{$time}')";
//
//                DB::connection('masterDB')->insert($sql);
//            }
//
//        }catch (\Exception $e)
//        {
//
//        }

        //收集男女头像
        $tar=array_random(['男','女','动漫男','动漫女']);
        $url="https://api.uomg.com/api/rand.avatar?sort={$tar}&format=json";

        try
        {
            $text=jsonDecode(trim((string)file_get_contents($url)));

            $imgurl=$text['imgurl'];

            $md5=md5($imgurl);

            $sql="select * from oneAvatar where md5Index='{$md5}'";

            $res=DB::connection('masterDB')->select($sql);

            if (empty($res))
            {
                $sql="insert into oneAvatar values (null,'{$tar}','{$md5}','{$imgurl}')";

                DB::connection('masterDB')->insert($sql);
            }

        }catch (\Exception $e)
        {

        }

        return $next($request);
    }
}
