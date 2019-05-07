<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;

class SystemMessageModel extends Model
{
    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='sys_msg';

    protected $guarded=[];

}
