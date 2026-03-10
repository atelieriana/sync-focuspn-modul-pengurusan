<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TSPBSP extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_SPB_SP';
    public $timestamps = false;
}
