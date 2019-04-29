<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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

//过滤铭感词
function filter2($filterWord,$str)
{
    //黑名单词汇
    //$filterWord=['我','爱','拉芳'];

    //需要过滤的句子
    //$str='我爱拉芳';

    $tmp=array_combine($filterWord,array_fill(0,count($filterWord),'*'));

    return strtr($str,$tmp);
}

//只替换一次
function filter3($needle,$replace,$haystack)
{
    //needle in a haystack "草堆寻针" 是个英文俗语 相当于中文的 "大海捞针"

    $pos = strpos($haystack,$needle);

    if ($pos === false)
    {
        return $haystack;
    }

    return substr_replace($haystack,$replace,$pos,strlen($needle));
}

//二维数组按照某一列排序
function arraySort1($array,$cond=['desc','id'])
{
    //$array=[
    //    ['name'=>'张1','age'=>'23'],
    //    ['name'=>'李2','age'=>'64'],
    //    ['name'=>'王3','age'=>'55'],
    //    ['name'=>'赵4','age'=>'66'],
    //    ['name'=>'孙5','age'=>'17']
    //];

    //SORT_DESC降序，SORT_ASC升序，age排序字段
    //$sort=['asc/desc','age'];

    if ($cond[0]=='asc')
    {
        $cond[0]='SORT_ASC';

    }else
    {
        $cond[0]='SORT_DESC';
    }

    $sort=['Rule'=>$cond[0],'SortKey'=>$cond[1]];

    $arrSort=[];

    foreach($array as $uniqid=>$row)
    {
        foreach($row as $key=>$value)
        {
            $arrSort[$key][$uniqid]=$value;
        }
    }

    array_multisort($arrSort[$sort['SortKey']],constant($sort['Rule']),$array);

    return $array;
}

//快速排序
function arraySort2($array)
{
    if (count($array)<=1) return $array;

    $key=$array[0];

    $left_arr=[];
    $right_arr=[];

    for ($i=1;$i<count($array);$i++)
    {
        if ($array[$i]<=$key)
        {
            $left_arr[]=$array[$i];
        }
        else
        {
            $right_arr[]=$array[$i];
        }
    }

    $left_arr=arraySort2($left_arr);
    $right_arr=arraySort2($right_arr);

    return array_merge($left_arr,[$key],$right_arr);
}

//为字符串的指定位置添加指定字符
function mbSubstrReplace($string, $replacement,$start,$length=NULL)
{
    if (is_array($string))
    {
        $num = count($string);
        // $replacement
        $replacement = is_array($replacement) ? array_slice($replacement, 0, $num) : array_pad(array($replacement), $num, $replacement);
        // $start
        if (is_array($start))
        {
            $start = array_slice($start, 0, $num);
            foreach ($start as $key => $value) $start[$key] = is_int($value) ? $value : 0;
        }else
        {
            $start = array_pad(array($start), $num, $start);
        }
        // $length
        if (!isset($length))
        {
            $length = array_fill(0, $num, 0);
        }elseif (is_array($length))
        {
            $length = array_slice($length, 0, $num);
            foreach ($length as $key => $value) $length[$key] = isset($value) ? (is_int($value) ? $value : $num) : 0;
        }else
        {
            $length = array_pad(array($length), $num, $length);
        }
        // Recursive call
        return array_map(__FUNCTION__, $string, $replacement, $start, $length);
    }
    preg_match_all('/./us', (string)$string, $smatches);
    preg_match_all('/./us', (string)$replacement, $rmatches);
    if ($length === NULL) $length = mb_strlen($string);
    array_splice($smatches[0], $start, $length, $rmatches[0]);
    return join($smatches[0]);
}
//为字符串的指定位置添加指定字符
function insertSomething(&$str, array $offset, $delimiter = '-')
{
    foreach ($offset as $i=>$v)
    {
        $str=mbSubstrReplace($str,$delimiter,$i+$v,0);
    }
    return $str;
}

//ip地址查询
function addressForIP($ip)
{
    $res_json=file_get_contents('http://apis.juhe.cn/ip/ip2addr?ip='.$ip.'&dtype=json&key=ffb7c65113fddc659264139050eaccf2');

    $res_arry=json_decode($res_json,true);

    if ($res_arry['error_code']!='0' || $res_arry['resultcode']!='200')
    {
        return ['area'=>'查询失败','location'=>'loading...'];
    }else
    {
        return $res_arry['result'];
    }
}

//修改一维或多维数组的键名，参数一：需要修改的数组，参数二：['从什么'=>'改成什么']
function changeArrKey($arr,$example)
{
    $res = [];

    foreach ($arr as $key => $value)
    {
        if (is_array($value))
        {
            if (array_key_exists($key,$example))
            {
                $key = $example[$key];
            }

            $res[$key] = changeArrKey($value,$example);

        }else
        {
            if (array_key_exists($key,$example))
            {
                $res[$example[$key]] = $value;

            }else
            {
                $res[$key] = $value;
            }
        }
    }

    return $res;
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
function amapSelect($lng,$lat,$flood=4)
{
    $lng=\sprintf("%.{$flood}f",$lng);

    $lat=\sprintf("%.{$flood}f",$lat);

    $url=\Illuminate\Support\Facades\Config::get('myDefine.AmapUrl');

    $key=\Illuminate\Support\Facades\Config::get('myDefine.AmapKey');

    $key=array_random($key);

    $fullUrl=$url.'?'.'key='.$key.'&location='.$lng.','.$lat;

    try
    {
        $res=\file_get_contents($fullUrl);

    }catch (\Exception $e)
    {
        sleep(5);

        return false;
    }

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

//坐标数据入库
function insertGeohash($Geo,$lng,$lat,$data)
{
    $connection='tssj_new_2019';
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
        if (!Schema::connection($connection)->hasTable($data['country'].'_'.$data['province'].'_geohash'))
        {
            Schema::connection($connection)->create($data['country'].'_'.$data['province'].'_geohash', function (Blueprint $table)
            {
                $table->string('geohash','10')->unique();
                $table->string('city','100');
                $table->string('district','100');
                $table->engine='InnoDB';
            });
        }

        $arr['geohash']=$Geo->encode($lat,$lng,'9');
        $arr['city']=empty($data['city'])?'':$data['city'];
        $arr['district']=empty($data['district'])?'':$data['district'];

        //直接insert ignore
        return [$data['country'].'_'.$data['province'].'_geohash',"'{$arr['geohash']}','{$arr['city']}','{$arr['district']}'"];
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
        if (!Schema::connection($connection)->hasTable($data['country'].'_geohash'))
        {
            Schema::connection($connection)->create($data['country'].'_geohash', function (Blueprint $table)
            {
                $table->string('geohash','10')->unique();
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

        //直接insert ignore
        return [$data['country'].'_geohash',"'{$arr['geohash']}','{$arr['province']}','{$arr['city']}','{$arr['district']}'"];
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

        if (!Schema::connection($connection)->hasTable('SeaArea_'.$name.'_geohash'))
        {
            Schema::connection($connection)->create('SeaArea_'.$name.'_geohash', function (Blueprint $table)
            {
                $table->string('geohash','10')->unique();
                $table->engine='InnoDB';
            });
        }

        $arr['geohash']=$Geo->encode($lat,$lng,'9');

        //直接insert ignore
        return ['SeaArea_'.$name.'_geohash',"'{$arr['geohash']}'"];
    }

    return false;
}

//坐标关联用户数据入库
function insertUserGeo($geohash,$userid,$dateline)
{
    $tableNum=choseTable($userid);

    $tableName='UserGeohash_'.$tableNum;

    $connection='tssj_new_2019';

    if (!Schema::connection($connection)->hasTable($tableName))
    {
        Schema::connection($connection)->create($tableName, function (Blueprint $table)
        {
            $table->integer('userid')->unsigned();
            $table->string('geohash','10');
            $table->string('dateline','12');
            $table->unique(['userid','geohash']);
            $table->engine='InnoDB';
        });
    }

    //直接insert ignore
    $arr=[
        'userid'=>$userid,
        'geohash'=>$geohash,
        'dateline'=>$dateline,
    ];

    return [$tableName,"{$arr['userid']},'{$arr['geohash']}','{$arr['dateline']}'"];
}

//分表
function choseTable($userid)
{
    $tableNum=$userid%200;

    return $tableNum;
}

//自制分页
function myPage($data,$limit,$page=1)
{
    $tmp=[];

    $offset=($page-1)*$limit;

    for ($i=$offset;$i<=$limit*$page-1;$i++)
    {
        if (!isset($data[$i])) break;

        $tmp[]=$data[$i];
    }

    return $tmp;
}

//使用requestToken 防止用户暴力请求
function useRequestToken($uid)
{
    $key=$uid;

    if (Redis::connection('RequestToken')->set($key,1,'nx','ex',Config::get('myDefine.RequestTokenExpireTime')))
    {
        return true;

    }else
    {
        return false;
    }
}

//写log
function writeLog()
{

}

//发送curl
function curlSend($url,$data,$isPost=true,$headerArray=[]):array
{
    $curl=curl_init();//初始化

    curl_setopt($curl,CURLOPT_URL,$url);//设置请求地址

    curl_setopt($curl,CURLOPT_POST,$isPost);//设置post方式请求

    if (!empty($headerArray) && is_array($headerArray)) curl_setopt($curl, CURLOPT_HTTPHEADER,$headerArray);

    curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,5);//几秒后没链接上就自动断开

    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);

    //$data=json_encode($data);//转换成json

    curl_setopt($curl,CURLOPT_POSTFIELDS,$data);//提交的数据

    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);//返回值不直接显示

    $res=curl_exec($curl);//发送请求

    if(curl_errno($curl))//判断是否有错
    {
        $msg=null;
        $msg=curl_error($curl);
        curl_close($curl);//释放

        return ['error'=>'1','msg'=>$msg];
    }else
    {
        curl_close($curl);//释放
        return ['error'=>'0','msg'=>$res];
    }
}

//获取以前tssj用户信息
function getTssjUserInfo($uid)
{
    if ($uid==0)
    {
        $userinfo=new stdClass();

        $userinfo->username='系统';

    }else
    {
        $userinfo=DB::connection('tssj_old')->table('tssj_member')->where('userid',$uid)->first();
    }

    return $userinfo;
}

//获取base64格式图片内容
function uploadMyImg($base64Pic)
{
    if ($base64Pic=='' || $base64Pic==null || empty($base64Pic))
    {
        return false;
    }

    //变成二进制
    $picContent=base64_decode($base64Pic);

    return $picContent;
}

//图片贮存到服务器
function storeFile($content,$uid,$grid,$type)
{
    $suffix=$uid%5;

    if ($type=='pic1')
    {
        $path=public_path(DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR);

        $pathStoreInDB=DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR;

        if (!is_dir($path))
        {
            mkdir($path,0777,true);
        }

        $filename=$uid.'_'.$grid->id."_$type".".jpg";
    }

    try
    {
        //file_put_contents($path.$filename,$content);

        $image=\Intervention\Image\Facades\Image::make($content)->resize(100,100,function($constraint)
        {
            $constraint->aspectRatio();
            $constraint->upsize();

        })->save($path.$filename);

    }catch (Exception $e)
    {
        return false;
    }

    return $pathStoreInDB.$filename;
}

//多少小时前，多少分钟前
function formatDate($timestamp)
{
    $todaytimestamp = time();

    if(intval($todaytimestamp-$timestamp) < 3600)
    {
        return intval(($todaytimestamp-$timestamp)/60) .'分钟前';

    }elseif(intval($todaytimestamp-$timestamp) < 86400)
    {
        return intval(($todaytimestamp-$timestamp)/3600) .'小时前';

    }else
    {
        return date('n月j日',$timestamp);
    }
}

//encode for AES
function encodeForAES($str)
{
    $method=['AES-128-ECB','AES-256-ECB'];//加密方法

    $salt='WoYaoZhanLingDiQiu';//盐值

    $codeStr=bin2hex(openssl_encrypt($str,array_random($method),$salt,OPENSSL_RAW_DATA));

    return $codeStr;
}

//decode for AES
function decodeForAES($str,$secret)
{
    $method1='AES-128-ECB';
    $method2='AES-256-ECB';

    $salt='WoYaoZhanLingDiQiu';

    $res=openssl_decrypt(pack("H*",$secret),$method1,$salt,OPENSSL_RAW_DATA);

    if ($res==$str) return true;

    $res=openssl_decrypt(pack("H*",$secret),$method2,$salt,OPENSSL_RAW_DATA);

    if ($res==$str) return true;

    return false;
}

//obj to array
function obj2arr(&$object)
{
    //10万数据量性能也不会差
    $object=json_decode(json_encode($object),true);

    return $object;
}

//判断远程或本地文件存在
function checkFileExists($file)
{
    if (strtolower(substr($file,0,4))=='http')
    {
        $header=get_headers($file,true);

        return isset($header[0]) && (strpos($header[0],'200') || strpos($header[0],'304'));

    }else
    {
        return file_exists($file);
    }
}






