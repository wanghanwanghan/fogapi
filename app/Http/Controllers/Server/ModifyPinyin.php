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

        if (isset($arr[$str])) return $arr[$str];

        foreach ($arr as $key => $value)
        {
            $str=str_replace($key,$value,$str);
        }

        return $str;
    }

    public function modifyArray($arr)
    {
        $modifyFromTo=$this->modifyFromTo();

        foreach ($arr as $key => $value)
        {
            if (isset($modifyFromTo[$value]))
            {
                $arr[$key]=$modifyFromTo[$value];
            }else
            {
                foreach ($modifyFromTo as $k => $v)
                {
                    $arr[$key]=str_replace($k,$v,$arr[$key]);
                }
            }
        }

        return $arr;
    }





}
