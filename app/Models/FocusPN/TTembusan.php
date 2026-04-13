<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TTembusan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_TEMBUSAN';
    public $timestamps = false;
}
