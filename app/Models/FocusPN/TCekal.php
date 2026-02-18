<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TCekal extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_CEKAL';
    public $timestamps = false;
}
