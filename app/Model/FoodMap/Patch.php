<?php

namespace App\Model\FoodMap;

use Illuminate\Database\Eloquent\Model;

class Patch extends Model
{
    protected $primaryKey='id';

    protected $connection='FoodMap';

    protected $table='patch';

    protected $guarded=[];

    public $timestamps=false;

    public function auctionHouse()
    {
        return $this->belongsTo('App\Model\FoodMap\AuctionHouse','id','pid');
    }

    //禁用自动更新时间
    public function getUpdatedAtColumn()
    {
        return null;
    }

    //禁用创建时间
    public function getCreatedAtColumn()
    {
        return null;
    }


}
