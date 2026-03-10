<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TSKPPNPengembalian extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_SKPPN_PENGEMBALIAN';
    public $timestamps = false;
}
