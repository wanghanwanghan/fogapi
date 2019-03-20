<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

//去掉特殊字符
function filter1($str)
{
    $arr=[
        '~','`','!',
        '@','#','$',
        '%','^','&',
        '*','(',')',
        '-','_','+',
        '=','[',']',
        '{','}','\\',
        ';',':','"',
        '\'','|','<',
        '>',',','.',
        '/','?',' '
    ];

    return str_replace($arr,'',$str);
}

//只含有26个字母或者数字的并且都是半角的字符串，转换成数字
function string2Number($str)
{
    $j=0;

    for($i=0;$i<strlen($str);$i++)
    {
        if (is_numeric($str[$i]))
        {
            $j+=$str[$i];
        }else
        {
            $j+=ord($str[$i]);
        }
    }

    return $j;
}

//调用百度翻译
function baiduTranslate($q,$from='auto',$to='en')
{
    $url=\Illuminate\Support\Facades\Config::get('myDefine.BaiduUrl');

    $appid=\Illuminate\Support\Facades\Config::get('myDefine.BaiduAppId');

    $key=\Illuminate\Support\Facades\Config::get('myDefine.BaiduKey');

    $salt=time().mt_rand(0,10000);

    //账号index
    $i=0;
    $appid=$appid[$i];
    $key=$key[$i];

    //appid+q+salt+key的顺序拼接得到字符串1。
    //对字符串1做md5得到32位小写的sign
    $sign=md5($appid.$q.$salt.$key);

    $fullUrl=$url.'?'.'q='.$q.'&'.'from='.$from.'&'.'to='.$to.'&'.'appid='.$appid.'&'.'salt='.$salt.'&'.'sign='.$sign;

    $res=file_get_contents($fullUrl);

    $res=\json_decode($res,true);

    if (isset($res['trans_result']))
    {
        $res=current($res['trans_result']);

        if (isset($res['dst']))
        {
            return $res['dst'];
        }
    }

    return false;
}

//调用高德经纬度查询
function amapSelect($lng,$lat)
{
    $lng=\sprintf("%.4f",$lng);

    $lat=\sprintf("%.4f",$lat);

    $url=\Illuminate\Support\Facades\Config::get('myDefine.AmapUrl');

    $key=\Illuminate\Support\Facades\Config::get('myDefine.AmapKey');

    $key=array_random($key);

    $fullUrl=$url.'?'.'key='.$key.'&location='.$lng.','.$lat;

    $res=file_get_contents($fullUrl);

    $res=\json_decode($res,true);

    if (isset($res['regeocode']['addressComponent']) && !empty($res['regeocode']['addressComponent']) && is_array($res['regeocode']['addressComponent']))
    {
        $arr=$res['regeocode']['addressComponent'];

        $country =isset($arr['country']) ?$arr['country'] :'';
        $province=isset($arr['province'])?$arr['province']:'';
        $city    =isset($arr['city'])    ?$arr['city']    :'';
        $district=isset($arr['district'])?$arr['district']:'';

        if ($country=='中国')
        {
            $country='China';

            //根据省份拿到省份编码
            $ProvinceCode=\Illuminate\Support\Facades\Config::get('myDefine.ProvinceCode');

            $province=filter1($province);

            if (in_array($province,$ProvinceCode))
            {
                $code=array_search($province,$ProvinceCode);
            }else
            {
                $country='Unknown';
                $code=[];
            }

            $province=$code;

        }else
        {
            $country=filter1($country);

            if (empty($country)) $country='Unknown';

            if (($country_name=\Illuminate\Support\Facades\Redis::get('CountryName'))!='')
            {
                $country_name=\json_decode($country_name,true);

                if (!in_array($country,$country_name))
                {
                    array_push($country_name,$country);

                    $encode=\json_encode($country_name);

                    \Illuminate\Support\Facades\Redis::set('CountryName',$encode);
                }

            }else
            {
                $encode=\json_encode([$country]);

                \Illuminate\Support\Facades\Redis::set('CountryName',$encode);
            }
        }

        $tmp['country']=$country;
        $tmp['province']=filter1($province);
        $tmp['city']=filter1($city);
        $tmp['district']=filter1($district);

        return $tmp;

    }else
    {
        return false;
    }
}

//调用腾讯经纬度查询
function tencentSelect($lng,$lat)
{

}

//数据入库
function insertGeohash($Geo,$lng,$lat,$data)
{
    //第一种情况：经纬度在中国，并且属于某个省
    //第二种情况：经纬度在外国
    //第三种情况：经纬度不属于任何国家

    //情况一：
    //array:4 [
    //  "country" => "China"
    //  "province" => "11"
    //  "city" => []
    //  "district" => "海淀区"
    //]
    if ($data['country']=='China' && is_numeric($data['province']))
    {
        if (!Schema::connection('tssj_new_2019')->hasTable($data['country'].'_'.$data['province'].'_geohash'))
        {
            Schema::connection('tssj_new_2019')->create($data['country'].'_'.$data['province'].'_geohash', function (Blueprint $table)
            {
                $table->increments('id')->unsigned();
                $table->string('geohash','10')->index();
                $table->string('city','100');
                $table->string('district','100');
                $table->engine='InnoDB';
            });
        }

        $arr['geohash']=$Geo->encode($lat,$lng,'9');
        $arr['city']=empty($data['city'])?'':$data['city'];
        $arr['district']=empty($data['district'])?'':$data['district'];

        $tmp=DB::connection('tssj_new_2019')->table($data['country'].'_'.$data['province'].'_geohash')->where('geohash',$arr['geohash'])->first();

        if ($tmp==null)
        {
            $tmp=DB::connection('tssj_new_2019')->table($data['country'].'_'.$data['province'].'_geohash')->insert($arr);

            return $tmp;

        }else
        {
            return true;
        }
    }

    //情况二：
    //array:4 [
    //  "country" => "Australia"
    //  "province" => "NewSouthWales"
    //  "city" => "Chatswood"
    //  "district" => []
    //]
    if ($data['country']!='China' && $data['country']!='Unknown')
    {
        if (!Schema::connection('tssj_new_2019')->hasTable($data['country'].'_geohash'))
        {
            Schema::connection('tssj_new_2019')->create($data['country'].'_geohash', function (Blueprint $table)
            {
                $table->increments('id')->unsigned();
                $table->string('geohash','10')->index();
                $table->string('province','100');
                $table->string('city','100');
                $table->string('district','100');
                $table->engine='InnoDB';
            });
        }

        $arr['geohash']=$Geo->encode($lat,$lng,'9');
        $arr['province']=empty($data['province'])?'':$data['province'];
        $arr['city']=empty($data['city'])?'':$data['city'];
        $arr['district']=empty($data['district'])?'':$data['district'];

        $tmp=DB::connection('tssj_new_2019')->table($data['country'].'_geohash')->where('geohash',$arr['geohash'])->first();

        if ($tmp==null)
        {
            $tmp=DB::connection('tssj_new_2019')->table($data['country'].'_geohash')->insert($arr);

            return $tmp;

        }else
        {
            return true;
        }
    }

    //情况三：
    //array:4 [
    //  "country" => "Unknown"
    //  "province" => []
    //  "city" => []
    //  "district" => []
    //]
    if ($data['country']=='Unknown')
    {
        //这块总共分4张表 首先分南北半球 其次%2再分一次
        //根据$lat分表 赤道归南半球
        $num=intval($lat);//直接取整 舍去小数

        //大于零表示在北半球
        if ($num>0)
        {
            $name='north_'.$num%2;
        }else
        {
            $name='south_'.$num%2*-1;
        }

        if (!Schema::connection('tssj_new_2019')->hasTable('SeaArea_'.$name.'_geohash'))
        {
            Schema::connection('tssj_new_2019')->create('SeaArea_'.$name.'_geohash', function (Blueprint $table)
            {
                $table->increments('id')->unsigned();
                $table->string('geohash','10')->index();
                $table->engine='InnoDB';
            });
        }

        $arr['geohash']=$Geo->encode($lat,$lng,'9');

        $tmp=DB::connection('tssj_new_2019')->table('SeaArea_'.$name.'_geohash')->where('geohash',$arr['geohash'])->first();

        if ($tmp==null)
        {
            $tmp=DB::connection('tssj_new_2019')->table('SeaArea_'.$name.'_geohash')->insert($arr);

            return $tmp;

        }else
        {
            return true;
        }
    }

    return false;
}
