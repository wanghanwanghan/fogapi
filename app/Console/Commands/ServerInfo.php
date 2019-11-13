<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ServerInfo extends Command
{
    protected $signature = 'Admin:ServerInfoNew';

    protected $description = '后台admin的控制面板，计算cpu，内存，硬盘占用';

    public function __construct()
    {
        parent::__construct();
    }

    public function fogUploadNum()
    {
        $num=0;

        for ($i=0;$i<=9;$i++)
        {
            $num += (int)Redis::connection('TssjFog')->llen('FogUploadList_'.$i);
        }

        return $num;
    }

    public function handle()
    {
        $fp=popen("top -b -n 2 | egrep 'Cpu|KiB Mem|KiB Swap'","r");

        $rs="";

        while(!feof($fp))
        {
            $rs .= fread($fp,1024);
        }

        pclose($fp);

        $res=array_filter(explode(PHP_EOL,$rs));

        $info=[];
        foreach ($res as $one)
        {
            $tmp=explode(' ',$one);

            preg_match_all('/Cpu/',$tmp[0],$thisRes);

            if (!empty(current($thisRes)))
            {
                foreach ($tmp as $cpu)
                {
                    if (is_numeric($cpu))
                    {
                        $info['cpu']['us']=$cpu;
                        break;
                    }
                }
            }

            //内存不这么取了
            //preg_match_all('/Mem/',$tmp[1],$thisRes);

            //if (!empty(current($thisRes)))
            //{
            //    $info['mem']=['totle'=>$tmp[3],'free'=>$tmp[5],'used'=>$tmp[8]];
            //}

            preg_match_all('/Swap/',$tmp[1],$thisRes);

            if (!empty(current($thisRes)))
            {
                $info['swap']=['totle'=>$tmp[2],'free'=>$tmp[4],'used'=>$tmp[13]];
            }
        }

        //内存用free -h取
        $fp=popen("free -m | egrep 'Mem'","r");

        $rs="";

        while(!feof($fp))
        {
            $rs .= fread($fp,1024);
        }

        pclose($fp);

        $res=current(array_filter(explode(PHP_EOL,$rs)));

        $res=array_values(array_filter(explode(' ',$res)));

        $per=$res[2] / $res[1] * 100;

        $info['mem']=['totle'=>round($res[1]/1024,0),'used'=>round($res[2]/1024,0),'free'=>round($res[3]/1024,1),'per'=>round($per,1).'%'];

        //查看负载
        //load average
        $fp=popen("top -b -n 2 | egrep 'load average' | head -1","r");

        $rs=fread($fp,1024);

        pclose($fp);

        $res=current(array_filter(explode(PHP_EOL,$rs)));

        $res=explode(' ',$res);

        $arrCount=count($res);

        $num=rtrim($res[$arrCount-3],',');

        $info['cpu']['loadAverage']=rtrim($num,',');

        //查看磁盘
        $fp=popen('df -lh | egrep "/dev/sda3"',"r");

        $rs=fread($fp,1024);

        pclose($fp);

        $res=array_filter(explode(PHP_EOL,$rs));

        $res=current($res);

        $res=preg_replace("/\s{2,}/",' ',$res);

        $res=explode(' ',$res);

        $info['disk']=['name'=>$res[0],'totle'=>$res[1],'used'=>$res[2],'free'=>$res[3],'percentage'=>$res[4]];

        $info['lastUpdate']=time();

        $info['fogUploadNum']=$this->fogUploadNum();

        Redis::connection('default')->set('ServerInfo',jsonEncode($info));
    }
}
