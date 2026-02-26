<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TPanggilan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_PANGGILAN';
    public $timestamps = false;
}
