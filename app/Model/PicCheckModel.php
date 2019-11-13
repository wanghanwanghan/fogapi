<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PicCheckModel extends Model
{
    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='pic_check';

    protected $guarded=[];

}
