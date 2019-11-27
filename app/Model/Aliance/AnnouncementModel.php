<?php

namespace App\Model\Aliance;

use Illuminate\Database\Eloquent\Model;

class AnnouncementModel extends Model
{
    protected $primaryKey='id';

    protected $connection='Aliance';

    protected $table='announcement';

    protected $guarded=[];

}
