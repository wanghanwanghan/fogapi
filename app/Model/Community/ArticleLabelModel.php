<?php

namespace App\Model\Community;

use App\Http\Traits\CompositePrimaryKey;
use App\Http\Traits\TableSuffix;
use Illuminate\Database\Eloquent\Model;

class ArticleLabelModel extends Model
{
    use CompositePrimaryKey;
    use TableSuffix;

    public $incrementing=false;

    //protected $keyType='string';

    protected $primaryKey=['aid','labelId'];

    protected $connection='communityDB';

    protected $table='community_article_label_';

    protected $guarded=[];
}
