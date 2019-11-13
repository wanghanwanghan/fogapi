<?php

namespace App\Model\Community;

use Illuminate\Database\Eloquent\Model;

class LabelForPeopleModel extends Model
{
    protected $primaryKey='id';

    protected $connection='communityDB';

    protected $table='community_label_for_people';

    protected $guarded=[];

}
