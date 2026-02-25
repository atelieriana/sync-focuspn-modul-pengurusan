<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TNilaiPBPJPN extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_NILAIPBPJPN';
    public $timestamps = false;
}
