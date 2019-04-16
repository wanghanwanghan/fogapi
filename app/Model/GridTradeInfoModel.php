<?php

namespace App\Model;

use App\Http\Traits\TableSuffix;
use Illuminate\Database\Eloquent\Model;

class GridTradeInfoModel extends Model
{
    //格子交易数据分表orm，每一个格子的所有交易记录都在自己的分表里

    use TableSuffix;

    protected $primaryKey='id';

    protected $connection='gridTradeInfoDB';

    protected $table='grid_trade_info_';

    protected $guarded=[];

}
