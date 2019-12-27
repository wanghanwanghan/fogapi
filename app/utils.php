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




