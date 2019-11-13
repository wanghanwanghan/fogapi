<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RankListModel extends Model
{
    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='user_rank_list';

    public $timestamps=false;

    protected $guarded=[];

}
