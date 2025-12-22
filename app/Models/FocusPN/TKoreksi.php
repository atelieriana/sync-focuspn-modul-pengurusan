<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TKoreksi extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_KOREKSI';
    public $timestamps = false;
}
