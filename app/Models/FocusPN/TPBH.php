<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TPBH extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_PBH';
    public $timestamps = false;
}
