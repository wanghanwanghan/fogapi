<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserFeedbackModel extends Model
{
    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='user_feedback';

    protected $guarded=[];

}
