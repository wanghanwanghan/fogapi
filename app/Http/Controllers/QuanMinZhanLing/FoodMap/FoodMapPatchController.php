<?php

namespace App\Http\Controllers\QuanMinZhanLing\FoodMap;

use App\Http\Traits\Singleton;

class FoodMapPatchController
{
    use Singleton;

    private static $db='FoodMap';

    private function getTreasureType()
    {
        return (new FoodMapController())->getTreasureType();
    }

    //用户通过某些方式得到一个碎片
    public function getOnePatch($int)
    {
        $way=[
            '开屏'=>1,
            '签到'=>2,
            '任务'=>3,
            '领钱袋'=>4,
            '买格子'=>5,
        ];

        if (!in_array($int,$way)) return null;

        return true;
    }












}
