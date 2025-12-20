<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TTahap extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_TAHAP';
    public $timestamps = false;
}
