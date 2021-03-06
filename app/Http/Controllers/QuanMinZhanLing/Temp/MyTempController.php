<?php

namespace App\Http\Controllers\QuanMinZhanLing\Temp;

use App\Exports\ArticleExport;
use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\QuanMinZhanLing\FoodMap\FoodMapBaseController;
use App\Http\Controllers\QuanMinZhanLing\FoodMap\FoodMapUserController;
use App\Http\Controllers\Server\CacheByFile;
use App\Http\Controllers\Server\ModifyPinyin;
use App\Http\Controllers\Server\PayBase;
use App\Model\DailyTasksModel;
use App\Model\FoodMap\Patch;
use App\Model\GridModel;
use Carbon\Carbon;
use DfaFilter\SensitiveHelper;
use Geohash\GeoHash;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Overtrue\Pinyin\Pinyin;
use Qiniu\Auth;
use Qiniu\Sms\Sms;
use Vinkla\Hashids\Facades\Hashids;

class MyTempController extends BaseController
{
    public function test(Request $request)
    {
        // Redis::connection('TrackUserInfo')->hset('Track_10083','VipInfo',jsonEncode(['level'=>3,'expire'=>1885507851]));
        // Redis::connection('TrackUserInfo')->hset('Track_28109','VipInfo',jsonEncode(['level'=>3,'expire'=>1885507851]));

        // Redis::connection('WriteLog')->zincrby('DiamondRankListForTssj',3400+188,97105);

        dd(123);

        Redis::connection('UserInfo')->hset(26074,'name','周子蕊');
        Redis::connection('UserInfo')->hset(26078,'name','豆豆卉');
        Redis::connection('UserInfo')->hset(26079,'name','海天黄豆酱');

        $key='DiamondRankListForTssj';

        $res=Redis::connection('WriteLog')->zadd($key,108264+2400+13788,26074);
        $res=Redis::connection('WriteLog')->zadd($key,108264+2400+6788,26078);
        $res=Redis::connection('WriteLog')->zadd($key,108264+2400+3588,26079);

        dd($res);






        $res=numToWord(123456789);

        dd($res);


















        $a=Carbon::now()->format('i');

        //uid加00-23
        $res=Hashids::encode(1,22222222);












        dd($res,$a);


















        dd(123);






        $arr=[
            '北京'=>'长城',
            '四川'=>'九寨沟',
            '云南'=>'丽江古城',
            '上海'=>'陆家嘴',
            '浙江'=>'乌镇',
        ];

        $quality='蓝';

        $place=['A','B','C','D'];
        $belongType='地方景点';


        foreach ($arr as $k=>$v)
        {
            $subject=$v;
            $belongCity=$k;

            foreach ($place as $one)
            {
                Patch::create([
                    'subject'=>$subject.$one,
                    'place'=>$one,
                    'quality'=>$quality,
                    'belongType'=>$belongType,
                    'belongCity'=>$belongCity
                ]);
            }
        }



        dd($subject,$quality);











        $uid=18426;

        //买格总花费
        $key='BuyGridPayMoneyTotal_'.Carbon::now()->format('Ymd');

        $paymoney=0;
        for ($i=0;$i<=100;$i++)
        {
            $mouth=Carbon::now()->subMonths($i)->format('Ym');

            if ($mouth < 201905) break;

            $res=DB::connection('masterDB')->table('buy_sale_info_'.$mouth)
                ->where('uid',$uid)
                ->select(DB::connection('masterDB')->raw('sum(paymoney) as paymoney'))->get();

            $tmp=(int)current($res)[0]->paymoney;

            $paymoney+=$tmp;
        }

        //加入集合
        Redis::connection('WriteLog')->zadd($key,$paymoney,$uid);

        //取得前200
        $limit200=Redis::connection('WriteLog')->zrevrange($key,0,199,'withscores');

        //我的排名
        $myRank=Redis::connection('WriteLog')->zrevrank($key,$uid)+1;


        dd($limit200,$myRank);


        $tar=array_random(['男','女','动漫男','动漫女']);
        $url="https://api.uomg.com/api/rand.avatar?sort={$tar}&format=json";

        $text=jsonDecode(trim((string)file_get_contents($url)));

        dd($text);

        $imgurl=$text['imgurl'];

        $md5=md5($imgurl);

        $sql="select * from oneAvatar where md5Index='{$md5}'";

        $res=DB::connection('masterDB')->select($sql);

        if (empty($res))
        {
            $sql="insert into oneAvatar values (null,'{$tar}','{$md5}','{$imgurl}')";

            DB::connection('masterDB')->insert($sql);
        }

        dd('lightMap：'.Carbon::now()->format('Y-m-d H:i:s'));

        $uid=70893;
        $productId=5;

        $res['level']=1;
        $res['expire']=Carbon::now()->addDays(31)->timestamp;

        Redis::connection('TrackUserInfo')->hset('Track_'.$uid,'VipInfo',jsonEncode($res));

        $expire=0;

        $res=$expire ? date('Y-m-d',$expire) : 0;






        dd($res);



        for ($i=1;$i<=30;$i++)
        {
            //dump(SnowflakeFacade::nextId());
            dump(randomUUID());
        }

        dd('wancheng');


        $obj=new GeoHash();

        $res10=$obj->encode('35.9262980000','105.3660250000','10');
        $res11=$obj->encode('35.9262980000','105.3660250000','11');
        $res12=$obj->encode('35.9262980000','105.3660250000','12');









        dd($res10,$res11,$res12);




        $wordData=[
            '傻逼',
        ];

        $handle=SensitiveHelper::init()->setTree($wordData);

        $content='傻哈逼';

        //检测是否含有敏感词
        $a=$handle->islegal($content);

        //敏感词替换为*为例（会替换为相同字符长度的*）
        $b=$handle->replace($content,'*',true);

        //或敏感词替换为***为例
        $c=$handle->replace($content,'***');

        //获取内容中所有的敏感词
        $d=$handle->getBadWord($content);

        //仅且获取一个敏感词
        $e=$handle->getBadWord($content,1);




        dd($a,$b,$c);




        dd(randomUserName());


        return Excel::download(new ArticleExport([]),'xxx.xlsx');




        $ak="PPlFNlpidaN6rrcRcgnLAKX2NC1EXSq98smv72XQ";
        $sk="QHwYaLC8XtB6IZ9o3K8fsCj8B4EMaYAd4KmkM8JI";
        $auth =new Auth($ak,$sk);
        $client=new Sms($auth);

        $template_id="1182207669239291904";
        $mobiles=['18618457910','15210929119','18511936093','15011449324'];

        $code=$this->GetRandStr(4);

        try
        {
            //发送短信
            $resp=$client->sendMessage($template_id,$mobiles,['code'=>$code]);

            dd($resp);

        }catch(\Exception $e)
        {
            echo "Error:", $e, "\n";
        }

        return view('inMap');
    }

    private function GetRandStr($len)
    {
        $chars=["0","1","2","3","4","5","6","7","8","9"];

        $charsLen=count($chars)-1;

        shuffle($chars);

        $output="";

        for ($i=0;$i<$len;$i++)
        {
            $output.=$chars[mt_rand(0,$charsLen)];
        }

        return $output;
    }

    //where is bk
    public function whereIsBk(Request $request)
    {
        $ymd=Carbon::now()->format('Ymd');

        if ($request->id=='ios') $key="AccordingToUidUploadLatLng_18426_{$ymd}";
        if ($request->id=='android') $key="AccordingToUidUploadLatLng_30209_{$ymd}";

        //从第0开始，到第0结束，取1个
        $limit1=Redis::connection('default')->zrevrange($key,0,0,'withscores');

        //是空说明今天一个经纬度都没有
        if (empty($limit1)) $limit1=["oQh4KmG_75VtYYqb" => Carbon::now()->startOfDay()->timestamp];

        foreach ($limit1 as $k=>$v)
        {
            $hashString=$k;
            $update_at=date('Y-m-d H:i:s',$v);
        }

        $res['lat']=latlngStrTolatlng($hashString)['lat'];
        $res['lng']=latlngStrTolatlng($hashString)['lng'];

        if ($request->id=='ios')
        {
            //谷歌坐标转高德坐标
            $url='https://restapi.amap.com/v3/assistant/coordinate/convert?';
            $url.='key=b976002b11601f21e73fc2ded6a34d0e&';
            $url.="locations={$res['lng']},{$res['lat']}&";
            $url.="coordsys=gps";

            try
            {
                $tmp=file_get_contents($url);
                $tmp=jsonDecode($tmp);
                $tmp=explode(',',$tmp['locations']);

                $res['lat']=$tmp[1];
                $res['lng']=$tmp[0];

            }catch (\Exception $e)
            {

            }
        }

        $res['update_at']=$update_at;
        $res['time']=date('Y-m-d H:i:s',time());;

        //汉太华高德地图经纬度
        $cc=Config::get('myDefine.CompanyCoordinate');
        $res['gongsiLat']=$cc['lat'];
        $res['gongsiLng']=$cc['lng'];

        if (isMobile()) return view('whereisbk')->with(['info'=>$res]);

        return abort(404);
    }





}
