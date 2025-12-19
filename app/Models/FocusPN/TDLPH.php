<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TDLPH extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_DLPH';
    public $timestamps = false;
}
