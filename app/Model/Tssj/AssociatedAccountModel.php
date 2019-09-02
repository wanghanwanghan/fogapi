<?php

namespace App\Model\Tssj;

use App\Http\Traits\CompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;

class AssociatedAccountModel extends Model
{
    use CompositePrimaryKey;

    protected $primaryKey=['uid','phone'];

    protected $connection='aboutTssj';

    protected $table='AssociatedAccount';

    protected $guarded=[];
}
