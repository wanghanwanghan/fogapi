<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AvatarCheckModel extends Model
{
    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='avatar_check';

    protected $guarded=[];

}
