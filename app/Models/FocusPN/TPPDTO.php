<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TPPDTO extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_PPDTO';
    public $timestamps = false;
}
