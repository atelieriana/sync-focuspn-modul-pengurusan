<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TPBPJPNUraian extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_PB_PJPN_URAIAN';
    public $timestamps = false;
}
