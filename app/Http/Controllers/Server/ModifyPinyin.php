<?php
namespace App\Http\Controllers\Server;

use App\Http\Traits\Singleton;

class ModifyPinyin
{
    use Singleton;

    //从什么 => 改成什么
    private function modifyFromTo()
    {
        return [
            'lyu'=>'lv',
            'nyu'=>'nv',
            'ɑ'=>'a',
        ];
    }

    public function modify($str)
    {
        $str=trim($str);

        $arr=$this->modifyFromTo();

        return isset($arr[$str]) ? $arr[$str] : $str;
    }






}
