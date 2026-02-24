<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TJaminan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_JAMINAN';
    public $timestamps = false;
}
