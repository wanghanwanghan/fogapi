<?php

namespace App\Model\Aliance;

use Illuminate\Database\Eloquent\Model;

class InviteModel extends Model
{
    protected $primaryKey='id';

    protected $connection='Aliance';

    protected $table='invite';

    protected $guarded=[];

}
