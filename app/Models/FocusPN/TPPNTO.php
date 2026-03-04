<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TPPNTO extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_PPNTO';
    public $timestamps = false;
}
