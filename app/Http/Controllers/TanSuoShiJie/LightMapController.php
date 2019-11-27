<?php

namespace App\Http\Controllers\TanSuoShiJie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class LightMapController extends Controller
{
    public function lightMap(Request $request)
    {
        $mapZone=strtolower(trim($request->map_zone));

        if (!is_numeric($request->u) || $request->u < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $request->uid=$request->u;

        //返回图片链接
        $mapZone==='china' ? $res=$this->lightChina($request) : $res=$this->lightWorld($request);

        $suffix=$request->uid%10;

        if ($res!='') $res="http://newfogapi.wodeluapp.com/lightMap/{$suffix}/{$request->uid}.png?".time();

        return response()->json(['resCode'=>Config::get('resCode.200'),'url'=>$res]);
    }

    private function lightChina($request)
    {
        $userCitys = explode(',',trim($request->c));

        $mapColor = '#ECC939';

        //中国地图svg 模板
        $fileMap = public_path('lightMap/base/CNSVG_baise.svg');

        $mapData = file_get_contents($fileMap);

        $CityList = Config::get('lightMap.getCnCity');
        $CNMap = Config::get('lightMap.getCnProvince');

        //查找省份所对应的区域
        $maps = [];
        foreach($userCitys as $city)
        {
            foreach($CityList as $province=>$citys)
            {
                if(in_array($city,$citys))
                {
                    foreach($CNMap as $mapIndex=>$map)
                    {
                        if($map == $province)
                        {
                            $maps[] = $mapIndex;
                            break;
                        }
                    }
                    break;
                }
            }

        }

        foreach($maps as $map)
        {
            if(preg_match('/<path id="'.$map.'".*fill="(.*)".*\/>/Uis',$mapData,$matchMap))
            {
                $pathHtml = str_replace($matchMap[1],$mapColor,$matchMap[0]);
                $mapData = str_replace($matchMap[0],$pathHtml,$mapData);
            }
        }

        $file_name=$this->createPathFileName($request->uid);

        $svgfile_name = $file_name.'.svg';
        $pngfile_name = $file_name.'.png';

        $pic_url_full='';

        if ($mapData)
        {
            file_put_contents($svgfile_name,$mapData);

            $url_svg_full=public_path('lightMap/'.$svgfile_name);

            $im = new \Imagick();
            $svg = $mapData;

            $im->setBackgroundColor(new \ImagickPixel('transparent'));
            $im->readImageBlob($svg);
            $im->setImageFormat("png24");
            $im->resizeImage(791, 616, \Imagick::FILTER_LANCZOS,1); /*改变大小*/

            $pngFile = $pngfile_name;

            $im->writeImage($pngFile);
            $im->clear();
            $im->destroy();

            $pic_url_full = $pngFile;
        }

        return $pic_url_full;
    }

    private function lightWorld($request)
    {
        $countrys = [];
        $userCountrys = explode(',',$request->c);

        //地图svg文件点亮到访地区
        $mapColor = '#ECC939';

        //世界地图svg 模板
        $fileMap = public_path('lightMap/base/map.svg');

        $mapData = file_get_contents($fileMap);

        $CountryList = Config::get('lightMap.getCountry');
        $Contient = Config::get('lightMap.getContient');
        $MapList = Config::get('lightMap.getMapList');

        $CountryList = jsonDecode($CountryList);

        unset($CountryList['hot']);

        //查找用户到访国家
        foreach ($CountryList as $k => $item)
        {
            foreach ($userCountrys as $country)
            {
                if (in_array($country, $item))
                {
                    $index = $Contient[$k];
                    $countrys[$index][] = $country;
                }
            }
        }

        $file_name=$this->createPathFileName($request->uid);

        $svgfile_name = $file_name.'.svg';
        $pngfile_name = $file_name.'.png';

        //查找国家所对应的区域
        $maps = [];
        foreach($userCountrys as $country)
        {
            if(in_array($country,$MapList))
            {
                foreach($MapList as $mapIndex=>$map)
                {
                    if($map == $country)
                    {
                        $maps[] = $mapIndex;

                        //大陆区域没有包括台湾，点亮时加入台湾所在的地图id
                        if( $country == '中国' )
                        {
                            $maps[] = 'map_158';
                        }

                        break;
                    }
                }
            }
        }

        foreach($maps as $map)
        {
            if(preg_match('/<path id="'.$map.'".*fill="(.*)".*\/>/Uis',$mapData,$matchMap))
            {
                $pathHtml = str_replace($matchMap[1], $mapColor,$matchMap[0]);
                $mapData = str_replace($matchMap[0], $pathHtml, $mapData);
            }
        }

        $pic_url_full='';

        if ($mapData)
        {
            file_put_contents($svgfile_name,$mapData);

            $url_svg_full = $svgfile_name;

            $im = new \Imagick();
            $svg = $mapData;

            $im->setBackgroundColor(new \ImagickPixel('transparent'));

            $im->readImageBlob($svg);

            /*png settings*/
            $im->setImageFormat("png24");
            $im->resizeImage(1188, 583, \Imagick::FILTER_LANCZOS, 1); /*改变大小*/

            /*jpeg*/
            $im->setImageFormat("jpeg");
            $im->adaptiveResizeImage(1188, 583); /*Optional, if you need to resize*/
            $pngFile = $pngfile_name;

            $im->writeImage($pngFile);
            $im->clear();
            $im->destroy();

            $pic_url_full = $pngFile;
        }

        return $pic_url_full;
    }

    private function createPathFileName($uid)
    {
        $path=public_path('lightMap/'.$uid%10);

        $fileName=$path.'/'.$uid;

        if (!is_dir($path)) mkdir($path,0777,true);

        return $fileName;
    }


}
