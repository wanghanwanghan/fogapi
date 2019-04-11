<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GridInfoModel extends Model
{
    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='grid_info';

    protected $guarded=[];

}
