<?php

namespace App\Http\Controllers\Server;

class TokenBase
{
    private $hex_iv='11111111111111111111111111111111';

    private $key='397e2eb61307109f6e68006ebcb62f98';

    public function __construct($key)
    {
        $this->key=$key;
        $this->key=hash('sha256',$this->key,true);
    }

    //字符串加密
    public function encrypt($input)
    {
        $data=openssl_encrypt($input,'AES-256-CBC',$this->key,OPENSSL_RAW_DATA,$this->hexToStr($this->hex_iv));

        $data=base64_encode($data);

        return $data;
    }

    //给PHP文件加密
    public function filecrypt($filename)
    {
        $type=strtolower(substr(strrchr($filename,'.'),1));

        if ('php'==$type && is_file($filename) && is_writable($filename))
        {
            $contents=php_strip_whitespace($filename);

            $data=openssl_encrypt($contents,'AES-256-CBC',$this->key,OPENSSL_RAW_DATA,$this->hexToStr($this->hex_iv));

            $data=base64_encode($data);

            return file_put_contents($filename,$data);
        }

        return false;
    }

    //字符串解密
    public function decrypt($input)
    {
        $decrypted=openssl_decrypt(base64_decode($input),'AES-256-CBC',$this->key,OPENSSL_RAW_DATA,$this->hexToStr($this->hex_iv));

        return $decrypted;
    }

    //For PKCS7 padding
    private function addpadding($string,$blocksize=16)
    {
        $len=strlen($string);

        $pad=$blocksize - ($len % $blocksize);

        $string.=str_repeat(chr($pad),$pad);

        return $string;
    }

    private function strippadding($string)
    {
        $slast=ord(substr($string,-1));

        $slastc=chr($slast);

        $pcheck=substr($string,-$slast);

        if (preg_match("/$slastc{".$slast."}/",$string))
        {
            $string=substr($string,0,strlen($string) - $slast);

            return $string;

        }else
        {
            return false;
        }

    }

    private function hexToStr($hex)
    {
        $string='';

        for ($i=0;$i<strlen($hex)-1;$i+=2)
        {
            $string.=chr(hexdec($hex[$i].$hex[$i+1]));
        }

        return $string;
    }

}
