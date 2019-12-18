<?php

namespace App\Model\FoodMap;

use App\Http\Traits\CompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;

class UserGetPatchByWay extends Model
{
    use CompositePrimaryKey;

    protected $primaryKey=['uid','way','date'];

    protected $connection='FoodMap';

    protected $table='userGetPatchByWay';

    protected $guarded=[];
}
