<?php

namespace App\Model\Tssj;

use Illuminate\Database\Eloquent\Model;

class AssociatedAccountModel extends Model
{
    protected $primaryKey='uid';

    protected $connection='aboutTssj';

    protected $table='AssociatedAccount';

    protected $guarded=[];
}
