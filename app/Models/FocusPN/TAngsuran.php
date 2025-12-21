<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TAngsuran extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_ANGSURAN';
    public $timestamps = false;
}
