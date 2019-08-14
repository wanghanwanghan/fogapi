<?php

namespace App\Model\Community;

use App\Http\Traits\TableSuffix;
use Illuminate\Database\Eloquent\Model;

class PrivateMailModel extends Model
{
    use TableSuffix;

    protected $primaryKey='id';

    protected $connection='communityDB';

    protected $table='community_private_mail_';

    protected $guarded=[];
}
