<?php

namespace App\Model\Community;

use App\Http\Traits\TableSuffix;
use Illuminate\Database\Eloquent\Model;

class ArticleModel extends Model
{
    use TableSuffix;

    protected $primaryKey='aid';

    protected $connection='communityDB';

    //后缀是当年，比如2019
    protected $table='community_article_';

    protected $guarded=[];

}
