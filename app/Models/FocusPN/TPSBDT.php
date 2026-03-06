<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TPSBDT extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_PSBDT';
    public $timestamps = false;
}
