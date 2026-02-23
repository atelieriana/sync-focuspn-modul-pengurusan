<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TDasarHukum extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_DASARHUKUM';
    public $timestamps = false;
}
