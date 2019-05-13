<?php

namespace App\Model;

use App\Http\Traits\TableSuffix;
use Illuminate\Database\Eloquent\Model;

class GetGoodsBySysMsgModel extends Model
{
    use TableSuffix;

    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='get_goods_by_sys_msg_';

    protected $guarded=[];

}
