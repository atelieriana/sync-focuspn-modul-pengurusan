<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TKeringananUsulan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_KERINGANAN_USULAN';
    public $timestamps = false;
}
