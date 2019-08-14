<?php

namespace App\Model\Community;

use App\Http\Traits\CompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;

class PeopleLabelModel extends Model
{
    use CompositePrimaryKey;

    public $incrementing=false;

    //protected $keyType='string';

    protected $primaryKey=['uid','labelId'];

    protected $connection='communityDB';

    protected $table='community_people_label';

    protected $guarded=[];

    public function peopleLabelName()
    {
        return $this->belongsTo(LabelForPeopleModel::class,'labelId','id');
    }
}
