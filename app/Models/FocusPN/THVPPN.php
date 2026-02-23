<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class THVPPN extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_HVPPN';
    public $timestamps = false;
}
