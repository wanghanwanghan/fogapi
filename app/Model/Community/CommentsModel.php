<?php

namespace App\Model\Community;

use App\Http\Traits\TableSuffix;
use Illuminate\Database\Eloquent\Model;

class CommentsModel extends Model
{
    use TableSuffix;

    protected $primaryKey='id';

    protected $connection='communityDB';

    protected $table='community_article_comment_';

    protected $guarded=[];

}
