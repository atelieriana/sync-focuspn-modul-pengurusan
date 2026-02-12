<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TBAPenyitaan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_BA_PENYITAAN';
    public $timestamps = false;
}
