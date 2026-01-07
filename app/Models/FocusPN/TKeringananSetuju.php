<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TKeringananSetuju extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_KERINGANAN_SETUJU';
    public $timestamps = false;
}