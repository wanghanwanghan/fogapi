<?php

namespace App\Model\FoodMap;

use Illuminate\Database\Eloquent\Model;

class Patch extends Model
{
    protected $primaryKey='id';

    protected $connection='FoodMap';

    protected $table='patch';

    protected $guarded=[];



}
