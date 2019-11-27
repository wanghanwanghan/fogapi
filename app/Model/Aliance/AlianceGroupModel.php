<?php

namespace App\Model\Aliance;

use Illuminate\Database\Eloquent\Model;

class AlianceGroupModel extends Model
{
    protected $primaryKey='uid';

    protected $connection='Aliance';

    protected $table='alianceGroup';

    protected $guarded=[];

}
