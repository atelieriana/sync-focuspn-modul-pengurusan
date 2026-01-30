<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TBASP extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_BASP';
    public $timestamps = false;
}
