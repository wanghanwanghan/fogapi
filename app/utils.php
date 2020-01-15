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

//阿拉伯数字转为中文数字（1-1 亿范围）123 => 一百二十三
function numToWord($num)
{
    $chiNum = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
    $chiUni = ['','十', '百', '千', '万', '十', '百', '千', '亿'];
    $num_str = (string)$num;
    $count = strlen($num_str);
    $last_flag = true; //上一个 是否为0
    $zero_flag = true; //是否第一个
    $temp_num = null; //临时数字
    $chiStr = '';//拼接结果
    if ($count == 2)
    {
        //两位数
        $temp_num = $num_str[0];
        $chiStr = $temp_num == 1 ? $chiUni[1] : $chiNum[$temp_num].$chiUni[1];
        $temp_num = $num_str[1];
        $chiStr .= $temp_num == 0 ? '' : $chiNum[$temp_num];
    }else if ($count > 2)
    {
        $index = 0;
        for ($i=$count-1;$i >= 0;$i--)
        {
            $temp_num = $num_str[$i];
            if ($temp_num == 0)
            {
                if (!$zero_flag && !$last_flag)
                {
                    $chiStr = $chiNum[$temp_num].$chiStr;
                    $last_flag = true;
                }

                if($index == 4 && $temp_num == 0)
                {
                    $chiStr = "万".$chiStr;
                }
            }else
            {
                if($i == 0 && $temp_num == 1 && $index == 1 && $index == 5)
                {
                    $chiStr = $chiUni[$index%9].$chiStr;
                }else
                {
                    $chiStr = $chiNum[$temp_num].$chiUni[$index%9].$chiStr;
                }
                $zero_flag = false;
                $last_flag = false;
            }
            $index ++;
        }
    }else
    {
        $chiStr = $chiNum[$num_str[0]];
    }
    return $chiStr;
}

//把中文数字转为阿拉伯数字
function wordToNum($string)
{
    if(is_numeric($string)) return $string;

    // '仟' => '千','佰' => '百','拾' => '十',
    $string = str_replace('仟', '千', $string);
    $string = str_replace('佰', '百', $string);
    $string = str_replace('拾', '十', $string);
    $num = 0;
    $wan = explode('万', $string);
    if (count($wan) > 1)
    {
        $num += numToWord($wan[0]) * 10000;
        $string = $wan[1];
    }
    $qian = explode('千', $string);
    if (count($qian) > 1)
    {
        $num += numToWord($qian[0]) * 1000;
        $string = $qian[1];
    }
    $bai = explode('百', $string);
    if (count($bai) > 1)
    {
        $num += numToWord($bai[0]) * 100;
        $string = $bai[1];
    }
    $shi = explode('十', $string);
    if (count($shi) > 1)
    {
        $num += numToWord($shi[0] ? $shi[0] : '一') * 10;
        $string = $shi[1] ? $shi[1] : '零';
    }
    $ling = explode('零', $string);
    if (count($ling) > 1)
    {
        $string = $ling[1];
    }
    $d = [
        '一' => '1','二' => '2','三' => '3','四' => '4','五' => '5','六' => '6','七' => '7','八' => '8','九' => '9',
        '壹' => '1','贰' => '2','叁' => '3','肆' => '4','伍' => '5','陆' => '6','柒' => '7','捌' => '8','玖' => '9',
        '零' => 0, '0' => 0, 'O' => 0, 'o' => 0,
        '两' => 2
    ];
    return $num + @$d[$string];
}










