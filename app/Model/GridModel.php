<?php

namespace App\Model;

use App\Http\Traits\CompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;

class GridModel extends Model
{
    use CompositePrimaryKey;

    protected $primaryKey=['id','belong'];

    protected $connection='masterDB';

    protected $table='grid';

    protected $guarded=[];

}
