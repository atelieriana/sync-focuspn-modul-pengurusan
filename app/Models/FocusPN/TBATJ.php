<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TBATJ extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_BATJ';
    public $timestamps = false;
}
