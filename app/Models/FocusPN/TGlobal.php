<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TGlobal extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_GLOBAL';
    public $timestamps = false;
}
