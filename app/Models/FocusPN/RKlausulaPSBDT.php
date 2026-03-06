<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class RKlausulaPSBDT extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'R_KLAUSULA_PSBDT';
    public $timestamps = false;
}
