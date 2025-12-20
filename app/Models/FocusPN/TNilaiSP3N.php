<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TNilaiSP3N extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_NILAISP3N';
    public $timestamps = false;
}
