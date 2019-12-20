<?php

use Carbon\Carbon;
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

//全角转半角
function filter4($str)
{
    $arr = [
        '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
        'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
        'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
        'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
        'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
        'ｙ' => 'y', 'ｚ' => 'z',
        '（' => '(', '）' => ')', '〔' => '(', '〕' => ')', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']', '“' => '"', '”' => '"',
        '‘' => '\'', '’' => '\'', '｛' => '{', '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+', '—' => '-', '－' => '-',
        '～' => '~', '：' => ':', '。' => '.', '、' => ',', '，' => ',', '、' => ',', '；' => ';', '？' => '?', '！' => '!', '…' => '-',
        '‖' => '|', '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"', '　' => ' ', '×' => '*', '￣' => '~', '．' => '.', '＊' => '*',
        '＆' => '&', '＜' => '<', '＞' => '>', '＄' => '$', '＠' => '@', '＾' => '^', '＿' => '_', '＂' => '"', '￥' => '$', '＝' => '=',
        '＼' => '\\', '／' => '/','“'=>'"'
    ];

    return strtr($str,$arr);
}

//二维数组按照某一列排序
function arraySort1($array,$cond=['desc','id'])
{
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

//无限极分类
function traverseMenu($menus,&$result,$pid=0)
{
    //数据样子
    //$menus = [
    //    ['id' => 1, 'pid' => 0, 'name' => '商品管理'],
    //    ['id' => 2, 'pid' => 1, 'name' => '平台自营'],
    //    ['id' => 3, 'pid' => 2, 'name' => '图书品类'],
    //    ['id' => 4, 'pid' => 2, 'name' => '3C品类'],
    //    ['id' => 5, 'pid' => 0, 'name' => '第三方'],
    //    ['id' => 6, 'pid' => 5, 'name' => '家私用品'],
    //    ['id' => 7, 'pid' => 5, 'name' => '书法品赏'],
    //    ['id' => 8, 'pid' => 7, 'name' => '行书'],
    //    ['id' => 9, 'pid' => 8, 'name' => '行楷'],
    //    ['id' => 10, 'pid' => 9, 'name' => '张山行楷字帖'],
    //    ['id' => 11, 'pid' => 22, 'name' => '李四行楷字帖'],
    //];

    //使用方式
    //$result = [];
    //traverseMenu($menus,$result,0);
    //dd($result);

    foreach ($menus as $child_menu)
    {
        if ($child_menu['pid']==$pid)
        {
            $item=[
                'id'=>$child_menu['id'],
                'name'=>$child_menu['name'],
                'children'=>[]
            ];

            traverseMenu($menus,$item['children'],$child_menu['id']);

            $result[]=$item;
        }else
        {
            continue;
        }
    }
}

//encode
function jsonEncode($target)
{
    return json_encode($target);
}

//decode
function jsonDecode($target,$type='array')
{
    $type=='array' ? $type=true : $type=false;

    return json_decode($target,$type);
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

    $res_arry=jsonDecode($res_json);

    if ($res_arry['error_code']!='0' || $res_arry['resultcode']!='200')
    {
        return ['area'=>'查询失败','location'=>'loading...'];
    }else
    {
        return $res_arry;
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

//只含有26个字母或者数字的并且都是半角的字符串，转换成数字，用于hash分表
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

    $res=jsonDecode($res);

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

    $res=jsonDecode($res);

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

    //$data=jsonEncode($data);//转换成json

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

    $width =200;
    $height=200;

    //pic2是格子排行榜第一名的图片
    if ($type=='pic2')
    {
        $path=public_path(DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR);

        $pathStoreInDB=DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR;

        if (!is_dir($path))
        {
            mkdir($path,0777,true);
        }

        $filename=$uid.'_'.$grid->id."_$type".'readyToCheck'.".jpg";

        $width =640;
        $height=360;
    }

    //pic1是格子图片
    if ($type=='pic1')
    {
        $path=public_path(DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR);

        $pathStoreInDB=DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR;

        if (!is_dir($path))
        {
            mkdir($path,0777,true);
        }

        $filename=$uid.'_'.$grid->id."_$type".'readyToCheck'.".jpg";
    }

    if ($type=='avatar')
    {
        $path=public_path(DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR);

        $pathStoreInDB=DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR;

        if (!is_dir($path))
        {
            mkdir($path,0777,true);
        }

        $filename=$uid.'_avatarreadyToCheck.jpg';
    }

    if ($type=='redisPic1')
    {
        $path=public_path(DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR);

        $pathStoreInDB=DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR;

        if (!is_dir($path))
        {
            mkdir($path,0777,true);
        }

        $filename=$uid."_$type".'readyToCheck'.".jpg";

        $width =640;
        $height=360;
    }

    try
    {
        \Intervention\Image\Facades\Image::make($content)->resize($width,$height)->save($path.$filename);

        return $pathStoreInDB.$filename;

    }catch (Exception $e)
    {
        sleep(1);

        try
        {
            \Intervention\Image\Facades\Image::make($content)->resize($width,$height)->save($path.$filename);

            return $pathStoreInDB.$filename;

        }catch (Exception $w)
        {
            return '';
        }
    }
}

//贮存准备source的sql文件
function storeReadyToSourceSqlFile($DB_target,$Table_target)
{

}

//多少小时前，多少分钟前
function formatDate($timestamp,$type='')
{
    $todaytimestamp = time();

    if ($type!='')
    {
        //几天几小时后到期
        $target=Carbon::parse(date('Y-m-d H:i:s',$timestamp));

        $day=(new Carbon)->diffInDays($target,true);

        if ($day===0)
        {
            $hours=(new Carbon)->diffInHours($target,true);

            return "剩余 0 天 {$hours} 小时";
        }else
        {
            return "剩余 {$day} 天";
        }
    }

    //==========================================

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

//多少天，多少小时，多少分钟，多少秒
function formatDateNew($second)
{
    $day = floor($second/(3600*24));

    $second = $second%(3600*24);//除去整天之后剩余的时间

    $hour = floor($second/3600);

    $second = $second%3600;//除去整小时之后剩余的时间

    $minute = floor($second/60);

    $second = $second%60;//除去整分钟之后剩余的时间

    //返回字符串
    return $day.'天'.$hour.'小时'.$minute.'分'.$second.'秒';
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

    $salt='WoYaoZhanLingDiQiu';//盐值

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
    $object=jsonDecode(jsonEncode($object));

    return $object;
}

//判断远程或本地文件存在
function checkFileExists($file)
{
    if (strtolower(substr($file,0,4))=='http')
    {
        try
        {
            $header=get_headers($file,true);

        }catch (Exception $e)
        {
            return false;
        }

        return isset($header[0]) && (strpos($header[0],'200') || strpos($header[0],'304'));

    }else
    {
        return file_exists($file);
    }
}

//删除文件夹下$n分钟前创建的文件
function delFileByCtime($dir,$n)
{
    if (is_dir($dir))
    {
        if ($dh = opendir($dir))
        {
            while (false !== ($file = readdir($dh)))
            {
                if ($file != "." && $file != "..")
                {
                    $fullpath = $dir . "/" . $file;
                    if (is_dir($fullpath))
                    {
                        if (count(scandir($fullpath)) == 2 && $file != date('Y-m-d'))
                        {
                            //目录为空,=2是因为.和..存在
                            rmdir($fullpath); // 删除空目录
                        } else
                        {
                            delFileByCtime($fullpath,$n); //不为空继续判断文件夹中文件
                        }
                    } else
                    {
                        $filedate = filemtime($fullpath); //获取文件创建时间
                        $minutes = round((time() - $filedate) / 60); //计算已创建分钟
                        if ($minutes > $n) unlink($fullpath); //删除过期文件
                    }
                }
            }
        }
        closedir($dh);
    }
}

//获取本次请求的执行时间，该方法放到后置中间件
function getElapsedTime(int $decimals=2)
{
    //REQUEST_TIME_FLOAT是请求开始时候的时间戳
    return number_format(microtime(true) - request()->server('REQUEST_TIME_FLOAT'),$decimals).'s';
}

//获取本次请求的内存使用情况，该方法放到后置中间件
function getMemoryUsage(int $precision=2)
{
    $size=memory_get_usage(true);

    $unit=['b','kb','mb','gb','tb','pb'];

    return round($size / pow(1024, ($i = floor(log($size, 1024)))), $precision) . '' . $unit[$i];
}

//获取执行时间大于几秒的sql，并记录到数据库
function getSlowlySql($s)
{
    //记录慢查询，此表可以随意删
    if (!Schema::connection('masterDB')->hasTable('slow_sql'))
    {
        Schema::connection('masterDB')->create('slow_sql', function (Blueprint $table) {

            $table->increments('id')->unsigned()->comment('自增主键');
            $table->string('uuid',35)->nullable()->index()->comment('sql uuid');
            $table->text('sql')->nullable()->comment('执行语句');
            $table->text('bind')->nullable()->comment('绑定数值');
            $table->float('execTime',4,2)->nullable()->unsigned()->index()->comment('执行耗时');
            $table->string('time',25)->nullable()->comment('执行时间');

        });
    }

    DB::connection('masterDB')->listen(function ($query) use ($s)
    {
        $time=round($query->time/1000,2);

        //超过几秒的sql存入数据库
        if ($time > $s)
        {
            $sql=addslashes($query->sql);

            $sql=str_replace(["\n","\r\n"],'',$sql);

            //如果是处理迷雾数据，不记录到慢sql
            if (strpos($sql,'user_fog_')!==false) return true;
            //如果是自动调整格子价值，不记录到慢sql
            if (strpos($sql,'update grid set price=case when round')!==false) return true;

            $md5Sql=md5($sql);

            $res=DB::connection('masterDB')->table('slow_sql')->where('uuid',$md5Sql)->first();

            if ($res==null)
            {
                $query->bindings==[] ? $bind='' : $bind=jsonEncode($query->bindings);

                $sql="insert into slow_sql values(null,'{$md5Sql}','{$sql}','{$bind}',{$time},unix_timestamp())";

                try
                {
                    DB::connection('masterDB')->insert($sql);

                }catch (\Exception $e)
                {
                    return true;
                }

                return true;
            }else
            {
                //更新sql执行时间
                $sql="update slow_sql set execTime={$time},time=unix_timestamp() where uuid='{$res->uuid}'";

                try
                {
                    DB::connection('masterDB')->update($sql);

                }catch (\Exception $e)
                {
                    return true;
                }

                return true;
            }
        }
    });

    return true;
}

//自制分页
function paginateByMyself($res,$page,$limit)
{
    $offset=($page-1)*$limit;

    $data=[];

    for ($i=$offset;$i<=$limit*$page-1;$i++)
    {
        if (!isset($res[$i])) break;

        $data[]=$res[$i];
    }

    return $data;
}

//随机用户名昵称
function randomUserName()
{
    if (time()%2)
    {
        $firstName=['赵','钱','孙','李','周','吴','郑','王','冯','陈','褚','卫','蒋','沈','韩','杨','朱','秦','尤','许','何','吕','施','张','孔','曹','严','华','金','魏','陶','姜','戚','谢','邹',
            '喻','柏','水','窦','章','云','苏','潘','葛','奚','范','彭','郎','鲁','韦','昌','马','苗','凤','花','方','任','袁','柳','鲍','史','唐','费','薛','雷','贺','倪','汤','滕','殷','罗',
            '毕','郝','安','常','傅','卞','齐','元','顾','孟','平','黄','穆','萧','尹','姚','邵','湛','汪','祁','毛','狄','米','伏','成','戴','谈','宋','茅','庞','熊','纪','舒','屈','项','祝',
            '董','梁','杜','阮','蓝','闵','季','贾','路','娄','江','童','颜','郭','梅','盛','林','钟','徐','邱','骆','高','夏','蔡','田','樊','胡','凌','霍','虞','万','支','柯','管','卢','莫',
            '柯','房','裘','缪','解','应','宗','丁','宣','邓','单','杭','洪','包','诸','左','石','崔','吉','龚','程','嵇','邢','裴','陆','荣','翁','荀','于','惠','甄','曲','封','储','仲','伊',
            '宁','仇','甘','武','符','刘','景','詹','龙','叶','幸','司','黎','溥','印','怀','蒲','邰','从','索','赖','卓','屠','池','乔','胥','闻','莘','党','翟','谭','贡','劳','逄','姬','申',
            '扶','堵','冉','宰','雍','桑','寿','通','燕','浦','尚','农','温','别','庄','晏','柴','瞿','阎','连','习','容','向','古','易','廖','庾','终','步','都','耿','满','弘','匡','国','文',
            '寇','广','禄','阙','东','欧','利','师','巩','聂','关','荆','司马','上官','欧阳','夏侯','诸葛','闻人','东方','赫连','皇甫','尉迟','公羊','澹台','公冶','宗政','濮阳','淳于','单于','太叔',
            '申屠','公孙','仲孙','轩辕','令狐','徐离','宇文','长孙','慕容','司徒','司空','皮'];

        $lastName=['伟','刚','勇','毅','俊','峰','强','军','平','保','东','文','辉','力','明','永','健','世','广','志','义','兴','良','海','山','仁','波','宁','贵','福','生','龙','元','全'
            ,'国','胜','学','祥','才','发','武','新','利','清','飞','彬','富','顺','信','子','杰','涛','昌','成','康','星','光','天','达','安','岩','中','茂','进','林','有','坚','和','彪','博','诚'
            ,'先','敬','震','振','壮','会','思','群','豪','心','邦','承','乐','绍','功','松','善','厚','庆','磊','民','友','裕','河','哲','江','超','浩','亮','政','谦','亨','奇','固','之','轮','翰'
            ,'朗','伯','宏','言','若','鸣','朋','斌','梁','栋','维','启','克','伦','翔','旭','鹏','泽','晨','辰','士','以','建','家','致','树','炎','德','行','时','泰','盛','雄','琛','钧','冠','策'
            ,'腾','楠','榕','风','航','弘','秀','娟','英','华','慧','巧','美','娜','静','淑','惠','珠','翠','雅','芝','玉','萍','红','娥','玲','芬','芳','燕','彩','春','菊','兰','凤','洁','梅','琳'
            ,'素','云','莲','真','环','雪','荣','爱','妹','霞','香','月','莺','媛','艳','瑞','凡','佳','嘉','琼','勤','珍','贞','莉','桂','娣','叶','璧','璐','娅','琦','晶','妍','茜','秋','珊','莎'
            ,'锦','黛','青','倩','婷','姣','婉','娴','瑾','颖','露','瑶','怡','婵','雁','蓓','纨','仪','荷','丹','蓉','眉','君','琴','蕊','薇','菁','梦','岚','苑','婕','馨','瑗','琰','韵','融','园'
            ,'艺','咏','卿','聪','澜','纯','毓','悦','昭','冰','爽','琬','茗','羽','希','欣','飘','育','滢','馥','筠','柔','竹','霭','凝','晓','欢','霄','枫','芸','菲','寒','伊','亚','宜','可','姬'
            ,'舒','影','荔','枝','丽','阳','妮','宝','贝','初','程','梵','罡','恒','鸿','桦','骅','剑','娇','纪','宽','苛','灵','玛','媚','琪','晴','容','睿','烁','堂','唯','威','韦','雯','苇','萱'
            ,'阅','彦','宇','雨','洋','忠','宗','曼','紫','逸','贤','蝶','菡','绿','蓝','儿','翠','烟'];

        if (time()%10>4) return array_random($firstName).array_random($lastName).array_random($lastName);

        return array_random($firstName).array_random($lastName);

    }else
    {
        $firstName=['迷你的','鲜艳的','飞快的','真实的','清新的','幸福的','可耐的','快乐的','冷静的','醉熏的','潇洒的','糊涂的','积极的','冷酷的','深情的','粗暴的',
            '温柔的','可爱的','愉快的','义气的','认真的','威武的','帅气的','传统的','潇洒的','漂亮的','自然的','专一的','听话的','昏睡的','狂野的','等待的','搞怪的',
            '幽默的','魁梧的','活泼的','开心的','高兴的','超帅的','留胡子的','坦率的','直率的','轻松的','痴情的','完美的','精明的','无聊的','有魅力的','丰富的','繁荣的',
            '饱满的','炙热的','暴躁的','碧蓝的','俊逸的','英勇的','健忘的','故意的','无心的','土豪的','朴实的','兴奋的','幸福的','淡定的','不安的','阔达的','孤独的',
            '独特的','疯狂的','时尚的','落后的','风趣的','忧伤的','大胆的','爱笑的','矮小的','健康的','合适的','玩命的','沉默的','斯文的','香蕉','苹果','鲤鱼','鳗鱼',
            '任性的','细心的','粗心的','大意的','甜甜的','酷酷的','健壮的','英俊的','霸气的','阳光的','默默的','大力的','孝顺的','忧虑的','着急的','紧张的','善良的',
            '凶狠的','害怕的','重要的','危机的','欢喜的','欣慰的','满意的','跳跃的','诚心的','称心的','如意的','怡然的','娇气的','无奈的','无语的','激动的','愤怒的',
            '美好的','感动的','激情的','激昂的','震动的','虚拟的','超级的','寒冷的','精明的','明理的','犹豫的','忧郁的','寂寞的','奋斗的','勤奋的','现代的','过时的',
            '稳重的','热情的','含蓄的','开放的','无辜的','多情的','纯真的','拉长的','热心的','从容的','体贴的','风中的','曾经的','追寻的','儒雅的','优雅的','开朗的',
            '外向的','内向的','清爽的','文艺的','长情的','平常的','单身的','伶俐的','高大的','懦弱的','柔弱的','爱笑的','乐观的','耍酷的','酷炫的','神勇的','年轻的',
            '唠叨的','瘦瘦的','无情的','包容的','顺心的','畅快的','舒适的','靓丽的','负责的','背后的','简单的','谦让的','彩色的','缥缈的','欢呼的','生动的','复杂的',
            '慈祥的','仁爱的','魔幻的','虚幻的','淡然的','受伤的','雪白的','高高的','糟糕的','顺利的','闪闪的','羞涩的','缓慢的','迅速的','优秀的','聪明的','含糊的',
            '俏皮的','淡淡的','坚强的','平淡的','欣喜的','能干的','灵巧的','友好的','机智的','机灵的','正直的','谨慎的','俭朴的','殷勤的','虚心的','辛勤的','自觉的',
            '无私的','无限的','踏实的','老实的','现实的','可靠的','务实的','拼搏的','个性的','粗犷的','活力的','成就的','勤劳的','单纯的','落寞的','朴素的','悲凉的',
            '忧心的','洁净的','清秀的','自由的','小巧的','单薄的','贪玩的','刻苦的','干净的','壮观的','和谐的','文静的','调皮的','害羞的','安详的','自信的','端庄的',
            '坚定的','美满的','舒心的','温暖的','专注的','勤恳的','美丽的','腼腆的','优美的','甜美的','甜蜜的','整齐的','动人的','典雅的','尊敬的','舒服的','妩媚的',
            '秀丽的','喜悦的','甜美的','彪壮的','强健的','大方的','俊秀的','聪慧的','迷人的','陶醉的','悦耳的','动听的','明亮的','结实的','魁梧的','标致的','清脆的',
            '敏感的','光亮的','大气的','老迟到的','知性的','冷傲的','呆萌的','野性的','隐形的','笑点低的','微笑的','笨笨的','难过的','沉静的','火星上的','失眠的',
            '安静的','纯情的','要减肥的','迷路的','烂漫的','哭泣的','贤惠的','苗条的','温婉的','发嗲的','会撒娇的','贪玩的','执着的','眯眯眼的','花痴的','想人陪的',
            '眼睛大的','高贵的','傲娇的','心灵美的','爱撒娇的','细腻的','天真的','怕黑的','感性的','飘逸的','怕孤独的','忐忑的','高挑的','傻傻的','冷艳的','爱听歌的',
            '还单身的','怕孤单的','懵懂的'];

        $lastName=['嚓茶','皮皮虾','皮卡丘','马里奥','小霸王','凉面','便当','毛豆','花生','可乐','灯泡','哈密瓜','野狼','背包','眼神','缘分','雪碧','人生','牛排',
            '蚂蚁','飞鸟','灰狼','斑马','汉堡','悟空','巨人','绿茶','自行车','保温杯','大碗','墨镜','魔镜','煎饼','月饼','月亮','星星','芝麻','啤酒','玫瑰',
            '大叔','小伙','哈密瓜，数据线','太阳','树叶','芹菜','黄蜂','蜜粉','蜜蜂','信封','西装','外套','裙子','大象','猫咪','母鸡','路灯','蓝天','白云',
            '星月','彩虹','微笑','摩托','板栗','高山','大地','大树','电灯胆','砖头','楼房','水池','鸡翅','蜻蜓','红牛','咖啡','机器猫','枕头','大船','诺言',
            '钢笔','刺猬','天空','飞机','大炮','冬天','洋葱','春天','夏天','秋天','冬日','航空','毛衣','豌豆','黑米','玉米','眼睛','老鼠','白羊','帅哥','美女',
            '季节','鲜花','服饰','裙子','白开水','秀发','大山','火车','汽车','歌曲','舞蹈','老师','导师','方盒','大米','麦片','水杯','水壶','手套','鞋子','自行车',
            '鼠标','手机','电脑','书本','奇迹','身影','香烟','夕阳','台灯','宝贝','未来','皮带','钥匙','心锁','故事','花瓣','滑板','画笔','画板','学姐','店员',
            '电源','饼干','宝马','过客','大白','时光','石头','钻石','河马','犀牛','西牛','绿草','抽屉','柜子','往事','寒风','路人','橘子','耳机','鸵鸟','朋友',
            '苗条','铅笔','钢笔','硬币','热狗','大侠','御姐','萝莉','毛巾','期待','盼望','白昼','黑夜','大门','黑裤','钢铁侠','哑铃','板凳','枫叶','荷花','乌龟',
            '仙人掌','衬衫','大神','草丛','早晨','心情','茉莉','流沙','蜗牛','战斗机','冥王星','猎豹','棒球','篮球','乐曲','电话','网络','世界','中心','鱼','鸡','狗',
            '老虎','鸭子','雨','羽毛','翅膀','外套','火','丝袜','书包','钢笔','冷风','八宝粥','烤鸡','大雁','音响','招牌','胡萝卜','冰棍','帽子','菠萝','蛋挞','香水',
            '泥猴桃','吐司','溪流','黄豆','樱桃','小鸽子','小蝴蝶','爆米花','花卷','小鸭子','小海豚','日记本','小熊猫','小懒猪','小懒虫','荔枝','镜子','曲奇','金针菇',
            '小松鼠','小虾米','酒窝','紫菜','金鱼','柚子','果汁','百褶裙','项链','帆布鞋','火龙果','奇异果','煎蛋','唇彩','小土豆','高跟鞋','戒指','雪糕','睫毛','铃铛',
            '手链','香氛','红酒','月光','酸奶','银耳汤','咖啡豆','小蜜蜂','小蚂蚁','蜡烛','棉花糖','向日葵','水蜜桃','小蝴蝶','小刺猬','小丸子','指甲油','康乃馨','糖豆',
            '薯片','口红','超短裙','乌冬面','冰淇淋','棒棒糖','长颈鹿','豆芽','发箍','发卡','发夹','发带','铃铛','小馒头','小笼包','小甜瓜','冬瓜','香菇','小兔子',
            '含羞草','短靴','睫毛膏','小蘑菇','跳跳糖','小白菜','草莓','柠檬','月饼','百合','纸鹤','小天鹅','云朵','芒果','面包','海燕','小猫咪','龙猫','唇膏','鞋垫',
            '羊','黑猫','白猫','万宝路','金毛','山水','音响','纸飞机','烧鹅'];

        return array_random($firstName).array_random($lastName);
    }
}

//随机用户头像
function randomUserAvatar()
{
    $count=\Illuminate\Support\Facades\Cache::remember('randomUserAvatarCount',1440,function()
    {
        return DB::connection('masterDB')->table('oneAvatar')->count();
    });

    $where=random_int(1,$count);

    $res=DB::connection('masterDB')->table('oneAvatar')->where('id',$where)->first();

    return $res->imgurl;
}

//随机uuid
function randomUUID()
{
    return md5(uniqid(mt_rand(),true));
}

//redis加锁
function redisLock($key,$lockTime)
{
    return Redis::connection('RequestToken')->set($key,'Locking...','ex',$lockTime,'nx');
}

//redis解锁
function redisUnlock($key)
{
    return Redis::connection('RequestToken')->del($key);
}
