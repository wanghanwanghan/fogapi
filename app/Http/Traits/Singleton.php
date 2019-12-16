<?php
/**
 * Created by PhpStorm.
 * User: 王瀚
 * Date: 2019/12/12
 * Time: 10:09
 */
namespace App\Http\Traits;

trait Singleton
{
    private static $instance;

    static function getInstance(...$args)
    {
        if(!isset(self::$instance))
        {
            self::$instance = new static(...$args);
        }

        return self::$instance;
    }
}
