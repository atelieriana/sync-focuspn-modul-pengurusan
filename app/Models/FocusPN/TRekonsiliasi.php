<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TRekonsiliasi extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_REKONSILIASI';
    public $timestamps = false;
}
