<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ServerInfo extends Command
{
    protected $signature = 'Admin:ServerInfo';

    protected $description = '后台admin的控制面板，计算cpu，内存，硬盘占用';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        for ($i=1;$i<=3;$i++)
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
                    $info['cpu']['us']=$tmp[2];
                }

                preg_match_all('/Mem/',$tmp[1],$thisRes);

                if (!empty(current($thisRes)))
                {
                    $info['mem']=['totle'=>$tmp[3],'free'=>$tmp[5],'used'=>$tmp[8]];
                }

                preg_match_all('/Swap/',$tmp[1],$thisRes);

                if (!empty(current($thisRes)))
                {
                    $info['swap']=['totle'=>$tmp[2],'free'=>$tmp[4],'used'=>$tmp[13]];
                }
            }

            //查看负载
            //load average
            $fp=popen("top -b -n 2 | egrep 'load average' | head -1","r");

            $rs=fread($fp,1024);

            pclose($fp);

            $res=current(array_filter(explode(PHP_EOL,$rs)));

            $res=explode(' ',$res);

            $info['cpu']['loadAverage']=rtrim($res[13],',');

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

            Redis::connection('default')->set('ServerInfo',json_encode($info));

            sleep(17);
        }
    }
}
