<?php

namespace App\Model;

use App\Http\Traits\TableSuffix;
use Illuminate\Database\Eloquent\Model;

class UserTradeInfoModel extends Model
{
    //用户交易数据分表orm，这个是按月分表的buy_sale_info_201904

    use TableSuffix;

    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='buy_sale_info_';

    protected $guarded=[];

}
