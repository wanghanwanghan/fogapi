<?php
namespace App\Http\Controllers\Server;

use App\Http\Traits\Singleton;

class CacheByFile
{
    use Singleton;

    //存储目录
    private $cacheDir;
    //数据文件
    private $fileName='cacheByFile';
    //分隔符
    private $sc='#&w$1!2@3$h&#';
    //锁文件
    private $lock='lock';

    private function __construct($opt=[])
    {
        isset($opt['cacheDir']) ? $this->cacheDir=$opt['cacheDir'] : $this->cacheDir=storage_path($this->fileName);

        $this->cacheDir.=DIRECTORY_SEPARATOR;

        $this->createDir();
    }

    //建目录
    private function createDir()
    {
        if (!is_dir($this->cacheDir)) mkdir($this->cacheDir,0777,true);

        return self::$instance;
    }

    //只含有26个字母或者数字的并且都是半角的字符串，转换成数字，用于hash分表
    private function string2Number($str)
    {
        $str=(string)$str;

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

    //返回cache文件名
    private function getFileName($key)
    {
        return $this->fileName.$this->string2Number($key)%10;
    }

    //返回锁文件的文件名
    private function getLockFileName($key)
    {
        return $this->lock.$this->string2Number($key)%10;
    }

    //打开文件返回fp
    private function fileOpen($key,$fileType,$mode)
    {
        if ($fileType==='lock')
        {
            $fp=fopen($this->cacheDir.$this->getLockFileName($key),$mode);
        }elseif ($fileType==='cache')
        {
            $fp=fopen($this->cacheDir.$this->getFileName($key),$mode);
        }else
        {
            $fp=null;
        }

        return $fp;
    }

    //写文件
    private function fileWrite($fp,$key,$val,$expir)
    {
        return fwrite($fp,$key.$this->sc.$val.$this->sc.$expir.PHP_EOL);
    }

    //文件读入内存
    private function file($key)
    {
        return file($this->cacheDir.$this->getFileName($key));
    }

    //文件是否存在
    private function fileExists($key)
    {
        return file_exists($this->cacheDir.$this->getFileName($key));
    }

    //创建一个key value缓存
    public function createCache($key,$val,$expir=0)
    {
        $fp=$this->fileOpen($key,'lock','a+');

        while (true)
        {
            $lock=flock($fp,LOCK_EX);

            if ($lock) break;

            //100毫秒后再试
            usleep(100000);
        }

        //得到锁后
        //判断缓存文件是否存在
        if (!$this->fileExists($key))
        {
            //不存在，直接写入缓存
            $fpCache=$this->fileOpen($key,'cache','w+');
            $this->fileWrite($fpCache,$key,$val,$expir);

        }else
        {
            //如果存在，取出全部内容
            $content=$this->file($key);

            $fpCache=$this->fileOpen($key,'cache','w+');

            foreach ($content as $one)
            {
                //重新写入缓存文件
                $one=str_replace(PHP_EOL,'',$one);

                $row=explode($this->sc,$one);

                if ((string)$row[0]===(string)$key)
                {
                    //写入新的
                    $isNew=false;
                    $this->fileWrite($fpCache,$key,$val,$expir);
                }else
                {
                    //照搬
                    $this->fileWrite($fpCache,$row[0],$row[1],$row[2]);
                }
            }

            //文件里的搬完了，写入新缓存
            if (!isset($isNew))
            {
                $this->fileWrite($fpCache,$key,$val,$expir);
            }else
            {
                unset($isNew);
            }
        }

        //可以关闭文件也释放锁
        fclose($fpCache);
        flock($fp,LOCK_UN);
        fclose($fp);

        return true;
    }

    //取缓存
    public function getCache($key)
    {
        $cache=$this->file($key);

        $val='';

        foreach ($cache as $one)
        {
            $arr=explode($this->sc,$one);

            if ((string)$arr[0]===(string)$key && (time() <= $arr[2] || $arr[2]==0))
            {
                $val=$arr[1];
                break;
            }
        }

        return $val;
    }









}
