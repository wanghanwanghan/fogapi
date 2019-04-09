<?php
/**
 * Created by PhpStorm.
 * User: 王瀚
 * Date: 2018/10/8
 * Time: 10:09
 */
namespace App\Http\Traits;

trait TableSuffix
{
    private static $suffix;

    //在分表的model中use，执行别的函数之前调用suffix修改EloquentModel后缀
    public static function suffix($suffix)
    {
        static::$suffix = $suffix;
    }

    public function __construct(array $attributes = [])
    {
        $this->table .= static::$suffix;

        parent::__construct($attributes);
    }
}
