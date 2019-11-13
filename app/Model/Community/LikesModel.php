<?php

namespace App\Model\Community;

use App\Http\Traits\CompositePrimaryKey;
use App\Http\Traits\TableSuffix;
use Illuminate\Database\Eloquent\Model;

class LikesModel extends Model
{
    use CompositePrimaryKey;
    use TableSuffix;

    protected $primaryKey=['aid','uid'];

    protected $connection='communityDB';

    protected $table='community_article_like_';

    protected $guarded=[];

}
