<?php

namespace App\Model\Tssj;

use Illuminate\Database\Eloquent\Model;

class InviteCode extends Model
{
    protected $primaryKey='id';

    protected $connection='aboutTssj';

    protected $table='inviteCode';

    protected $guarded=[];
}
