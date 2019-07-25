<?php

namespace App\Http\Controllers\Server;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Intervention\Image\Facades\Image;

class StoreVideoBase
{
    public $config=[
        'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
        'ffprobe.binaries' => '/usr/bin/ffprobe',
        //'ffmpeg.binaries'  => 'C:/duanran/ffmpeg.exe',
        //'ffprobe.binaries' => 'C:/duanran/ffprobe.exe',
        'timeout'          => 3600,
        'ffmpeg.threads'   => 12,
    ];

    public $ffmpeg=null;
    public $ffprobe=null;

    public $testUrl='http://vfx.mtime.cn/Video/2019/02/04/mp4/190204084208765161.mp4';

    public function __construct()
    {
        if ($this->ffmpeg==null)
        {
            $this->ffmpeg=FFMpeg::create($this->config);
        }

        if ($this->ffprobe==null)
        {
            $this->ffprobe=FFProbe::create($this->config);
        }
    }

    //存储视频缩略图
    public function storeVideoThum($urlOrLocalFile,$pathAndFileName)
    {
        try
        {
            $this->ffmpeg->open($urlOrLocalFile)->frame(TimeCode::fromSeconds(1))->save($pathAndFileName);

        }catch (\Exception $e)
        {
            return 'get thum error';
        }

        $picInfo=getimagesize($pathAndFileName);
        $width=$height=null;
        if ($picInfo[0] > $picInfo[1])
        {
            //横视频
            $width=350;

        }else
        {
            //竖视频
            $height=350;
        }

        try
        {
            Image::make($pathAndFileName)->resize($width,$height,function($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->crop(192,120)->save($pathAndFileName);

        }catch (\Exception $e)
        {
            return 'store thum error';
        }

        return [$pathAndFileName];
    }

    //获取视频的宽高
    public function getWidthHeight($urlOrLocalFile)
    {
        $res=$this->ffprobe->streams($urlOrLocalFile)->videos()->first()->getDimensions();



        dd($res->getWidth(),$res->getHeight());
    }










}