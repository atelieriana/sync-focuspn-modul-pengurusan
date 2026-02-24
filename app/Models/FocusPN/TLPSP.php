<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TLPSP extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_LPSP';
    public $timestamps = false;
}
