<?php

namespace App\Model;

use App\Http\Traits\CompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;

class GridInfoModel extends Model
{
    use CompositePrimaryKey;

    protected $primaryKey=['id','uid'];

    protected $connection='masterDB';

    protected $table='grid_info';

    protected $guarded=[];

}
