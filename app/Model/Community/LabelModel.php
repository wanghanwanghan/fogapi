<?php

namespace App\Model\Community;

use Illuminate\Database\Eloquent\Model;

class LabelModel extends Model
{
    protected $primaryKey='id';

    protected $connection='communityDB';

    protected $table='community_label';

    protected $guarded=[];

}
