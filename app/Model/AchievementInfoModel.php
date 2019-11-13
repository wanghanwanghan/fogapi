<?php

namespace App\Model;

use App\Http\Traits\TableSuffix;
use Illuminate\Database\Eloquent\Model;

class AchievementInfoModel extends Model
{
    use TableSuffix;

    protected $primaryKey='id';

    protected $connection='masterDB';

    protected $table='achievement_info_';

    protected $guarded=[];

}
