<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DailyTasksModel extends Model
{
    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='dailytasks';

    protected $guarded=[];

}
