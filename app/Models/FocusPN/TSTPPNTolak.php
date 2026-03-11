<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TSTPPNTolak extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_STPPN_TOLAK';
    public $timestamps = false;
}
