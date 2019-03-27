<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

class paihangbang extends Model
{
    use InsertOnDuplicateKey;

    protected $primaryKey=false;
    protected $connection='tssj_old';
    protected $table='paihangbang';
    public $timestamps=false;
    protected $guarded=[];









}
