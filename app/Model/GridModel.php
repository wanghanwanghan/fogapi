<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GridModel extends Model
{
    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='grid';

    protected $guarded=[];

}
