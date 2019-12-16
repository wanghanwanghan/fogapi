<?php

namespace App\Model\FoodMap;

use Illuminate\Database\Eloquent\Model;

class AuctionHouse extends Model
{
    protected $primaryKey='id';

    protected $connection='FoodMap';

    protected $table='auctionHouse';

    protected $guarded=[];

    public function patch()
    {
        return $this->hasOne('App\Model\FoodMap\Patch','id','pid');
    }

}
