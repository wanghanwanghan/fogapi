<?php

namespace App\Model\Tssj;

use App\Http\Traits\DatabaseAndTableSuffix;
use Illuminate\Database\Eloquent\Model;

class FogModel extends Model
{
    use DatabaseAndTableSuffix;

    protected $primaryKey='id';

    protected $connection='TssjFog';

    protected $table='user_fog_';

    protected $guarded=[];
}
