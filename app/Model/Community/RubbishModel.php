<?php

namespace App\Model\Community;

use Illuminate\Database\Eloquent\Model;

class RubbishModel extends Model
{
    protected $primaryKey='id';

    protected $connection='communityDB';

    protected $table='community_rubbish';

    protected $guarded=[];

}
