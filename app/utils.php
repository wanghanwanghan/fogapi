<?php

use Vinkla\Hashids\Facades\Hashids;

//BD-09(百度)坐标转换成GCJ-02(火星，高德)坐标
//@param bd_lon 百度经度
//@param bd_lat 百度纬度
function bd_decrypt($bd_lon, $bd_lat)
{
    $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    $x = $bd_lon - 0.0065;
    $y = $bd_lat - 0.006;
    $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
    $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
    // $data['gg_lon'] = $z * cos($theta);
    // $data['gg_lat'] = $z * sin($theta);
    $gg_lon = $z * cos($theta);
    $gg_lat = $z * sin($theta);
    // 保留小数点后六位
    $data['gg_lon'] = round($gg_lon, 6);
    $data['gg_lat'] = round($gg_lat, 6);
    return $data;
}

//GCJ-02(火星，高德)坐标转换成BD-09(百度)坐标
//@param bd_lon 百度经度
//@param bd_lat 百度纬度
function bd_encrypt($gg_lon,$gg_lat)
{
    $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    $x = $gg_lon;
    $y = $gg_lat;
    $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
    $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
    $bd_lon = $z * cos($theta) + 0.0065;
    $bd_lat = $z * sin($theta) + 0.006;
    // 保留小数点后六位
    $data['bd_lon'] = round($bd_lon, 6);
    $data['bd_lat'] = round($bd_lat, 6);
    return $data;
}

//hashids加密后的经纬度字符串转成经纬度
function latlngStrTolatlng($str)
{
    $hashids=explode('_',$str);

    $hashids_lat=$hashids[0];
    $hashids_lng=$hashids[1];

    $hashids_lat=Hashids::decode($hashids_lat);
    $hashids_lng=Hashids::decode($hashids_lng);

    $lat=$hashids_lat[0].'.'.$hashids_lat[1];
    $lng=$hashids_lng[0].'.'.$hashids_lng[1];

    return ['lat'=>$lat,'lng'=>$lng];
}

//判断是否是移动端访问
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
    {
        return true;
    }

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA']))
    {
        // 找不到为flase,否则为TRUE
        return stristr($_SERVER['HTTP_VIA'],"wap") ? true : false;
    }

    // 判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords=[
            'mobile',
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
        ];

        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(".implode('|',$clientkeywords).")/i",strtolower($_SERVER['HTTP_USER_AGENT'])))
        {
            return true;
        }
    }

    // 协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT']))
    {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'],'vnd.wap.wml')!==false) && (strpos($_SERVER['HTTP_ACCEPT'],'text/html')===false || (strpos($_SERVER['HTTP_ACCEPT'],'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'],'text/html'))))
        {
            return true;
        }
    }

    return false;
}
