<?php
/**
 * Created by PhpStorm.
 * User: 王瀚
 * Date: 2018/9/17
 * Time: 12:32
 */
namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CompositePrimaryKey
{
    //复合主键
    public function getIncrementing()
    {
        return false;
    }

    protected function setKeysForSaveQuery(Builder $query)
    {
        foreach ($this->getKeyName() as $key)
        {
            if ($this->$key)

                $query->where($key, '=', $this->$key);

            else

                throw new \Exception(__METHOD__ . 'Missing part of the primary key: ' . $key);
        }

        return $query;
    }
}
