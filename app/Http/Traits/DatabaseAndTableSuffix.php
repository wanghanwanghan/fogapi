<?php
/**
 * Created by PhpStorm.
 * User: 王瀚
 * Date: 2019/6/20
 * Time: 11:40
 */
namespace App\Http\Traits;

trait DatabaseAndTableSuffix
{
    private static $suffixForDatabase;
    private static $suffixForTable;

    public static function databaseSuffix($suffix)
    {
        static::$suffixForDatabase = $suffix;
    }

    public static function tableSuffix($suffix)
    {
        static::$suffixForTable = $suffix;
    }

    public function __construct(array $attributes = [])
    {
        $this->connection .= static::$suffixForDatabase;

        $this->table .= static::$suffixForTable;

        parent::__construct($attributes);
    }
}
