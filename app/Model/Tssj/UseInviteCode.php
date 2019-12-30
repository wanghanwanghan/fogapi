<?php

namespace App\Model\Tssj;

use Illuminate\Database\Eloquent\Model;

class UseInviteCode extends Model
{
    protected $primaryKey='id';

    protected $connection='aboutTssj';

    protected $table='useInviteCode';

    protected $guarded=[];
}
