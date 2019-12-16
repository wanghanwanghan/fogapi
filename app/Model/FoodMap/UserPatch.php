<?php

namespace App\Model\FoodMap;

use App\Http\Traits\CompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;

class UserPatch extends Model
{
    use CompositePrimaryKey;

    protected $primaryKey=['uid','pid'];

    protected $connection='FoodMap';

    protected $table='userPatch';

    protected $guarded=[];

    public function patch()
    {
        return $this->hasOne('App\Model\FoodMap\Patch','id','pid');
    }
}
